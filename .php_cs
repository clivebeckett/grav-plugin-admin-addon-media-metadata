<?php
/**
 * This file represents the configuration for Code Sniffing PSR-2-related
 * checks of coding guidelines and is based on a template from the news extension
 * by Georg Ringer for the TYPO3 CMS.
 *
 * Install @fabpot's great php-cs-fixer tool via
 *
 *  $ composer global require friendsofphp/php-cs-fixer
 *
 * And then simply run
 *
 *  $ php-cs-fixer fix --config .php_cs
 *
 * For more information read:
 * 	 http://www.php-fig.org/psr/psr-2/
 * 	 http://cs.sensiolabs.org
 */

if (PHP_SAPI !== 'cli') {
    die('This script supports command line usage only. Please check your command.');
}
// Define in which folders to search and which folders to exclude
// Exclude some directories that are excluded by Git anyways to speed up the sniffing
$finder = PhpCsFixer\Finder::create()
    ->exclude('vendor/')
    ->in(__DIR__);

// Return a Code Sniffing configuration using
// all sniffers needed for PSR-2
// and additionally:
//  - Remove leading slashes in use clauses.
//  - PHP single-line arrays should not have trailing comma.
//  - Single-line whitespace before closing semicolon are prohibited.
//  - Remove unused use statements in the PHP source code
//  - Ensure Concatenation to have at least one whitespace around
//  - Remove trailing whitespace at the end of blank lines.
return PhpCsFixer\Config::create()
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR2' => true,
        'align_multiline_comment' => [
            'comment_type' => 'all_multiline',
        ],
        'array_syntax' => [
            'syntax' => 'short'
        ],
        'binary_operator_spaces' => [
            'default' => 'single_space'
        ],
        'concat_space' => [
            'spacing' => 'one'
        ],
        'function_typehint_space' => true,
        'hash_to_slash_comment' => true,
        'lowercase_cast' => true,
        'native_function_casing' => true,
        'no_alias_functions' => true,
        'no_blank_lines_after_phpdoc' => true,
        'no_empty_statement' => true,
        'no_extra_consecutive_blank_lines' => true,
        'no_leading_import_slash' => true,
        'no_leading_namespace_whitespace' => true,
        'no_trailing_comma_in_singleline_array' => true,
        'no_short_bool_cast' => true,
        'no_singleline_whitespace_before_semicolons' => true,
        'no_unused_imports' => true,
        'no_unneeded_control_parentheses' => true,
        'no_whitespace_in_blank_line' => true,
        'ordered_imports' => true,
        'phpdoc_no_empty_return' => true,
        'phpdoc_no_package' => false,
        'phpdoc_scalar' => true,
        'phpdoc_trim' => true,
        'return_type_declaration' => [
            'space_before' => 'none'
        ],
        'self_accessor' => true,
        'single_quote' => true,
        'standardize_not_equals' => true,
        'whitespace_after_comma_in_array' => true,
    ])
    ->setFinder($finder);
