/**
 * Bug Tracking System (BugTrace) - Main Interactive Logic
 */

document.addEventListener('DOMContentLoaded', () => {
    // --- Mobile Sidebar Toggle ---
    const sidebar = document.querySelector('.app-sidebar');
    const mobileToggle = document.querySelector('.mobile-toggle');
    
    if (mobileToggle && sidebar) {
        mobileToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            sidebar.classList.toggle('open');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (sidebar.classList.contains('open') && !sidebar.contains(e.target) && e.target !== mobileToggle) {
                sidebar.classList.remove('open');
            }
        });
    }

    // --- Auto-dismiss Flash Alerts after 6 seconds ---
    const flashAlerts = document.querySelectorAll('.alert');
    flashAlerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => alert.remove(), 400);
        }, 6000);
    });

    // --- Modal Dialog Helpers ---
    window.openModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    };

    window.closeModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    };

    // Close modal on click outside modal-box
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    });

    // --- Image Lightbox for Screenshots ---
    const screenshotImages = document.querySelectorAll('.lightbox-trigger');
    const lightboxModal = document.getElementById('lightbox-modal');
    const lightboxImg = document.getElementById('lightbox-image');

    screenshotImages.forEach(img => {
        img.addEventListener('click', () => {
            if (lightboxModal && lightboxImg) {
                lightboxImg.src = img.src;
                lightboxModal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        });
    });

    // --- Confirm Destructive Actions ---
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', (e) => {
            const message = el.getAttribute('data-confirm') || 'Are you sure you want to perform this action?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });

    // --- Quick Demo Credential Autofill Helper ---
    window.fillCredentials = function(email, password) {
        const emailInput = document.getElementById('email');
        const passInput = document.getElementById('password');
        if (emailInput && passInput) {
            emailInput.value = email;
            passInput.value = password;
            // Visual highlight
            emailInput.style.backgroundColor = '#eff6ff';
            passInput.style.backgroundColor = '#eff6ff';
            setTimeout(() => {
                emailInput.style.backgroundColor = '';
                passInput.style.backgroundColor = '';
            }, 500);
        }
    };

    // --- Screenshot preview in bug create form ---
    const screenshotInput = document.getElementById('screenshot');
    const previewContainer = document.getElementById('screenshot-preview-container');
    const previewImage = document.getElementById('screenshot-preview-image');

    if (screenshotInput && previewContainer && previewImage) {
        screenshotInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size exceeds 5MB limit. Please select a smaller image.');
                    this.value = '';
                    previewContainer.style.display = 'none';
                    return;
                }
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    previewContainer.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                previewContainer.style.display = 'none';
            }
        });
    }
});
