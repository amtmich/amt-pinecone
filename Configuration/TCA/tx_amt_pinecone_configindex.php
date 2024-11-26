<?php

return [
    'ctrl' => [
        'title' => 'Pinecone index configuration',
        'label' => 'tablename',
        'tstamp' => 'updated_at',
        'crdate' => 'created_at',
        'rootLevel' => -1,
        'cruser_id' => 'cruser_id',
        'security' => [
            'ignorePageTypeRestriction',
        ],
        'iconfile' => 'EXT:amt_pinecone/Resources/Public/Icons/Extension.svg',
    ],
    'columns' => [
        'pid' => [
            'label' => 'pid',
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'tablename' => [
            'exclude' => true,
            'label' => 'Table Name',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['Please choose...', ''],
                ],
                'itemsProcFunc' => Amt\AmtPinecone\Service\TableSelectorConfigIndex::class.'->getValidTables',
                'size' => 1,
                'maxitems' => 1,
                'eval' => 'required',
            ],
        ],
        'columns_index' => [
            'label' => 'Columns to index',
            'config' => [
                'type' => 'input',
                'size' => 255,
                'eval' => 'trim',
            ],
        ],
        'record_pid' => [
            'exclude' => true,
            'label' => 'Record Storage Page',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'pages',
                'default' => 'empty',
            ],
            'suggestOptions' => [
                'pages' => [
                    'pid' => '###CURRENT_PID###',
                ],
            ],
        ],
        'crdate' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' => 'tablename, columns_index, record_pid'],
    ],
];
