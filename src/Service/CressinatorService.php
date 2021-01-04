<?php

namespace App\Service;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CressinatorService
{
    /** @var HttpClientInterface */
    private $client;

    /** @var array */
    private $measurementSources;

    /** @var array */
    private $recordingSources;

    /** @var string|null */
    private $host;

    /** @var string|null */
    private $token;

    /** @var string */
    const TOKEN_HEADER = 'X-AUTH-TOKEN';

    /**
     * CressinatorService constructor.
     */
    public function __construct()
    {
        // FIXME: DI of HttpClientInterface does not work for some reason
        $this->client = HttpClient::create();
        $this->measurementSources = [];
        $this->recordingSources = [];
    }

    /**
     * @param string $host
     */
    public function setHost(string $host): void
    {
        $this->host = $host;
    }

    /**
     * @param string $token
     */
    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    /**
     * @param int $groupId
     * @return array
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function fetchMeasurementSources(int $groupId): array
    {
        $response = $this->client->request(
            'GET',
            $this->host . '/api/measurement_sources/group:' . urlencode($groupId),
            [
                'headers' => [
                    self::TOKEN_HEADER => $this->token
                ]
            ]
        );

        return $response->toArray();
    }

    /**
     * @param int $groupId
     * @param string $name
     * @return int|null
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function getMeasurementSourceFromName(int $groupId, string $name): ?int
    {
        if (empty($this->measurementSources[$groupId])) {
            $this->measurementSources[$groupId] = $this->fetchMeasurementSources($groupId);
        }

        foreach ($this->measurementSources[$groupId] as $source) {
            if ($source['name'] === $name) {
                return $source['id'];
            }
        }

        return null;
    }

    /**
     * @param int $groupId
     * @return array
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function fetchRecordingSources(int $groupId): array
    {
        $response = $this->client->request(
            'GET',
            $this->host . '/api/recording_sources/group:' . urlencode($groupId),
            [
                'headers' => [
                    self::TOKEN_HEADER => $this->token
                ]
            ]
        );

        return $response->toArray();
    }

    /**
     * @param int $groupId
     * @param string $name
     * @return int|null
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function getRecordingSourceFromName(int $groupId, string $name): ?int
    {
        if (empty($this->recordingSources[$groupId])) {
            $this->recordingSources[$groupId] = $this->fetchRecordingSources($groupId);
        }

        foreach ($this->recordingSources[$groupId] as $source) {
            if ($source['name'] === $name) {
                return $source['id'];
            }
        }

        return null;
    }

    /**
     * @param int $sourceId
     * @param float $value
     * @return array
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function addMeasurement(int $sourceId, float $value): array
    {
        $response = $this->client->request(
            'POST',
            $this->host . '/api/measurements/add',
            [
                'headers' => [
                    self::TOKEN_HEADER => $this->token
                ],
                'body' => [
                    'measurement[value]'  => $value,
                    'measurement[source]' => $sourceId
                ]
            ]
        );

        return $response->toArray();
    }

    /**
     * @param int $sourceId
     * @param string $file
     * @return array
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function addRecording(int $sourceId, string $file): array
    {
        $formFields = [
            'recording' => [
                'source' => (string)$sourceId,
                'file'   => DataPart::fromPath($file)
            ]
        ];
        $formData = new FormDataPart($formFields);

        $preHeaders = $formData->getPreparedHeaders();
        $headers['Content-Type'] = $preHeaders->get('Content-Type')->getBodyAsString();
        $headers[self::TOKEN_HEADER] = $this->token;

        $response = $this->client->request(
            'POST',
            $this->host . '/api/recordings/add',
            [
                'headers' => $headers,
                'body'    => $formData->bodyToString()
            ]
        );

        return $response->toArray();
    }
}
