<?php

$finder = \PhpCsFixer\Finder::create()
    ->exclude('Resources')
    ->exclude('public')
    ->in(__DIR__);

return (new \PhpCsFixer\Config())
    ->setCacheFile('.php_cs.cache')
    ->setFinder($finder)
    ->setRules([
        '@PSR2' => true,
        '@Symfony' => true,
    ])
    ->setLineEnding("\n");
