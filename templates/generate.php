<?php
/**
 * Generate a product view.
 *
 * @var array<int, \AIProductStudio\Prompt\Prompt> $prompts
 * @var array<string, string>                      $providers
 * @var array<int, array{key: string, label: string}> $steps
 * @var string                                     $defaultProvider
 * @var bool                                       $wooActive
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap aips-wrap">
    <h1><?php esc_html_e('Générer un produit', 'ai-product-studio'); ?></h1>

    <?php if (! $wooActive) : ?>
        <div class="notice notice-error"><p><?php esc_html_e('WooCommerce est requis pour générer des produits.', 'ai-product-studio'); ?></p></div>
    <?php endif; ?>

    <nav class="aips-tabs" role="tablist">
        <button type="button" class="aips-tab is-active" data-tab="single"><?php esc_html_e('Produit unique', 'ai-product-studio'); ?></button>
        <button type="button" class="aips-tab" data-tab="import"><?php esc_html_e('Import CSV / Excel', 'ai-product-studio'); ?></button>
    </nav>

    <div class="aips-generate">
        <div class="aips-generate__forms">
            <form id="aips-generate-form" class="aips-generate__form aips-tab-panel is-active" data-panel="single">
                <fieldset class="aips-source">
                    <legend><?php esc_html_e('Type de saisie', 'ai-product-studio'); ?></legend>
                    <label>
                        <input type="radio" name="source" value="image" checked>
                        <?php esc_html_e('Image', 'ai-product-studio'); ?>
                    </label>
                    <label>
                        <input type="radio" name="source" value="description">
                        <?php esc_html_e('Description', 'ai-product-studio'); ?>
                    </label>
                    <p class="description">
                        <?php esc_html_e('Choisissez une source : l\'image est obligatoire en mode image, la description est obligatoire en mode description.', 'ai-product-studio'); ?>
                    </p>
                </fieldset>

                <div class="aips-field aips-mode aips-mode--image">
                    <label><?php esc_html_e('Image principale', 'ai-product-studio'); ?> <span class="aips-required">*</span></label>
                    <div id="aips-main-image" class="aips-image-picker"></div>
                    <input type="hidden" name="main_image_id" id="aips-main-image-id" value="">
                    <button type="button" class="button aips-pick-main"><?php esc_html_e('Choisir l\'image', 'ai-product-studio'); ?></button>
                </div>

                <div class="aips-field aips-mode aips-mode--image">
                    <label><?php esc_html_e('Galerie', 'ai-product-studio'); ?></label>
                    <div id="aips-gallery" class="aips-image-picker aips-image-picker--multi"></div>
                    <input type="hidden" name="gallery_image_ids" id="aips-gallery-ids" value="">
                    <button type="button" class="button aips-pick-gallery"><?php esc_html_e('Ajouter à la galerie', 'ai-product-studio'); ?></button>
                </div>

                <div class="aips-field aips-mode aips-mode--description" style="display:none;">
                    <label for="aips-user-description"><?php esc_html_e('Description du produit', 'ai-product-studio'); ?> <span class="aips-required">*</span></label>
                    <textarea id="aips-user-description" name="user_description" rows="6" placeholder="<?php esc_attr_e('Décrivez le produit : nom, matériaux, usage, public visé…', 'ai-product-studio'); ?>"></textarea>
                </div>

                <div class="aips-field aips-field--inline">
                    <div>
                        <label for="aips-price"><?php esc_html_e('Prix', 'ai-product-studio'); ?></label>
                        <input type="number" step="0.01" min="0" id="aips-price" name="price" value="">
                    </div>
                    <div>
                        <label for="aips-sale-price"><?php esc_html_e('Prix promotionnel', 'ai-product-studio'); ?></label>
                        <input type="number" step="0.01" min="0" id="aips-sale-price" name="sale_price" value="">
                    </div>
                </div>

                <div class="aips-field aips-mode aips-mode--image">
                    <label for="aips-user-description-optional"><?php esc_html_e('Précisions (facultatif)', 'ai-product-studio'); ?></label>
                    <textarea id="aips-user-description-optional" rows="3" placeholder="<?php esc_attr_e('Contexte supplémentaire pour l\'agent.', 'ai-product-studio'); ?>"></textarea>
                </div>

                <div class="aips-field">
                    <label for="aips-related"><?php esc_html_e('Produits associés (IDs séparés par des virgules, facultatif)', 'ai-product-studio'); ?></label>
                    <input type="text" id="aips-related" name="related_product_ids" value="" placeholder="e.g. 12, 34">
                </div>

                <div class="aips-field aips-field--inline">
                    <div>
                        <label for="aips-provider"><?php esc_html_e('Fournisseur IA', 'ai-product-studio'); ?></label>
                        <select id="aips-provider" name="provider">
                            <?php foreach ($providers as $slug => $label) : ?>
                                <option value="<?php echo esc_attr($slug); ?>" <?php selected($slug, $defaultProvider); ?>><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="aips-prompt"><?php esc_html_e('Prompt', 'ai-product-studio'); ?></label>
                        <select id="aips-prompt" name="prompt_id">
                            <option value="0"><?php esc_html_e('— Prompt actif par défaut —', 'ai-product-studio'); ?></option>
                            <?php foreach ($prompts as $prompt) : ?>
                                <option value="<?php echo esc_attr((string) $prompt->id); ?>"><?php echo esc_html($prompt->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="aips-actions">
                    <button type="submit" class="button button-primary button-hero" id="aips-generate-btn" <?php disabled(! $wooActive); ?>>
                        <?php esc_html_e('Générer', 'ai-product-studio'); ?>
                    </button>
                    <button type="button" class="button" id="aips-cancel-btn" style="display:none;">
                        <?php esc_html_e('Annuler', 'ai-product-studio'); ?>
                    </button>
                </div>
            </form>

            <form id="aips-import-form" class="aips-generate__form aips-tab-panel" data-panel="import" style="display:none;">
                <p><?php esc_html_e('Importez un CSV ou un Excel (.xlsx). Chaque ligne devient un produit via l\'agent IA. Colonnes reconnues : title / titre, description, price / prix, sale_price / promo, related_ids.', 'ai-product-studio'); ?></p>
                <p>
                    <a href="<?php echo esc_url('data:text/csv;charset=utf-8,' . rawurlencode("title,description,price,sale_price,related_ids\nBracelet argent,Bracelet en argent 925 pour femme,29.90,24.90,\n")); ?>">
                        <?php esc_html_e('Télécharger un modèle CSV', 'ai-product-studio'); ?>
                    </a>
                </p>

                <div class="aips-field">
                    <label for="aips-import-file"><?php esc_html_e('Fichier CSV ou Excel', 'ai-product-studio'); ?> <span class="aips-required">*</span></label>
                    <input type="file" id="aips-import-file" name="import_file" accept=".csv,.txt,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet">
                </div>

                <div class="aips-field aips-field--inline">
                    <div>
                        <label for="aips-import-provider"><?php esc_html_e('Fournisseur IA', 'ai-product-studio'); ?></label>
                        <select id="aips-import-provider" name="provider">
                            <?php foreach ($providers as $slug => $label) : ?>
                                <option value="<?php echo esc_attr($slug); ?>" <?php selected($slug, $defaultProvider); ?>><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="aips-import-prompt"><?php esc_html_e('Prompt', 'ai-product-studio'); ?></label>
                        <select id="aips-import-prompt" name="prompt_id">
                            <option value="0"><?php esc_html_e('— Prompt actif par défaut —', 'ai-product-studio'); ?></option>
                            <?php foreach ($prompts as $prompt) : ?>
                                <option value="<?php echo esc_attr((string) $prompt->id); ?>"><?php echo esc_html($prompt->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="aips-actions">
                    <button type="submit" class="button" id="aips-parse-import-btn" <?php disabled(! $wooActive); ?>>
                        <?php esc_html_e('Analyser le fichier', 'ai-product-studio'); ?>
                    </button>
                    <button type="button" class="button button-primary" id="aips-run-import-btn" style="display:none;" <?php disabled(! $wooActive); ?>>
                        <?php esc_html_e('Importer les produits', 'ai-product-studio'); ?>
                    </button>
                </div>

                <div id="aips-import-preview" class="aips-import-preview"></div>
                <div id="aips-import-results" class="aips-import-results"></div>
            </form>
        </div>

        <aside class="aips-progress" id="aips-progress" style="display:none;">
            <h2><?php esc_html_e('Progression', 'ai-product-studio'); ?></h2>
            <ul class="aips-steps">
                <?php foreach ($steps as $step) : ?>
                    <li class="aips-step" data-step="<?php echo esc_attr($step['key']); ?>">
                        <span class="aips-step__icon">○</span>
                        <span class="aips-step__label"><?php echo esc_html($step['label']); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="aips-progress__bar"><span id="aips-progress-fill"></span></div>
            <div id="aips-result" class="aips-result"></div>
        </aside>
    </div>
</div>
