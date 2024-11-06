<?php

namespace Amt\AmtPinecone\Controller;

use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
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
}
