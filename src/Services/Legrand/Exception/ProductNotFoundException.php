<?php

declare(strict_types=1);

namespace MyAIAgent\Services\Legrand\Exception;

final class ProductNotFoundException extends LegrandException
{
    public function __construct(
        string $reference
    ) {
        parent::__construct(
            sprintf(
                'Produit Legrand introuvable pour la référence "%s".',
                $reference
            )
        );
    }
}
