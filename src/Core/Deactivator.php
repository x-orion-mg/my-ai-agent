<?php

declare(strict_types=1);

namespace MyAIAgent\Core;

final class Deactivator
{
    public static function deactivate(): void
    {
        flush_rewrite_rules();
    }
}
