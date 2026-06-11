<?php

declare(strict_types=1);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        '@PSR12:risky' => true,
        'array_syntax' => ['syntax' => 'short'],
        'combine_consecutive_unsets' => true,
        'declare_strict_types' => true,
        'linebreak_after_opening_tag' => true,
        'native_function_invocation' => ['include' => ['@all']],
        'no_blank_lines_before_namespace' => false,
        'no_unused_imports' => true,
        'not_operator_with_successor_space' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'phpdoc_align' => true,
        'phpdoc_no_access' => true,
        'phpdoc_no_empty_return' => true,
        'phpdoc_no_package' => true,
        'phpdoc_scalar' => true,
        'phpdoc_summary' => false,
        'phpdoc_to_comment' => false,
        'phpdoc_types' => true,
        'return_type_declaration' => ['space_before' => 'none'],
        'single_quote' => true,
        'space_after_semicolon' => true,
        'trailing_comma_in_multiline' => true,
        'trim_array_spaces' => true,
        'whitespace_after_comma_in_array' => true,
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in(__DIR__)
    );
