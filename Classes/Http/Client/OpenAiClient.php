<?php

declare(strict_types=1);

namespace Amt\AmtPinecone\Http\Client;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

class OpenAiClient extends BaseClient
{
    protected const CLIENT_API_KEY = 'openAiApiKey';
    protected string $clientApiKeyValue = '';
    protected string $baseUrl = 'https://api.openai.com/v1/';

    public function __construct(ExtensionConfiguration $extensionConfiguration)
    {
        parent::__construct();
        $this->clientApiKeyValue = $extensionConfiguration->get('amt_pinecone')[self::CLIENT_API_KEY] ?? '';
    }

    public function validateResponse($response): \stdClass
    {
        if (!is_string($response)) {

            throw new \Exception('Error, please provide a valid API key');
        }
        $response = $this->decodeData($response);

        if ($response->error ?? null) {
            throw new \Exception($response->error->message);
        }
        return $response;
    }

    public function getTestApiCall(): \stdClass
    {
        $header = [
            'Content-Type' => 'application/json',
            'Authorization' => "Bearer {$this->clientApiKeyValue}",
        ];
        $response = $this->validateResponse($this->sendRequest($header, 'models', 'GET'));

        return $response;
    }

    public function getRequestHeader(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Authorization' => "Bearer {$this->clientApiKeyValue}",
        ];
    }
}
