<?php

declare(strict_types=1);

namespace Amt\AmtPinecone\Http\Client;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

abstract class BaseClient implements ClientInterface
{
    protected const CLIENT_API_KEY = 'apiKey';
    protected const OPTIONAL_HOST_KEY = null;
    protected string $clientApiKeyValue = '';
    protected string $baseUrl = '';
    protected ?string $optionalHost = null;
    protected HttpClientInterface $client;

    public function __construct()
    {
        $this->client = HttpClient::create();
    }

    public function getTestApiCall(): \stdClass
    {
        return new \stdClass();
    }

    public function validateResponse($response): \stdClass
    {
        return new \stdClass();
    }

    /**
     * @param array<string,string> $header
     */
    public function sendRequest(array $header, string $url, string $method, ?string $jsonData = null, ?string $optionalHost = null): string
    {
        $host = empty($optionalHost) ? $this->baseUrl : $optionalHost;
        $response = $this->client->request(
            $method,
            $host.$url,
            [
                'headers' => $header,
                'body' => $jsonData,
            ]
        );

        return $response->getContent(false);
    }

    /**
     * @param array<mixed> $data
     */
    public function serializeData(array $data): ?string
    {
        $encodedData = \json_encode($data);
        if (false === $encodedData) {
            return null;
        }

        return $encodedData;
    }

    public function validateApiKey(): bool
    {
        try {
            $this->getTestApiCall();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function decodeData(string $jsonData, ?bool $associative = null): mixed
    {
        return json_decode($jsonData, $associative);
    }
}
