<?php

declare(strict_types=1);

namespace MyAIAgent\Services\Legrand\Repository;

final class TagRepository extends AbstractRepository
{
    protected static function tableSuffix(): string
    {
        return 'tags';
    }
}
