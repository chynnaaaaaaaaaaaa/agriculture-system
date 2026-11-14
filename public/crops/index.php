<?php
require __DIR__ . '/../../includes/bootstrap.php';
Auth::requireLogin();

$model  = new Crop();
$result = $model->search($_GET);
$admin  = Auth::isAdmin();   // crops are master data: only administrators can change them

$pageTitle = 'Crops';
$nav = 'crops';
include ROOT_PATH . '/includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h2 class="mb-0">Crops</h2>
    <?php if ($admin): ?><a href="form.php" class="btn btn-success">+ Add Crop</a><?php endif; ?>
</div>

<form method="get" class="row g-2 mb-3">
    <div class="col-12 col-md-4">
        <input type="text" name="q" class="form-control" placeholder="Search crop or variety"
               value="<?= e($_GET['q'] ?? '') ?>">
    </div>
    <div class="col-6 col-md-2">
        <select name="crop_category_id" class="form-select">
            <?= options(Lookup::all('crop_category'), $_GET['crop_category_id'] ?? '', 'All categories') ?>
        </select>
    </div>
    <div class="col-6 col-md-3">
        <select name="season_id" class="form-select">
            <?= options(Lookup::all('season'), $_GET['season_id'] ?? '', 'All seasons') ?>
        </select>
    </div>
    <div class="col-12 col-md-3 d-flex gap-2">
        <button class="btn btn-success flex-fill">Search</button>
        <a href="index.php" class="btn btn-outline-secondary flex-fill">Reset</a>
    </div>
</form>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle mb-0">
            <thead class="table-success">
                <tr>
                    <?= sort_th('ID', 'id') ?>
                    <?= sort_th('Crop', 'name') ?>
                    <?= sort_th('Variety', 'variety') ?>
                    <?= sort_th('Category', 'category') ?>
                    <?= sort_th('Growing days', 'days') ?>
                    <?= sort_th('Season', 'season') ?>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($result['rows'] as $r): ?>
                <tr>
                    <td><?= (int) $r['crop_id'] ?></td>
                    <td><?= e($r['crop_name']) ?></td>
                    <td><?= e($r['variety']) ?></td>
                    <td><?= e($r['category_name']) ?></td>
                    <td><?= (int) $r['growing_period_days'] ?></td>
                    <td><?= e($r['season_name']) ?></td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-primary" href="view.php?id=<?= (int) $r['crop_id'] ?>">View</a>
                        <?php if ($admin): ?>
                            <a class="btn btn-sm btn-outline-warning" href="form.php?id=<?= (int) $r['crop_id'] ?>">Edit</a>
                            <?= delete_button('crops/delete.php', (int) $r['crop_id'], $r['crop_name'] . ' - ' . $r['variety']) ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$result['rows']): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No crops found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer"><?= render_pagination($result) ?></div>
</div>
<?php include ROOT_PATH . '/includes/footer.php'; ?>
