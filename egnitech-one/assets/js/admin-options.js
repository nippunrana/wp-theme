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

    document.addEventListener('DOMContentLoaded', function () {
        initTabs();
        initMediaUploader();
        initReadingProgress();
        initScriptsManager();
        initRecaptcha();
        initSmtpToggle();
        initContactFormManager();
    });

    /**
     * Initialize SMTP Toggle Logic
     */
    function initSmtpToggle() {
        setupToggleVisibility('egnitech_one_smtp_enabled', 'egnitech-smtp-details');
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

    /**
     * Initialize reCAPTCHA Toggle Logic
     */
    function initRecaptcha() {
        setupToggleVisibility('egnitech_one_recaptcha_enabled', 'egnitech-recaptcha-keys-row');
    }

    /**
     * Initialize Contact Form Field Manager (Contact Tab)
     */
    function initContactFormManager() {
        const addBtn = document.getElementById('egnitech_add_contact_field_btn');
        const fieldsList = document.getElementById('egnitech_contact_fields_list');
        const hiddenInput = document.getElementById('egnitech_one_contact_form_fields');
        const form = document.querySelector('.egnitech-options-form');

        if (!addBtn || !fieldsList || !hiddenInput || !form) return;

        // Initialize shared accordion & delete logic
        initAccordionInteractions(fieldsList);
        addBtn.addEventListener('click', function () {
            const btn = this;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span class="dashicons dashicons-update egnitech-spin"></span> ' + (egnitechAdmin.i18n.loading || 'Loading...');
            btn.disabled = true;

            const formData = new FormData();
            formData.append('action', 'egnitech_one_get_new_contact_field_html');
            formData.append('nonce', egnitechAdmin.nonce);
            formData.append('field_id', Date.now());

            fetch(egnitechAdmin.ajaxUrl, {
                method: 'POST',
                body: formData
            })
                .then(r => r.json())
                .then(res => {
                    if (res.success && res.data && res.data.html) {
                        fieldsList.insertAdjacentHTML('beforeend', res.data.html);
                        const newItem = fieldsList.lastElementChild;
                        const labelInput = newItem.querySelector('.field-label-input');
                        if (labelInput) labelInput.focus();
                    } else {
                        alert(egnitechAdmin.i18n.error || 'Failed to add field.');
                    }
                })
                .catch(e => {
                    console.error(e);
                    alert('Error adding field.');
                })
                .finally(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                });
        });

        // Dynamic updates for highlights and headers
        fieldsList.addEventListener('input', function (e) {
            const item = e.target.closest('.egnitech-contact-field-item');
            if (!item) return;

            if (e.target.matches('.field-label-input')) {
                const nameEl = item.querySelector('.egnitech-script-name');
                if (nameEl) nameEl.textContent = e.target.value.trim() || 'Unnamed Field';
            }
        });

        fieldsList.addEventListener('change', function (e) {
            const item = e.target.closest('.egnitech-contact-field-item');
            if (!item) return;

            if (e.target.matches('.field-type-select')) {
                const badge = item.querySelector('.highlight-type');
                if (badge) {
                    badge.textContent = e.target.options[e.target.selectedIndex].text;
                }
            }
        });

        // Serialize fields on sumbit
        form.addEventListener('submit', function () {
            const fields = [];
            const items = fieldsList.querySelectorAll('.egnitech-contact-field-item');

            items.forEach(item => {
                const labelInp = item.querySelector('.field-label-input');
                const typeSel = item.querySelector('.field-type-select');
                const placeholdersInp = item.querySelector('.field-placeholder-input');
                const reqInp = item.querySelector('.field-required-toggle');

                if (labelInp && typeSel && placeholdersInp) {
                    fields.push({
                        id: item.dataset.id || Date.now(),
                        label: labelInp.value.trim(),
                        type: typeSel.value,
                        placeholder: placeholdersInp.value.trim(),
                        required: reqInp ? reqInp.checked : false
                    });
                }
            });

            hiddenInput.value = JSON.stringify(fields);
        });
    }

})();
