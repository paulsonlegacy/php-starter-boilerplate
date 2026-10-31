<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($context['title'] ?? 'Home') ?></title>
    <link rel="stylesheet" href="<?= e(STATIC_ROOT) ?>/css/style.css">
</head>
<body>
    <nav>
        <a href="<?= e(ROOT) ?>">Home</a>
        <a href="<?= e(ROOT) ?>/login">Login</a>
    </nav>

    <?php if (!empty($_SESSION['message'])): ?>
        <div class="alert alert-<?= e($_SESSION['message_tag'] ?? 'info') ?>">
            <?= e($_SESSION['message']) ?>
        </div>
    <?php endif; ?>

    <main>
        <?php require(VIEW_PATH . "landing/$name.view.php"); ?>
    </main>

    <script src="<?= e(STATIC_ROOT) ?>/js/app.js"></script>
</body>
</html>
