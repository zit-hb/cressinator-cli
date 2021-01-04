<?php

namespace App\Service;

use App\Exception\OptionNotFoundException;
use Symfony\Component\Console\Input\InputInterface;

class OptionService
{
    /**
     * @param InputInterface $input
     * @param array $requiredOptions
     * @throws OptionNotFoundException if required options are missing
     */
    public function checkForRequiredOptions(InputInterface $input, array $requiredOptions)
    {
        $missingOptions = [];
        foreach ($requiredOptions as $requiredOption) {
            if (!$input->hasOption($requiredOption) || empty($input->getOption($requiredOption))) {
                $missingOptions[] = $requiredOption;
            }
        }
        if (!empty($missingOptions)) {
            throw new OptionNotFoundException($missingOptions);
        }
    }
}
