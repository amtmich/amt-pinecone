<?php

declare(strict_types=1);

return [
    'AmtPinecone' => [
        'parent' => 'system',
        'position' => [],
        'access' => 'user',
        'workspaces' => 'online',
        'path' => '/module/amt_pinecone',
        'iconIdentifier' => 'amt_pinecone',
        'icon' => 'EXT:amt_pinecone/Resources/Public/Icons/Extension.svg',
        'extensionName' => 'AmtPinecone',
        'labels' => [
            'title' => 'AmtPinecone',
        ],
        'controllerActions' => [
            Amt\AmtPinecone\Controller\SettingsController::class => [
                'settings',
            ],
            Amt\AmtPinecone\Controller\SearchController::class => [
                'search', 'index',
            ],
        ],
        'routes' => [
            '_default' => [
                'target' => Amt\AmtPinecone\Controller\SettingsController::class.'::settings',
            ],
        ],
    ],
];
