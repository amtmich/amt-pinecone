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
        $pineconeIndexedRecords = count($pineconeClient->getVectorsList());
        $dataIntegrityStatus = $this->clientService->checkDataIntegrityStatus($pineconeIndexedRecords);

        $indexingStatus = [
            'typo3IndexedRecords' => $this->clientService->getIndexedRecordsCount(),
            'pineconeIndexedRecords' => $pineconeIndexedRecords,
            'dataIntegrityStatus' => $dataIntegrityStatus,
        ];

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

        $this->displayFlashMessage('OpenAI API token limit exceeded.', $openAiClient->hasTokensAvailable(), 2);
        $this->displayFlashMessage('Index name is invalid.', $pineconeValidateIndexName, 2);
        $this->displayFlashMessage('Please provide a valid OpenAI model for embeddings.', $openAiValidateModel, 2);
        $this->displayFlashMessage('Potential integrity problems - please run scheduler command "amt-pinecone:data-integrity".', $dataIntegrityStatus, 1);

        $moduleTemplate->assignMultiple(
            [
                'openAiDTO' => $openAiDTO,
                'pineconeDTO' => $pineconeDTO,
                'indexingStatus' => $indexingStatus
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

    public function displayFlashMessage(string $messageBody, bool $boolValue, int $feedbackSeverity): void
    {
        if (!$boolValue) {
            $this->addFlashMessage($messageBody, '', $feedbackSeverity);
        }
    }
}
