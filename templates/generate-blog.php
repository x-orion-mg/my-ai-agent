<?php
/**
 * Blog generation form.
 * @var string $title
 * @var string $defaultProvider
 * @var string $slug
 * @var array<int, string> $languages
 * @var array<int, string> $tones
 * @var array<int, string> $providers
 * @var array<int, Prompt> $prompts
 */

use MyAIAgent\Prompt\Prompt;
?>

<div class="wrap aips-agent-page">

    <h1 class="wp-heading-inline">
        <?php echo esc_html($title); ?>
    </h1>

    <hr class="wp-header-end">

    <div class="aips-agent-card">

        <div class="aips-agent-card__header">
            <h2><?php esc_html_e('Générer un article de blog', MY_AI_AGENT_DOMAIN); ?></h2>
            <p>
                <?php esc_html_e(
                    'Configurez les paramètres de génération puis lancez votre agent IA.',
                    MY_AI_AGENT_DOMAIN
                ); ?>
            </p>
        </div>

        <form
            id="my-ai-blog-form"
            class="aips-agent-form"
            data-agent="blog"
        >

            <div class="aips-form-grid">
                <input type="hidden" name="agent" value="<?php echo $slug; ?>">

                <!-- Theme -->
                <div class="aips-form-field aips-form-field--full">
                    <label for="aips-blog-theme">
                        <?php esc_html_e('Thème de l’article', MY_AI_AGENT_DOMAIN); ?>
                        <span class="aips-required">*</span>
                    </label>

                    <input
                        type="text"
                        id="aips-blog-theme"
                        name="theme"
                        class="aips-input"
                        placeholder="<?php esc_attr_e(
                            'Ex. Les avantages de WordPress pour une entreprise',
                            MY_AI_AGENT_DOMAIN
                        ); ?>"
                        required
                    >

                    <p class="aips-form-help">
                        <?php esc_html_e(
                            'Décrivez le sujet principal de l’article.',
                            MY_AI_AGENT_DOMAIN
                        ); ?>
                    </p>
                </div>

                <!-- Language -->
                <div class="aips-form-field">
                    <label for="aips-blog-language">
                        <?php esc_html_e('Langue', MY_AI_AGENT_DOMAIN); ?>
                        <span class="aips-required">*</span>
                    </label>

                    <select
                        id="aips-blog-language"
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
                    <label for="aips-blog-tone">
                        <?php esc_html_e('Ton', MY_AI_AGENT_DOMAIN); ?>
                        <span class="aips-required">*</span>
                    </label>

                    <select
                        id="aips-blog-tone"
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
                    <label for="aips-blog-provider">
                        <?php esc_html_e('Provider IA', MY_AI_AGENT_DOMAIN); ?>
                        <span class="aips-required">*</span>
                    </label>

                    <select
                        id="aips-blog-provider"
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
                    <label for="aips-blog-prompt">
                        <?php esc_html_e('Prompt', MY_AI_AGENT_DOMAIN); ?>
                        <span class="aips-required">*</span>
                    </label>

                    <select
                        id="aips-blog-prompt"
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
                    <?php esc_html_e('Générer l’article', MY_AI_AGENT_DOMAIN); ?>
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
