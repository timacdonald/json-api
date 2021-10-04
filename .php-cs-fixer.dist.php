<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude('vendor');

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        '@PSR12:risky' => true,
        'no_unused_imports' => true,
        'global_namespace_import' => ['import_classes' => true, 'import_constants' => true, 'import_functions' => true],
        'native_function_invocation' => true,
        'declare_strict_types' => true,
        'strict_comparison' => true,
        'strict_param' => true,
        'explicit_string_variable' => true,
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder);
