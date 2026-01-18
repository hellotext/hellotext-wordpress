<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->name('*.php');

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,

        // Array/Hash syntax (like Ruby's modern syntax)
        'array_syntax' => ['syntax' => 'short'],
        'trailing_comma_in_multiline' => true,

        // String literals (single quotes like Ruby)
        'single_quote' => true,

        // Imports/Use statements (alphabetically ordered)
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,

        // Spacing rules (match Ruby spacing preferences)
        'binary_operator_spaces' => ['default' => 'single_space'],
        'concat_space' => ['spacing' => 'one'],
        'method_chaining_indentation' => true,
        'no_spaces_around_offset' => true,
        'no_whitespace_before_comma_in_array' => true,
        'whitespace_after_comma_in_array' => true,

        // Empty lines and whitespace
        'no_extra_blank_lines' => [
            'tokens' => [
                'extra',
                'throw',
                'use',
            ],
        ],
        'no_trailing_whitespace' => true,
        'no_trailing_whitespace_in_comment' => true,
        'single_blank_line_at_eof' => true,

        // Braces (your preference)
        'braces_position' => [
            'classes_opening_brace' => 'same_line',
            'functions_opening_brace' => 'same_line',
        ],

        // Method/Function definitions
        'method_argument_space' => [
            'on_multiline' => 'ensure_fully_multiline',
        ],
        'return_type_declaration' => ['space_before' => 'none'],

        // PHPDoc
        'phpdoc_scalar' => true,
        'phpdoc_single_line_var_spacing' => true,
        'phpdoc_var_without_name' => true,
        'phpdoc_align' => ['align' => 'left'],

        // Operators
        'not_operator_with_successor_space' => false,
        'standardize_not_equals' => true,

        // Control structures
        'no_unneeded_control_parentheses' => true,
        'no_unneeded_braces' => true,
    ])
    ->setFinder($finder);
