/* global MY_AI_AGENT, jQuery, wp */
(function ($) {
    'use strict';

    var i18n = (MY_AI_AGENT && MY_AI_AGENT.i18n) || {};

    function post(action, data) {
        return $.post(MY_AI_AGENT.ajaxUrl, $.extend({ action: action, nonce: MY_AI_AGENT.nonce }, data));
    }

    /* ---------------------------------------------------------------------
     * Media picker (main image + gallery)
     * ------------------------------------------------------------------- */
    function initMediaPickers() {
        var $mainInput = $('#aips-main-image-id');
        var $mainWrap = $('#aips-main-image');
        var $galleryInput = $('#aips-gallery-ids');
        var $galleryWrap = $('#aips-gallery');

        function renderThumb($wrap, id, url, onRemove) {
            var $thumb = $('<div class="aips-thumb"></div>');
            $('<img>').attr('src', url).appendTo($thumb);
            $('<span class="aips-thumb__remove">×</span>').on('click', function () {
                onRemove(id);
                $thumb.remove();
            }).appendTo($thumb);
            $wrap.append($thumb);
        }

        $('.aips-pick-main').on('click', function (e) {
            e.preventDefault();
            var frame = wp.media({ title: i18n.selectMain, multiple: false, library: { type: 'image' } });
            frame.on('select', function () {
                var att = frame.state().get('selection').first().toJSON();
                $mainInput.val(att.id);
                $mainWrap.empty();
                renderThumb($mainWrap, att.id, att.sizes && att.sizes.thumbnail ? att.sizes.thumbnail.url : att.url, function () {
                    $mainInput.val('');
                });
            });
            frame.open();
        });

        $('.aips-pick-gallery').on('click', function (e) {
            e.preventDefault();
            var frame = wp.media({ title: i18n.selectGallery, multiple: true, library: { type: 'image' } });
            frame.on('select', function () {
                var ids = ($galleryInput.val() ? $galleryInput.val().split(',') : []);
                frame.state().get('selection').map(function (item) {
                    var att = item.toJSON();
                    if (ids.indexOf(String(att.id)) === -1) {
                        ids.push(String(att.id));
                        renderThumb($galleryWrap, att.id, att.sizes && att.sizes.thumbnail ? att.sizes.thumbnail.url : att.url, function (rid) {
                            var cur = $galleryInput.val().split(',').filter(function (v) { return v !== String(rid); });
                            $galleryInput.val(cur.join(','));
                        });
                    }
                });
                $galleryInput.val(ids.join(','));
            });
            frame.open();
        });
    }

    /* ---------------------------------------------------------------------
     * Tabs + source (image vs description)
     * ------------------------------------------------------------------- */
    function initTabsAndSource() {
        $('.aips-tab').on('click', function () {
            var tab = $(this).data('tab');
            $('.aips-tab').removeClass('is-active');
            $(this).addClass('is-active');
            $('.aips-tab-panel').hide().removeClass('is-active');
            $('.aips-tab-panel[data-panel="' + tab + '"]').show().addClass('is-active');
        });

        function applySource() {
            var source = $('#aips-generate-form input[name="source"]:checked').val() || 'image';
            $('.aips-mode--image').toggle(source === 'image');
            $('.aips-mode--description').toggle(source === 'description');
        }

        $('#aips-generate-form').on('change', 'input[name="source"]', applySource);
        applySource();
    }

    /* ---------------------------------------------------------------------
     * Product generation with live progress
     * ------------------------------------------------------------------- */
    function initGenerate() {
        var $form = $('#aips-generate-form');
        if (!$form.length) { return; }

        var $panel = $('#aips-progress');
        var $fill = $('#aips-progress-fill');
        var $result = $('#aips-result');
        var $genBtn = $('#aips-generate-btn');
        var $cancelBtn = $('#aips-cancel-btn');
        var pollTimer = null;
        var jobId = null;

        function uuid() {
            return 'xxxxxxxxxxxx4xxxyxxxxxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
                var r = (Math.random() * 16) | 0;
                var v = c === 'x' ? r : (r & 0x3) | 0x8;
                return v.toString(16);
            });
        }

        function resetSteps() {
            $('.aips-step').removeClass('is-running is-done is-error')
                .find('.aips-step__icon').text('○');
            $fill.css('width', '0%');
            $result.empty();
        }

        function applyProgress(progress) {
            if (!progress || !progress.steps) { return; }
            var total = $('.aips-step').length;
            var done = 0;
            $('.aips-step').each(function () {
                var key = $(this).data('step');
                var step = progress.steps[key];
                var $icon = $(this).find('.aips-step__icon');
                $(this).removeClass('is-running is-done is-error');
                if (step && step.state === 'done') {
                    $(this).addClass('is-done');
                    $icon.text('✔');
                    done++;
                } else if (step && step.state === 'running') {
                    $(this).addClass('is-running');
                    $icon.text('•');
                } else {
                    $icon.text('○');
                }
            });
            $fill.css('width', total ? Math.round((done / total) * 100) + '%' : '0%');
        }

        function poll() {
            post('aips_generation_progress', { job_id: jobId }).done(function (res) {
                if (res && res.success) {
                    applyProgress(res.data.progress);
                }
            });
        }

        function stopPolling() {
            if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
        }

        function finish(success, message, links) {
            stopPolling();
            $genBtn.prop('disabled', false).text('Générer');
            $cancelBtn.hide();
            var cls = success ? 'notice-success' : 'notice-error';
            var html = '<div class="notice ' + cls + '"><p>' + message + '</p>';
            if (links && links.edit) {
                html += '<p><a class="button button-primary" href="' + links.edit + '">' + (i18n.done || 'Voir le produit') + '</a></p>';
            }
            html += '</div>';
            $result.html(html);
            if (success) { $fill.css('width', '100%'); }
        }

        $form.on('submit', function (e) {
            e.preventDefault();

            var source = $form.find('input[name="source"]:checked').val() || 'image';
            var description = source === 'image'
                ? $('#aips-user-description-optional').val()
                : $('#aips-user-description').val();

            if (source === 'image' && !$('#aips-main-image-id').val()) {
                window.alert(i18n.noImage || 'Image principale requise.');
                return;
            }

            if (source === 'description' && !$.trim(description || '')) {
                window.alert(i18n.noDescription || 'Description requise.');
                return;
            }

            jobId = uuid();
            resetSteps();
            $panel.show();
            $genBtn.prop('disabled', true).text(i18n.generating || 'Génération…');
            $cancelBtn.show();

            pollTimer = setInterval(poll, 1200);

            var payload = {
                job_id: jobId,
                source: source,
                main_image_id: source === 'image' ? $('#aips-main-image-id').val() : '',
                gallery_image_ids: source === 'image' ? $('#aips-gallery-ids').val() : '',
                price: $('#aips-price').val(),
                sale_price: $('#aips-sale-price').val(),
                user_description: description,
                related_product_ids: $('#aips-related').val(),
                provider: $('#aips-provider').val(),
                prompt_id: $('#aips-prompt').val()
            };

            post('aips_generate_product', payload).done(function (res) {
                if (res && res.success) {
                    poll();
                    finish(true, 'Produit « ' + (res.data.title || '') + ' » créé en ' + res.data.duration + 's.', { edit: res.data.edit_link });
                } else {
                    var msg = (res && res.data && res.data.message) ? res.data.message : (i18n.error || 'Erreur');
                    if (res && res.data && res.data.errors && res.data.errors.length) {
                        msg += ' — ' + res.data.errors.join(', ');
                    }
                    finish(false, msg);
                }
            }).fail(function () {
                finish(false, i18n.error || 'Erreur réseau.');
            });
        });

        $cancelBtn.on('click', function () {
            if (jobId) { post('aips_cancel_generation', { job_id: jobId }); }
            finish(false, i18n.cancelled || 'Annulé.');
        });
    }

    /* ---------------------------------------------------------------------
     * CSV / Excel import
     * ------------------------------------------------------------------- */
    function initImport() {
        var $form = $('#aips-import-form');
        if (!$form.length) { return; }

        var rows = [];
        var $preview = $('#aips-import-preview');
        var $results = $('#aips-import-results');
        var $runBtn = $('#aips-run-import-btn');
        var $parseBtn = $('#aips-parse-import-btn');

        $form.on('submit', function (e) {
            e.preventDefault();
            var fileInput = document.getElementById('aips-import-file');
            if (!fileInput || !fileInput.files || !fileInput.files[0]) {
                window.alert(i18n.noFile || 'Fichier requis.');
                return;
            }

            var fd = new FormData();
            fd.append('action', 'aips_parse_import');
            fd.append('nonce', AIPS.nonce);
            fd.append('import_file', fileInput.files[0]);

            $parseBtn.prop('disabled', true);
            $preview.html('<p>' + (i18n.parsing || 'Analyse…') + '</p>');
            $runBtn.hide();
            $results.empty();

            $.ajax({
                url: AIPS.ajaxUrl,
                method: 'POST',
                data: fd,
                processData: false,
                contentType: false
            }).done(function (res) {
                $parseBtn.prop('disabled', false);
                if (!res || !res.success) {
                    var msg = (res && res.data && res.data.message) ? res.data.message : (i18n.error || 'Erreur');
                    $preview.html('<div class="notice notice-error"><p>' + msg + '</p></div>');
                    return;
                }
                rows = res.data.rows || [];
                var html = '<p>' + (i18n.rowsFound || 'Lignes') + ' : ' + rows.length + '</p>';
                html += '<table class="widefat striped"><thead><tr><th>#</th><th>Title</th><th>Description</th><th>Prix</th></tr></thead><tbody>';
                rows.forEach(function (row, index) {
                    html += '<tr><td>' + (index + 1) + '</td><td>' + $('<div>').text(row.title || '').html() +
                        '</td><td>' + $('<div>').text((row.description || '').slice(0, 140)).html() +
                        '</td><td>' + $('<div>').text(row.price || '').html() + '</td></tr>';
                });
                html += '</tbody></table>';
                $preview.html(html);
                $runBtn.toggle(rows.length > 0);
            }).fail(function () {
                $parseBtn.prop('disabled', false);
                $preview.html('<div class="notice notice-error"><p>' + (i18n.error || 'Erreur réseau.') + '</p></div>');
            });
        });

        $runBtn.on('click', function () {
            if (!rows.length) { return; }

            $('#aips-progress').show();
            $runBtn.prop('disabled', true);
            $results.empty();

            var index = 0;
            var created = 0;

            function next() {
                if (index >= rows.length) {
                    $runBtn.prop('disabled', false);
                    $results.prepend('<div class="notice notice-success"><p>' + created + ' / ' + rows.length + ' ' + (i18n.importDone || 'produits créés.') + '</p></div>');
                    return;
                }

                var row = rows[index];
                var n = index + 1;
                index += 1;

                var payload = {
                    job_id: 'import-' + Date.now() + '-' + n,
                    source: 'import',
                    user_description: row.description || row.title || '',
                    price: row.price || '',
                    sale_price: row.sale_price || '',
                    related_product_ids: row.related_ids || '',
                    provider: $('#aips-import-provider').val(),
                    prompt_id: $('#aips-import-prompt').val()
                };

                post('aips_generate_product', payload).done(function (res) {
                    if (res && res.success) {
                        created += 1;
                        var title = res.data.title || '';
                        var link = res.data.edit_link ? '<a href="' + res.data.edit_link + '">' + title + '</a>' : title;
                        $results.append('<p class="aips-import-ok">✔ ' + n + ' — ' + link + '</p>');
                    } else {
                        var msg = (res && res.data && res.data.message) ? res.data.message : (i18n.error || 'Erreur');
                        $results.append('<p class="aips-import-ko">✖ ' + n + ' — ' + msg + '</p>');
                    }
                    next();
                }).fail(function () {
                    $results.append('<p class="aips-import-ko">✖ ' + n + ' — ' + (i18n.error || 'Erreur réseau.') + '</p>');
                    next();
                });
            }

            next();
        });
    }

    /* ---------------------------------------------------------------------
     * Prompts CRUD
     * ------------------------------------------------------------------- */
    function initPrompts() {
        var $form = $('#aips-prompt-form');
        if (!$form.length) { return; }

        function reset() {
            $('#aips-prompt-id').val('0');
            $('#aips-prompt-name-input').val('');
            $('#aips-prompt-description').val('');
            $('#aips-prompt-content-input').val('');
            $('#aips-prompt-active').prop('checked', true);
            $('#aips-prompt-form-title').text('Nouveau prompt');
        }

        $('#aips-prompt-reset').on('click', reset);

        $(document).on('click', '.aips-edit-prompt', function () {
            var $row = $(this).closest('tr');
            $('#aips-prompt-id').val($row.data('id'));
            $('#aips-prompt-name-input').val($row.data('name'));
            $('#aips-prompt-description').val($row.data('description'));
            $('#aips-prompt-content-input').val($row.find('.aips-prompt-content').val());
            $('#aips-prompt-active').prop('checked', String($row.data('active')) === '1');
            $('#aips-prompt-form-title').text('Éditer le prompt');
            $('html, body').animate({ scrollTop: $form.offset().top - 60 }, 300);
        });

        $(document).on('click', '.aips-delete-prompt', function (e) {
            e.preventDefault();

            if (!window.confirm(i18n.confirmDelete)) {
                return;
            }

            var $button = $(this);
            var $row = $button.closest('tr');
            // Empêche les clics multiples
            if ($button.prop('disabled')) {
                return;
            }
            $row.addClass('is-loading');
            $button.prop('disabled', true);

            const id = $button.data('id');

            post('my_ai_agent_delete_prompt', { id: id })
                .done(function () {
                    // Supprime la ligne du tableau
                    $row.remove();
                })
                .fail(function () {
                    $row.removeClass('is-loading');
                    // Réactive le bouton uniquement si la requête échoue
                    $button.prop('disabled', false);
                    window.alert('Une erreur est survenue.');
                });
        });

        $(document).on('click', '.aips-toggle-prompt', function () {
            var id = $(this).data('id');
            post('my_ai_agent_toggle_prompt', { id: id }).done(function () { window.location.reload(); });
        });

        $form.on('submit', function (e) {
            e.preventDefault();

            const $submitButton = $form.find('[type="submit"]');

            if ($submitButton.prop('disabled')) {
                return;
            }

            $form.addClass('is-loading');
            $submitButton.prop('disabled', true);

            var data = {
                id: $('#aips-prompt-id').val(),
                name: $('#aips-prompt-name-input').val(),
                description: $('#aips-prompt-description').val(),
                content: $('#aips-prompt-content-input').val(),
                is_active: $('#aips-prompt-active').is(':checked') ? 1 : 0
            };

            post('my_ai_agent_save_prompt', data)
                .done(function (res) {
                    if (res && res.success) {
                        window.location.reload();
                    } else {
                        $form.removeClass('is-loading');
                        window.alert(res?.data?.message || 'Erreur');
                        $submitButton.prop('disabled', false);
                    }
                })
                .fail(function () {
                    $form.removeClass('is-loading');
                    $submitButton.prop('disabled', false);
                    window.alert('Une erreur est survenue.');
                });
        });
    }

    /* ---------------------------------------------------------------------
     * API keys CRUD
     * ------------------------------------------------------------------- */
    function initKeys() {
        var $form = $('#aips-key-form');
        if (!$form.length) { return; }

        function reset() {
            $('#aips-key-id').val('0');
            $('#aips-key-label').val('');
            $('#aips-key-value').val('');
            $('#aips-key-model').val('');
            $('#aips-key-endpoint').val('');
            $('#aips-key-priority').val('10');
            $('#aips-key-active').prop('checked', true);
            $('#aips-key-form-title').text('Nouvelle clé');
        }

        $('#aips-key-reset').on('click', reset);

        $(document).on('click', '.aips-edit-key', function () {
            var $row = $(this).closest('tr');
            $('#aips-key-id').val($row.data('id'));
            $('#aips-key-provider').val($row.data('provider'));
            $('#aips-key-label').val($row.data('label'));
            $('#aips-key-model').val($row.data('model'));
            $('#aips-key-endpoint').val($row.data('endpoint'));
            $('#aips-key-priority').val($row.data('priority'));
            $('#aips-key-active').prop('checked', String($row.data('active')) === '1');
            $('#aips-key-value').val('');
            $('#aips-key-form-title').text('Éditer la clé');
            $('html, body').animate({ scrollTop: $form.offset().top - 60 }, 300);
        });

        $(document).on('click', '.aips-delete-key', function (e) {
            e.preventDefault();

            if (!window.confirm(i18n.confirmDelete)) {
                return;
            }
            const $button = $(this);
            const $row = $button.closest('tr');
            // Empêche les clics multiples
            if ($button.prop('disabled')) {
                return;
            }
            $row.addClass('is-loading');
            $button.prop('disabled', true);

            post('my_ai_agent_delete_api_key', { id: $(this).data('id') })
                .done(function () {
                    $row.remove();
                })
                .fail(function () {
                    $row.removeClass('is-loading');
                    // Réactive le bouton uniquement si la requête échoue
                    $button.prop('disabled', false);
                    window.alert('Une erreur est survenue.');
                });
        });

        $(document).on('click', '.aips-toggle-key', function () {
            post('aips_toggle_api_key', { id: $(this).data('id') }).done(function () { window.location.reload(); });
        });

        $form.on('submit', function (e) {
            e.preventDefault();

            const $submitButton = $form.find('[type="submit"]');

            if ($submitButton.prop('disabled')) {
                return;
            }

            $form.addClass('is-loading');
            $submitButton.prop('disabled', true);

            var data = {
                id: $('#aips-key-id').val(),
                provider: $('#aips-key-provider').val(),
                label: $('#aips-key-label').val(),
                api_key: $('#aips-key-value').val(),
                model: $('#aips-key-model').val(),
                priority: $('#aips-key-priority').val(),
                is_active: $('#aips-key-active').is(':checked') ? 1 : 0
            };
            post('my_ai_agent_save_api_key', data)
                .done(function (res) {
                    if (res && res.success) {
                        window.location.reload();
                    }
                    else {
                        window.alert(res.data.message || 'Erreur');
                    }
                })
                .fail(function () {
                    $form.removeClass('is-loading');
                    $submitButton.prop('disabled', false);
                    window.alert('Une erreur est survenue.');
                });
        });
    }

    /* ---------------------------------------------------------------------
 * AI Agents
 * ------------------------------------------------------------------- */



        function initAgents() {

            var $form = $('.aips-agent-form');

            if (!$form.length) {
                return;
            }


            var $result = $('.aips-agent-result');

            if (!$result.length) {
                return;
            }


            /*
             * =====================================================
             * STATE
             * =====================================================
             *
             * Toute l'information concernant l'exécution est
             * centralisée ici.
             */

            let state = {

                executionId: null,

                steps: [],

                /*
                 * Index du step actuellement exécuté.
                 *
                 * Exemple :
                 *
                 * 0 = step 1
                 * 1 = step 2
                 * 2 = step 3
                 */

                currentStepIndex: null,

                completedSteps: 0,

                running: false,

                waitingForHuman: false,

                status: 'idle'

            };


            /*
             * =====================================================
             * HELPERS
             * =====================================================
             */

            function escapeHtml(value) {

                return $('<div>')
                    .text(
                        value == null
                            ? ''
                            : String(value)
                    )
                    .html();

            }


            function getStepStatusClass(status) {

                switch (status) {

                    case 'completed':
                        return 'is-completed';

                    case 'running':
                        return 'is-running';

                    case 'waiting':
                    case 'waiting_human':
                        return 'is-waiting';

                    case 'failed':
                        return 'is-failed';

                    case 'pending':
                    default:
                        return 'is-pending';
                }

            }


            function getStepIcon(status, index) {

                switch (status) {

                    case 'completed':
                        return '✓';

                    case 'running':
                        return String(index + 1);

                    case 'waiting':
                    case 'waiting_human':
                        return '!';

                    case 'failed':
                        return '×';

                    case 'pending':
                    default:
                        return String(index + 1);
                }

            }


            function getStepStatusLabel(status) {

                switch (status) {

                    case 'completed':
                        return 'Terminé';

                    case 'running':
                        return 'En cours...';

                    case 'waiting':
                    case 'waiting_human':
                        return 'Votre validation est requise';

                    case 'failed':
                        return 'Erreur';

                    case 'pending':
                    default:
                        return 'En attente';
                }

            }


            function getDefaultStepMessage(status) {

                switch (status) {

                    case 'completed':
                        return 'Step exécuté.';

                    case 'running':
                        return 'Exécution du step en cours...';

                    case 'waiting':
                    case 'waiting_human':
                        return 'Une validation est requise pour continuer.';

                    case 'failed':
                        return 'Une erreur est survenue pendant l’exécution.';

                    case 'pending':
                    default:
                        return '';
                }

            }


            function getErrorMessage(response, fallback) {

                if (
                    response &&
                    response.data &&
                    response.data.message
                ) {
                    return response.data.message;
                }


                if (
                    response &&
                    response.message
                ) {
                    return response.message;
                }


                return fallback;

            }


            /*
             * =====================================================
             * API
             * =====================================================
             *
             * Aucun rendu DOM ici.
             * Aucun changement de state ici.
             * Cette partie s'occupe uniquement des AJAX.
             */

            var api = {

                createExecution: function (data) {

                    return post(
                        'my_ai_agent_create_execution',
                        data
                    );

                },


                runExecution: function (executionId) {

                    return post(
                        'my_ai_agent_run_execution',
                        {
                            execution_id: executionId
                        }
                    );

                },


                resumeExecution: function (
                    executionId,
                    approved
                ) {

                    return post(
                        'my_ai_agent_resume_execution',
                        {
                            execution_id: executionId,
                            approved: approved ? 1 : 0
                        }
                    );

                }

            };


            /*
             * =====================================================
             * RENDERER
             * =====================================================
             *
             * Cette partie est responsable uniquement de l'affichage.
             */

            var renderer = {


                /*
                 * -------------------------------------------------
                 * FORM LOADING
                 * -------------------------------------------------
                 */

                setFormLoading: function (loading) {

                    $form.toggleClass(
                        'is-loading',
                        loading
                    );


                    $form
                        .find('[type="submit"]')
                        .prop(
                            'disabled',
                            loading
                        );

                },


                /*
                 * -------------------------------------------------
                 * CREATE LOADING
                 * -------------------------------------------------
                 *
                 * À ce moment-là les steps ne sont PAS encore connus.
                 */

                showCreationLoading: function () {

                    $result.html(
                        '<div class="aips-agent-result__loading">' +
                        'Création de l’exécution…' +
                        '</div>'
                    );

                },


                /*
                 * -------------------------------------------------
                 * RENDER EXECUTION
                 * -------------------------------------------------
                 *
                 * Appelé uniquement après CREATE.
                 *
                 * À ce moment-là nous connaissons les steps.
                 */

                renderExecution: function () {

                    if (!state.steps.length) {

                        $result.html(
                            '<div class="aips-agent-result__empty">' +
                            'Aucune étape disponible.' +
                            '</div>'
                        );

                        return;
                    }


                    var html = '';


                    html += '<div class="aips-agent-result__execution">';


                    /*
                     * -------------------------------------------------
                     * HEADER
                     * -------------------------------------------------
                     */

                    html +=
                        '<div class="aips-agent-result__header">';

                    html +=
                        '<h3 class="aips-agent-result__title">' +
                        'Exécution de l\'Agent' +
                        '</h3>';

                    html +=
                        '<p class="aips-agent-result__subtitle">' +
                        'L\'Agent traite votre demande' +
                        '</p>';

                    html +=
                        '</div>';


                    /*
                     * -------------------------------------------------
                     * PROGRESS
                     * -------------------------------------------------
                     */

                    html +=
                        '<div class="aips-agent-result__progress">';

                    html +=
                        '<div class="aips-agent-result__progress-header">';

                    html +=
                        '<span class="aips-agent-result__progress-label">' +
                        'Progression' +
                        '</span>';

                    html +=
                        '<span class="aips-agent-result__progress-value">' +
                        '0 / ' +
                        state.steps.length +
                        '</span>';

                    html +=
                        '</div>';


                    html +=
                        '<div class="aips-agent-result__progress-bar">';

                    html +=
                        '<div ' +
                        'class="aips-agent-result__progress-fill" ' +
                        'style="width: 0%;"' +
                        '></div>';

                    html +=
                        '</div>';

                    html +=
                        '</div>';


                    /*
                     * -------------------------------------------------
                     * STEPS
                     * -------------------------------------------------
                     */

                    html +=
                        '<div class="aips-agent-result__steps">';


                    state.steps.forEach(
                        function (step, index) {

                            html +=
                                renderer.renderStep(
                                    step,
                                    index,
                                    'pending'
                                );

                        }
                    );


                    html +=
                        '</div>';


                    /*
                     * -------------------------------------------------
                     * FINAL AREA
                     * -------------------------------------------------
                     */

                    html +=
                        '<div class="aips-agent-result__final"></div>';


                    html +=
                        '</div>';


                    $result.html(html);

                },


                /*
                 * -------------------------------------------------
                 * RENDER ONE STEP
                 * -------------------------------------------------
                 */

                renderStep: function (
                    step,
                    index,
                    status
                ) {

                    var statusClass =
                        getStepStatusClass(status);

                    var icon =
                        getStepIcon(
                            status,
                            index
                        );

                    var statusLabel =
                        getStepStatusLabel(status);

                    var message =
                        getDefaultStepMessage(status);


                    var html = '';


                    html +=
                        '<div ' +
                        'class="aips-agent-result__step ' +
                        statusClass + '" ' +
                        'data-step-index="' +
                        index +
                        '" ' +
                        'data-step-id="' +
                        escapeHtml(step.id) +
                        '"' +
                        '>';


                    /*
                     * ICON
                     */

                    html +=
                        '<div class="aips-agent-result__step-icon">' +
                        icon +
                        '</div>';


                    /*
                     * CONTENT
                     */

                    html +=
                        '<div class="aips-agent-result__step-content">';


                    /*
                     * HEADER
                     */

                    html +=
                        '<div class="aips-agent-result__step-header">';


                    html +=
                        '<div class="aips-agent-result__step-label">' +
                        escapeHtml(step.label) +
                        '</div>';


                    html +=
                        '<div class="aips-agent-result__step-status">' +
                        statusLabel +
                        '</div>';


                    html +=
                        '</div>';


                    /*
                     * MESSAGE
                     */

                    if (message) {

                        html +=
                            '<div class="aips-agent-result__step-message">' +
                            escapeHtml(message) +
                            '</div>';

                    }


                    /*
                     * RESULT
                     */

                    html +=
                        '<div class="aips-agent-result__step-result"></div>';


                    html +=
                        '</div>';


                    html +=
                        '</div>';


                    return html;

                },


                /*
                 * -------------------------------------------------
                 * UPDATE ONE STEP
                 * -------------------------------------------------
                 */

                updateStep: function (
                    index,
                    status,
                    result,
                    message
                ) {

                    var $step =
                        $result.find(
                            '.aips-agent-result__step[data-step-index="' +
                            index +
                            '"]'
                        );


                    if (!$step.length) {
                        return;
                    }


                    var step =
                        state.steps[index];


                    /*
                     * Classes
                     */

                    $step.removeClass(
                        'is-pending ' +
                        'is-running ' +
                        'is-completed ' +
                        'is-waiting ' +
                        'is-failed'
                    );


                    $step.addClass(
                        getStepStatusClass(status)
                    );


                    /*
                     * Icon
                     */

                    $step
                        .find(
                            '.aips-agent-result__step-icon'
                        )
                        .text(
                            getStepIcon(
                                status,
                                index
                            )
                        );


                    /*
                     * Status
                     */

                    $step
                        .find(
                            '.aips-agent-result__step-status'
                        )
                        .text(
                            getStepStatusLabel(status)
                        );


                    /*
                     * Message
                     */

                    var finalMessage =
                        message ||
                        getDefaultStepMessage(status);


                    var $message =
                        $step.find(
                            '.aips-agent-result__step-message'
                        );


                    if (finalMessage) {

                        if ($message.length) {

                            $message.text(
                                finalMessage
                            );

                        } else {

                            $step
                                .find(
                                    '.aips-agent-result__step-header'
                                )
                                .after(
                                    '<div class="aips-agent-result__step-message">' +
                                    escapeHtml(finalMessage) +
                                    '</div>'
                                );

                        }

                    } else {

                        $message.remove();

                    }


                    /*
                     * Result
                     */

                    if (
                        result !== undefined &&
                        result !== null
                    ) {

                        renderer.renderStepResult(
                            $step.find(
                                '.aips-agent-result__step-result'
                            ),
                            result
                        );

                    }


                    /*
                     * Si c'est un step waiting,
                     * on peut afficher la validation.
                     */

                    if (
                        status === 'waiting' ||
                        status === 'waiting_human'
                    ) {

                        renderer.renderHumanValidation(
                            $step
                        );

                    }

                },


                /*
                 * -------------------------------------------------
                 * STEP RESULT
                 * -------------------------------------------------
                 */

                renderStepResult: function (
                    $container,
                    result
                ) {

                    if (!$container.length) {
                        return;
                    }


                    /*
                     * Valeur simple
                     */

                    if (
                        typeof result !== 'object' ||
                        result === null
                    ) {

                        if (
                            result !== ''
                        ) {

                            $container.html(
                                '<div class="aips-agent-result__result-text">' +
                                escapeHtml(result) +
                                '</div>'
                            );

                        }

                        return;

                    }


                    var html = '';



                    /*
                     * MESSAGE
                     */

                    if (result.message) {

                        html +=
                            '<div class="aips-agent-result__result-block">';

                        html +=
                            '<div class="aips-agent-result__result-label">' +
                            'Resultat du traitement' +
                            '</div>';

                        html +=
                            '<div class="aips-agent-result__result-pre">' +
                            result.message +
                            '</div>';

                        html +=
                            '</div>';

                    }

                    /*
                     * GENERIC RESULT
                     */

                    if (!html) {
                        const resultToDisplay = { ...result };

                        delete resultToDisplay.message;
                        html +=
                            '<div class="aips-agent-result__result-block">';

                        html +=
                            '<pre class="aips-agent-result__result-pre">' +
                            escapeHtml(
                                JSON.stringify(
                                    resultToDisplay,
                                    null,
                                    2
                                )
                            ) +
                            '</pre>';

                        html +=
                            '</div>';

                    }


                    $container.html(html);

                },


                /*
                 * -------------------------------------------------
                 * PROGRESS
                 * -------------------------------------------------
                 */

                updateProgress: function () {

                    var total =
                        state.steps.length;


                    if (!total) {
                        return;
                    }


                    var completed =
                        state.completedSteps;


                    if (completed < 0) {
                        completed = 0;
                    }


                    if (completed > total) {
                        completed = total;
                    }


                    var percentage =
                        Math.round(
                            (completed / total) * 100
                        );


                    $result
                        .find(
                            '.aips-agent-result__progress-value'
                        )
                        .text(
                            completed +
                            ' / ' +
                            total
                        );


                    $result
                        .find(
                            '.aips-agent-result__progress-fill'
                        )
                        .css(
                            'width',
                            percentage + '%'
                        );

                },


                /*
                 * -------------------------------------------------
                 * HUMAN VALIDATION
                 * -------------------------------------------------
                 */

                renderHumanValidation: function ($step) {

                    var $container =
                        $step.find(
                            '.aips-agent-result__step-result'
                        );


                    $container.html(

                        '<div class="aips-agent-result__validation">' +

                        '<h4 class="aips-agent-result__validation-title">' +
                        'Validation nécessaire' +
                        '</h4>' +

                        '<p class="aips-agent-result__validation-message">' +
                        'Vérifiez le contenu avant de continuer.' +
                        '</p>' +

                        '<div class="aips-agent-result__validation-actions">' +

                        '<button ' +
                        'type="button" ' +
                        'class="button button-primary aips-validate-execution">' +
                        'Valider' +
                        '</button>' +

                        '<button ' +
                        'type="button" ' +
                        'class="button aips-reject-execution">' +
                        'Modifier' +
                        '</button>' +

                        '</div>' +

                        '</div>'

                    );

                },


                /*
                 * -------------------------------------------------
                 * VALIDATION LOADING
                 * -------------------------------------------------
                 */

                showValidationLoading: function ($step) {

                    $step
                        .find(
                            '.aips-agent-result__step-result'
                        )
                        .html(
                            '<div class="aips-agent-result__validation-loading">' +
                            'Validation en cours…' +
                            '</div>'
                        );

                },


                /*
                 * -------------------------------------------------
                 * FINAL RESULT
                 * -------------------------------------------------
                 */

                showFinalResult: function (result) {

                    var $final =
                        $result.find(
                            '.aips-agent-result__final'
                        );


                    if (!$final.length) {
                        return;
                    }


                    var html = '';


                    html +=
                        '<div class="aips-agent-result__final-result">';


                    html +=
                        '<div class="aips-agent-result__final-title">' +
                        '✓ Exécution terminée avec succès' +
                        '</div>';


                    /*
                     * Titre éventuel
                     */

                    if (
                        result &&
                        (
                            result.postTitle ||
                            result.title
                        )
                    ) {

                        html +=
                            '<div class="aips-agent-result__final-title-value">' +
                            escapeHtml(
                                result.postTitle ||
                                result.title
                            ) +
                            '</div>';

                    }


                    /*
                     * Excerpt éventuel
                     */

                    if (
                        result &&
                        result.excerpt
                    ) {

                        html +=
                            '<div class="aips-agent-result__final-excerpt">' +
                            escapeHtml(
                                result.excerpt
                            ) +
                            '</div>';

                    }


                    /*
                     * Debug
                     */

                    if (result) {

                        html +=
                            '<details class="aips-agent-result__final-details">';

                        html +=
                            '<summary>' +
                            'Voir les données complètes' +
                            '</summary>';

                        html +=
                            '<pre class="aips-agent-result__result-pre">' +
                            escapeHtml(
                                JSON.stringify(
                                    result,
                                    null,
                                    2
                                )
                            ) +
                            '</pre>';

                        html +=
                            '</details>';

                    }


                    html +=
                        '</div>';


                    $final.html(html);

                },


                /*
                 * -------------------------------------------------
                 * EXECUTION ERROR
                 * -------------------------------------------------
                 */

                showExecutionError: function (message) {

                    var $final =
                        $result.find(
                            '.aips-agent-result__final'
                        );


                    $final.html(

                        '<div class="aips-agent-result__error">' +

                        '<div class="aips-agent-result__error-title">' +
                        '✕ L\'exécution a échoué' +
                        '</div>' +

                        '<div class="aips-agent-result__error-message">' +
                        escapeHtml(message) +
                        '</div>' +

                        '</div>'

                    );

                }

            };


            /*
             * =====================================================
             * EXECUTION
             * =====================================================
             *
             * Toute la logique de l'Agent se trouve ici.
             */

            var execution = {


                /*
                 * -------------------------------------------------
                 * RESET
                 * -------------------------------------------------
                 */

                reset: function () {

                    state.executionId = null;

                    state.steps = [];

                    state.currentStepIndex = null;

                    state.completedSteps = 0;

                    state.running = false;

                    state.waitingForHuman = false;

                    state.status = 'idle';

                },


                /*
                 * -------------------------------------------------
                 * CREATE
                 * -------------------------------------------------
                 */

                create: function (input) {

                    this.reset();


                    state.status =
                        'creating';


                    renderer.setFormLoading(
                        true
                    );


                    renderer.showCreationLoading();


                    api
                        .createExecution(input)

                        .done(
                            function (response) {

                                console.log(
                                    '[AIPS] CREATE response:',
                                    response
                                );


                                if (
                                    !response ||
                                    !response.success
                                ) {

                                    execution.handleError(
                                        getErrorMessage(
                                            response,
                                            'Impossible de créer l’exécution.'
                                        )
                                    );

                                    return;
                                }


                                var data =
                                    response.data || {};


                                /*
                                 * -------------------------------------
                                 * EXECUTION ID
                                 * -------------------------------------
                                 */

                                state.executionId =
                                    data.execution_id ||
                                    (
                                        data.execution &&
                                        data.execution.id
                                    );


                                /*
                                 * -------------------------------------
                                 * STEPS
                                 * -------------------------------------
                                 *
                                 * Ici seulement les steps deviennent
                                 * connus.
                                 */

                                state.steps =
                                    data.steps ||
                                    (
                                        data.execution &&
                                        data.execution.steps
                                    ) ||
                                    [];


                                /*
                                 * -------------------------------------
                                 * VALIDATION
                                 * -------------------------------------
                                 */

                                if (
                                    !state.executionId
                                ) {

                                    execution.handleError(
                                        'L’identifiant de l’exécution est manquant.'
                                    );

                                    return;
                                }


                                if (
                                    !state.steps.length
                                ) {

                                    execution.handleError(
                                        'Aucune étape n’a été retournée par le serveur.'
                                    );

                                    return;
                                }


                                /*
                                 * -------------------------------------
                                 * STATE INITIAL
                                 * -------------------------------------
                                 */

                                state.currentStepIndex =
                                    null;

                                state.completedSteps =
                                    0;

                                state.status =
                                    'ready';


                                /*
                                 * -------------------------------------
                                 * RENDER
                                 * -------------------------------------
                                 */

                                renderer.renderExecution();

                                renderer.updateProgress();


                                /*
                                 * -------------------------------------
                                 * RUN PREMIER STEP
                                 * -------------------------------------
                                 */

                                setTimeout(
                                    function () {

                                        execution.run();

                                    },
                                    150
                                );

                            }
                        )

                        .fail(
                            function (xhr) {

                                console.error(
                                    '[AIPS] CREATE AJAX error:',
                                    xhr
                                );


                                execution.handleError(
                                    'Une erreur est survenue lors de la création de l’exécution.'
                                );

                            }
                        );

                },


                /*
                 * -------------------------------------------------
                 * FIND NEXT STEP
                 * -------------------------------------------------
                 *
                 * IMPORTANT :
                 *
                 * On utilise le STATE et non le DOM.
                 */

                getNextStepIndex: function () {

                    /*
                     * Aucun step
                     */

                    if (
                        !state.steps.length
                    ) {

                        return null;

                    }


                    /*
                     * Si un step est explicitement courant,
                     * on continue avec lui.
                     */

                    if (
                        state.currentStepIndex !== null
                    ) {

                        return state.currentStepIndex;

                    }


                    /*
                     * Sinon on cherche le premier step
                     * non terminé.
                     */

                    for (
                        var i = 0;
                        i < state.steps.length;
                        i++
                    ) {

                        var $step =
                            $result.find(
                                '.aips-agent-result__step[data-step-index="' +
                                i +
                                '"]'
                            );


                        if (
                            !$step.hasClass(
                                'is-completed'
                            )
                        ) {

                            return i;

                        }

                    }


                    return null;

                },


                /*
                 * -------------------------------------------------
                 * RUN
                 * -------------------------------------------------
                 */

                run: function () {

                    if (
                        !state.executionId ||
                        state.running ||
                        state.waitingForHuman
                    ) {

                        return;

                    }


                    /*
                     * -----------------------------------------------
                     * STEP À EXÉCUTER
                     * -----------------------------------------------
                     *
                     * C'est LE point important.
                     *
                     * Dès que nous appelons le RUN AJAX,
                     * nous savons quel step est concerné.
                     */

                    var stepIndex =
                        this.getNextStepIndex();


                    if (
                        stepIndex === null
                    ) {

                        return;

                    }


                    /*
                     * On mémorise le step AVANT l'AJAX.
                     */

                    state.currentStepIndex =
                        stepIndex;


                    state.running =
                        true;

                    state.status =
                        'running';


                    /*
                     * -----------------------------------------------
                     * LOADING SUR LE STEP COURANT
                     * -----------------------------------------------
                     */

                    renderer.updateStep(
                        stepIndex,
                        'running'
                    );


                    /*
                     * -----------------------------------------------
                     * AJAX RUN
                     * -----------------------------------------------
                     */

                    api
                        .runExecution(
                            state.executionId
                        )

                        .done(
                            function (response) {

                                console.log(
                                    '[AIPS] RUN response:',
                                    response
                                );


                                /*
                                 * -----------------------------------
                                 * AJAX SUCCESS = FALSE
                                 * -----------------------------------
                                 *
                                 * Le step concerné reste stepIndex.
                                 */

                                if (
                                    !response ||
                                    !response.success
                                ) {

                                    execution.handleStepError(
                                        stepIndex,
                                        getErrorMessage(
                                            response,
                                            'Erreur pendant l’exécution.'
                                        )
                                    );

                                    return;
                                }


                                var data =
                                    response.data || {};


                                execution.handleRunResponse(
                                    data,
                                    stepIndex
                                );

                            }
                        )

                        .fail(
                            function (xhr) {

                                console.error(
                                    '[AIPS] RUN AJAX error:',
                                    xhr
                                );


                                var message =
                                    'Une erreur est survenue pendant l’exécution.';


                                if (
                                    xhr &&
                                    xhr.responseJSON &&
                                    xhr.responseJSON.data &&
                                    xhr.responseJSON.data.message
                                ) {

                                    message =
                                        xhr.responseJSON.data.message;

                                }


                                /*
                                 * IMPORTANT :
                                 *
                                 * Même en cas d'erreur HTTP,
                                 * le step concerné est stepIndex.
                                 */

                                execution.handleStepError(
                                    stepIndex,
                                    message
                                );

                            }
                        );

                },


                /*
                 * -------------------------------------------------
                 * HANDLE RUN RESPONSE
                 * -------------------------------------------------
                 */

                handleRunResponse: function (
                    data,
                    stepIndex
                ) {

                    var executionData =
                        data.execution ||
                        data;


                    /*
                     * -----------------------------------------------
                     * STATUS
                     * -----------------------------------------------
                     */

                    var status =
                        data.step_status ||
                        data.status ||
                        executionData.step_status ||
                        executionData.status ||
                        'completed';


                    /*
                     * -----------------------------------------------
                     * RESULT
                     * -----------------------------------------------
                     */

                    var result =
                        data.result !== undefined
                            ? data.result
                            : (
                                data.data !== undefined
                                    ? data.data
                                    : (
                                        executionData.result !== undefined
                                            ? executionData.result
                                            : executionData.data
                                    )
                            );


                    /*
                     * -----------------------------------------------
                     * MESSAGE
                     * -----------------------------------------------
                     */

                    var message =
                        data.message ||
                        executionData.message ||
                        null;


                    /*
                     * ===============================================
                     * FAILED
                     * ===============================================
                     */

                    if (
                        status === 'failed'
                    ) {

                        this.handleStepError(
                            stepIndex,
                            data.error ||
                            executionData.error ||
                            message ||
                            'Le step a échoué.',
                            result
                        );

                        return;

                    }


                    /*
                     * ===============================================
                     * WAITING HUMAN
                     * ===============================================
                     */

                    if (
                        status === 'waiting' ||
                        status === 'waiting_human'
                    ) {

                        state.running =
                            false;

                        state.waitingForHuman =
                            true;

                        state.status =
                            'waiting_human';


                        /*
                         * Le step courant devient waiting.
                         */

                        renderer.updateStep(
                            stepIndex,
                            'waiting',
                            result,
                            message
                        );


                        /*
                         * La validation est affichée
                         * sur CE step.
                         */

                        renderer.renderHumanValidation(
                            $result.find(
                                '.aips-agent-result__step[data-step-index="' +
                                stepIndex +
                                '"]'
                            )
                        );


                        renderer.updateProgress();


                        return;

                    }


                    /*
                     * ===============================================
                     * STEP TERMINÉ
                     * ===============================================
                     */

                    renderer.updateStep(
                        stepIndex,
                        'completed',
                        result,
                        message
                    );


                    /*
                     * -----------------------------------------------
                     * COMPLETED STEPS
                     * -----------------------------------------------
                     */

                    var completed;


                    if (
                        data.completed_steps !== undefined
                    ) {

                        completed =
                            parseInt(
                                data.completed_steps,
                                10
                            );

                    } else {

                        /*
                         * Si le serveur ne donne pas le nombre,
                         * le step courant + 1 est terminé.
                         */

                        completed =
                            stepIndex + 1;

                    }


                    if (
                        isNaN(completed)
                    ) {

                        completed =
                            stepIndex + 1;

                    }


                    /*
                     * Protection.
                     */

                    completed =
                        Math.max(
                            0,
                            Math.min(
                                completed,
                                state.steps.length
                            )
                        );


                    state.completedSteps =
                        completed;


                    renderer.updateProgress();


                    /*
                     * ===============================================
                     * EXECUTION TERMINÉE
                     * ===============================================
                     */

                    var finalResult =
                        data.final === true ||
                        executionData.final === true ||
                        (
                            status === 'completed' &&
                            completed >= state.steps.length
                        );


                    if (
                        finalResult
                    ) {

                        state.running =
                            false;

                        state.waitingForHuman =
                            false;

                        state.status =
                            'completed';

                        state.currentStepIndex =
                            null;


                        renderer.showFinalResult(
                            result
                        );


                        renderer.setFormLoading(
                            false
                        );


                        return;

                    }


                    /*
                     * ===============================================
                     * STEP SUIVANT
                     * ===============================================
                     */

                    state.running =
                        false;


                    /*
                     * Le prochain step correspond normalement
                     * au nombre de steps terminés.
                     *
                     * Exemple :
                     *
                     * completed = 1
                     * => prochain step = index 1
                     */

                    state.currentStepIndex =
                        completed;


                    /*
                     * Petit délai pour permettre au navigateur
                     * de rendre visuellement le résultat.
                     */

                    setTimeout(
                        function () {

                            execution.run();

                        },
                        250
                    );

                },


                /*
                 * -------------------------------------------------
                 * STEP ERROR
                 * -------------------------------------------------
                 *
                 * C'est ici que nous garantissons que l'erreur
                 * est affichée sur le step courant.
                 */

                handleStepError: function (
                    stepIndex,
                    message,
                    result
                ) {

                    state.running =
                        false;

                    state.waitingForHuman =
                        false;

                    state.status =
                        'failed';


                    /*
                     * IMPORTANT :
                     *
                     * stepIndex vient directement de run().
                     *
                     * Il correspond donc exactement au step
                     * qui était en cours d'exécution.
                     */

                    renderer.updateStep(
                        stepIndex,
                        'failed',
                        result,
                        message
                    );


                    /*
                     * Message global éventuel.
                     */

                    renderer.showExecutionError(
                        message
                    );


                    /*
                     * On arrête le formulaire.
                     */

                    renderer.setFormLoading(
                        false
                    );

                },


                /*
                 * -------------------------------------------------
                 * GLOBAL ERROR
                 * -------------------------------------------------
                 *
                 * Utilisé pour les erreurs AVANT l'exécution
                 * des steps :
                 *
                 * - CREATE échoue
                 * - execution_id manquant
                 * - steps absents
                 */

                handleError: function (message) {

                    state.running =
                        false;

                    state.waitingForHuman =
                        false;

                    state.status =
                        'failed';


                    renderer.showExecutionError(
                        message
                    );


                    renderer.setFormLoading(
                        false
                    );

                },


                /*
                 * -------------------------------------------------
                 * HUMAN VALIDATION
                 * -------------------------------------------------
                 */

                validate: function (
                    approved
                ) {

                    if (
                        !state.executionId ||
                        state.currentStepIndex === null
                    ) {

                        return;

                    }


                    if (
                        state.running
                    ) {

                        return;

                    }


                    var stepIndex =
                        state.currentStepIndex;


                    var $step =
                        $result.find(
                            '.aips-agent-result__step[data-step-index="' +
                            stepIndex +
                            '"]'
                        );


                    state.running =
                        true;


                    renderer.showValidationLoading(
                        $step
                    );


                    api
                        .resumeExecution(
                            state.executionId,
                            approved
                        )

                        .done(
                            function (response) {

                                console.log(
                                    '[AIPS] VALIDATE response:',
                                    response
                                );


                                /*
                                 * -----------------------------------
                                 * ERROR
                                 * -----------------------------------
                                 */

                                if (
                                    !response ||
                                    !response.success
                                ) {

                                    execution.handleStepError(
                                        stepIndex,
                                        getErrorMessage(
                                            response,
                                            'Impossible de valider l’exécution.'
                                        )
                                    );

                                    return;

                                }


                                var data =
                                    response.data || {};


                                /*
                                 * -----------------------------------
                                 * REFUS
                                 * -----------------------------------
                                 */

                                if (
                                    !approved
                                ) {

                                    state.running =
                                        false;

                                    state.waitingForHuman =
                                        false;

                                    state.status =
                                        'failed';


                                    renderer.updateStep(
                                        stepIndex,
                                        'failed',
                                        null,
                                        data.message ||
                                        'L’exécution a été arrêtée.'
                                    );


                                    renderer.showExecutionError(
                                        data.message ||
                                        'L’exécution a été arrêtée.'
                                    );


                                    renderer.setFormLoading(
                                        false
                                    );


                                    return;

                                }


                                /*
                                 * -----------------------------------
                                 * VALIDATION ACCEPTÉE
                                 * -----------------------------------
                                 */

                                state.running =
                                    false;

                                state.waitingForHuman =
                                    false;


                                /*
                                 * Le step de validation est considéré
                                 * comme terminé.
                                 *
                                 * Le backend peut éventuellement déjà
                                 * avoir fourni le nouveau nombre.
                                 */

                                if (
                                    data.completed_steps !== undefined
                                ) {

                                    state.completedSteps =
                                        parseInt(
                                            data.completed_steps,
                                            10
                                        );

                                } else {

                                    state.completedSteps =
                                        stepIndex + 1;

                                }


                                if (
                                    isNaN(
                                        state.completedSteps
                                    )
                                ) {

                                    state.completedSteps =
                                        stepIndex + 1;

                                }


                                renderer.updateStep(
                                    stepIndex,
                                    'completed',
                                    data.result ||
                                    data.data ||
                                    null,
                                    data.message ||
                                    'Validation effectuée.'
                                );


                                renderer.updateProgress();


                                /*
                                 * -----------------------------------
                                 * CONTINUE
                                 * -----------------------------------
                                 */

                                state.currentStepIndex =
                                    state.completedSteps;


                                setTimeout(
                                    function () {

                                        execution.run();

                                    },
                                    150
                                );

                            }
                        )

                        .fail(
                            function (xhr) {

                                console.error(
                                    '[AIPS] VALIDATE AJAX error:',
                                    xhr
                                );


                                state.running =
                                    false;


                                var message =
                                    'Une erreur est survenue pendant la validation.';


                                if (
                                    xhr &&
                                    xhr.responseJSON &&
                                    xhr.responseJSON.data &&
                                    xhr.responseJSON.data.message
                                ) {

                                    message =
                                        xhr.responseJSON.data.message;

                                }


                                /*
                                 * Même principe :
                                 *
                                 * l'erreur appartient au step
                                 * actuellement en validation.
                                 */

                                execution.handleStepError(
                                    stepIndex,
                                    message
                                );

                            }
                        );

                }

            };


            /*
             * =====================================================
             * EVENTS
             * =====================================================
             */

            /*
             * -----------------------------------------------------
             * FORM SUBMIT
             * -----------------------------------------------------
             */

            $form.on(
                'submit',
                function (event) {

                    event.preventDefault();


                    /*
                     * Protection double submit.
                     */

                    if (
                        state.running ||
                        state.status === 'creating'
                    ) {

                        return;

                    }


                    var formData =
                        new FormData(this);


                    var input = {};


                    formData.forEach(
                        function (value, key) {

                            input[key] =
                                value;

                        }
                    );


                    execution.create(
                        input
                    );

                }
            );


            /*
             * -----------------------------------------------------
             * HUMAN VALIDATION
             * -----------------------------------------------------
             */

            $result.on(
                'click',
                '.aips-validate-execution',
                function () {

                    if (
                        state.running
                    ) {

                        return;

                    }


                    execution.validate(
                        true
                    );

                }
            );


            /*
             * -----------------------------------------------------
             * HUMAN REJECTION
             * -----------------------------------------------------
             */

            $result.on(
                'click',
                '.aips-reject-execution',
                function () {

                    if (
                        state.running
                    ) {

                        return;

                    }


                    execution.validate(
                        false
                    );

                }
            );

        }


        /*
         * =========================================================
         * AJAX HELPER
         * =========================================================
         *
         * Ton helper existait déjà.
         */

        function post(
            action,
            data
        ) {

            return $.post(
                MY_AI_AGENT.ajaxUrl,
                $.extend(
                    {
                        action: action,
                        nonce: MY_AI_AGENT.nonce
                    },
                    data
                )
            );

        }




    $(function () {
        initMediaPickers();
        initTabsAndSource();
        initGenerate();
        initImport();
        initPrompts();
        initKeys();
        initAgents();
    });
})(jQuery);
