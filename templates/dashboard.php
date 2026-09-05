<?php
/**
 * Dashboard view.
 *
 * @var int                                                   $historyCount
 * @var int                                                   $promptCount
 * @var int                                                   $keyCount
 * @var array<int, \AIProductStudio\History\HistoryEntry>     $recent
 * @var bool                                                  $wooActive
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap aips-wrap">
    <h1><span class="dashicons dashicons-superhero"></span> <?php esc_html_e('AI Product Studio', MY_AI_AGENT_DOMAIN); ?></h1>
    <p class="aips-subtitle"><?php esc_html_e('Automatisez la création de vos produits WooCommerce à partir d\'une image grâce à l\'IA.', MY_AI_AGENT_DOMAIN); ?></p>

    <?php if (! $wooActive) : ?>
        <div class="notice notice-warning"><p><?php esc_html_e('WooCommerce n\'est pas actif. La création de produits sera indisponible tant que WooCommerce n\'est pas installé.', MY_AI_AGENT_DOMAIN); ?></p></div>
    <?php endif; ?>

    <div class="aips-cards">
        <div class="aips-card">
            <span class="aips-card__value"><?php echo esc_html((string) $historyCount); ?></span>
            <span class="aips-card__label"><?php esc_html_e('Générations', MY_AI_AGENT_DOMAIN); ?></span>
        </div>
        <div class="aips-card">
            <span class="aips-card__value"><?php echo esc_html((string) $promptCount); ?></span>
            <span class="aips-card__label"><?php esc_html_e('Prompts', MY_AI_AGENT_DOMAIN); ?></span>
        </div>
        <div class="aips-card">
            <span class="aips-card__value"><?php echo esc_html((string) $keyCount); ?></span>
            <span class="aips-card__label"><?php esc_html_e('Clés API', MY_AI_AGENT_DOMAIN); ?></span>
        </div>
    </div>

    <p>
        <a class="button button-primary button-hero" href="<?php echo esc_url(admin_url('admin.php?page=ai-product-studio-generate')); ?>">
            <?php esc_html_e('➜ Générer un produit', MY_AI_AGENT_DOMAIN); ?>
        </a>
    </p>

    <h2><?php esc_html_e('Dernières générations', MY_AI_AGENT_DOMAIN); ?></h2>
    <table class="widefat striped">
        <thead>
            <tr>
                <th><?php esc_html_e('Date', MY_AI_AGENT_DOMAIN); ?></th>
                <th><?php esc_html_e('Statut', MY_AI_AGENT_DOMAIN); ?></th>
                <th><?php esc_html_e('Fournisseur', MY_AI_AGENT_DOMAIN); ?></th>
                <th><?php esc_html_e('Durée', MY_AI_AGENT_DOMAIN); ?></th>
                <th><?php esc_html_e('Produit', MY_AI_AGENT_DOMAIN); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php if ($recent === []) : ?>
            <tr><td colspan="5"><?php esc_html_e('Aucune génération pour le moment.', MY_AI_AGENT_DOMAIN); ?></td></tr>
        <?php else : ?>
            <?php foreach ($recent as $entry) : ?>
                <tr>
                    <td><?php echo esc_html($entry->createdAt); ?></td>
                    <td><span class="aips-badge aips-badge--<?php echo esc_attr($entry->status); ?>"><?php echo esc_html($entry->status); ?></span></td>
                    <td><?php echo esc_html($entry->provider); ?></td>
                    <td><?php echo esc_html((string) $entry->duration); ?>s</td>
                    <td>
                        <?php if ($entry->productId > 0) : ?>
                            <a href="<?php echo esc_url((string) get_edit_post_link($entry->productId)); ?>">#<?php echo esc_html((string) $entry->productId); ?></a>
                        <?php else : ?>
                            &mdash;
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
