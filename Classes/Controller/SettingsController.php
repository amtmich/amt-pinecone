<?php

namespace Amt\AmtPinecone\Controller;

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
        $pineconeValidateIndexName === true ? $this->addFlashMessage('Index name is valid') : $this->addFlashMessage('Index name is invalid', '', ContextualFeedbackSeverity::ERROR);

        try {
            $openAiValidateApiKey = $openAiClient->getTestApiCall();
            $pineconeValidateApiKey = $pineconeClient->getTestApiCall();
            $this->addFlashMessage('OpenAI Api Key is valid');
            $this->addFlashMessage('Pinecone Api Key is valid');

        } catch (\Exception $e) {
            $this->addFlashMessage($e->getMessage(), '', ContextualFeedbackSeverity::ERROR);
        }
        $moduleTemplate->assignMultiple(
            [
                'openAiApiKey' => $configuration['openAiApiKey'],
                'pineconeApiKey' => $configuration['pineconeApiKey'],
                'openAiModelForEmbeddings' => $configuration['openAiModelForEmbeddings'],
                'openAi' => $openAiValidateApiKey,
                'pinecone' => $pineconeValidateApiKey,
                'pineconeOptionalHost' => $pineconeClient->getOptionalHost(),
                'pineconeIndexName' => $pineconeClient->getIndexName(),
                'pineconeAllIndexes' => $pineconeClient->getAllIndexes(),
                'pineconeValidateIndexName' => $pineconeValidateIndexName,
                'usedOpenAiTokens' => $this->clientService->getTotalTokens()
            ]);

        return $moduleTemplate->renderResponse('Settings');
    }
}
