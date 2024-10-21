<?php
return [
    'ctrl' => [
        'title' => 'Pinecone index configuration',
        'label' => 'tablename',
        'tstamp' => 'updated_at',
        'crdate' => 'created_at',
        'rootLevel' => 1,
        'cruser_id' => 'cruser_id',
        'security' => [
            'ignorePageTypeRestriction',
        ],
        'iconfile' => 'EXT:amt_pinecone/Resources/Public/Icons/Extension.svg',
    ],
    'columns' => [
        'tablename' => [
            'label' => 'Table Name',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'eval' => 'trim,required',
            ],
        ],
        'crdate' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' => 'tablename'],
    ],
];
