# Pipeline produit Legrand

Le pipeline commence après `imports` / `import_rows`.

Flux :

`import_rows`
→ `SOURCE`
→ `AI_GENERATION`
→ `VALIDATION`
→ `WOOCOMMERCE`

Le pipeline ne lit pas le CSV. Il utilise les lignes déjà présentes dans
`my_ai_agent_legrand_import_rows`.

Chaque couple `(import_row_id, type)` est idempotent grâce à une contrainte
UNIQUE dans `product_jobs`.

Les jobs sont exécutés de manière asynchrone avec Action Scheduler lorsque
WooCommerce est disponible, sinon avec WP-Cron.

## Intégration

Le `Plugin` doit enregistrer :

- `ProductJobRepository`
- `RetryPolicy`
- `ProductJobScheduler`
- `SourceJobHandler`
- `AiGenerationJobHandler`
- `ValidationJobHandler`
- `WooCommerceJobHandler`
- `ProductJobService`
- `PipelineDispatcher`
- `PipelineRecoveryService`

Le scheduler doit recevoir `register()` pendant le boot.

Une table `my_ai_agent_product_jobs` est nécessaire. Voir la définition
SQL dans la documentation/migration du projet.
