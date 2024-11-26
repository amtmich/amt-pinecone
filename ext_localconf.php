<?php

defined('TYPO3') or exit;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = Amt\AmtPinecone\Backend\Hooks\CustomDataHandler::class;
