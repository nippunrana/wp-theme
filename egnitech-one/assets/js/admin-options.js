/**
 * EgniTech One — Theme Options Admin Script
 *
 * Handles tab switching and the media uploader for the dark mode logo.
 *
 * @package EgniTech_One
 * @since   1.1.0
 */
(function () {
    'use strict';

    function initAll() {
        initTabs();
        initMediaUploader();
        initReadingProgress();
        initScriptsManager();
        initSmtpToggle();
        initWooCommerceLayoutToggle();
        initFastCgiToggle();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }

    /**
     * Initialize SMTP Toggle Logic
     */
    function initSmtpToggle() {
        setupToggleVisibility('egnitech_one_smtp_enabled', 'egnitech-smtp-details');
    }

    /**
     * Initialize FastCGI Cache Toggle and Purge Method Logic
     */
    function initFastCgiToggle() {
        setupToggleVisibility('egnitech_one_fastcgi_cache_enabled', 'egnitech-fastcgi-details');

        var purgeMethod = document.getElementById('egnitech_one_fastcgi_purge_method');
        var pathRow = document.getElementById('egnitech-fastcgi-path-row');
        var httpRow = document.getElementById('egnitech-fastcgi-http-purge-row');

        if (purgeMethod && pathRow && httpRow) {
            purgeMethod.addEventListener('change', function () {
                if (this.value === 'filesystem') {
                    pathRow.style.display = '';
                    pathRow.style.animation = 'fadeUp 0.3s ease forwards';
                    httpRow.style.display = 'none';
                } else if (this.value === 'http_purge') {
                    pathRow.style.display = 'none';
                    httpRow.style.display = '';
                    httpRow.style.animation = 'fadeUp 0.3s ease forwards';
                }
            });
        }

        // Manual cache purge button handler
        var clearBtn = document.getElementById('egnitech-clear-fastcgi-cache-btn');
        var clearStatus = document.getElementById('egnitech-clear-cache-status');
        var sizeValSpan = document.getElementById('egnitech-fastcgi-cache-size-val');

        if (clearBtn && clearStatus) {
            clearBtn.addEventListener('click', function (e) {
                e.preventDefault();
                if (clearBtn.disabled) return;

                clearBtn.disabled = true;
                clearStatus.style.color = 'var(--egnitech-text-muted)';
                clearStatus.innerHTML = '<span class="dashicons dashicons-update spin" style="animation: spin 1s linear infinite; vertical-align: text-bottom; margin-right: 4px;"></span> ' + (egnitechAdmin.i18n.loading || 'Clearing…');

                // Add dynamic styling for rotation if not already in style
                if (!document.getElementById('egnitech-spin-style-admin')) {
                    var style = document.createElement('style');
                    style.id = 'egnitech-spin-style-admin';
                    style.innerHTML = '@keyframes spin { 100% { transform: rotate(360deg); } }';
                    document.head.appendChild(style);
                }

                var data = new URLSearchParams();
                data.append('action', 'egnitech_one_clear_fastcgi_cache');
                data.append('security', clearBtn.getAttribute('data-nonce'));

                fetch(egnitechAdmin.ajaxUrl, {
                    method: 'POST',
                    body: data,
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    }
                })
                .then(function (response) { return response.json(); })
                .then(function (res) {
                    clearBtn.disabled = false;
                    if (res.success) {
                        clearStatus.style.color = 'var(--egnitech-success)';
                        clearStatus.innerHTML = '<span class="dashicons dashicons-yes" style="vertical-align: text-bottom; color: var(--egnitech-success); margin-right: 4px;"></span> ' + res.data.message;
                        
                        // Update size display in page
                        if (sizeValSpan) {
                            sizeValSpan.textContent = res.data.formatted || '0 B';
                        }

                        // Update size display in Admin Bar if visible
                        var abSizeDisplay = document.querySelector('#wp-admin-bar-egnitech-fastcgi-cache-size-display .egnitech-ab-size');
                        if (abSizeDisplay) {
                            abSizeDisplay.textContent = res.data.formatted || '0 B';
                        }

                        setTimeout(function () {
                            clearStatus.innerHTML = '';
                        }, 5000);
                    } else {
                        clearStatus.style.color = 'var(--egnitech-danger)';
                        clearStatus.innerHTML = '<span class="dashicons dashicons-warning" style="vertical-align: text-bottom; color: var(--egnitech-danger); margin-right: 4px;"></span> ' + (res.data.message || 'Failed to clear cache.');
                    }
                })
                .catch(function () {
                    clearBtn.disabled = false;
                    clearStatus.style.color = 'var(--egnitech-danger)';
                    clearStatus.innerHTML = '<span class="dashicons dashicons-warning" style="vertical-align: text-bottom; color: var(--egnitech-danger); margin-right: 4px;"></span> Connection error.';
                });
            });
        }

        // Recalculate cache size button handler (in Theme Options page)
        var recalcSizeBtn = document.getElementById('egnitech-recalculate-fastcgi-cache-size-btn');

        if (recalcSizeBtn && sizeValSpan) {
            recalcSizeBtn.addEventListener('click', function (e) {
                e.preventDefault();
                if (recalcSizeBtn.disabled) return;

                recalcSizeBtn.disabled = true;
                var originalHtml = recalcSizeBtn.innerHTML;
                recalcSizeBtn.innerHTML = '<span class="dashicons dashicons-update spin" style="animation: spin 1s linear infinite; vertical-align: text-bottom; margin-right: 4px;"></span> ' + (egnitechAdmin.i18n.loading || 'Calculating…');

                // Add dynamic styling for rotation if not already in style
                if (!document.getElementById('egnitech-spin-style-admin')) {
                    var style = document.createElement('style');
                    style.id = 'egnitech-spin-style-admin';
                    style.innerHTML = '@keyframes spin { 100% { transform: rotate(360deg); } }';
                    document.head.appendChild(style);
                }

                var data = new URLSearchParams();
                data.append('action', 'egnitech_one_recalculate_cache_size');
                data.append('security', recalcSizeBtn.getAttribute('data-nonce'));

                fetch(egnitechAdmin.ajaxUrl, {
                    method: 'POST',
                    body: data,
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    }
                })
                .then(function (response) { return response.json(); })
                .then(function (res) {
                    recalcSizeBtn.disabled = false;
                    recalcSizeBtn.innerHTML = originalHtml;
                    if (res.success) {
                        // Update Options display
                        sizeValSpan.textContent = res.data.formatted;

                        // Update Admin Bar display if visible
                        var abSizeDisplay = document.querySelector('#wp-admin-bar-egnitech-fastcgi-cache-size-display .egnitech-ab-size');
                        if (abSizeDisplay) {
                            abSizeDisplay.textContent = res.data.formatted;
                        }
                    } else {
                        alert(res.data.message || 'Failed to recalculate size.');
                    }
                })
                .catch(function () {
                    recalcSizeBtn.disabled = false;
                    recalcSizeBtn.innerHTML = originalHtml;
                    alert('Connection error.');
                });
            });
        }
    }

    /**
     * Initialize WooCommerce Layout Toggle
     */
    function initWooCommerceLayoutToggle() {
        var layoutRadios = document.querySelectorAll('input[name="egnitech_one_woocommerce_gallery_layout"]');
        var thumbStyleRow = document.getElementById('egnitech-gallery-thumbnail-style-row');
        if (!layoutRadios.length || !thumbStyleRow) return;

        layoutRadios.forEach(function (radio) {
            radio.addEventListener('change', function () {
                if (this.value === 'custom') {
                    thumbStyleRow.style.display = '';
                    thumbStyleRow.style.animation = 'fadeUp 0.3s ease forwards';
                } else {
                    thumbStyleRow.style.display = 'none';
                }
            });
        });
    }

    /**
     * Shared logic for accordion headers and deletion
     */
    function initAccordionInteractions(containerList) {
        if (!containerList) return;

        containerList.addEventListener('click', function (e) {
            // Delete Item
            var deleteBtn = e.target.closest('.egnitech-script-delete');
            if (deleteBtn) {
                var deleteMsg = egnitechAdmin.i18n && egnitechAdmin.i18n.confirmDelete ? egnitechAdmin.i18n.confirmDelete : 'Are you sure you want to delete this item?';
                if (confirm(deleteMsg)) {
                    var item = deleteBtn.closest('.egnitech-script-item');
                    if (item) {
                        item.style.transition = 'all 0.3s ease';
                        item.style.opacity = '0';
                        item.style.transform = 'scale(0.95)';
                        setTimeout(function () {
                            item.remove();
                        }, 300);
                    }
                }
                return;
            }

            // Accordion Header Toggle
            var header = e.target.closest('.egnitech-script-header');
            if (header) {
                // Ignore clicks on inner actions container
                if (e.target.closest('.egnitech-script-actions')) return;

                var item = header.closest('.egnitech-script-item');
                var content = item.querySelector('.egnitech-script-content');
                var isExpanded = item.classList.contains('is-expanded');

                if (isExpanded) {
                    item.classList.remove('is-expanded');
                    if (content) content.style.display = 'none';
                } else {
                    item.classList.add('is-expanded');
                    if (content) {
                        content.style.display = 'block';
                        content.style.animation = 'fadeUp 0.3s ease forwards';
                    }
                }
            }
        });
    }

    /* ===== Tab Navigation ===== */
    function initTabs() {
        var tabs = document.querySelectorAll('.egnitech-options-tabs a');
        var panels = document.querySelectorAll('.egnitech-tab-panel');

        if (!tabs.length) return;

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function (e) {
                e.preventDefault();
                var target = this.getAttribute('href').substring(1);

                // Deactivate all.
                tabs.forEach(function (t) { t.classList.remove('active'); });
                panels.forEach(function (p) { p.classList.remove('active'); });

                // Activate clicked.
                this.classList.add('active');
                var panel = document.getElementById(target);
                if (panel) panel.classList.add('active');

                // Remember active tab.
                if (window.sessionStorage) {
                    sessionStorage.setItem('egnitech_active_tab', target);
                }
            });
        });

        // Restore last active tab.
        var saved = window.sessionStorage ? sessionStorage.getItem('egnitech_active_tab') : null;
        if (saved) {
            var savedTab = document.querySelector('.egnitech-options-tabs a[href="#' + saved + '"]');
            if (savedTab) {
                savedTab.click();
                return;
            }
        }

        // Default: activate first tab.
        if (tabs[0]) tabs[0].click();
    }

    /* ===== Media Uploader (Logo Uploaders) ===== */
    function initMediaUploader() {
        // Dark Mode Logo
        setupLogoUploader(
            'egnitech_select_logo',
            'egnitech_remove_logo',
            'egnitech_one_dark_logo_url',
            'egnitech-dark-logo-preview',
            egnitechAdmin.selectLogoTitle || 'Select Dark Mode Logo',
            false // Store URL
        );

        // Light Mode Logo
        setupLogoUploader(
            'egnitech_select_light_logo',
            'egnitech_remove_light_logo',
            'egnitech_one_light_logo_id',
            'egnitech-light-logo-preview',
            'Select Light Mode Logo',
            true // Store ID
        );
    }

    /**
     * Setup helper for logo uploaders
     */
    function setupLogoUploader(selectId, removeId, inputId, previewId, title, storeId) {
        var selectBtn = document.getElementById(selectId);
        var removeBtn = document.getElementById(removeId);
        var inputVal = document.getElementById(inputId);
        var preview = document.getElementById(previewId);

        if (!selectBtn || !inputVal) return;

        var frame;

        selectBtn.addEventListener('click', function (e) {
            e.preventDefault();

            if (frame) {
                frame.open();
                return;
            }

            frame = wp.media({
                title: title,
                button: { text: egnitechAdmin.useLogoText || 'Use this logo' },
                multiple: false
            });

            frame.on('select', function () {
                var attachment = frame.state().get('selection').first().toJSON();
                inputVal.value = storeId ? attachment.id : attachment.url;
                if (preview) {
                    preview.src = attachment.url;
                    var previewContainer = preview.closest('.egnitech-logo-preview');
                    if (previewContainer) {
                        previewContainer.style.display = 'block';
                    }
                }
                if (removeBtn) removeBtn.style.display = '';
            });

            frame.open();
        });

        if (removeBtn) {
            removeBtn.addEventListener('click', function (e) {
                e.preventDefault();
                inputVal.value = storeId ? '0' : '';
                if (preview) {
                    preview.src = '';
                    var previewContainer = preview.closest('.egnitech-logo-preview');
                    if (previewContainer) {
                        previewContainer.style.display = 'none';
                    }
                }
                if (removeBtn) removeBtn.style.display = 'none';
            });
        }
    }


    /**
     * Helper to setup visibility toggle between a checkbox and a target container
     */
    function setupToggleVisibility(toggleId, targetId) {
        var toggle = document.getElementById(toggleId);
        var target = document.getElementById(targetId);

        if (!toggle || !target) return;

        toggle.addEventListener('change', function () {
            if (this.checked) {
                target.style.display = '';
                target.style.animation = 'fadeUp 0.3s ease forwards';
            } else {
                target.style.display = 'none';
            }
        });
    }

    /**
     * Initialize Reading Progress Logic
     */
    function initReadingProgress() {
        setupToggleVisibility('egnitech_one_reading_progress', 'reading-progress-height-row');

        var rangeInput = document.getElementById('egnitech_one_reading_progress_height');
        var rangeValue = document.querySelector('.egnitech-range-value span');

        // Range slider value update.
        if (rangeInput && rangeValue) {
            rangeInput.addEventListener('input', function () {
                rangeValue.textContent = this.value;
            });
        }
    }

    /* ===== Scripts Manager (Advanced Tab) ===== */
    function initScriptsManager() {
        var addBtn = document.getElementById('egnitech_add_script_btn');
        var scriptsList = document.getElementById('egnitech_scripts_list');

        if (!addBtn || !scriptsList) return;

        if (!addBtn || !scriptsList) return;

        // Initialize shared accordion & delete logic
        initAccordionInteractions(scriptsList);

        // Status Toggle logic specific to scripts
        scriptsList.addEventListener('click', function (e) {
            var toggle = e.target.closest('.egnitech-script-status input');
            if (toggle) {
                var item = toggle.closest('.egnitech-script-item');
                if (item) {
                    item.classList.toggle('is-inactive', !toggle.checked);
                }
            }
        });

        // Add Script
        addBtn.addEventListener('click', function () {
            var btn = this;
            var originalText = btn.innerHTML;
            btn.innerHTML = '<span class="dashicons dashicons-update egnitech-spin"></span> ' + (egnitechAdmin.i18n.loading || 'Loading...');
            btn.disabled = true;

            var formData = new FormData();
            formData.append('action', 'egnitech_one_get_new_script_html');
            formData.append('nonce', egnitechAdmin.nonce);
            formData.append('script_id', Date.now());

            fetch(egnitechAdmin.ajaxUrl, {
                method: 'POST',
                body: formData
            })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res.success && res.data && res.data.html) {
                        scriptsList.insertAdjacentHTML('beforeend', res.data.html);

                        // Auto focus the name input of the newly added item
                        var newItems = scriptsList.querySelectorAll('.egnitech-script-item');
                        var lastItem = newItems[newItems.length - 1];
                        var nameInput = lastItem.querySelector('.script-name-input');
                        if (nameInput) {
                            nameInput.focus();
                        }
                    } else {
                        alert(egnitechAdmin.i18n.error || 'Failed to add script.');
                    }
                })
                .catch(function (e) {
                    alert(egnitechAdmin.i18n.error || 'Failed to add script.');
                    console.error(e);
                })
                .finally(function () {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                });
        });

        // Add dynamic name updates for all items
        scriptsList.addEventListener('input', function (e) {
            if (e.target.matches('.script-name-input')) {
                var newName = e.target.value.trim() || 'Unnamed Script';
                var item = e.target.closest('.egnitech-script-item');
                if (item) {
                    var nameEl = item.querySelector('.egnitech-script-name');
                    if (nameEl) nameEl.textContent = newName;
                }
            }
        });

        // Handle radio changes: Update highlights and toggle disclaimer
        scriptsList.addEventListener('change', function (e) {
            if (!e.target.matches('input[type="radio"]')) return;

            var item = e.target.closest('.egnitech-script-item');
            if (!item) return;

            var value = e.target.value;
            var name = e.target.name;

            // 1. Update Highlights
            if (name.indexOf('location') !== -1) {
                var badgeLocation = item.querySelector('.highlight-location');
                if (badgeLocation) {
                    badgeLocation.textContent = value.charAt(0).toUpperCase() + value.slice(1);
                }
            } else if (name.indexOf('load') !== -1) {
                var badgeLoad = item.querySelector('.highlight-load');
                if (badgeLoad) {
                    var labels = {
                        'normal': 'Normal',
                        'after_dom': 'After DOM',
                        'delayed_3s': 'Delayed'
                    };
                    badgeLoad.textContent = labels[value] || value;
                }

                // 2. Toggle delayed disclaimer
                var disclaimer = item.querySelector('.egnitech-delayed-disclaimer');
                if (disclaimer) {
                    if (value === 'delayed_3s') {
                        disclaimer.style.display = 'block';
                        disclaimer.style.animation = 'fadeUp 0.3s ease forwards';
                    } else {
                        disclaimer.style.display = 'none';
                    }
                }
            }
        });

        // Initialize inactive states
        var toggles = scriptsList.querySelectorAll('.egnitech-script-status input');
        toggles.forEach(function (toggle) {
            if (!toggle.checked) {
                var item = toggle.closest('.egnitech-script-item');
                if (item) item.classList.add('is-inactive');
            }
        });

        // Serialize scripts on form sumbit
        var form = document.querySelector('.egnitech-options-form');
        var hiddenInput = document.getElementById('egnitech_one_custom_scripts');

        if (form && hiddenInput) {
            form.addEventListener('submit', function () {
                var scripts = [];
                var items = scriptsList.querySelectorAll('.egnitech-script-item');

                items.forEach(function (item) {
                    var nameInput = item.querySelector('.script-name-input');
                    var locChecked = item.querySelector('input[type="radio"][name^="location"]:checked');
                    var loadChecked = item.querySelector('input[type="radio"][name^="load"]:checked');
                    var codeInput = item.querySelector('.egnitech-code-field');
                    var statusInput = item.querySelector('.egnitech-script-status input[type="checkbox"]');

                    if (nameInput && locChecked && loadChecked && codeInput) {
                        scripts.push({
                            name: nameInput.value.trim(),
                            location: locChecked.value,
                            load_type: loadChecked.value,
                            code: codeInput.value,
                            is_active: statusInput ? statusInput.checked : false
                        });
                    }
                });

                hiddenInput.value = JSON.stringify(scripts);
            });
        }
    }

})();
