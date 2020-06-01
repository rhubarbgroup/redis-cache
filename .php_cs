<?php

$rules = array(
    'no_spaces_inside_parenthesis' => false,
);

return PhpCsFixer\Config::create()
    ->setRiskyAllowed(true)
    ->setRules($rules)
    ->setUsingCache(true)
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->exclude('assets')
            ->exclude('languages')
            ->in(__DIR__)
    );
