<?php

namespace Amt\AmtPinecone\Controller;

use Amt\AmtPinecone\Service\ClientService;
use Amt\AmtPinecone\Utility\ClientUtility;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;

class IndexingStatusController extends BaseController
{
    protected ClientService $clientService;

    public function __construct(ModuleTemplateFactory $moduleTemplateFactory, ClientService $clientService)
    {
        parent::__construct($moduleTemplateFactory);
        $this->clientService = $clientService;
    }

    public function indexingStatusAction(): ResponseInterface
    {
        $this->initializeJsAndCssModules();
        $moduleTemplate = $this->createRequestModuleTemplate();
        $pineconeClient = ClientUtility::createPineconeClient();
        $pineconeIndexedRecords = count($pineconeClient->getVectorsList());
        $dataIntegrityStatus = $this->clientService->checkDataIntegrityStatus($pineconeIndexedRecords);
        $this->displayFlashMessage('Potential integrity problems - please run scheduler command "amt-pinecone:data-integrity".', $dataIntegrityStatus, 1);

        $indexingStatus = [
            'typo3IndexedRecords' => $this->clientService->getIndexedRecordsCount(),
            'pineconeIndexedRecords' => $pineconeIndexedRecords,
            'dataIntegrityStatus' => $dataIntegrityStatus,
            'indexingProgress' => $this->clientService->getIndexingProgress(),
        ];
        $moduleTemplate->assignMultiple(
            [
                'indexingStatus' => $indexingStatus,
            ]);

        return $moduleTemplate->renderResponse('IndexingStatus');
    }
}
