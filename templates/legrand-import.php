<?php
/**
 * Product generation form.
 * @var string $title
 * @var string $slug
 * @var string $exempleCsv
 */
?>

<div class="wrap aips-agent-page">

    <h1 class="wp-heading-inline">
        <?php echo esc_html($title); ?>
    </h1>

    <hr class="wp-header-end">

    <div class="aips-agent-card">

        <div class="aips-agent-card__header">
            <h2><?php esc_html_e('Import catalogue Legrand', MY_AI_AGENT_DOMAIN); ?></h2>
            <div class="card">

                <h2>Importer un CSV</h2>

                <p>
                    Le fichier doit respecter les colonnes suivantes :
                </p>

                <code>
                    Reference,
                    Libelle produit (fr),
                    Code famille remise,
                    Nom famille remise,
                    Code EAN,
                    Prix promotion,
                    Prix
                </code>
            </div>
        </div>

        <form
                id="my-ai-legrand-form"
                class="aips-agent-form"
                data-agent="legrand"
        >

            <div class="aips-form-grid">
                <input type="hidden" name="agent" value="<?php echo $slug; ?>">

                <!-- CSV -->
                <div class="aips-form-field">
                    <label for="legrand_csv">
                        <strong>Fichier CSV</strong>
                    </label>
                    <input
                            type="file"
                            id="legrand_csv"
                            name="legrand_csv"
                            accept=".csv,text/csv"
                            required
                    >
                </div>
            </div>

            <div class="aips-agent-form__footer">
                <?php submit_button(
                    'Valider et importer'
                ); ?>

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
