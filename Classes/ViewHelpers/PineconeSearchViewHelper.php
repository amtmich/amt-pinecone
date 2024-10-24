<?php

declare(strict_types=1);

namespace Amt\AmtPinecone\ViewHelpers;

use Amt\AmtPinecone\Service\PineconeSearchService;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class PineconeSearchViewHelper extends AbstractViewHelper
{
    private PineconeSearchService $pineconeSearchService;

    public function injectPineconeSearchService(PineconeSearchService $pineconeSearchService): void
    {
        $this->pineconeSearchService = $pineconeSearchService;
    }

    public function initializeArguments()
    {
        $this->registerArgument('query', 'string', 'Search query from the user input', true);
        $this->registerArgument('count', 'int', 'Number of results to return', false, 10);
        $this->registerArgument('table', 'string', 'Table name to search from', false, '');
    }

    public function render()
    {
        $query = $this->arguments['query'];
        $count = $this->arguments['count'];
        $table = $this->arguments['table'];

        return json_decode(json_encode($this->pineconeSearchService->search($query, $count, $table)), true)['matches'];
    }
}
