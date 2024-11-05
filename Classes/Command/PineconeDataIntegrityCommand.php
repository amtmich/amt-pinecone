<?php

declare(strict_types=1);

namespace Amt\AmtPinecone\Command;

use Amt\AmtPinecone\Domain\Repository\PineconeRepository;
use Amt\AmtPinecone\Http\Client\PineconeClient;
use Amt\AmtPinecone\Service\ClientService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class PineconeDataIntegrityCommand extends Command
{

    private ClientService $clientService;
    private PineconeRepository $pineconeRepository;
    private PineconeClient $pineconeClient;

    public function __construct(ClientService $clientService, PineconeRepository $pineconeRepository, PineconeClient $pineconeClient)
    {
        parent::__construct();
        $this->clientService = $clientService;
        $this->pineconeRepository = $pineconeRepository;
        $this->pineconeClient = $pineconeClient;
    }

    /**
     * Execute the command to sync data integrity between AmtPinecone extension and the Pinecone records.
     */
    public function configure(): void
    {
        $this->setDescription('Sync data integrity between TYPO3 and Pinecone')
            ->setHelp('Sync data integrity between TYPO3 and Pinecone');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $detachedRecords = $this->pineconeRepository->getDetachedRecords();
        $this->pineconeRepository->softDelete($detachedRecords);
        $this->pineconeRepository->hardDelete($detachedRecords);
        $this->pineconeClient->vectorsDelete($detachedRecords);
        $uidsOfRecordsToDelete = $this->clientService->compareLocalToPinecone();
        $this->pineconeClient->vectorsDelete($uidsOfRecordsToDelete);
        $pineconeIndexEntriesToDelete = $this->clientService->findDetachedRecordsInPinecone();
        $this->pineconeRepository->deleteByUids($pineconeIndexEntriesToDelete);

        return Command::SUCCESS;
    }
}
