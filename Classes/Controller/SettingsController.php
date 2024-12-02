<?php

namespace Amt\AmtPinecone\Controller;

use Amt\AmtPinecone\DTO\OpenAiDTO;
use Amt\AmtPinecone\DTO\PineconeDTO;
use Amt\AmtPinecone\Http\Client\BaseClient;
use Amt\AmtPinecone\Service\ClientService;
use Amt\AmtPinecone\Utility\ClientUtility;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;

class SettingsController extends BaseController
{
    protected ClientService $clientService;

    public function __construct(ModuleTemplateFactory $moduleTemplateFactory, ClientService $clientService)
    {
        parent::__construct($moduleTemplateFactory);
        $this->clientService = $clientService;
    }

    public function settingsAction(): ResponseInterface
    {
        $this->initializeJsAndCssModules();

        $moduleTemplate = $this->createRequestModuleTemplate();

        $configuration = ClientUtility::createExtensionConfigurationObject()->get('amt_pinecone');
        $openAiClient = ClientUtility::createOpenAiClient();
        $pineconeClient = ClientUtility::createPineconeClient();
        $pineconeValidateIndexName = $pineconeClient->validateIndexProvidedByUser();
        $openAiValidateModel = $openAiClient->validateEmbeddingModels();

        $openAiDTO = new OpenAiDTO(
            $openAiClient->getMaskedApiKey($configuration['openAiApiKey']),
            $configuration['openAiModelForEmbeddings'],
            $openAiClient->getUsedTokens(),
            $configuration['openAiTokenLimit'],
            $this->clientService->getPercentageTokensUsed($openAiClient->getUsedTokens(), $configuration['openAiTokenLimit']),
            $openAiClient->calculateAvailableTokens(),
            $this->validateApiCall($openAiClient),
            $openAiValidateModel
        );

        $pineconeDTO = new PineconeDTO(
            $pineconeClient->getMaskedApiKey($configuration['pineconeApiKey']),
            $pineconeClient->getOptionalHost(),
            $pineconeClient->getIndexName(),
            $pineconeClient->getAllIndexes(),
            $this->validateApiCall($pineconeClient),
            $pineconeValidateIndexName,
            $this->clientService->getIndexingProgress(),
            $this->clientService->getNonExistsTables()
        );

        $this->displayFlashMessage('Hint - to change AmtPinecone extension configuration go to "Install tool -> Settings -> Extension Configuration" to change configuration values', $this->displayHintMessage($this->validateApiCall($openAiClient), $this->validateApiCall($pineconeClient)), ContextualFeedbackSeverity::INFO);
        $this->displayFlashMessage('OpenAI API token limit exceeded.', $openAiClient->hasTokensAvailable(), ContextualFeedbackSeverity::ERROR);
        $this->displayFlashMessage('Index name is invalid.', $pineconeValidateIndexName, ContextualFeedbackSeverity::ERROR);
        $this->displayFlashMessage('Please provide a valid OpenAI model for embeddings.', $openAiValidateModel, ContextualFeedbackSeverity::ERROR);

        $moduleTemplate->assignMultiple(
            [
                'openAiDTO' => $openAiDTO,
                'pineconeDTO' => $pineconeDTO,
            ]);

        return $moduleTemplate->renderResponse('Settings/Settings');
    }

    public function validateApiCall(BaseClient $client): bool
    {
        try {
            $client->getTestApiCall();
        } catch (\Exception $e) {
            $this->addFlashMessage($e->getMessage(), '', ContextualFeedbackSeverity::ERROR);

            return false;
        }

        return true;
    }

    private function displayHintMessage(bool $validateOpenAiApiKey, bool $validatePineconeApiKey): bool
    {
        return $validateOpenAiApiKey && $validatePineconeApiKey;
    }
}
