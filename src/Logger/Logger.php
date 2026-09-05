<?php

declare(strict_types=1);

namespace MyAIAgent\Logger;

use MyAIAgent\Services\Settings;

/**
 * Simple PSR-3-inspired file logger with daily files and automatic rotation.
 * Every AI request/response and error flows through here.
 */
final class Logger
{
    private const LEVELS = [
        'debug'   => 100,
        'info'    => 200,
        'notice'  => 250,
        'warning' => 300,
        'error'   => 400,
        'critical'=> 500,
    ];

    private string $logDir;

    private Settings $settings;

    public function __construct(Settings $settings, ?string $logDir = null)
    {
        $this->settings = $settings;
        $this->logDir   = rtrim($logDir ?? (AIPS_STORAGE_DIR . 'logs'), '/\\') . '/';

        if (! is_dir($this->logDir)) {
            wp_mkdir_p($this->logDir);
        }
    }

    /**
     * @param array<string, mixed> $context
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function log(string $level, string $message, array $context = []): void
    {
        $threshold = self::LEVELS[$this->settings->get('log_level', 'info')] ?? 200;
        $current   = self::LEVELS[$level] ?? 200;

        if ($current < $threshold) {
            return;
        }

        $line = sprintf(
            "[%s] %s: %s %s%s",
            gmdate('Y-m-d H:i:s'),
            strtoupper($level),
            $message,
            $context === [] ? '' : $this->encodeContext($context),
            PHP_EOL
        );

        $file = $this->logDir . 'my_ai_agent-' . gmdate('Y-m-d') . '.log';

        file_put_contents($file, $line, FILE_APPEND | LOCK_EX);

        $this->rotate();
    }

    /**
     * Redact secrets before serialising the context.
     *
     * @param array<string, mixed> $context
     */
    private function encodeContext(array $context): string
    {
        array_walk_recursive($context, static function (&$value, $key): void {
            if (is_string($key) && preg_match('/(api_key|authorization|secret|token|password)/i', $key) === 1) {
                $value = '***redacted***';
            }
        });

        return (string) wp_json_encode($context);
    }

    /**
     * Remove log files older than the configured retention window and keep the
     * number of files under the configured maximum.
     */
    public function rotate(): void
    {
        $retentionDays = (int) $this->settings->get('log_retention_days', 14);
        $maxFiles      = (int) $this->settings->get('log_max_files', 14);

        $files = glob($this->logDir . 'my_ai_agent-*.log') ?: [];

        // Delete by age.
        $cutoff = time() - ($retentionDays * DAY_IN_SECONDS);
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                @unlink($file);
            }
        }

        // Delete by count (oldest first).
        $files = glob($this->logDir . 'my_ai_agent-*.log') ?: [];
        if (count($files) > $maxFiles) {
            usort($files, static fn ($a, $b): int => filemtime($a) <=> filemtime($b));
            $excess = array_slice($files, 0, count($files) - $maxFiles);
            foreach ($excess as $file) {
                @unlink($file);
            }
        }
    }

    /**
     * Return recent log lines for display in the admin, newest first.
     *
     * @return array<int, string>
     */
    public function tail(int $lines = 200): array
    {
        $files = glob($this->logDir . 'my_ai_agent-*.log') ?: [];
        if ($files === []) {
            return [];
        }

        usort($files, static fn ($a, $b): int => filemtime($b) <=> filemtime($a));

        $collected = [];
        foreach ($files as $file) {
            $content   = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            $collected = array_merge($collected, array_reverse($content));
            if (count($collected) >= $lines) {
                break;
            }
        }

        return array_slice($collected, 0, $lines);
    }

    public function clear(): void
    {
        $files = glob($this->logDir . 'my_ai_agent-*.log') ?: [];
        foreach ($files as $file) {
            @unlink($file);
        }
    }
}
