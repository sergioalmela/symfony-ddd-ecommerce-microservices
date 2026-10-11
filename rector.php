<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Attribute\SortAttributeNamedArgsRector;
use Rector\CodeQuality\Rector\FuncCall\SortCallLikeNamedArgsRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\Property\RemoveUnusedPrivatePropertyRector;
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->configure()->withComposerBased(symfony: true);

    $rectorConfig->paths([
        __DIR__.'/src',
    ]);

    $rectorConfig->skip([
        __DIR__.'/src/Kernel.php',
        __DIR__.'/vendor',
        __DIR__.'/var',
        __DIR__.'/migrations',
        // The version is mapped only in Doctrine XML, which reads and writes it by reflection.
        RemoveUnusedPrivatePropertyRector::class => [__DIR__.'/src/Order/Domain/Entity/Order.php'],
        // Keep OpenAPI attribute arguments in reading order instead of parameter order.
        SortAttributeNamedArgsRector::class,
        SortCallLikeNamedArgsRector::class,
    ]);

    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_85,
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,
        SetList::TYPE_DECLARATION,
        SetList::PRIVATIZATION,
        SetList::NAMING,
        SetList::INSTANCEOF,
        DoctrineSetList::DOCTRINE_CODE_QUALITY,
        DoctrineSetList::ANNOTATIONS_TO_ATTRIBUTES,
    ]);

    $rectorConfig->rules([
        AddVoidReturnTypeWhereNoReturnRector::class,
    ]);

    $rectorConfig->importNames();
    $rectorConfig->importShortClasses();

    $rectorConfig->parallel();

    $rectorConfig->memoryLimit('1G');
};
