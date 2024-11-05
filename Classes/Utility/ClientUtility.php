<?php

declare(strict_types=1);

namespace Amt\AmtPinecone\Utility;

use Amt\AmtPinecone\Domain\Repository\PineconeRepository;
use Amt\AmtPinecone\Http\Client\OpenAiClient;
use Amt\AmtPinecone\Http\Client\PineconeClient;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ClientUtility
{
    public static function createExtensionConfigurationObject(): ExtensionConfiguration
    {
        return GeneralUtility::makeInstance(ExtensionConfiguration::class);
    }

    public static function createOpenAiClient(): OpenAiClient
    {
        return GeneralUtility::makeInstance(OpenAiClient::class, self::createExtensionConfigurationObject(), self::createRegistryObject());
    }

    public static function createPineconeClient(): PineconeClient
    {
        return GeneralUtility::makeInstance(PineconeClient::class, self::createExtensionConfigurationObject(), self::createPineconeRepositoryObject());
    }

    public static function createRegistryObject(): Registry
    {
        return GeneralUtility::makeInstance(Registry::class);
    }

    public static function createPineconeRepositoryObject(): PineconeRepository
    {
        return GeneralUtility::makeInstance(PineconeRepository::class);
    }
}
