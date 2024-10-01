<?php

declare(strict_types=1);

namespace Amt\AmtPinecone\Utility;

use Amt\AmtPinecone\Http\Client\OpenAiClient;
use Amt\AmtPinecone\Http\Client\PineconeClient;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ClientUtility
{
    public static function createExtensionConfigurationObject(): ExtensionConfiguration
    {
        return GeneralUtility::makeInstance(ExtensionConfiguration::class);
    }

    public static function createOpenAiClient(): OpenAiClient
    {
        return GeneralUtility::makeInstance(OpenAiClient::class, self::createExtensionConfigurationObject());
    }

    public static function createPineconeClient(): PineconeClient
    {
        return GeneralUtility::makeInstance(PineconeClient::class, self::createExtensionConfigurationObject());
    }
}
