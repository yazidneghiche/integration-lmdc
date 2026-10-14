<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class MonitoringService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $baseUrl,
        private readonly string $apiKey,
        private readonly string $monitoringPath,
    ) {
    }

    public function getStatus(): array
    {
        try {
            $response = $this->httpClient->request(
                'GET',
                rtrim($this->baseUrl, '/') . '/' . ltrim($this->monitoringPath, '/'),
                [
                    'headers' => [
                        'Accept' => 'application/json',
                        'X-API-KEY' => $this->apiKey,
                    ],
                    'timeout' => 8,
                ]
            );

            return [
                'available' => $response->getStatusCode() >= 200
                    && $response->getStatusCode() < 300,
                'statusCode' => $response->getStatusCode(),
                'data' => $response->toArray(false),
                'error' => null,
            ];
        } catch (\Throwable $exception) {
            return [
                'available' => false,
                'statusCode' => null,
                'data' => [],
                'error' => $exception->getMessage(),
            ];
        }
    }
}
