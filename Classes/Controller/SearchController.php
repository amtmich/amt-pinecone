<?php

namespace Amt\AmtPinecone\Controller;

use Psr\Http\Message\ResponseInterface;

class SearchController extends BaseController
{
    public function indexAction(): ResponseInterface
    {
        $this->initializeJsAndCssModules();

        $moduleTemplate = $this->createRequestModuleTemplate();

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

        $moduleTemplate->assignMultiple(
            [
                'query' => $query,
            ]);

        return $moduleTemplate->renderResponse('Search');
    }
}
