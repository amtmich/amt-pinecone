<?php

declare(strict_types=1);

namespace Amt\AmtPinecone\Command;

use Amt\AmtPinecone\Service\ClientService;
use Amt\AmtPinecone\Utility\ClientUtility;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class IndexToPineconeCommand extends Command
{

    private ClientService $clientService;
    private string $tableName;
    private int $batchSize;

    public function __construct(ClientService $clientService)
    {
        parent::__construct();
        $this->tableName = '';
        $this->batchSize = (int)ClientUtility::createExtensionConfigurationObject()->get('amt_pinecone')['pineconeBatchSize'] ?? 10;
        $this->clientService = $clientService;
    }

    /**
     * Execute the command to index table records into Pinecone.
     */
    public function configure(): void
    {
        $this->setDescription('Index to Pinecone API')
            ->setHelp('Index to Pinecone API with scheduler - please, input tablename to index in Pinecone API')
            ->addArgument('tableName', InputArgument::REQUIRED);
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->clientService->indexRecordsToPinecone((string)$input->getArgument('tableName'), $this->batchSize);

        return Command::SUCCESS;
    }
}
