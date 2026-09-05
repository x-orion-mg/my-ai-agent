<?php
/**
 * Settings view.
 *
 * @var array<string, mixed>                       $settings
 * @var array<string, string>                      $providers
 * @var array<int, \AIProductStudio\Prompt\Prompt> $prompts
 * @var string                                     $nonceAction
 * @var string                                     $nonceField
 */

if (! defined('ABSPATH')) {
    exit;
}

settings_errors('aips_settings');
?>
<div class="wrap aips-wrap">
    <h1><?php esc_html_e('Configuration', 'ai-product-studio'); ?></h1>

    <form method="post" action="">
        <?php wp_nonce_field($nonceAction, $nonceField); ?>

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><label for="default_provider"><?php esc_html_e('Fournisseur IA par défaut', 'ai-product-studio'); ?></label></th>
                <td>
                    <select name="default_provider" id="default_provider">
                        <?php foreach ($providers as $slug => $label) : ?>
                            <option value="<?php echo esc_attr($slug); ?>" <?php selected($slug, $settings['default_provider']); ?>><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="default_prompt_id"><?php esc_html_e('Prompt par défaut', 'ai-product-studio'); ?></label></th>
                <td>
                    <select name="default_prompt_id" id="default_prompt_id">
                        <option value="0"><?php esc_html_e('— Premier prompt actif —', 'ai-product-studio'); ?></option>
                        <?php foreach ($prompts as $prompt) : ?>
                            <option value="<?php echo esc_attr((string) $prompt->id); ?>" <?php selected($prompt->id, (int) $settings['default_prompt_id']); ?>><?php echo esc_html($prompt->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="default_status"><?php esc_html_e('Statut du produit créé', 'ai-product-studio'); ?></label></th>
                <td>
                    <select name="default_status" id="default_status">
                        <?php foreach (['draft' => __('Brouillon', 'ai-product-studio'), 'publish' => __('Publié', 'ai-product-studio'), 'pending' => __('En attente', 'ai-product-studio')] as $val => $label) : ?>
                            <option value="<?php echo esc_attr($val); ?>" <?php selected($val, $settings['default_status']); ?>><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="language"><?php esc_html_e('Langue de génération', 'ai-product-studio'); ?></label></th>
                <td><input type="text" name="language" id="language" value="<?php echo esc_attr((string) $settings['language']); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Images', 'ai-product-studio'); ?></th>
                <td>
                    <label><?php esc_html_e('Largeur max (px)', 'ai-product-studio'); ?> <input type="number" name="image_max_width" value="<?php echo esc_attr((string) $settings['image_max_width']); ?>" min="256" step="1"></label><br>
                    <label><?php esc_html_e('Qualité (10-100)', 'ai-product-studio'); ?> <input type="number" name="image_quality" value="<?php echo esc_attr((string) $settings['image_quality']); ?>" min="10" max="100" step="1"></label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Requêtes IA', 'ai-product-studio'); ?></th>
                <td>
                    <label><?php esc_html_e('Timeout (s)', 'ai-product-studio'); ?> <input type="number" name="request_timeout" value="<?php echo esc_attr((string) $settings['request_timeout']); ?>" min="10" step="1"></label><br>
                    <label><?php esc_html_e('Erreurs avant désactivation d\'une clé (0 = jamais)', 'ai-product-studio'); ?> <input type="number" name="max_error_before_disable" value="<?php echo esc_attr((string) $settings['max_error_before_disable']); ?>" min="0" step="1"></label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('SEO', 'ai-product-studio'); ?></th>
                <td>
                    <label><input type="checkbox" name="generate_seo" <?php checked((bool) $settings['generate_seo']); ?>> <?php esc_html_e('Générer les métadonnées SEO', 'ai-product-studio'); ?></label><br>
                    <label><?php esc_html_e('Plugin SEO', 'ai-product-studio'); ?>
                        <select name="seo_plugin">
                            <?php foreach (['auto' => 'Auto', 'yoast' => 'Yoast SEO', 'rankmath' => 'Rank Math', 'none' => __('Aucun', 'ai-product-studio')] as $val => $label) : ?>
                                <option value="<?php echo esc_attr($val); ?>" <?php selected($val, $settings['seo_plugin']); ?>><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Logs', 'ai-product-studio'); ?></th>
                <td>
                    <label><?php esc_html_e('Niveau', 'ai-product-studio'); ?>
                        <select name="log_level">
                            <?php foreach (['debug', 'info', 'notice', 'warning', 'error'] as $level) : ?>
                                <option value="<?php echo esc_attr($level); ?>" <?php selected($level, $settings['log_level']); ?>><?php echo esc_html($level); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label><br>
                    <label><?php esc_html_e('Rétention (jours)', 'ai-product-studio'); ?> <input type="number" name="log_retention_days" value="<?php echo esc_attr((string) $settings['log_retention_days']); ?>" min="1" step="1"></label><br>
                    <label><?php esc_html_e('Nombre max de fichiers', 'ai-product-studio'); ?> <input type="number" name="log_max_files" value="<?php echo esc_attr((string) $settings['log_max_files']); ?>" min="1" step="1"></label>
                </td>
            </tr>
        </table>

        <?php submit_button(__('Enregistrer', 'ai-product-studio'), 'primary', 'aips_settings_submit'); ?>
    </form>
</div>
