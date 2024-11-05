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
        $moduleTemplate = $this->createRequestModuleTemplate();

        $configuration = ClientUtility::createExtensionConfigurationObject()->get('amt_pinecone');
        $openAiClient = ClientUtility::createOpenAiClient();
        $pineconeClient = ClientUtility::createPineconeClient();
        $pineconeValidateIndexName = $pineconeClient->validateIndexProvidedByUser();
        $openAiValidateModel = $openAiClient->validateEmbeddingModels();

        $openAiDTO = new OpenAiDTO(
            $configuration['openAiApiKey'],
            $configuration['openAiModelForEmbeddings'],
            $openAiClient->getTotalTokens(),
            $configuration['openAiTokenLimit'],
            $openAiClient->calculateAvailableTokens(),
            $this->validateApiCall($openAiClient),
            $openAiValidateModel
        );

        $pineconeDTO = new PineconeDTO(
            $configuration['pineconeApiKey'],
            $pineconeClient->getOptionalHost(),
            $pineconeClient->getIndexName(),
            $pineconeClient->getAllIndexes(),
            $this->validateApiCall($pineconeClient),
            $pineconeValidateIndexName,
            $this->clientService->getIndexingProgress(),
            $this->clientService->getNonExistsTables()
        );

        if (!$openAiClient->hasTokensAvailable()) {
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
                'openAiDTO' => $openAiDTO,
                'pineconeDTO' => $pineconeDTO,
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
