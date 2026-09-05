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

    function initAgents() {

        var $form = $('.aips-agent-form');

        if (!$form.length) {
            return;
        }

        var $result = $form.find('.aips-agent-result');

        /**
         * Escape HTML.
         */
        function escapeHtml(value) {

            if (value === null || value === undefined) {
                return '';
            }

            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        /**
         * Try to pretty print an object.
         */
        function formatValue(value) {

            if (value === null || value === undefined) {
                return '';
            }

            if (typeof value === 'object') {
                return escapeHtml(
                    JSON.stringify(value, null, 2)
                );
            }

            return escapeHtml(value);
        }

        /**
         * Reset execution UI.
         */
        function resetResult() {

            $result
                .removeClass(
                    'is-running is-completed is-failed is-waiting'
                )
                .html('');
        }

        /**
         * Render the step list.
         */
        function renderSteps(steps, currentIndex) {

            if (!Array.isArray(steps)) {
                return;
            }

            var html = '';

            html += '<div class="aips-execution">';

            html += '<div class="aips-execution-header">';
            html += '<h3>Progression</h3>';
            html += '</div>';

            html += '<div class="aips-steps">';

            steps.forEach(function (step, index) {

                var state = 'pending';

                if (index < currentIndex) {
                    state = 'completed';
                }
                else if (index === currentIndex) {
                    state = 'running';
                }

                html += '<div class="aips-step aips-step--' + state + '"';
                html += ' data-step-index="' + index + '">';

                html += '<div class="aips-step-indicator">';

                if (state === 'completed') {
                    html += '<span class="aips-step-icon">✓</span>';
                }
                else if (state === 'running') {
                    html += '<span class="aips-step-icon">';
                    html += '<span class="aips-spinner"></span>';
                    html += '</span>';
                }
                else {
                    html += '<span class="aips-step-number">';
                    html += (index + 1);
                    html += '</span>';
                }

                html += '</div>';

                html += '<div class="aips-step-content">';

                html += '<div class="aips-step-label">';
                html += escapeHtml(step.label || step.id);
                html += '</div>';

                html += '<div class="aips-step-status">';

                if (state === 'completed') {
                    html += 'Terminé';
                }
                else if (state === 'running') {
                    html += 'En cours…';
                }
                else {
                    html += 'En attente';
                }

                html += '</div>';

                html += '</div>';
                html += '</div>';
            });

            html += '</div>';
            html += '</div>';

            $result.html(html);
        }

        /**
         * Update the current step in the step list.
         */
        function updateStepState(stepIndex, status) {

            var $steps = $result.find('.aips-step');

            $steps.each(function (index) {

                var $step = $(this);

                $step.removeClass(
                    'aips-step--pending ' +
                    'aips-step--running ' +
                    'aips-step--completed ' +
                    'aips-step--failed ' +
                    'aips-step--waiting'
                );

                if (index < stepIndex) {

                    $step.addClass(
                        'aips-step--completed'
                    );

                    $step.find('.aips-step-status')
                        .text('Terminé');

                    $step.find('.aips-step-icon')
                        .remove();

                    $step.find('.aips-step-indicator')
                        .html(
                            '<span class="aips-step-icon">✓</span>'
                        );

                    return;
                }

                if (index > stepIndex) {

                    $step.addClass(
                        'aips-step--pending'
                    );

                    $step.find('.aips-step-status')
                        .text('En attente');

                    return;
                }

                if (status === 'completed') {

                    $step.addClass(
                        'aips-step--completed'
                    );

                    $step.find('.aips-step-status')
                        .text('Terminé');

                    $step.find('.aips-step-indicator')
                        .html(
                            '<span class="aips-step-icon">✓</span>'
                        );

                }
                else if (status === 'failed') {

                    $step.addClass(
                        'aips-step--failed'
                    );

                    $step.find('.aips-step-status')
                        .text('Erreur');

                    $step.find('.aips-step-indicator')
                        .html(
                            '<span class="aips-step-icon">!</span>'
                        );

                }
                else if (status === 'waiting') {

                    $step.addClass(
                        'aips-step--waiting'
                    );

                    $step.find('.aips-step-status')
                        .text('Validation requise');

                    $step.find('.aips-step-indicator')
                        .html(
                            '<span class="aips-step-icon">?</span>'
                        );

                }
                else {

                    $step.addClass(
                        'aips-step--running'
                    );

                    $step.find('.aips-step-status')
                        .text('En cours…');

                    $step.find('.aips-step-indicator')
                        .html(
                            '<span class="aips-step-icon">' +
                            '<span class="aips-spinner"></span>' +
                            '</span>'
                        );
                }
            });
        }

        /**
         * Render the result of one step.
         */
        function renderStepResult(step, data) {

            if (!step) {
                return;
            }

            var html = '';

            html += '<div class="aips-step-result">';
            html += '<div class="aips-step-result-header">';

            html += '<span class="aips-step-result-number">';
            html += 'Étape ' + (Number(step.index) + 1);
            html += '</span>';

            html += '<h4>';
            html += escapeHtml(step.label || step.id);
            html += '</h4>';

            html += '</div>';

            if (data && Object.keys(data).length) {

                html += '<div class="aips-step-result-body">';

                Object.keys(data).forEach(function (key) {

                    var value = data[key];

                    /*
                     * Pour le BuildPromptStep, on veut afficher
                     * clairement le prompt généré.
                     */
                    if (
                        key === 'prompt' ||
                        key === 'generated_prompt'
                    ) {

                        html += '<div class="aips-result-field">';
                        html += '<div class="aips-result-label">';
                        html += escapeHtml(key);
                        html += '</div>';

                        html += '<pre class="aips-result-code">';
                        html += formatValue(value);
                        html += '</pre>';

                        html += '</div>';

                        return;
                    }

                    /*
                     * Les réponses AI peuvent être longues.
                     */
                    if (
                        key === 'ai_response' ||
                        key === 'content'
                    ) {

                        html += '<div class="aips-result-field">';
                        html += '<div class="aips-result-label">';
                        html += escapeHtml(key);
                        html += '</div>';

                        html += '<div class="aips-result-text">';
                        html += formatValue(value);
                        html += '</div>';

                        html += '</div>';

                        return;
                    }

                    html += '<div class="aips-result-field">';
                    html += '<div class="aips-result-label">';
                    html += escapeHtml(key);
                    html += '</div>';

                    html += '<div class="aips-result-value">';
                    html += formatValue(value);
                    html += '</div>';

                    html += '</div>';
                });

                html += '</div>';
            }

            html += '</div>';

            $result.find('.aips-step-results').append(html);
        }

        /**
         * Render human validation.
         */
        function renderHumanValidation(execution) {

            var html = '';

            html += '<div class="aips-human-validation">';

            html += '<div class="aips-human-validation-icon">';
            html += '✓';
            html += '</div>';

            html += '<div class="aips-human-validation-content">';

            html += '<h3>';
            html += 'Validation requise';
            html += '</h3>';

            html += '<p>';
            html += 'Le traitement est en attente de votre validation.';
            html += '</p>';

            html += '<div class="aips-human-validation-actions">';

            html += '<button type="button" ';
            html += 'class="button button-primary aips-validate-execution" ';
            html += 'data-execution-id="' +
                escapeHtml(execution.execution_id) +
                '">';
            html += 'Valider et continuer';
            html += '</button>';

            html += '<button type="button" ';
            html += 'class="button aips-reject-execution" ';
            html += 'data-execution-id="' +
                escapeHtml(execution.execution_id) +
                '">';
            html += 'Refuser';
            html += '</button>';

            html += '</div>';

            html += '</div>';
            html += '</div>';

            $result.append(html);
        }

        /**
         * Render final result.
         */
        function renderCompleted(execution) {

            $result
                .removeClass('is-running is-waiting is-failed')
                .addClass('is-completed');

            var html = '';

            html += '<div class="aips-execution-final">';
            html += '<div class="aips-execution-final-icon">✓</div>';

            html += '<div>';
            html += '<h3>Article généré avec succès</h3>';
            html += '<p>';
            html += 'Toutes les étapes ont été exécutées.';
            html += '</p>';
            html += '</div>';

            html += '</div>';

            /*
             * On affiche les données finales si elles existent.
             */
            if (
                execution.data &&
                Object.keys(execution.data).length
            ) {

                html += '<div class="aips-final-data">';
                html += '<h4>Résultat</h4>';

                Object.keys(execution.data).forEach(function (key) {

                    var value = execution.data[key];

                    html += '<div class="aips-result-field">';
                    html += '<div class="aips-result-label">';
                    html += escapeHtml(key);
                    html += '</div>';

                    html += '<div class="aips-result-value">';
                    html += formatValue(value);
                    html += '</div>';

                    html += '</div>';
                });

                html += '</div>';
            }

            $result.append(html);
        }

        /**
         * Render execution error.
         */
        function renderError(message) {

            $result
                .removeClass(
                    'is-running is-waiting is-completed'
                )
                .addClass('is-failed');

            var html = '';

            html += '<div class="aips-execution-error">';

            html += '<div class="aips-execution-error-icon">';
            html += '!';
            html += '</div>';

            html += '<div>';
            html += '<h3>Une erreur est survenue</h3>';
            html += '<p>';
            html += escapeHtml(
                message || 'Une erreur inconnue est survenue.'
            );
            html += '</p>';
            html += '</div>';

            html += '</div>';

            $result.append(html);
        }

        /**
         * Run exactly one step.
         */
        function runStep(executionId) {

            return post(
                'my_ai_agent_run_execution',
                {
                    execution_id: executionId
                }
            );
        }

        /**
         * Process the response of one step.
         */
        function processStepResponse(res, executionId) {

            if (
                !res ||
                !res.success ||
                !res.data
            ) {

                renderError(
                    res &&
                    res.data &&
                    res.data.message
                        ? res.data.message
                        : 'Réponse AJAX invalide.'
                );

                return;
            }

            var data = res.data;

            /*
             * Compatibilité avec plusieurs structures possibles.
             */
            var step = data.step || null;

            var status = data.status || 'running';

            /*
             * Si le backend renvoie directement l'étape courante.
             */
            if (step) {

                var stepIndex = Number(
                    step.index !== undefined
                        ? step.index
                        : data.step_index || 0
                );

                updateStepState(
                    stepIndex,
                    step.status || status
                );

                renderStepResult(
                    step,
                    step.result || step.data || {}
                );
            }

            /*
             * WAITING HUMAN
             */
            if (
                status === 'waiting_human' ||
                status === 'waiting'
            ) {

                $result
                    .removeClass(
                        'is-running is-completed is-failed'
                    )
                    .addClass('is-waiting');

                renderHumanValidation({
                    execution_id: executionId
                });

                return;
            }

            /*
             * FAILED
             */
            if (status === 'failed') {

                renderError(
                    data.error ||
                    data.message ||
                    'Le step a échoué.'
                );

                return;
            }

            /*
             * COMPLETED
             */
            if (status === 'completed') {

                renderCompleted({
                    execution_id: executionId,
                    data: data.data || {}
                });

                return;
            }

            /*
             * RUNNING
             *
             * On lance automatiquement le step suivant.
             */
            if (status === 'running') {

                $result.addClass('is-running');

                window.setTimeout(function () {

                    runStep(executionId)
                        .done(function (nextResponse) {

                            processStepResponse(
                                nextResponse,
                                executionId
                            );

                        })
                        .fail(function (xhr) {

                            var message =
                                'Une erreur est survenue pendant l’exécution.';

                            if (
                                xhr.responseJSON &&
                                xhr.responseJSON.data &&
                                xhr.responseJSON.data.message
                            ) {
                                message =
                                    xhr.responseJSON.data.message;
                            }

                            renderError(message);
                        });

                }, 150);

                return;
            }
        }

        /**
         * Start execution.
         */
        function startExecution(input, $submitButton) {

            resetResult();

            $result
                .addClass('is-running');

            /*
             * Première requête :
             *
             * création de l'exécution + récupération
             * de la définition des steps.
             */
            post(
                'my_ai_agent_create_execution',
                input
            )
                .done(function (res) {

                    console.log(
                        'Execution created:',
                        res
                    );

                    if (
                        !res ||
                        !res.success ||
                        !res.data
                    ) {

                        renderError(
                            res &&
                            res.data &&
                            res.data.message
                                ? res.data.message
                                : 'Impossible de démarrer l’exécution.'
                        );

                        $submitButton
                            .prop('disabled', false);

                        return;
                    }

                    var data = res.data;

                    var executionId =
                        data.execution_id;

                    if (!executionId) {

                        renderError(
                            'Identifiant d’exécution manquant.'
                        );

                        $submitButton
                            .prop('disabled', false);

                        return;
                    }

                    /*
                     * Afficher les steps AVANT de commencer.
                     */
                    renderSteps(
                        data.steps || [],
                        0
                    );

                    /*
                     * Conteneur des résultats individuels.
                     */
                    $result.append(
                        '<div class="aips-step-results"></div>'
                    );

                    /*
                     * L'exécution est créée.
                     * Maintenant on lance le premier step.
                     */
                    runStep(executionId)
                        .done(function (runResponse) {

                            processStepResponse(
                                runResponse,
                                executionId
                            );

                        })
                        .fail(function (xhr) {

                            var message =
                                'Impossible de lancer le premier step.';

                            if (
                                xhr.responseJSON &&
                                xhr.responseJSON.data &&
                                xhr.responseJSON.data.message
                            ) {
                                message =
                                    xhr.responseJSON.data.message;
                            }

                            renderError(message);
                        })
                        .always(function () {

                            $submitButton
                                .prop('disabled', false);
                        });
                })
                .fail(function (xhr) {

                    console.log(
                        'AJAX error:',
                        xhr
                    );

                    var message =
                        'Une erreur est survenue.';

                    if (
                        xhr.responseJSON &&
                        xhr.responseJSON.data &&
                        xhr.responseJSON.data.message
                    ) {
                        message =
                            xhr.responseJSON.data.message;
                    }

                    renderError(message);

                    $submitButton
                        .prop('disabled', false);
                });
        }

        /**
         * Submit form.
         */
        $form.on('submit', function (e) {

            e.preventDefault();

            var $submitButton =
                $form.find('[type="submit"]');

            if ($submitButton.prop('disabled')) {
                return;
            }

            $form.addClass('is-loading');

            $submitButton.prop(
                'disabled',
                true
            );

            var formData =
                new FormData(this);

            var input = {};

            formData.forEach(function (value, key) {
                input[key] = value;
            });

            startExecution(
                input,
                $submitButton
            );
        });

        /**
         * Human validation.
         *
         * Pour l'instant on utilise resume().
         */
        $result.on(
            'click',
            '.aips-validate-execution',
            function () {

                var $button = $(this);

                var executionId =
                    $button.data('execution-id');

                if (!executionId) {
                    return;
                }

                $button.prop(
                    'disabled',
                    true
                );

                post(
                    'my_ai_agent_resume_execution',
                    {
                        execution_id: executionId
                    }
                )
                    .done(function (res) {

                        if (
                            !res ||
                            !res.success
                        ) {

                            renderError(
                                res &&
                                res.data &&
                                res.data.message
                                    ? res.data.message
                                    : 'Impossible de reprendre l’exécution.'
                            );

                            return;
                        }

                        /*
                         * On retire le bloc de validation.
                         */
                        $result
                            .find('.aips-human-validation')
                            .remove();

                        $result
                            .removeClass('is-waiting')
                            .addClass('is-running');

                        /*
                         * On reprend avec le step suivant.
                         */
                        runStep(executionId)
                            .done(function (runResponse) {

                                processStepResponse(
                                    runResponse,
                                    executionId
                                );

                            })
                            .fail(function () {

                                renderError(
                                    'Impossible de reprendre l’exécution.'
                                );
                            });
                    })
                    .fail(function () {

                        renderError(
                            'Une erreur est survenue pendant la validation.'
                        );
                    });
            }
        );

        /**
         * Human rejection.
         *
         * Pour l'instant on arrête simplement l'exécution
         * côté interface. On pourra ensuite ajouter
         * un vrai endpoint reject.
         */
        $result.on(
            'click',
            '.aips-reject-execution',
            function () {

                var $button = $(this);

                $button.prop(
                    'disabled',
                    true
                );

                $result
                    .find('.aips-human-validation')
                    .html(
                        '<div class="aips-execution-error">' +
                        '<div class="aips-execution-error-icon">!</div>' +
                        '<div>' +
                        '<h3>Validation refusée</h3>' +
                        '<p>L’exécution a été arrêtée.</p>' +
                        '</div>' +
                        '</div>'
                    );
            }
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
