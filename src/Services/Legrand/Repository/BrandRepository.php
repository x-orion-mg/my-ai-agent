<?php

declare(strict_types=1);

namespace MyAIAgent\Services\Legrand\Repository;

final class BrandRepository extends AbstractRepository
{
    protected static function tableSuffix(): string
    {
        return 'brands';
    }
}
