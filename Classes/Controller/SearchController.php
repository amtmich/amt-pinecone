<?php

namespace Amt\AmtPinecone\Controller;

use Amt\AmtPinecone\Service\ClientService;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;

class SearchController extends BaseController
{
    protected ClientService $clientService;

    public function __construct(ModuleTemplateFactory $moduleTemplateFactory, ClientService $clientService)
    {
        parent::__construct($moduleTemplateFactory);
        $this->clientService = $clientService;
    }

    public function indexAction(): ResponseInterface
    {
        $this->initializeJsAndCssModules();
        $moduleTemplate = $this->createRequestModuleTemplate();
        $tablesToIndex = $this->clientService->getTablesToIndex();

        $moduleTemplate->assignMultiple(
            [
                'tablesToIndex' => $tablesToIndex,
            ]
        );

        return $moduleTemplate->renderResponse();
    }

    public function searchAction(): ResponseInterface
    {
        $this->initializeJsAndCssModules();
        $moduleTemplate = $this->createRequestModuleTemplate();

        if (!$this->request->hasArgument('query')) {
            return $this->redirect('settings', 'Settings');
        }

        $query = $this->request->getArgument('query');
        $selectedTable = $this->request->getArgument('table');
        $tablesToIndex = $this->clientService->getTablesToIndex();

        $moduleTemplate->assignMultiple(
            [
                'query' => $query,
                'selectedTable' => $selectedTable,
                'tablesToIndex' => $tablesToIndex,
            ]);

        return $moduleTemplate->renderResponse('Search');
    }
}
