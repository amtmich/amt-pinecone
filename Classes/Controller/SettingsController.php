<?php

namespace Amt\AmtPinecone\Controller;

use Amt\AmtPinecone\Utility\ClientUtility;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;

class SettingsController extends BaseController
{
    public function settingsAction(): ResponseInterface
    {
        $moduleTemplate = $this->createRequestModuleTemplate();

        $configuration = ClientUtility::createExtensionConfigurationObject()->get('amt_pinecone');
        $openAiClient = ClientUtility::createOpenAiClient();
        $pineconeClient = ClientUtility::createPineconeClient();

        try {
            $openAiValidateApiKey = $openAiClient->getTestApiCall();
            $pineconeValidateApiKey = $pineconeClient->getTestApiCall();
            //$pineconeCreatedIndexName = $pineconeClient->createIndex($pineconeClient->getIndexName());
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
                //'pineconeCreatedIndexName' => $pineconeCreatedIndexName,
                'pineconeAllIndexes' => $pineconeClient->getAllIndexes(),

            ]);

        return $moduleTemplate->renderResponse('Settings');
    }
}
