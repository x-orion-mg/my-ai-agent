<?php

declare(strict_types=1);

namespace MyAIAgent\Services;

/**
 * Typed accessor around the plugin's single option row. Centralises defaults,
 * reads and writes so no other class touches the options table directly.
 */
final class Settings
{
    public const OPTION_KEY = 'my_ai_agent_settings';

    /** @var array<string, mixed>|null */
    private ?array $cache = null;

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'default_provider'    => 'openai',
            'default_prompt_id'   => 0,
            'image_max_width'     => 1600,
            'image_quality'       => 82,
            'request_timeout'     => 120,
            'max_error_before_disable' => 5,
            'log_level'           => 'info',
            'log_max_files'       => 14,
            'log_retention_days'  => 14,
            'default_status'      => 'draft',
            'generate_seo'        => true,
            'seo_plugin'          => 'auto', // auto|yoast|rankmath|none
            'language'            => 'fr',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        if ($this->cache === null) {
            $stored      = get_option(self::OPTION_KEY, []);
            $stored      = is_array($stored) ? $stored : [];
            $this->cache = array_merge(self::defaults(), $stored);
        }

        return $this->cache;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();

        return $all[$key] ?? $default;
    }

    /**
     * Persist a partial or full set of settings.
     *
     * @param array<string, mixed> $values
     */
    public function update(array $values): void
    {
        $merged = array_merge($this->all(), $values);
        update_option(self::OPTION_KEY, $merged);
        $this->cache = $merged;
    }
}
