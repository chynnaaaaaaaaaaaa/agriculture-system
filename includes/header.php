<?php
/** Shared page top. Set $pageTitle and $nav before including. */
$pageTitle = $pageTitle ?? 'Agriculture Information System';
$nav       = $nav ?? '';
$user      = Auth::user();
$links = [
    'dashboard' => ['Dashboard', 'index.php'],
    'farmers'   => ['Farmers',   'farmers/index.php'],
    'farms'     => ['Farms',     'farms/index.php'],
    'crops'     => ['Crops',     'crops/index.php'],
    'plantings' => ['Plantings', 'plantings/index.php'],
    'harvests'  => ['Harvests',  'harvests/index.php'],
    'reports'   => ['Reports',   'reports/index.php'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | Agriculture Information System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(url('assets/style.css')) ?>" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-success no-print">
    <div class="container">
        <a class="navbar-brand" href="<?= e(url('index.php')) ?>">Agriculture System</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto">
                <?php foreach ($links as $key => [$label, $path]): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $nav === $key ? 'active fw-bold' : '' ?>"
                           href="<?= e(url($path)) ?>"><?= e($label) ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php if ($user): ?>
                <span class="navbar-text me-3 small">
                    <?= e($user['full_name']) ?>
                    <span class="badge bg-light text-success"><?= e(ucfirst($user['role'])) ?></span>
                </span>
                <a class="btn btn-outline-light btn-sm" href="<?= e(url('logout.php')) ?>">Logout</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
<main class="container py-4">
<?= flash_render() ?>
