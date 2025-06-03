<?php
/*
 * This document has been generated with
 * https://mlocati.github.io/php-cs-fixer-configurator/#version:3.13.2|configurator
 * you can change this configuration by importing this file.
 */
$config = new PhpCsFixer\Config();
return $config
    ->setRules([
        '@PSR12' => true,
        'visibility_required' => false, // php 5.6 doesn't support "public const ..."
    ])
    ->setFinder(PhpCsFixer\Finder::create()
        ->in(__DIR__)
        ->name('*.php')
        ->ignoreDotFiles(true)
        ->ignoreVCS(true)
    )
;
