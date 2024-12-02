<?php

$EM_CONF['amt_pinecone'] = [
    'title' => 'Semantic search (OpenAI + Pinecone)',
    'description' => 'AMT_Pinecone is a TYPO3 extension that integrates semantic search capabilities into your website using OpenAI embedding models and the Pinecone vector database. Semantic search focuses on understanding the meaning and context of search queries rather than relying on exact keyword matches. It enables more intuitive and relevant search results by analyzing the relationships between words and their meanings. This extension leverages advanced AI models to provide users with highly accurate and context-aware search experiences.',
    'category' => 'be',
    'author' => 'Michał Cygankiewicz, Krystian Chanek',
    'author_company' => 'AMT Solution',
    'author_email' => 'kontakt@amtsolution.pl',
    'state' => 'stable',
    'clearCacheOnLoad' => true,
    'version' => '0.1.1',
    'constraints' => [
        'depends' => [
            'typo3' => '12.0.0-12.99.99',
        ],
    ],
];
