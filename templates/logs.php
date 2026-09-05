<?php
/**
 * Logs view.
 *
 * @var array<int, string> $lines
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap aips-wrap">
    <h1><?php esc_html_e('Logs', MY_AI_AGENT_DOMAIN); ?></h1>

    <form method="post" action="" style="margin-bottom:1em;">
        <?php wp_nonce_field('aips_clear_logs', 'aips_logs_nonce'); ?>
        <button type="submit" name="aips_clear_logs" value="1" class="button"><?php esc_html_e('Vider les logs', MY_AI_AGENT_DOMAIN); ?></button>
    </form>

    <div class="aips-logs">
        <?php if ($lines === []) : ?>
            <p><?php esc_html_e('Aucun log pour le moment.', MY_AI_AGENT_DOMAIN); ?></p>
        <?php else : ?>
            <pre class="aips-logs__pre"><?php echo esc_html(implode("\n", $lines)); ?></pre>
        <?php endif; ?>
    </div>
</div>
