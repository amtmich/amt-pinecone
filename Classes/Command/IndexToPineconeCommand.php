<?php

declare(strict_types=1);

namespace Amt\AmtPinecone\Command;

use Amt\AmtPinecone\Service\ClientService;
use Amt\AmtPinecone\Utility\ClientUtility;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class IndexToPineconeCommand extends Command
{
    private ClientService $clientService;
    private int $batchSize;

    public function __construct(ClientService $clientService)
    {
        parent::__construct();
        $this->batchSize = (int) ClientUtility::createExtensionConfigurationObject()->get('amt_pinecone')['pineconeBatchSize'];
        $this->clientService = $clientService;
    }

    /**
     * Execute the command to index table records into Pinecone.
     */
    public function configure(): void
    {
        $this->setDescription('Index to Pinecone API')
            ->setHelp('Index to Pinecone API with scheduler - please, add Pinecone index configuration records - tablename in the backend List Module');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $tablesToIndex = $this->clientService->getTablesToIndex();

        foreach ($tablesToIndex as $tableConfig) {
            $tableName = (string) $tableConfig['tablename'];
            if (!$this->clientService->doesTableExist($tableName)) {
                $this->clientService->sendFlashMessage('The table "'.$tableName.'" does not exist in the database.');
                continue;
            }
            $this->clientService->indexRecordsToPinecone($tableName, $this->batchSize);
        }

        return Command::SUCCESS;
    }
}
