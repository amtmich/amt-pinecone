<?php

namespace Amt\AmtPinecone\Controller;

use Amt\AmtPinecone\Service\ClientService;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use Psr\Http\Message\ResponseInterface;

class SearchController extends BaseController
{
    private ClientService $clientService;

    public function __construct(ModuleTemplateFactory $moduleTemplateFactory, ClientService $clientService)
    {
        parent::__construct($moduleTemplateFactory);
        $this->clientService = $clientService;

    }

    public function indexAction(): ResponseInterface
    {
        $moduleTemplate = $this->createRequestModuleTemplate();

        return $moduleTemplate->renderResponse();
    }

    public function searchAction(): ResponseInterface
    {
        $moduleTemplate = $this->createRequestModuleTemplate();

        if (!$this->request->hasArgument('query')) {
            return $this->redirect('settings', 'Settings');
        }

        $query = $this->request->getArgument('query');
        $embeddings = $this->clientService->generateEmbedding($query);
        $results = $this->clientService->getResultQuery($embeddings);

        $moduleTemplate->assignMultiple(
            [
                'results' => $results
            ]);

        return $moduleTemplate->renderResponse('Search');
    }
}
