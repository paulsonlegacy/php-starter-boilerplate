<?php
/*
 * Flash Message Component
 *
 * Renders the session flash message if one is present, then it is
 * cleared automatically by unset_message() after the view finishes.
 *
 * Place this once per layout — it reads directly from the session so
 * no $data variables need to be passed.
 *
 * Usage:
 *   component('flash-message');
 *
 * Message types (Bootstrap alert classes):
 *   'success' — green  (operation completed)
 *   'danger'  — red    (error or access denied)
 *   'warning' — yellow (non-critical warning)
 *   'info'    — blue   (neutral information, default)
 */
?>
<?php if (!empty($_SESSION['message'])): ?>
<div class="alert alert-<?= e($_SESSION['message_tag'] ?? 'info') ?>" role="alert">
    <?= e($_SESSION['message']) ?>
</div>
<?php endif; ?>
