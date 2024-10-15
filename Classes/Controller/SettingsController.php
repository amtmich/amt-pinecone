<?php

namespace Amt\AmtPinecone\Controller;

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
        $moduleTemplate = $this->createRequestModuleTemplate();

        $configuration = ClientUtility::createExtensionConfigurationObject()->get('amt_pinecone');
        $openAiClient = ClientUtility::createOpenAiClient();
        $pineconeClient = ClientUtility::createPineconeClient();
        $pineconeValidateIndexName = $pineconeClient->validateIndexProvidedByUser();
        $openAiValidateApiKey = $this->validateApiCall($openAiClient);
        $pineconeValidateApiKey = $this->validateApiCall($pineconeClient);
        $openAiValidateModel = $openAiClient->validateEmbeddingModels();
        $openAiUsedTokens = $this->clientService->getTotalTokens();
        $openAiAvailableTokens = $this->clientService->calculateAvailableTokens();

        if (!$this->clientService->hasTokensAvailable()) {
            $this->addFlashMessage('OpenAI API token limit exceeded.', '', ContextualFeedbackSeverity::ERROR);
        }
        if ($pineconeValidateIndexName === false) {
            $this->addFlashMessage('Index name is invalid.', '', ContextualFeedbackSeverity::ERROR);
        }

        if ($openAiValidateModel === false) {
            $this->addFlashMessage('Please provide a valid OpenAI model for embeddings.', '', ContextualFeedbackSeverity::ERROR);
        }

        $moduleTemplate->assignMultiple(
            [
                'openAiApiKey' => $configuration['openAiApiKey'],
                'pineconeApiKey' => $configuration['pineconeApiKey'],
                'openAiModelForEmbeddings' => $configuration['openAiModelForEmbeddings'],
                'pineconeOptionalHost' => $pineconeClient->getOptionalHost(),
                'pineconeIndexName' => $pineconeClient->getIndexName(),
                'pineconeAllIndexes' => $pineconeClient->getAllIndexes(),
                'openAiUsedTokens' => $openAiUsedTokens,
                'openAiTokenLimit' => $configuration['openAiTokenLimit'],
                'openAiAvailableTokens' => $openAiAvailableTokens,
                'validateOpenAiApiKey' => $openAiValidateApiKey,
                'validatePineconeApiKey' => $pineconeValidateApiKey,
                'validatePineconeIndexName' => $pineconeValidateIndexName,
                'validateOpenAiModel' => $openAiValidateModel,
            ]);

        return $moduleTemplate->renderResponse('Settings');
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
}
