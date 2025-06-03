<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\ArrayNotation\ArraySyntaxFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return function (ECSConfig $ecsConfig): void {
    $ecsConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __DIR__ . '/composer.json',
        __DIR__ . '/ecs.php',
        __DIR__ . '/phpcs.xml',
        __DIR__ . '/phpstan.neon',
    ]);

    // Skip vendor, bin, and var directories
    $ecsConfig->skip([
        __DIR__ . '/vendor',
        __DIR__ . '/bin',
        __DIR__ . '/var',
    ]);

    // Use PSR-12 coding standard
    $ecsConfig->sets([
        SetList::PSR_12,
        SetList::CLEAN_CODE,
        SetList::COMMON,
    ]);

    // Configure array syntax to use short arrays
    $ecsConfig->ruleWithConfiguration(ArraySyntaxFixer::class, [
        'syntax' => 'short',
    ]);
};
