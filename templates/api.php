<?php
/**
 * API keys view (AJAX-driven CRUD).
 *
 * @var array<int, \AIProductStudio\API\ApiKey> $keys
 * @var array<string, string>                   $providers
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap aips-wrap">
    <h1><?php esc_html_e('Clés API', MY_AI_AGENT_DOMAIN); ?></h1>
    <p class="aips-subtitle"><?php esc_html_e('Gérez plusieurs clés par fournisseur. La rotation automatique utilise la priorité (croissante) puis le nombre d\'erreurs.', MY_AI_AGENT_DOMAIN); ?></p>

    <div class="aips-columns">
        <div class="aips-columns__list">
            <table class="widefat striped" id="aips-keys-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Fournisseur', MY_AI_AGENT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Libellé', MY_AI_AGENT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Clé', MY_AI_AGENT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Priorité', MY_AI_AGENT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Erreurs', MY_AI_AGENT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Dernier usage', MY_AI_AGENT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Actif', MY_AI_AGENT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Actions', MY_AI_AGENT_DOMAIN); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($keys === []) : ?>
                    <tr><td colspan="8"><?php esc_html_e('Aucune clé API.', MY_AI_AGENT_DOMAIN); ?></td></tr>
                <?php else : ?>
                    <?php foreach ($keys as $key) : ?>
                        <tr data-id="<?php echo esc_attr((string) $key->id); ?>"
                            data-provider="<?php echo esc_attr($key->provider); ?>"
                            data-label="<?php echo esc_attr($key->label); ?>"
                            data-model="<?php echo esc_attr($key->model); ?>"
                            data-endpoint="<?php echo esc_attr($key->endpoint); ?>"
                            data-priority="<?php echo esc_attr((string) $key->priority); ?>"
                            data-active="<?php echo esc_attr($key->isActive ? '1' : '0'); ?>">
                            <td><?php echo esc_html($providers[$key->provider] ?? $key->provider); ?></td>
                            <td><?php echo esc_html($key->label); ?></td>
                            <td><code><?php echo esc_html($key->maskedKey()); ?></code></td>
                            <td><?php echo esc_html((string) $key->priority); ?></td>
                            <td><?php echo esc_html((string) $key->errorCount); ?></td>
                            <td><?php echo esc_html($key->lastUsedAt ?? '—'); ?></td>
                            <td>
                                <button class="button-link aips-toggle-key" data-id="<?php echo esc_attr((string) $key->id); ?>">
                                    <?php echo $key->isActive ? '✔' : '—'; ?>
                                </button>
                            </td>
                            <td>
                                <button class="button aips-edit-key"><?php esc_html_e('Éditer', MY_AI_AGENT_DOMAIN); ?></button>
                                <button class="button aips-delete-key" data-id="<?php echo esc_attr((string) $key->id); ?>"><?php esc_html_e('Supprimer', MY_AI_AGENT_DOMAIN); ?></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="aips-columns__form">
            <h2 id="aips-key-form-title"><?php esc_html_e('Nouvelle clé', MY_AI_AGENT_DOMAIN); ?></h2>
            <form id="aips-key-form">
                <input type="hidden" name="id" id="aips-key-id" value="0">
                <p><label><?php esc_html_e('Fournisseur', MY_AI_AGENT_DOMAIN); ?><br>
                    <select name="provider" id="aips-key-provider">
                        <?php foreach ($providers as $slug => $label) : ?>
                            <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select></label></p>
                <p><label><?php esc_html_e('Libellé', MY_AI_AGENT_DOMAIN); ?><br><input type="text" name="label" id="aips-key-label" class="regular-text"></label></p>
                <p><label><?php esc_html_e('Clé API', MY_AI_AGENT_DOMAIN); ?><br><input type="password" name="api_key" id="aips-key-value" class="regular-text" autocomplete="new-password"></label>
                    <span class="description"><?php esc_html_e('Laissez vide en édition pour conserver la clé existante.', MY_AI_AGENT_DOMAIN); ?></span></p>
                <p><label><?php esc_html_e('Modèle', MY_AI_AGENT_DOMAIN); ?><br><input type="text" name="model" id="aips-key-model" class="regular-text" placeholder="gpt-4o-mini"></label></p>
                <p><label><?php esc_html_e('Endpoint (facultatif)', MY_AI_AGENT_DOMAIN); ?><br><input type="url" name="endpoint" id="aips-key-endpoint" class="regular-text"></label></p>
                <p><label><?php esc_html_e('Priorité', MY_AI_AGENT_DOMAIN); ?><br><input type="number" name="priority" id="aips-key-priority" value="10" min="1" step="1"></label></p>
                <p><label><input type="checkbox" name="is_active" id="aips-key-active" value="1" checked> <?php esc_html_e('Actif', MY_AI_AGENT_DOMAIN); ?></label></p>
                <p>
                    <button type="submit" class="button button-primary"><?php esc_html_e('Enregistrer', MY_AI_AGENT_DOMAIN); ?></button>
                    <button type="button" class="button" id="aips-key-reset"><?php esc_html_e('Réinitialiser', MY_AI_AGENT_DOMAIN); ?></button>
                </p>
            </form>
        </div>
    </div>
</div>
