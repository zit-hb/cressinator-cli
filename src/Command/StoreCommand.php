<?php

namespace App\Command;

use App\Exception\MeasurementSourceNotFoundException;
use App\Exception\OptionNotFoundException;
use App\Exception\RecordingSourceNotFoundException;
use App\Exception\SensorNotOnlineException;
use App\Exception\SensorTypeNotFoundException;
use App\Service\CressinatorService;
use App\Service\OptionService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;
use YAPI;

class StoreCommand extends Command
{
    /** @var CressinatorService */
    private $cressinatorService;

    /** @var OptionService */
    private $optionService;

    /** @var Filesystem */
    private $fs;

    /**
     * @param CressinatorService $cressinatorService
     * @param OptionService $optionService
     */
    public function __construct(
        CressinatorService $cressinatorService,
        OptionService $optionService
    )
    {
        $this->cressinatorService = $cressinatorService;
        $this->optionService = $optionService;
        $this->fs = new Filesystem();
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('cressinator:store')
            ->addOption(
                'group',
                'g',
                InputOption::VALUE_REQUIRED
            )
            ->addOption(
                'host',
                'H',
                InputOption::VALUE_REQUIRED,
                '',
                'http://127.0.0.1:4444'
            )
            ->addOption(
                'cressinator',
                'C',
                InputOption::VALUE_REQUIRED,
                ''
            )
            ->addOption(
                'token',
                'T',
                InputOption::VALUE_REQUIRED
            )
            ->addOption(
                'file',
                'F',
                InputOption::VALUE_REQUIRED
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws MeasurementSourceNotFoundException
     * @throws ProcessFailedException
     * @throws RecordingSourceNotFoundException
     * @throws SensorNotOnlineException
     * @throws SensorTypeNotFoundException
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->optionService->checkForRequiredOptions($input, ['host', 'group', 'cressinator', 'token', 'file']);
        } catch (OptionNotFoundException $exception) {
            $options = $exception->getMissingOptions();
            $output->writeln('<error>Error:</error> Missing value(s) for <info>' . implode(', ', $options) . '</info>');
            return Command::FAILURE;
        }

        $this->cressinatorService->setHost($input->getOption('cressinator'));
        $this->cressinatorService->setToken($input->getOption('token'));

        if (!$this->fs->exists($input->getOption('file'))) {
            $output->writeln('<error>Error:</error> File ' . $input->getOption('file') . ' does not exist');
            return Command::FAILURE;
        }

        $rawContent = file_get_contents($input->getOption('file'));
        $parsedContent = Yaml::parse($rawContent);

        if (!empty($parsedContent['sensors']) && is_array($parsedContent['sensors'])) {
            $this->handleSensors($input, $output, $parsedContent['sensors']);
        }

        if (!empty($parsedContent['cameras']) && is_array($parsedContent['cameras'])) {
            $this->handleCameras($input, $output, $parsedContent['cameras']);
        }

        return Command::SUCCESS;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param array $sensors
     * @throws MeasurementSourceNotFoundException
     * @throws SensorNotOnlineException
     * @throws SensorTypeNotFoundException
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    private function handleSensors(InputInterface $input, OutputInterface $output, array $sensors): void
    {
        YAPI::RegisterHub($input->getOption('host'));

        foreach ($sensors as $sensor) {
            if (empty($sensor['name']) || empty($sensor['type']) || empty($sensor['serial'])) {
                $output->writeln('<error>Error:</error> Invalid sensor entry in file');
                continue;
            }

            $value = $this->getSensorValue($sensor['type'], $sensor['serial']);
            $output->writeln('Received value ' . $value . ' from ' . $sensor['name'] . ' (' . $sensor['serial'] . ')');

            $groupId = $input->getOption('group');
            $sourceId = $this->cressinatorService->getMeasurementSourceFromName($groupId, $sensor['name']);
            if ($sourceId === null) {
                throw new MeasurementSourceNotFoundException($sensor['name']);
            }

            $this->cressinatorService->addMeasurement($sourceId, $value);
        }

        // HACK: reboot to solve the freeze problem
        $network = \YNetwork::FirstNetwork();
        $module = $network->get_module();
        $module->reboot(1);

        YAPI::FreeAPI();
    }

    /**
     * @param string $type
     * @param string $serial
     * @return float
     * @throws SensorNotOnlineException
     * @throws SensorTypeNotFoundException
     */
    private function getSensorValue(string $type, string $serial): float
    {
        switch ($type) {
            case 'Temperature':
                $dev = \YTemperature::FindTemperature($serial . '.temperature');
                break;
            case 'Light':
                $dev = \YLightSensor::FindLightSensor($serial . '.lightSensor');
                break;
            case 'CO2':
                $dev = \YCarbonDioxide::FindCarbonDioxide($serial . '.carbonDioxide');
                break;
            case 'Humidity':
                $dev = \YHumidity::FindHumidity($serial . '.humidity');
                break;
            case 'Pressure':
                $dev = \YPressure::FindPressure($serial . '.pressure');
                break;
            default:
                throw new SensorTypeNotFoundException($type);
        }

        if (!$dev->isOnline()) {
            throw new SensorNotOnlineException($serial);
        }

        return $dev->get_currentValue();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param array $cameras
     * @throws RecordingSourceNotFoundException
     * @throws ProcessFailedException
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    private function handleCameras(InputInterface $input, OutputInterface $output, array $cameras): void
    {
        foreach ($cameras as $camera) {
            if (empty($camera['name']) || empty($camera['command']) || empty($camera['extension'])) {
                $output->writeln('<error>Error:</error> Invalid camera entry in file');
                continue;
            }

            $image = $this->getImagePath($camera['command'], $camera['extension']);
            $output->writeln('Created image ' . $image . ' from ' . $camera['name']);

            $groupId = $input->getOption('group');
            $sourceId = $this->cressinatorService->getRecordingSourceFromName($groupId, $camera['name']);
            if ($sourceId === null) {
                throw new RecordingSourceNotFoundException($camera['name']);
            }

            $this->cressinatorService->addRecording($sourceId, $image);
            $this->fs->remove($image);
        }
    }

    /**
     * @param array $command
     * @param string $extension
     * @return string
     * @throws ProcessFailedException
     */
    private function getImagePath(array $command, string $extension): string
    {
        $image = '/tmp/' . md5(uniqid('cressinator', true)) . '.' . $extension;

        $command[] = $image;
        $process = new Process($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $image;
    }
}
