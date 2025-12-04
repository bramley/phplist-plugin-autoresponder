<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude(['plugins/Autoresponder/View'])
;

$config = new PhpCsFixer\Config();

return $config->setRules([
        '@PSR1' => true,
        '@PSR2' => true,
        '@Symfony' => true,
        'concat_space' => false,
        'phpdoc_no_alias_tag' => false,
        'yoda_style' => false,
        'array_syntax' => false,
        'no_superfluous_phpdoc_tags' => false,
        'ordered_imports' => [
            'sort_algorithm' => 'alpha',
            'imports_order' => ['class', 'function', 'const'],
        ],
        'blank_line_after_namespace' => true,
        'single_line_comment_style' => false,
        'visibility_required' => false,
        'phpdoc_to_comment' => false,
        'type_declaration_spaces' => false,
        'global_namespace_import' => false,
        'blank_line_after_opening_tag' => false,
    ])
    ->setFinder($finder)
;
