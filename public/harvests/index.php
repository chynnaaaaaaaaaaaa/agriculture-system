<?php
require __DIR__ . '/../../includes/bootstrap.php';
Auth::requireLogin();

$model  = new Harvest();
$result = $model->search($_GET);

$pageTitle = 'Harvest Records';
$nav = 'harvests';
include ROOT_PATH . '/includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h2 class="mb-0">Harvest Records</h2>
    <a href="form.php" class="btn btn-success">+ Add Harvest</a>
</div>

<form method="get" class="row g-2 mb-3">
    <div class="col-12 col-md-3">
        <input type="text" name="q" class="form-control" placeholder="Search farm, crop or remark"
               value="<?= e($_GET['q'] ?? '') ?>">
    </div>
    <div class="col-6 col-md-2">
        <select name="grade_id" class="form-select">
            <?= options(Lookup::all('quality_grade'), $_GET['grade_id'] ?? '', 'All grades') ?>
        </select>
    </div>
    <div class="col-6 col-md-2">
        <select name="remark_id" class="form-select">
            <?= options(Lookup::all('harvest_remark'), $_GET['remark_id'] ?? '', 'All remarks') ?>
        </select>
    </div>
    <div class="col-6 col-md-2">
        <select name="crop_id" class="form-select">
            <?= options((new Crop())->dropdown(), $_GET['crop_id'] ?? '', 'All crops') ?>
        </select>
    </div>
    <div class="col-6 col-md-3 d-flex gap-2">
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
                    <?= sort_th('Date', 'date') ?>
                    <?= sort_th('Farm', 'farm') ?>
                    <?= sort_th('Crop', 'crop') ?>
                    <?= sort_th('Qty (kg)', 'qty') ?>
                    <?= sort_th('Price/kg', 'price') ?>
                    <?= sort_th('Est. value', 'value') ?>
                    <?= sort_th('Grade', 'grade') ?>
                    <?= sort_th('Remark', 'remark') ?>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($result['rows'] as $r): ?>
                <tr>
                    <td><?= (int) $r['harvest_id'] ?></td>
                    <td><?= e($r['harvest_date']) ?></td>
                    <td><?= e($r['farm_name']) ?></td>
                    <td><?= e($r['crop_name']) ?></td>
                    <td><?= num($r['quantity_kg']) ?></td>
                    <td><?= money($r['selling_price_per_kg']) ?></td>
                    <td><?= money($r['total_value']) ?></td>
                    <td><?= e($r['grade_name']) ?></td>
                    <td><?= e($r['remark_description']) ?></td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-primary" href="view.php?id=<?= (int) $r['harvest_id'] ?>">View</a>
                        <a class="btn btn-sm btn-outline-warning" href="form.php?id=<?= (int) $r['harvest_id'] ?>">Edit</a>
                        <?= delete_button('harvests/delete.php', (int) $r['harvest_id'], 'harvest #' . $r['harvest_id'] . ' (' . $r['farm_name'] . ' - ' . $r['crop_name'] . ')') ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$result['rows']): ?>
                <tr><td colspan="10" class="text-center text-muted py-4">No harvest records found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer"><?= render_pagination($result) ?></div>
</div>
<?php include ROOT_PATH . '/includes/footer.php'; ?>
