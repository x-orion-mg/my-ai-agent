<?php
/**
 * History view.
 *
 * @var array<int, \AIProductStudio\History\HistoryEntry> $entries
 * @var int                                               $page
 * @var int                                               $perPage
 * @var int                                               $total
 * @var int                                               $pages
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap aips-wrap">
    <h1><?php esc_html_e('Historique', MY_AI_AGENT_DOMAIN); ?></h1>

    <table class="widefat striped">
        <thead>
            <tr>
                <th><?php esc_html_e('Date', MY_AI_AGENT_DOMAIN); ?></th>
                <th><?php esc_html_e('Statut', MY_AI_AGENT_DOMAIN); ?></th>
                <th><?php esc_html_e('Fournisseur', MY_AI_AGENT_DOMAIN); ?></th>
                <th><?php esc_html_e('Prompt', MY_AI_AGENT_DOMAIN); ?></th>
                <th><?php esc_html_e('Durée', MY_AI_AGENT_DOMAIN); ?></th>
                <th><?php esc_html_e('Produit', MY_AI_AGENT_DOMAIN); ?></th>
                <th><?php esc_html_e('Message', MY_AI_AGENT_DOMAIN); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php if ($entries === []) : ?>
            <tr><td colspan="7"><?php esc_html_e('Aucune entrée.', MY_AI_AGENT_DOMAIN); ?></td></tr>
        <?php else : ?>
            <?php foreach ($entries as $entry) : ?>
                <tr>
                    <td><?php echo esc_html($entry->createdAt); ?></td>
                    <td><span class="aips-badge aips-badge--<?php echo esc_attr($entry->status); ?>"><?php echo esc_html($entry->status); ?></span></td>
                    <td><?php echo esc_html($entry->provider); ?></td>
                    <td><?php echo esc_html((string) $entry->promptId); ?></td>
                    <td><?php echo esc_html((string) $entry->duration); ?>s</td>
                    <td>
                        <?php if ($entry->productId > 0) : ?>
                            <a href="<?php echo esc_url((string) get_edit_post_link($entry->productId)); ?>">#<?php echo esc_html((string) $entry->productId); ?></a>
                        <?php else : ?>
                            &mdash;
                        <?php endif; ?>
                    </td>
                    <td><?php echo esc_html($entry->message); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>

    <?php if ($pages > 1) : ?>
        <div class="tablenav"><div class="tablenav-pages">
            <?php
            echo wp_kses_post(paginate_links([
                'base'      => add_query_arg('paged', '%#%'),
                'format'    => '',
                'current'   => $page,
                'total'     => $pages,
                'prev_text' => '‹',
                'next_text' => '›',
            ]) ?? '');
            ?>
        </div></div>
    <?php endif; ?>
</div>
