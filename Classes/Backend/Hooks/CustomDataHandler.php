<?php

declare(strict_types=1);

namespace Amt\AmtPinecone\Backend\Hooks;

use Amt\AmtPinecone\Domain\Repository\PineconeConfigIndexRepository;
use Amt\AmtPinecone\Domain\Repository\PineconeRepository;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CustomDataHandler
{
    /**
     * @param array<mixed> $fieldArray
     */
    public function processDatamap_postProcessFieldArray(string $status, string $table, string $id, array &$fieldArray, DataHandler &$reference): void
    {
        $newTablename = $fieldArray['tablename'];

        if (PineconeRepository::TABLENAME_CONFIG === $table && isset($newTablename)) {
            $pineconeConfigRepository = GeneralUtility::makeInstance(PineconeConfigIndexRepository::class);
            $existingRecord = $pineconeConfigRepository->findOneByTableName($newTablename);

            if (!empty($existingRecord) && $newTablename === $existingRecord->getTablename() && ('new' === $status || 'update' === $status)) {
                $fieldArray = [];
                $this->addFlashMessage(
                    'A record with the table name "'.htmlspecialchars($newTablename).'" already exists.',
                    'Validation Error',
                    2
                );
            }
        }
    }

    private function addFlashMessage(string $message, string $title, int $severity): void
    {
        $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $message, $title, $severity, true);
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        $flashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $flashMessageQueue->addMessage($flashMessage);
    }
}
