<?php

declare(strict_types=1);

namespace Amt\AmtPinecone\Http\Client;

use Amt\AmtPinecone\Utility\ClientUtility;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Registry;

class OpenAiClient extends BaseClient
{
    protected const CLIENT_API_KEY = 'openAiApiKey';
    protected string $clientApiKeyValue = '';
    protected string $baseUrl = 'https://api.openai.com/v1/';
    protected const MODEL_FOR_EMBEDDINGS = 'openAiModelForEmbeddings';
    protected string $openAiModelValue = '';
    protected mixed $configuration;
    protected Registry $registry;

    public function __construct(ExtensionConfiguration $extensionConfiguration, Registry $registry)
    {
        parent::__construct();
        $this->clientApiKeyValue = $extensionConfiguration->get('amt_pinecone')[self::CLIENT_API_KEY] ?? '';
        $this->openAiModelValue = $extensionConfiguration->get('amt_pinecone')[self::MODEL_FOR_EMBEDDINGS] ?? '';
        $this->configuration = ClientUtility::createExtensionConfigurationObject()->get('amt_pinecone');
        $this->registry = $registry;
    }

    /**
     * @param string|bool $response
     *
     * @throws \Exception
     */
    public function validateResponse($response): \stdClass
    {
        if (!is_string($response)) {
            throw new \Exception('Error, please provide a valid API key.');
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

    /**
     * @return array<string,string>
     */
    public function getRequestHeader(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Authorization' => "Bearer {$this->clientApiKeyValue}",
        ];
    }

    public function validateEmbeddingModels(): bool
    {
        try {
            $configurationModel = $this->openAiModelValue;
            $availableModels = $this->getTestApiCall()->data;
            foreach ($availableModels as $model) {
                if ($model->id === $configurationModel) {
                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
        }

        return false;
    }

    /**
     * @return array<int,float|int>|null
     *
     * @throws \Exception
     */
    public function generateEmbedding(string $text): ?array
    {
        $data = [
            'input' => $text,
            'model' => $this->configuration['openAiModelForEmbeddings'],
        ];
        $jsonData = $this->serializeData($data);

        if (!$this->hasTokensAvailable()) {
            throw new \Exception('OpenAI API token limit exceeded.', 401);
        }

        $responseData = $this->validateResponse($this->sendRequest($this->getRequestHeader(), 'embeddings', 'POST', $jsonData));
        $this->sumUpUsedTokensOpenAi($responseData->usage->prompt_tokens);

        return $responseData->data[0]->embedding;
    }

    public function sumUpUsedTokensOpenAi(?int $usedTokens): void
    {
        if ($usedTokens) {
            $currentTotalTokens = $this->getTotalTokens();
            $updatedTotalTokens = $currentTotalTokens + $usedTokens;
            $this->registry->set('AmtPinecone', 'embeddings_prompt_tokens', $updatedTotalTokens);
        }
    }

    public function calculateAvailableTokens(): int
    {
        return (int) max(0, (int) $this->configuration['openAiTokenLimit'] - $this->getTotalTokens());
    }

    public function hasTokensAvailable(): bool
    {
        return $this->calculateAvailableTokens() > 0;
    }

    public function getTotalTokens(): int
    {
        return $this->registry->get('AmtPinecone', 'embeddings_prompt_tokens') ?? 0;
    }
}
