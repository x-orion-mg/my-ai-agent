<?php
declare(strict_types=1);

namespace MyAIAgent\Pipeline\Enum;

enum ProductJobType: string
{
    case SOURCE = 'source';
    case AI_GENERATION = 'ai_generation';
    case VALIDATION = 'validation';
    case WOOCOMMERCE = 'woocommerce';
}
