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
            <h2><?php esc_html_e('Générer un produit', MY_AI_AGENT_DOMAIN); ?></h2>
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

                <!-- Theme -->
                <div class="aips-form-field aips-form-field--full">
                    <label for="aips-product-theme">
                        <?php esc_html_e('Thème du produit', MY_AI_AGENT_DOMAIN); ?>
                        <span class="aips-required">*</span>
                    </label>

                    <textarea
                        type="text"
                        id="aips-product-theme"
                        rows="6"
                        name="theme"
                        class="aips-input"
                        placeholder="<?php esc_attr_e(
                            '419134 RX3 DISJ 1P B10 6000A BIC. Disjoncteur de la gamme RX3. Référence 419134. EAN 3414970366665. Catégorie niveau 1 : Tableaux & Protection électrique, Catégorie niveau 2 : Disjoncteurs / Appareils de protection, Catégorie niveau 3 :Disjoncteurs modulaires',
                            MY_AI_AGENT_DOMAIN
                        ); ?>"
                        required
                    >
                        <?php esc_attr_e(
                                '419134 RX3 DISJ 1P B10 6000A BIC. Disjoncteur de la gamme RX3. Référence 419134. EAN 3414970366665. Catégorie niveau 1 : Tableaux & Protection électrique, Catégorie niveau 2 : Disjoncteurs / Appareils de protection, Catégorie niveau 3 :Disjoncteurs modulaires',
                                MY_AI_AGENT_DOMAIN
                        ); ?>
                    </textarea>

                    <p class="aips-form-help">
                        <?php esc_html_e(
                            'Décrivez le sujet principal du produit.',
                            MY_AI_AGENT_DOMAIN
                        ); ?>
                    </p>
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
