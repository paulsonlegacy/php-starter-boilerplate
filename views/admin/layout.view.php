<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($context['title'] ?? 'Admin') ?></title>
    <link rel="stylesheet" href="<?= e(STATIC_ROOT) ?>/css/dashboard.css">
</head>
<body>
    <nav>
        <a href="<?= e(ROOT) ?>/admin/dashboard">Dashboard</a>
        <a href="<?= e(ROOT) ?>/admin/users">Users</a>
        <a href="<?= e(ROOT) ?>/account/logout">Logout</a>
    </nav>

    <?php if (!empty($_SESSION['message'])): ?>
        <div class="alert alert-<?= e($_SESSION['message_tag'] ?? 'info') ?>">
            <?= e($_SESSION['message']) ?>
        </div>
    <?php endif; ?>

    <main>
        <?php require(VIEW_PATH . "admin/$name.view.php"); ?>
    </main>
</body>
</html>
