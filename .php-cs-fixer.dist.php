<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
;

return Retailcrm\PhpCsFixer\Defaults::rules([
        'no_trailing_whitespace_in_string' => false,
    ])
    ->setFinder($finder)
    ->setCacheFile(__DIR__ . '/.php_cs.cache/results')
;
