/* global AIPS, jQuery, wp */
(function ($) {
    'use strict';

    var i18n = (AIPS && AIPS.i18n) || {};

    function post(action, data) {
        return $.post(AIPS.ajaxUrl, $.extend({ action: action, nonce: AIPS.nonce }, data));
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

        $(document).on('click', '.aips-delete-prompt', function () {
            if (!window.confirm(i18n.confirmDelete)) { return; }
            var id = $(this).data('id');
            post('aips_delete_prompt', { id: id }).done(function () { window.location.reload(); });
        });

        $(document).on('click', '.aips-toggle-prompt', function () {
            var id = $(this).data('id');
            post('aips_toggle_prompt', { id: id }).done(function () { window.location.reload(); });
        });

        $form.on('submit', function (e) {
            e.preventDefault();
            var data = {
                id: $('#aips-prompt-id').val(),
                name: $('#aips-prompt-name-input').val(),
                description: $('#aips-prompt-description').val(),
                content: $('#aips-prompt-content-input').val(),
                is_active: $('#aips-prompt-active').is(':checked') ? 1 : 0
            };
            post('aips_save_prompt', data).done(function (res) {
                if (res && res.success) { window.location.reload(); }
                else { window.alert(res.data.message || 'Erreur'); }
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

        $(document).on('click', '.aips-delete-key', function () {
            if (!window.confirm(i18n.confirmDelete)) { return; }
            post('aips_delete_api_key', { id: $(this).data('id') }).done(function () { window.location.reload(); });
        });

        $(document).on('click', '.aips-toggle-key', function () {
            post('aips_toggle_api_key', { id: $(this).data('id') }).done(function () { window.location.reload(); });
        });

        $form.on('submit', function (e) {
            e.preventDefault();
            var data = {
                id: $('#aips-key-id').val(),
                provider: $('#aips-key-provider').val(),
                label: $('#aips-key-label').val(),
                api_key: $('#aips-key-value').val(),
                model: $('#aips-key-model').val(),
                endpoint: $('#aips-key-endpoint').val(),
                priority: $('#aips-key-priority').val(),
                is_active: $('#aips-key-active').is(':checked') ? 1 : 0
            };
            post('aips_save_api_key', data).done(function (res) {
                if (res && res.success) { window.location.reload(); }
                else { window.alert(res.data.message || 'Erreur'); }
            });
        });
    }

    $(function () {
        initMediaPickers();
        initTabsAndSource();
        initGenerate();
        initImport();
        initPrompts();
        initKeys();
    });
})(jQuery);
