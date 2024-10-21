<?php
return [
    'ctrl' => [
        'title' => 'Pinecone Index',
        'label' => 'record_uid',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'searchFields' => 'tablename,record_uid',
        'iconfile' => 'EXT:amt_pinecone/Resources/Public/Icons/Extension.svg',
    ],
    'columns' => [
        'record_uid' => [
            'exclude' => true,
            'label' => 'Record UID',
            'config' => [
                'type' => 'input',
                'eval' => 'int',
            ],
        ],
        'tablename' => [
            'exclude' => true,
            'label' => 'Table Name',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
            ],
        ],
        'is_indexed' => [
            'exclude' => true,
            'label' => 'Is Indexed',
            'config' => [
                'type' => 'check',
                'default' => 0,
            ],
        ],
        'indexed_timestamp' => [
            'exclude' => true,
            'label' => 'Timestamp when indexed',
            'config' => [
                'type' => 'input',
                'eval' => 'datetime',
            ],
        ],
    ],
    'types' => [
        '1' => ['showitem' => 'record_uid, tablename, is_indexed, indexed_timestamp'],
    ],
];
