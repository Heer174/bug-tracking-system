<?php
/**
 * Bug Tracking System - Layout Footer
 */
?>
        </main>

        <footer style="padding: 20px 36px; border-top: 1px solid var(--slate-200); background: #ffffff; font-size: 0.82rem; color: var(--slate-500); display: flex; justify-content: space-between; align-items: center; flex-wrap: gap: 10px;">
            <div>
                &copy; <?= date('Y'); ?> <strong><?= APP_NAME; ?></strong> — Software Engineering Mini-Project
            </div>
            <div>
                Built with PHP, MySQL &amp; Vanilla CSS/JS
            </div>
        </footer>
    </div>
</div>

<!-- Image Lightbox Modal for Screenshots -->
<div id="lightbox-modal" class="modal-overlay" role="dialog" aria-modal="true">
    <div class="modal-box" style="max-width: 900px; background: transparent; box-shadow: none;">
        <div style="text-align: right; margin-bottom: 8px;">
            <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('lightbox-modal')" style="background: rgba(15,23,42,0.8); color: #fff; border: none;">
                Close &times;
            </button>
        </div>
        <div style="text-align: center;">
            <img id="lightbox-image" src="" alt="Screenshot Enlarged" style="max-width: 100%; max-height: 80vh; border-radius: var(--radius-md); box-shadow: 0 20px 25px -5px rgba(0,0,0,0.5); border: 2px solid #334155;">
        </div>
    </div>
</div>

<script src="<?= BASE_URL; ?>/assets/js/main.js"></script>
<script src="<?= BASE_URL; ?>/assets/js/charts.js"></script>
</body>
</html>
