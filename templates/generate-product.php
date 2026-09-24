<?php
/**
 * Product generation form.
 * @var string $title
 * @var string $defaultProvider
 * @var string $slug
 * @var array<int, string> $languages
 * @var array<int, string> $tones
 * @var array<int, string> $providers
 * @var array<int, Prompt> $prompts
 * @var bool $wooActive
 */

use MyAIAgent\Prompt\Prompt;
?>

<div class="wrap aips-agent-page">

    <h1 class="wp-heading-inline">
        <?php echo esc_html($title); ?>
    </h1>
    <?php if (! $wooActive) : ?>
        <div class="notice notice-error"><p><?php esc_html_e('WooCommerce est requis pour générer des produits.', 'ai-product-studio'); ?></p></div>
    <?php endif; ?>
    <hr class="wp-header-end">

    <div class="aips-agent-card">

        <div class="aips-agent-card__header">
            <h2><?php esc_html_e('Générer un produit Legrand', MY_AI_AGENT_DOMAIN); ?></h2>
            <p>
                <?php esc_html_e(
                    'Configurez les paramètres de génération puis lancez votre agent IA.',
                    MY_AI_AGENT_DOMAIN
                ); ?>
            </p>
        </div>

        <form
            id="my-ai-product-form"
            class="aips-agent-form"
            data-agent="product"
        >

            <div class="aips-form-grid">
                <input type="hidden" name="agent" value="<?php echo $slug; ?>">

                <!-- Reference -->
                <div class="aips-form-field">
                    <label for="aips-product-reference">
                        <?php esc_html_e('Référence', MY_AI_AGENT_DOMAIN); ?>
                        <span class="aips-required">*</span>
                    </label>

                    <input
                        type="text"
                        id="aips-product-reference"
                        name="reference"
                        class="aips-input"
                        value="419134"
                        placeholder="<?php esc_attr_e('Entrez la référence du produit', MY_AI_AGENT_DOMAIN); ?>"
                        required
                    />
                </div>

                <!-- Nom technique -->
                <div class="aips-form-field">
                    <label for="aips-product-technical-name">
                        <?php esc_html_e('Nom technique', MY_AI_AGENT_DOMAIN); ?>
                        <span class="aips-required">*</span>
                    </label>

                    <input
                        type="text"
                        id="aips-product-technical-name"
                        name="technical_name"
                        class="aips-input"
                        value="RX3 DISJ 1P B10 6000A BIC"
                        placeholder="<?php esc_attr_e('Entrez le nom technique du produit', MY_AI_AGENT_DOMAIN); ?>"
                        required
                    />
                </div>

                <!-- Nom famille -->
                <div class="aips-form-field">
                    <label for="aips-product-family-name">
                        <?php esc_html_e('Nom famille', MY_AI_AGENT_DOMAIN); ?>
                        <span class="aips-required">*</span>
                    </label>

                    <input
                        type="text"
                        id="aips-product-family-name"
                        name="family_name"
                        class="aips-input"
                        value="DISJONCTEURS RX3"
                        placeholder="<?php esc_attr_e('Entrez le nom de la famille du produit', MY_AI_AGENT_DOMAIN); ?>"
                        required
                    />
                </div>

                <!-- EAN - Code bar -->
                <div class="aips-form-field">
                    <label for="aips-product-ean">
                        <?php esc_html_e('Code EAN', MY_AI_AGENT_DOMAIN); ?>
                        <span class="aips-required">*</span>
                    </label>

                    <input
                        type="text"
                        id="aips-product-ean"
                        name="ean"
                        class="aips-input"
                        value="3414970366634"
                        placeholder="<?php esc_attr_e('Entrez l\'EAN du produit', MY_AI_AGENT_DOMAIN); ?>"
                        required
                    />
                </div>

                <!-- Prix normal -->
                <div class="aips-form-field">
                    <label for="aips-product-normal-price">
                        <?php esc_html_e('Prix normal', MY_AI_AGENT_DOMAIN); ?>
                    </label>

                    <input
                        type="number"
                        id="aips-product-normal-price"
                        name="normal_price"
                        class="aips-input"
                        placeholder="<?php esc_attr_e('Entrez le prix normal du produit', MY_AI_AGENT_DOMAIN); ?>"
                    />
                </div>

                <!-- Prix promotionnel -->
                <div class="aips-form-field">
                    <label for="aips-product-promotional-price">
                        <?php esc_html_e('Prix promotionnel', MY_AI_AGENT_DOMAIN); ?>
                    </label>

                    <input
                        type="number"
                        id="aips-product-promotional-price"
                        name="promotional_price"
                        class="aips-input"
                        placeholder="<?php esc_attr_e('Entrez le prix promotionnel du produit', MY_AI_AGENT_DOMAIN); ?>"
                    />
                </div>

                <!-- Language -->
                <div class="aips-form-field">
                    <label for="aips-product-language">
                        <?php esc_html_e('Langue', MY_AI_AGENT_DOMAIN); ?>
                        <span class="aips-required">*</span>
                    </label>

                    <select
                        id="aips-product-language"
                        name="language"
                        class="aips-select"
                        required
                    >
                        <option value="">
                            <?php esc_html_e('Sélectionner une langue', MY_AI_AGENT_DOMAIN); ?>
                        </option>

                        <?php if (!empty($languages)) : ?>
                            <?php foreach ($languages as $value => $label) : ?>
                                <option value="<?php echo esc_attr($value); ?>">
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <option selected value="fr">Français</option>
                            <option value="en">English</option>
                            <option value="es">Español</option>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- Tone -->
                <div class="aips-form-field">
                    <label for="aips-product-tone">
                        <?php esc_html_e('Ton', MY_AI_AGENT_DOMAIN); ?>
                        <span class="aips-required">*</span>
                    </label>

                    <select
                        id="aips-product-tone"
                        name="tone"
                        class="aips-select"
                        required
                    >
                        <option value="">
                            <?php esc_html_e('Sélectionner un ton', MY_AI_AGENT_DOMAIN); ?>
                        </option>

                        <?php if (!empty($tones)) : ?>
                            <?php foreach ($tones as $value => $label) : ?>
                                <option value="<?php echo esc_attr($value); ?>">
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <option selected value="professional">Professionnel</option>
                            <option value="friendly">Amical</option>
                            <option value="expert">Expert</option>
                            <option value="informative">Informatif</option>
                            <option value="humorous">Humoristique</option>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- Provider -->
                <div class="aips-form-field">
                    <label for="aips-product-provider">
                        <?php esc_html_e('Provider IA', MY_AI_AGENT_DOMAIN); ?>
                        <span class="aips-required">*</span>
                    </label>

                    <select
                        id="aips-product-provider"
                        name="provider"
                        class="aips-select"
                        required
                    >
                        <option value="">
                            <?php esc_html_e('Sélectionner un provider', MY_AI_AGENT_DOMAIN); ?>
                        </option>

                        <?php if (!empty($providers)) : ?>
                            <?php foreach ($providers as $slug => $label) : ?>
                                <option value="<?php echo esc_attr($slug); ?>" <?php selected($slug, $defaultProvider); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>

                    <p class="aips-form-help">
                        <?php esc_html_e(
                            'Provider utilisé pour cette génération.',
                            MY_AI_AGENT_DOMAIN
                        ); ?>
                    </p>
                </div>

                <!-- Prompt -->
                <div class="aips-form-field">
                    <label for="aips-product-prompt">
                        <?php esc_html_e('Prompt', MY_AI_AGENT_DOMAIN); ?>
                        <span class="aips-required">*</span>
                    </label>

                    <select
                        id="aips-product-prompt"
                        name="prompt"
                        class="aips-select"
                        required
                    >
                        <?php if (!empty($prompts)) : ?>
                            <?php foreach ($prompts as $prompt) : ?>
                                <option value="<?php echo esc_attr((string) $prompt->id); ?>">
                                    <?php echo esc_html($prompt->name); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>

                    <p class="aips-form-help">
                        <?php esc_html_e(
                            'Prompt utilisé pour guider la génération.',
                            MY_AI_AGENT_DOMAIN
                        ); ?>
                    </p>
                </div>

            </div>

            <div class="aips-agent-form__footer">

                <button
                    type="submit"
                    class="button button-primary button-large"
                >
                    <?php esc_html_e('Générer le produit', MY_AI_AGENT_DOMAIN); ?>
                </button>

                <span
                    class="aips-agent-form__spinner"
                    aria-hidden="true"
                ></span>

            </div>

        </form>
        <div class="aips-agent-result" aria-live="polite">
            <!--resultat-->
        </div>


    </div>

</div>
