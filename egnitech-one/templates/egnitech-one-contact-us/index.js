/**
 * Contact Form Interaction Script
 */

document.addEventListener('DOMContentLoaded', () => {
    const contactData = window.egnitechContactForm || {};
    const form = document.getElementById('contact-form');
    const feedback = document.getElementById('form-feedback');

    if (!form || !feedback) return;

    form.addEventListener('submit', (e) => {
        e.preventDefault();

        // Basic validation - check all required fields
        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;
        requiredFields.forEach(field => {
            if (!field.value.trim()) isValid = false;
        });

        if (!isValid) {
            showFeedback(contactData.i18n?.fillAll || 'Please fill in all required fields.', 'error');
            return;
        }

        // Send form data via AJAX
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.querySelector('.btn-text').textContent;

        submitBtn.disabled = true;
        submitBtn.querySelector('.btn-text').textContent = contactData.i18n?.sending || 'Sending...';

        const formData = new FormData(form);
        formData.append('action', 'egnitech_contact_submit');
        
        // Add nonce and other data from window.egnitechContactForm
        if (contactData.nonce) {
            formData.append('nonce', contactData.nonce);
        }

        fetch(contactData.ajaxUrl || '/wp-admin/admin-ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Hide form on success
                form.style.display = 'none';
                showFeedback(data.data.message, 'success');
                if (typeof grecaptcha !== 'undefined') {
                    grecaptcha.reset();
                }
            } else {
                showFeedback(data.data.message || contactData.i18n?.error || 'An error occurred. Please try again.', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showFeedback(contactData.i18n?.error || 'An error occurred. Please try again.', 'error');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.querySelector('.btn-text').textContent = originalBtnText;
            feedback.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });
    });

    function showFeedback(message, type) {
        feedback.textContent = message;
        feedback.className = `form-feedback ${type}`;
        feedback.style.display = 'block';

        if (type === 'success') {
            // Keep success message visible, maybe add a wrapper class for styling
            const wrapper = form.closest('.egnitech-contact-form-wrapper');
            if (wrapper) {
                wrapper.classList.add('submission-success');
            }
        }
    }
});
