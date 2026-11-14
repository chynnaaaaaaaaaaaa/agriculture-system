<?php
require __DIR__ . '/../../includes/bootstrap.php';
Auth::requireLogin();

$model = new Planting();
$id    = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT)
$p     = $id ? $model->find($id) : null;
if (!$p) {
    flash('danger', 'Planting record not found.');
    redirect('plantings/index.php');
}
$harvests = $model->harvests($id);

$pageTitle = 'Planting Details';
$nav = 'plantings';
include ROOT_PATH . '/includes/header.php';
?>
<div class="card shadow-sm mb-4">
    <div class="card-header bg-success text-white"><h5>Planting #<?= (int) $p['planting_id'] ?></h5></div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Farm</dt>
            <dd class="col-sm-9"><a href="<?= e(url('farms/view.php?id=' . $p['farm_id'])) ?>"><?= e($p['farm_name']) ?></a> (<?= e($p['farmer_name']) ?>)</dd>
            <dt class="col-sm-3">Crop</dt>
            <dd class="col-sm-9"><a href="<?= e(url('crops/view.php?id=' . $p['crop_id'])) ?>"><?= e($p['crop_name'] . ' - ' . $p['variety']) ?></a></dd>
            <dt class="col-sm-3">Planting date</dt><dd class="col-sm-9"><?= e($p['planting_date']) ?></dd>
            <dt class="col-sm-3">Expected harvest</dt><dd class="col-sm-9"><?= e($p['expected_harvest_date'] ?? '-') ?></dd>
            <dt class="col-sm-3">Area planted</dt><dd class="col-sm-9"><?= num($p['area_planted']) ?> ha</dd>
            <dt class="col-sm-3">Seed quantity</dt><dd class="col-sm-9"><?= num($p['seed_quantity']) ?></dd>
            <dt class="col-sm-3">Status</dt><dd class="col-sm-9"><?= e($p['status_name']) ?></dd>
        </dl>
        <?= audit_html($model->audit($id)) ?>
        <div class="mt-3 d-flex gap-2">
            <a href="form.php?id=<?= $id ?>" class="btn btn-warning">Edit</a>
            <a href="index.php" class="btn btn-secondary">Back to list</a>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Harvests for this planting (<?= count($harvests) ?>)</span>
        <a href="<?= e(url('harvests/form.php?planting_id=' . $id)) ?>" class="btn btn-sm btn-success">+ Record harvest</a>
    </div>
    <div class="table-responsive">
        <table class="table table-striped mb-0">
            <thead><tr><th>ID</th><th>Date</th><th>Qty (kg)</th><th>Price/kg</th><th>Grade</th><th>Remark</th></tr></thead>
            <tbody>
            <?php foreach ($harvests as $h): ?>
                <tr>
                    <td><a href="<?= e(url('harvests/view.php?id=' . $h['harvest_id'])) ?>">#<?= (int) $h['harvest_id'] ?></a></td>
                    <td><?= e($h['harvest_date']) ?></td>
                    <td><?= num($h['quantity_kg']) ?></td>
                    <td><?= money($h['selling_price_per_kg']) ?></td>
                    <td><?= e($h['grade_name']) ?></td>
                    <td><?= e($h['remark_description']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$harvests): ?><tr><td colspan="6" class="text-center text-muted">No harvests recorded yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include ROOT_PATH . '/includes/footer.php'; ?>
