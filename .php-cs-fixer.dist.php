<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
->in(__DIR__)
->exclude('var')
->exclude('vendor')
->exclude('node_modules')
->exclude('public')
->name('*.php');

return (new Config())
->setRules([
'@PSR12' => true,
'array_syntax' => ['syntax' => 'short'],
'declare_strict_types' => true,
'strict_comparison' => true,
])
->setFinder($finder)
->setRiskyAllowed(true);