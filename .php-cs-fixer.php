<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/public_html')
    ->name('*.php');

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12'                     => true,
        'array_syntax'               => ['syntax' => 'short'],
        'no_unused_imports'          => true,
        'trailing_comma_in_multiline'=> true,
        'single_quote'               => true,
    ])
    ->setFinder($finder);
