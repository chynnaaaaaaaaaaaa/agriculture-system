<?php
require __DIR__ . '/../../includes/bootstrap.php';
Auth::requireLogin();

$model  = new Planting();
$result = $model->search($_GET);

$pageTitle = 'Planting Records';
$nav = 'plantings';
include ROOT_PATH . '/includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h2 class="mb-0">Planting Records</h2>
    <a href="form.php" class="btn btn-success">+ Add Planting</a>
</div>

<form method="get" class="row g-2 mb-3">
    <div class="col-12 col-md-4">
        <input type="text" name="q" class="form-control" placeholder="Search farm, crop or farmer"
               value="<?= e($_GET['q'] ?? '') ?>">
    </div>
    <div class="col-6 col-md-2">
        <select name="status_id" class="form-select">
            <?= options(Lookup::all('planting_status'), $_GET['status_id'] ?? '', 'All status') ?>
        </select>
    </div>
    <div class="col-6 col-md-3">
        <select name="crop_id" class="form-select">
            <?= options((new Crop())->dropdown(), $_GET['crop_id'] ?? '', 'All crops') ?>
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
                    <?= sort_th('Farm', 'farm') ?>
                    <?= sort_th('Crop', 'crop') ?>
                    <?= sort_th('Planted', 'date') ?>
                    <?= sort_th('Area (ha)', 'area') ?>
                    <?= sort_th('Expected harvest', 'expected') ?>
                    <?= sort_th('Status', 'status') ?>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($result['rows'] as $r): ?>
                <tr>
                    <td><?= (int) $r['planting_id'] ?></td>
                    <td><?= e($r['farm_name']) ?><div class="small text-muted"><?= e($r['farmer_name']) ?></div></td>
                    <td><?= e($r['crop_name']) ?><div class="small text-muted"><?= e($r['variety']) ?></div></td>
                    <td><?= e($r['planting_date']) ?></td>
                    <td><?= num($r['area_planted']) ?></td>
                    <td><?= e($r['expected_harvest_date'] ?? '-') ?></td>
                    <td><span class="badge text-bg-<?= match ($r['status_name']) {
                        'Harvested' => 'success', 'Growing' => 'primary', 'Failed' => 'danger', default => 'secondary' } ?>">
                        <?= e($r['status_name']) ?></span></td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-primary" href="view.php?id=<?= (int) $r['planting_id'] ?>">View</a>
                        <a class="btn btn-sm btn-outline-warning" href="form.php?id=<?= (int) $r['planting_id'] ?>">Edit</a>
                        <?= delete_button('plantings/delete.php', (int) $r['planting_id'], 'planting #' . $r['planting_id'] . ' (' . $r['farm_name'] . ' - ' . $r['crop_name'] . ')') ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$result['rows']): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No planting records found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer"><?= render_pagination($result) ?></div>
</div>
<?php include ROOT_PATH . '/includes/footer.php'; ?>
