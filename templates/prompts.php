<?php
/**
 * Prompts view (AJAX-driven CRUD).
 *
 * @var array<int, \AIProductStudio\Prompt\Prompt> $prompts
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap aips-wrap">
    <h1><?php esc_html_e('Prompts', 'ai-product-studio'); ?></h1>
    <p class="aips-subtitle"><?php esc_html_e('Variables disponibles : {{description_utilisateur}}, {{image}}, {{categorie}}, {{prix}}, {{promotion}}, {{produits_associes}}, {{langue}}, {{orientation}}.', 'ai-product-studio'); ?></p>

    <div class="aips-columns">
        <div class="aips-columns__list">
            <table class="widefat striped" id="aips-prompts-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Nom', 'ai-product-studio'); ?></th>
                        <th><?php esc_html_e('Actif', 'ai-product-studio'); ?></th>
                        <th><?php esc_html_e('Actions', 'ai-product-studio'); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($prompts === []) : ?>
                    <tr><td colspan="3"><?php esc_html_e('Aucun prompt.', 'ai-product-studio'); ?></td></tr>
                <?php else : ?>
                    <?php foreach ($prompts as $prompt) : ?>
                        <tr data-id="<?php echo esc_attr((string) $prompt->id); ?>"
                            data-name="<?php echo esc_attr($prompt->name); ?>"
                            data-description="<?php echo esc_attr($prompt->description); ?>"
                            data-active="<?php echo esc_attr($prompt->isActive ? '1' : '0'); ?>">
                            <td class="aips-prompt-name"><?php echo esc_html($prompt->name); ?></td>
                            <td>
                                <button class="button-link aips-toggle-prompt" data-id="<?php echo esc_attr((string) $prompt->id); ?>">
                                    <?php echo $prompt->isActive ? '✔' : '—'; ?>
                                </button>
                            </td>
                            <td>
                                <button class="button aips-edit-prompt"><?php esc_html_e('Éditer', 'ai-product-studio'); ?></button>
                                <button class="button aips-delete-prompt" data-id="<?php echo esc_attr((string) $prompt->id); ?>"><?php esc_html_e('Supprimer', 'ai-product-studio'); ?></button>
                                <textarea class="aips-prompt-content" hidden><?php echo esc_textarea($prompt->content); ?></textarea>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="aips-columns__form">
            <h2 id="aips-prompt-form-title"><?php esc_html_e('Nouveau prompt', 'ai-product-studio'); ?></h2>
            <form id="aips-prompt-form">
                <input type="hidden" name="id" id="aips-prompt-id" value="0">
                <p><label><?php esc_html_e('Nom', 'ai-product-studio'); ?><br><input type="text" name="name" id="aips-prompt-name-input" class="regular-text" required></label></p>
                <p><label><?php esc_html_e('Description', 'ai-product-studio'); ?><br><textarea name="description" id="aips-prompt-description" rows="2" class="large-text"></textarea></label></p>
                <p><label><?php esc_html_e('Contenu', 'ai-product-studio'); ?><br><textarea name="content" id="aips-prompt-content-input" rows="10" class="large-text code"></textarea></label></p>
                <p><label><input type="checkbox" name="is_active" id="aips-prompt-active" value="1" checked> <?php esc_html_e('Actif', 'ai-product-studio'); ?></label></p>
                <p>
                    <button type="submit" class="button button-primary"><?php esc_html_e('Enregistrer', 'ai-product-studio'); ?></button>
                    <button type="button" class="button" id="aips-prompt-reset"><?php esc_html_e('Réinitialiser', 'ai-product-studio'); ?></button>
                </p>
            </form>
        </div>
    </div>
</div>
