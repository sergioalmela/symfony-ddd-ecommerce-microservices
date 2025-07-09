<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
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
    ]);

    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_84,
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
