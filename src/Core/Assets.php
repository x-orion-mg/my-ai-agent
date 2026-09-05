<?php

declare(strict_types=1);

namespace MyAIAgent\Core;



/**
 * Registers and enqueues admin CSS/JS, and exposes runtime data to the browser
 * (AJAX URL, nonce, pipeline steps).
 */
final class Assets
{
    

    public function __construct()
    {
        
    }

    public function register(): void
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue']);
    }

    public function enqueue(string $hook): void
    {
        // Only load on our own admin pages.
        if (! str_contains($hook, MY_AI_AGENT_DOMAIN)) {
            return;
        }

        wp_enqueue_media();

        wp_enqueue_style(
            'my-ai-agent-admin',
            MY_AI_AGENT_URL . 'assets/css/admin.css',
            [],
            MY_AI_AGENT_VERSION
        );

        wp_enqueue_script(
            'my_ai_agent-admin',
            MY_AI_AGENT_URL . 'assets/js/admin.js',
            ['jquery', 'wp-i18n'],
            MY_AI_AGENT_VERSION,
            true
        );

        wp_localize_script('my_ai_agent-admin', 'MY_AI_AGENT', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('my_ai_agent_nonce'),
            'i18n'    => [
                'generating'  => __('Génération en cours…', MY_AI_AGENT_DOMAIN),
                'cancelled'   => __('Génération annulée.', MY_AI_AGENT_DOMAIN),
                'done'        => __('Terminé', MY_AI_AGENT_DOMAIN),
                'error'       => __('Erreur', MY_AI_AGENT_DOMAIN),
                'confirmDelete' => __('Confirmer la suppression ?', MY_AI_AGENT_DOMAIN),
                'selectMain'  => __('Choisir l\'image principale', MY_AI_AGENT_DOMAIN),
                'selectGallery' => __('Ajouter à la galerie', MY_AI_AGENT_DOMAIN),
                'noImage'     => __('Veuillez ajouter une image principale.', MY_AI_AGENT_DOMAIN),
                'noDescription' => __('Veuillez saisir une description produit.', MY_AI_AGENT_DOMAIN),
                'noFile'      => __('Veuillez choisir un fichier CSV ou Excel.', MY_AI_AGENT_DOMAIN),
                'parsing'     => __('Analyse du fichier…', MY_AI_AGENT_DOMAIN),
                'rowsFound'   => __('Lignes détectées', MY_AI_AGENT_DOMAIN),
                'importDone'  => __('produits créés.', MY_AI_AGENT_DOMAIN),
            ],
        ]);
    }
}
