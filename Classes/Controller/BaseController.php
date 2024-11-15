<?php

namespace Amt\AmtPinecone\Controller;

use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class BaseController extends ActionController
{
    protected ?ModuleTemplateFactory $moduleTemplateFactory;

    public function __construct(ModuleTemplateFactory $moduleTemplateFactory)
    {
        $this->moduleTemplateFactory = $moduleTemplateFactory;
    }

    protected function createRequestModuleTemplate(): ModuleTemplate
    {
        if (!isset($this->moduleTemplateFactory)) {
            throw new \Exception('Error with module template - please, try again later.', 1623345720);
        }

        return $this->moduleTemplateFactory->create($this->request);
    }

    protected function displayFlashMessage(string $messageBody, bool $boolValue, int $feedbackSeverity): void
    {
        if (!$boolValue) {
            $this->addFlashMessage($messageBody, '', $feedbackSeverity);
        }
    }

    protected function initializeJsAndCssModules(): void
    {
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->addCssFile('EXT:amt_pinecone/Resources/Public/Css/base.css');
    }
}
