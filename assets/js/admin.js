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
    function initAgents2() {

        var $form = $('.aips-agent-form');
        if (!$form.length) { return; }
        $form.on('submit', function (e) {
            e.preventDefault();

            const $submitButton = $form.find('[type="submit"]');

            if ($submitButton.prop('disabled')) {
                return;
            }

            $form.addClass('is-loading');
            $submitButton.prop('disabled', true);

            const formData = new FormData(this);

            const input = {};

            formData.forEach(function (value, key) {
                input[key] = value;
            });


            post('my_ai_agent_create_execution', input)
                .done(function (res) {
                    console.log('AJAX response:', res);
                    if (res && res.success) {
                        $form.removeClass('is-loading');
                        $submitButton.prop('disabled', false);
                    }
                    else {
                        window.alert(res.data.message || 'Erreur');
                    }
                })
                .fail(function (xhr) {
                    console.log('AJAX error:', xhr);
                    $form.removeClass('is-loading');
                    $submitButton.prop('disabled', false);
                    window.alert('Une erreur est survenue.');
                });
        });
    }
    /*
         * =========================================================
         * AJAX
         * =========================================================
         *
         * Toute la communication avec WordPress passe ici.
         */

    const Ajax = {

        post(action, data = {}) {

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

        },


        createExecution(data) {

            return this.post(
                'my_ai_agent_create_execution',
                data
            );

        },


        runExecution(executionId) {

            return this.post(
                'my_ai_agent_run_execution',
                {
                    execution_id: executionId
                }
            );

        },


        resumeExecution(executionId, approved) {

            return this.post(
                'my_ai_agent_resume_execution',
                {
                    execution_id: executionId,
                    approved: approved ? 1 : 0
                }
            );

        }

    };


    /*
     * =========================================================
     * HELPERS
     * =========================================================
     */

    const Utils = {

        escapeHtml(value) {

            return $('<div>')
                .text(
                    value == null
                        ? ''
                        : String(value)
                )
                .html();

        },


        json(value) {

            try {

                return JSON.stringify(
                    value,
                    null,
                    2
                );

            } catch (error) {

                return String(value);

            }

        },


        isObject(value) {

            return (
                value !== null &&
                typeof value === 'object'
            );

        },


        toInteger(value, fallback = null) {

            const number =
                parseInt(value, 10);

            return Number.isNaN(number)
                ? fallback
                : number;

        }

    };


    /*
     * =========================================================
     * EXECUTION STATE
     * =========================================================
     *
     * Le DOM n'est PAS la source de vérité.
     *
     * L'état de l'exécution est conservé ici.
     */

    function createState() {

        return {

            executionId: null,

            steps: [],

            currentStepIndex: null,

            completedSteps: 0,

            status: 'idle',

            running: false,

            waitingForHuman: false

        };

    }


    /*
     * =========================================================
     * RENDERER
     * =========================================================
     *
     * Responsable uniquement de l'affichage.
     */

    class AgentRenderer {

        constructor($form, $result) {

            this.$form = $form;

            this.$result = $result;

        }


        /*
         * -----------------------------------------------------
         * FORM
         * -----------------------------------------------------
         */

        setLoading(isLoading) {

            this.$form.toggleClass(
                'is-loading',
                isLoading
            );


            this.$form
                .find('[type="submit"]')
                .prop(
                    'disabled',
                    isLoading
                );

        }


        /*
         * -----------------------------------------------------
         * GLOBAL LOADING
         * -----------------------------------------------------
         */

        showExecutionLoading() {

            this.$result.html(
                `
                <div class="aips-agent-result__loading">
                    <div class="aips-agent-result__loading-message">
                        Création de l'exécution…
                    </div>
                </div>
                `
            );

        }


        /*
         * -----------------------------------------------------
         * EXECUTION
         * -----------------------------------------------------
         *
         * Cette méthode est appelée UNIQUEMENT après CREATE.
         *
         * C'est ici que nous construisons toute la structure
         * .aips-agent-result__execution.
         */

        renderExecution(steps) {

            const total =
                steps.length;


            const stepsHtml =
                steps
                    .map(
                        (step, index) =>
                            this.renderStep(
                                step,
                                index
                            )
                    )
                    .join('');


            this.$result.html(
                `
                <div class="aips-agent-result__execution">

                    <div class="aips-agent-result__header">

                        <h3 class="aips-agent-result__title">
                            Génération de l'article
                        </h3>

                        <p class="aips-agent-result__subtitle">
                            Votre contenu est en cours de préparation.
                        </p>

                    </div>


                    <div class="aips-agent-result__progress">

                        <div class="aips-agent-result__progress-header">

                            <span class="aips-agent-result__progress-label">
                                Progression
                            </span>

                            <span class="aips-agent-result__progress-value">
                                0 / ${total}
                            </span>

                        </div>


                        <div class="aips-agent-result__progress-bar">

                            <div
                                class="aips-agent-result__progress-fill"
                                style="width: 0%;"
                            ></div>

                        </div>

                    </div>


                    <div class="aips-agent-result__steps">

                        ${stepsHtml}

                    </div>


                    <div class="aips-agent-result__final"></div>

                </div>
                `
            );

        }


        /*
         * -----------------------------------------------------
         * STEP INITIAL
         * -----------------------------------------------------
         */

        renderStep(step, index) {

            return `
                <div
                    class="aips-agent-result__step is-pending"
                    data-step-index="${index}"
                    data-step-id="${this.escapeAttribute(step.id)}"
                >

                    <div class="aips-agent-result__step-icon">
                        ${index + 1}
                    </div>


                    <div class="aips-agent-result__step-content">

                        <div class="aips-agent-result__step-header">

                            <div class="aips-agent-result__step-label">
                                ${Utils.escapeHtml(step.label)}
                            </div>

                            <div class="aips-agent-result__step-status">
                                En attente
                            </div>

                        </div>


                        <div class="aips-agent-result__step-message"></div>


                        <div class="aips-agent-result__step-result"></div>

                    </div>

                </div>
            `;

        }


        /*
         * -----------------------------------------------------
         * UPDATE STEP
         * -----------------------------------------------------
         */

        updateStep(
            index,
            status,
            result = null,
            message = null
        ) {

            const $step =
                this.getStep(index);


            if (!$step.length) {

                console.warn(
                    '[Agent] Step introuvable:',
                    index
                );

                return;

            }


            const config =
                this.getStatusConfig(
                    status,
                    index
                );


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
                config.className
            );


            /*
             * Icon
             */

            $step
                .find(
                    '.aips-agent-result__step-icon'
                )
                .text(
                    config.icon
                );


            /*
             * Status
             */

            $step
                .find(
                    '.aips-agent-result__step-status'
                )
                .text(
                    config.label
                );


            /*
             * Message
             */

            if (message !== null) {

                $step
                    .find(
                        '.aips-agent-result__step-message'
                    )
                    .text(
                        message
                    );

            }


            /*
             * Result
             */

            if (result !== null) {

                this.renderStepResult(
                    $step
                        .find(
                            '.aips-agent-result__step-result'
                        ),
                    result
                );

            }

        }


        /*
         * -----------------------------------------------------
         * STATUS CONFIG
         * -----------------------------------------------------
         */

        getStatusConfig(
            status,
            index
        ) {

            switch (status) {

                case 'running':

                    return {
                        className: 'is-running',
                        icon: index + 1,
                        label: 'En cours...'
                    };


                case 'completed':

                    return {
                        className: 'is-completed',
                        icon: '✓',
                        label: 'Terminé'
                    };


                case 'waiting':
                case 'waiting_human':

                    return {
                        className: 'is-waiting',
                        icon: '!',
                        label: 'Votre validation est requise'
                    };


                case 'failed':

                    return {
                        className: 'is-failed',
                        icon: '×',
                        label: 'Erreur'
                    };


                case 'pending':
                default:

                    return {
                        className: 'is-pending',
                        icon: index + 1,
                        label: 'En attente'
                    };

            }

        }


        /*
         * -----------------------------------------------------
         * STEP RESULT
         * -----------------------------------------------------
         */

        renderStepResult(
            $container,
            result
        ) {

            if (
                result === null ||
                result === undefined
            ) {

                $container.empty();

                return;

            }


            /*
             * String / primitive
             */

            if (!Utils.isObject(result)) {

                $container.html(
                    `
                    <div class="aips-agent-result__step-result-text">
                        ${Utils.escapeHtml(result)}
                    </div>
                    `
                );

                return;

            }


            const blocks = [];


            /*
             * AI response
             */

            if (
                result.ai_response !== undefined &&
                result.ai_response !== null
            ) {

                blocks.push(
                    this.renderResultBlock(
                        'Réponse IA',
                        result.ai_response
                    )
                );

            }


            /*
             * Prompt
             */

            if (
                result.prompt !== undefined &&
                result.prompt !== null
            ) {

                blocks.push(
                    this.renderResultBlock(
                        'Prompt généré',
                        result.prompt
                    )
                );

            }


            /*
             * Parsed response
             */

            if (
                result.parsed_response !== undefined &&
                result.parsed_response !== null
            ) {

                blocks.push(
                    this.renderResultBlock(
                        'Données analysées',
                        Utils.json(
                            result.parsed_response
                        )
                    )
                );

            }


            /*
             * Blog post
             */

            if (
                result.blog_post !== undefined &&
                result.blog_post !== null
            ) {

                const content =
                    typeof result.blog_post === 'string'
                        ? result.blog_post
                        : Utils.json(
                            result.blog_post
                        );


                blocks.push(
                    `
                    <div class="aips-agent-result__result-block">

                        <div class="aips-agent-result__result-label">
                            Article créé
                        </div>

                        <div class="aips-agent-result__result-blog">
                            ${Utils.escapeHtml(content)}
                        </div>

                    </div>
                    `
                );

            }


            /*
             * Generic object
             */

            if (!blocks.length) {

                blocks.push(
                    this.renderResultBlock(
                        'Résultat',
                        Utils.json(result)
                    )
                );

            }


            $container.html(
                blocks.join('')
            );

        }


        /*
         * -----------------------------------------------------
         * RESULT BLOCK
         * -----------------------------------------------------
         */

        renderResultBlock(
            label,
            value
        ) {

            return `
                <div class="aips-agent-result__result-block">

                    <div class="aips-agent-result__result-label">
                        ${Utils.escapeHtml(label)}
                    </div>

                    <pre class="aips-agent-result__result-pre">${Utils.escapeHtml(value)}</pre>

                </div>
            `;

        }


        /*
         * -----------------------------------------------------
         * PROGRESS
         * -----------------------------------------------------
         */

        updateProgress(
            completed,
            total
        ) {

            if (!total) {

                return;

            }


            completed =
                Math.max(
                    0,
                    Math.min(
                        completed,
                        total
                    )
                );


            const percentage =
                Math.round(
                    (
                        completed /
                        total
                    ) * 100
                );


            this.$result
                .find(
                    '.aips-agent-result__progress-value'
                )
                .text(
                    `${completed} / ${total}`
                );


            this.$result
                .find(
                    '.aips-agent-result__progress-fill'
                )
                .css(
                    'width',
                    `${percentage}%`
                );

        }


        /*
         * -----------------------------------------------------
         * HUMAN VALIDATION
         * -----------------------------------------------------
         */

        showHumanValidation(
            index
        ) {

            const $step =
                this.getStep(index);


            if (!$step.length) {

                return;

            }


            const $result =
                $step.find(
                    '.aips-agent-result__step-result'
                );


            $result.html(
                `
                <div class="aips-agent-result__validation">

                    <h4 class="aips-agent-result__validation-title">
                        Validation nécessaire
                    </h4>

                    <p class="aips-agent-result__validation-message">
                        Vérifiez le contenu avant de continuer.
                    </p>

                    <div class="aips-agent-result__validation-actions">

                        <button
                            type="button"
                            class="button button-primary aips-validate-execution"
                        >
                            Valider
                        </button>

                        <button
                            type="button"
                            class="button aips-reject-execution"
                        >
                            Modifier
                        </button>

                    </div>

                </div>
                `
            );

        }


        /*
         * -----------------------------------------------------
         * VALIDATION LOADING
         * -----------------------------------------------------
         */

        showValidationLoading(
            index
        ) {

            const $step =
                this.getStep(index);


            $step
                .find(
                    '.aips-agent-result__step-result'
                )
                .html(
                    `
                    <div class="aips-agent-result__validation-loading">
                        Validation en cours…
                    </div>
                    `
                );

        }


        /*
         * -----------------------------------------------------
         * FINAL RESULT
         * -----------------------------------------------------
         */

        showFinalResult(
            result
        ) {

            const $final =
                this.$result.find(
                    '.aips-agent-result__final'
                );


            if (!$final.length) {

                return;

            }


            const postTitle =
                result &&
                (
                    result.postTitle ||
                    result.title
                );


            const excerpt =
                result &&
                result.excerpt;


            $final.html(
                `
                <div class="aips-agent-result__final-result">

                    <div class="aips-agent-result__final-title">
                        ✓ Article généré avec succès
                    </div>


                    ${
                    postTitle
                        ? `
                            <div class="aips-agent-result__final-post-title">
                                ${Utils.escapeHtml(postTitle)}
                            </div>
                            `
                        : ''
                }


                    ${
                    excerpt
                        ? `
                            <div class="aips-agent-result__final-excerpt">
                                ${Utils.escapeHtml(excerpt)}
                            </div>
                            `
                        : ''
                }


                    <details class="aips-agent-result__final-details">

                        <summary>
                            Voir les données complètes
                        </summary>

                        <pre class="aips-agent-result__result-pre">${Utils.escapeHtml(
                    Utils.json(result)
                )}</pre>

                    </details>

                </div>
                `
            );

        }


        /*
         * -----------------------------------------------------
         * ERROR
         * -----------------------------------------------------
         */

        showError(
            message
        ) {

            /*
             * Si l'execution n'existe pas encore,
             * on peut afficher directement l'erreur.
             */

            let $container =
                this.$result.find(
                    '.aips-agent-result__final'
                );


            if (!$container.length) {

                this.$result.html(
                    `
                    <div class="aips-agent-result__error">

                        <div class="aips-agent-result__error-title">
                            ✕ Une erreur est survenue
                        </div>

                        <div class="aips-agent-result__error-message">
                            ${Utils.escapeHtml(message)}
                        </div>

                    </div>
                    `
                );

                return;

            }


            $container.html(
                `
                <div class="aips-agent-result__error">

                    <div class="aips-agent-result__error-title">
                        ✕ Une erreur est survenue
                    </div>

                    <div class="aips-agent-result__error-message">
                        ${Utils.escapeHtml(message)}
                    </div>

                </div>
                `
            );

        }


        /*
         * -----------------------------------------------------
         * DOM HELPERS
         * -----------------------------------------------------
         */

        getStep(index) {

            return this.$result.find(
                `.aips-agent-result__step[data-step-index="${index}"]`
            );

        }


        escapeAttribute(value) {

            return String(value == null ? '' : value)
                .replace(/&/g, '&amp;')
                .replace(/"/g, '&quot;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');

        }

    }


    /*
     * =========================================================
     * EXECUTION CONTROLLER
     * =========================================================
     *
     * Coordonne :
     *
     * CREATE
     * RUN
     * WAITING
     * RESUME
     * FINAL
     * ERROR
     */

    class ExecutionController {

        constructor(
            $form,
            renderer
        ) {

            this.$form =
                $form;

            this.renderer =
                renderer;

            this.state =
                createState();

        }


        /*
         * -----------------------------------------------------
         * RESET
         * -----------------------------------------------------
         */

        reset() {

            this.state =
                createState();

        }


        /*
         * -----------------------------------------------------
         * CREATE EXECUTION
         * -----------------------------------------------------
         */

        create(
            input
        ) {

            this.reset();


            this.renderer
                .setLoading(true);


            this.renderer
                .showExecutionLoading();


            Ajax
                .createExecution(input)

                .done(
                    (response) => {

                        console.log(
                            '[AIPS] CREATE response:',
                            response
                        );


                        /*
                         * Vérification AJAX
                         */

                        if (
                            !response ||
                            !response.success
                        ) {

                            this.handleError(
                                this.getErrorMessage(
                                    response,
                                    'Impossible de créer l’exécution.'
                                )
                            );

                            return;

                        }


                        const data =
                            response.data || {};


                        /*
                         * Execution ID
                         */

                        this.state.executionId =
                            data.execution_id ||
                            (
                                data.execution &&
                                data.execution.id
                            );


                        /*
                         * Steps
                         */

                        this.state.steps =
                            data.steps ||
                            (
                                data.execution &&
                                data.execution.steps
                            ) ||
                            [];


                        /*
                         * Validation
                         */

                        if (
                            !this.state.executionId
                        ) {

                            this.handleError(
                                'L’identifiant de l’exécution est manquant.'
                            );

                            return;

                        }


                        if (
                            !Array.isArray(
                                this.state.steps
                            ) ||
                            !this.state.steps.length
                        ) {

                            this.handleError(
                                'Aucune étape n’a été retournée par le serveur.'
                            );

                            return;

                        }


                        /*
                         * Le DOM est construit
                         * seulement maintenant.
                         */

                        this.renderer
                            .renderExecution(
                                this.state.steps
                            );


                        /*
                         * Lancer la première étape.
                         */

                        setTimeout(
                            () => {

                                this.run();

                            },
                            100
                        );

                    }
                )

                .fail(
                    (xhr) => {

                        console.error(
                            '[AIPS] CREATE AJAX error:',
                            xhr
                        );


                        this.handleError(
                            'Une erreur est survenue lors de la création de l’exécution.'
                        );

                    }
                );

        }


        /*
         * -----------------------------------------------------
         * RUN
         * -----------------------------------------------------
         */

        run() {

            if (
                !this.state.executionId ||
                this.state.running ||
                this.state.waitingForHuman
            ) {

                return;

            }


            this.state.running =
                true;


            /*
             * Trouver l'étape actuellement active.
             */

            const currentIndex =
                this.findCurrentStep();


            if (
                currentIndex !== null
            ) {

                this.state.currentStepIndex =
                    currentIndex;


                this.renderer
                    .updateStep(
                        currentIndex,
                        'running'
                    );

            }


            Ajax
                .runExecution(
                    this.state.executionId
                )

                .done(
                    (response) => {

                        console.log(
                            '[AIPS] RUN response:',
                            response
                        );


                        if (
                            !response ||
                            !response.success
                        ) {

                            this.handleError(
                                this.getErrorMessage(
                                    response,
                                    'Erreur pendant l’exécution.'
                                )
                            );

                            return;

                        }


                        this.handleRunResponse(
                            response.data || {}
                        );

                    }
                )

                .fail(
                    (xhr) => {

                        console.error(
                            '[AIPS] RUN AJAX error:',
                            xhr
                        );


                        this.handleError(
                            'Une erreur est survenue pendant l’exécution.'
                        );

                    }
                )

                .always(
                    () => {

                        this.state.running =
                            false;

                    }
                );

        }


        /*
         * -----------------------------------------------------
         * RUN RESPONSE
         * -----------------------------------------------------
         */

        handleRunResponse(
            data
        ) {

            /*
             * Pour rester compatible avec plusieurs formes
             * de réponses backend.
             *
             * Nous pourrons supprimer ces fallback
             * lorsque nous aurons figé le contrat PHP.
             */

            const execution =
                data.execution || data;


            /*
             * -------------------------------------------------
             * STEP INDEX
             * -------------------------------------------------
             */

            let stepIndex = null;


            /*
             * Format recommandé :
             *
             * step_index: 0
             */

            if (
                data.step_index !== undefined
            ) {

                stepIndex =
                    Utils.toInteger(
                        data.step_index
                    );

            }


            /*
             * Compatibilité :
             *
             * current_step: 1
             */

            if (
                stepIndex === null &&
                data.current_step !== undefined
            ) {

                stepIndex =
                    Utils.toInteger(
                        data.current_step
                    );


                if (
                    stepIndex !== null
                ) {

                    stepIndex--;

                }

            }


            /*
             * Compatibilité :
             *
             * execution.step: 1
             */

            if (
                stepIndex === null &&
                execution.step !== undefined
            ) {

                stepIndex =
                    Utils.toInteger(
                        execution.step
                    );


                if (
                    stepIndex !== null
                ) {

                    stepIndex--;

                }

            }


            /*
             * Dernier fallback :
             * état local.
             */

            if (
                stepIndex === null
            ) {

                stepIndex =
                    this.findCurrentStep();

            }


            /*
             * -------------------------------------------------
             * STATUS
             * -------------------------------------------------
             */

            const status =
                data.step_status ||
                data.status ||
                execution.step_status ||
                execution.status ||
                'running';


            /*
             * -------------------------------------------------
             * RESULT
             * -------------------------------------------------
             */

            const result =
                data.result ??
                data.data ??
                execution.result ??
                execution.data ??
                null;


            /*
             * -------------------------------------------------
             * MESSAGE
             * -------------------------------------------------
             */

            const message =
                data.message ||
                execution.message ||
                null;


            /*
             * -------------------------------------------------
             * FAILED
             * -------------------------------------------------
             */

            if (
                status === 'failed'
            ) {

                if (
                    stepIndex !== null
                ) {

                    this.renderer
                        .updateStep(
                            stepIndex,
                            'failed',
                            result,
                            message
                        );

                }


                this.state.status =
                    'failed';


                this.handleError(
                    execution.error ||
                    data.error ||
                    data.message ||
                    'Le step a échoué.'
                );


                return;

            }


            /*
             * -------------------------------------------------
             * WAITING HUMAN
             * -------------------------------------------------
             */

            if (
                status === 'waiting' ||
                status === 'waiting_human'
            ) {

                this.state.status =
                    'waiting_human';


                this.state.waitingForHuman =
                    true;


                this.state.currentStepIndex =
                    stepIndex;


                if (
                    stepIndex !== null
                ) {

                    this.renderer
                        .updateStep(
                            stepIndex,
                            'waiting',
                            result,
                            message
                        );


                    this.renderer
                        .showHumanValidation(
                            stepIndex
                        );

                }


                const completed =
                    this.getCompletedSteps(
                        data,
                        stepIndex
                    );


                this.state.completedSteps =
                    completed;


                this.renderer
                    .updateProgress(
                        completed,
                        this.state.steps.length
                    );


                return;

            }


            /*
             * -------------------------------------------------
             * STEP COMPLETED
             * -------------------------------------------------
             */

            if (
                stepIndex !== null
            ) {

                this.renderer
                    .updateStep(
                        stepIndex,
                        'completed',
                        result,
                        message
                    );

            }


            const completed =
                this.getCompletedSteps(
                    data,
                    stepIndex
                );


            this.state.completedSteps =
                completed;


            this.renderer
                .updateProgress(
                    completed,
                    this.state.steps.length
                );


            /*
             * -------------------------------------------------
             * FINAL
             * -------------------------------------------------
             */

            const isFinal =
                data.final === true ||
                status === 'completed' &&
                (
                    execution.status === 'completed' ||
                    completed >= this.state.steps.length
                );


            if (
                isFinal
            ) {

                this.state.status =
                    'completed';


                this.renderer
                    .showFinalResult(
                        result
                    );


                this.finish();


                return;

            }


            /*
             * -------------------------------------------------
             * NEXT STEP
             * -------------------------------------------------
             */

            setTimeout(
                () => {

                    this.run();

                },
                250
            );

        }


        /*
         * -----------------------------------------------------
         * HUMAN VALIDATION
         * -----------------------------------------------------
         */

        validate(
            approved
        ) {

            if (
                !this.state.executionId ||
                this.state.running ||
                !this.state.waitingForHuman
            ) {

                return;

            }


            this.state.running =
                true;


            const stepIndex =
                this.state.currentStepIndex;


            if (
                stepIndex !== null
            ) {

                this.renderer
                    .showValidationLoading(
                        stepIndex
                    );

            }


            Ajax
                .resumeExecution(
                    this.state.executionId,
                    approved
                )

                .done(
                    (response) => {

                        console.log(
                            '[AIPS] VALIDATE response:',
                            response
                        );


                        if (
                            !response ||
                            !response.success
                        ) {

                            this.handleError(
                                this.getErrorMessage(
                                    response,
                                    'Impossible de valider l’exécution.'
                                )
                            );

                            return;

                        }


                        const data =
                            response.data || {};


                        /*
                         * REFUS
                         */

                        if (
                            !approved
                        ) {

                            this.state.waitingForHuman =
                                false;


                            this.state.status =
                                'failed';


                            this.handleError(
                                data.message ||
                                'L’exécution a été arrêtée.'
                            );


                            return;

                        }


                        /*
                         * VALIDATION ACCEPTÉE
                         */

                        this.state.waitingForHuman =
                            false;


                        this.state.status =
                            'running';


                        /*
                         * Le step de validation est considéré
                         * comme terminé.
                         */

                        if (
                            stepIndex !== null
                        ) {

                            this.renderer
                                .updateStep(
                                    stepIndex,
                                    'completed'
                                );

                        }


                        /*
                         * On repart sur le RUN suivant.
                         */

                        setTimeout(
                            () => {

                                this.run();

                            },
                            150
                        );

                    }
                )

                .fail(
                    (xhr) => {

                        console.error(
                            '[AIPS] VALIDATE AJAX error:',
                            xhr
                        );


                        this.handleError(
                            'Une erreur est survenue pendant la validation.'
                        );

                    }
                )

                .always(
                    () => {

                        this.state.running =
                            false;

                    }
                );

        }


        /*
         * -----------------------------------------------------
         * CURRENT STEP
         * -----------------------------------------------------
         */

        findCurrentStep() {

            /*
             * On se base d'abord sur le state.
             */

            if (
                this.state.currentStepIndex !== null
            ) {

                return this.state.currentStepIndex;

            }


            /*
             * Sinon première étape non terminée.
             */

            const index =
                this.state.steps.findIndex(
                    (_, index) => {

                        const $step =
                            this.renderer.getStep(
                                index
                            );


                        return (
                            !$step.hasClass(
                                'is-completed'
                            ) &&
                            !$step.hasClass(
                                'is-failed'
                            )
                        );

                    }
                );


            return index === -1
                ? null
                : index;

        }


        /*
         * -----------------------------------------------------
         * COMPLETED STEPS
         * -----------------------------------------------------
         */

        getCompletedSteps(
            data,
            stepIndex
        ) {

            if (
                data.completed_steps !== undefined
            ) {

                return Utils.toInteger(
                    data.completed_steps,
                    0
                );

            }


            return stepIndex !== null
                ? stepIndex + 1
                : this.state.completedSteps;

        }

        /*
         * -----------------------------------------------------
         * ERROR
         * -----------------------------------------------------
         */
        getErrorMessage( response, fallback ) {
            if (response && response.data && response.data.message) {
                return response.data.message;
            }
            return fallback;
        }


        handleError( message ) {
            this.state.status = 'failed';

            this.state.running = false;

            this.state.waitingForHuman = false;

            this.renderer.showError( message );

            this.renderer.setLoading(false);

        }


        /*
         * -----------------------------------------------------
         * FINISH
         * -----------------------------------------------------
         */
        finish() {
            this.state.running = false;
            this.state.waitingForHuman = false;
            this.renderer.setLoading(false);
        }

    }

    /*
     * =========================================================
     * INITIALISATION AGENTS
     * =========================================================
     */
    function initAgents() {

        const $form = $('.aips-agent-form');

        if (!$form.length) { return; }

        const $result = $( '.aips-agent-result' );

        if (!$result.length) {
            return;
        }

        /*
         * Renderer
         */
        const renderer = new AgentRenderer($form, $result);

        /*
         * Controller
         */
        const execution = new ExecutionController($form, renderer);

        /*
         * -----------------------------------------------------
         * SUBMIT
         * -----------------------------------------------------
         */
        $form.on('submit', function (event) {
                event.preventDefault();
                if ( execution.state.running) {
                    return;
                }

                const $submit = $form.find('[type="submit"]');

                if ($submit.prop('disabled')) {
                    return;
                }

                /*
                 * FormData
                 */
                const formData = new FormData(this);
                const input = {};

                formData.forEach(function (value, key) {
                    input[key] = value;
                });

                /*
                 * CREATE
                 */
                execution.create(input);

            }
        );

        /*
         * -----------------------------------------------------
         * HUMAN VALIDATION
         * -----------------------------------------------------
         *
         * Event delegation car les boutons sont créés
         * dynamiquement après CREATE.
         */
        $result.on('click', '.aips-validate-execution',
            function () { execution.validate(true); }
        );

        $result.on('click', '.aips-reject-execution', function () {
            execution.validate( false);
        });

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
