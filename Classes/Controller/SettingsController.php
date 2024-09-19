<?php

namespace Amt\AmtPinecone\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SettingsController extends BaseController
{
    public function settingsAction(): ResponseInterface
    {
        $configuration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('amt_pinecone');
        $moduleTemplate = $this->createRequestModuleTemplate();

        $moduleTemplate->assignMultiple(
            [
                'openAiApiKey' => $configuration['openAiApiKey'],
                'openAiModelForEmbeddings' => $configuration['openAiModelForEmbeddings'],
                'pineocneApiKey' => $configuration['pineconeApiKey'],

            ]);

        return $moduleTemplate->renderResponse('Settings');
    }
}
