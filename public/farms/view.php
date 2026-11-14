<?php
require __DIR__ . '/../../includes/bootstrap.php';
Auth::requireLogin();

$model = new Farm();
$id    = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$f     = $id ? $model->find($id) : null;
if (!$f) {
    flash('danger', 'Farm not found.');
    redirect('farms/index.php');
}
$plantings = $model->plantings($id);

$pageTitle = 'Farm Details';
$nav = 'farms';
include ROOT_PATH . '/includes/header.php';
?>
<div class="card shadow-sm mb-4">
    <div class="card-header bg-success text-white"><h5><?= e($f['farm_name']) ?></h5></div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Farm ID</dt><dd class="col-sm-9"><?= (int) $f['farm_id'] ?></dd>
            <dt class="col-sm-3">Owner</dt>
            <dd class="col-sm-9"><a href="<?= e(url('farmers/view.php?id=' . $f['farmer_id'])) ?>"><?= e($f['farmer_name']) ?></a></dd>
            <dt class="col-sm-3">Location</dt><dd class="col-sm-9"><?= e($f['location']) ?></dd>
            <dt class="col-sm-3">Area</dt><dd class="col-sm-9"><?= num($f['area_hectares']) ?> hectares</dd>
            <dt class="col-sm-3">Soil type</dt><dd class="col-sm-9"><?= e($f['soil_type_name']) ?></dd>
            <dt class="col-sm-3">Irrigation</dt><dd class="col-sm-9"><?= e($f['irrigation_type_name']) ?></dd>
        </dl>
        <?= audit_html($model->audit($id)) ?>
        <div class="mt-3 d-flex gap-2">
            <a href="form.php?id=<?= $id ?>" class="btn btn-warning">Edit</a>
            <a href="index.php" class="btn btn-secondary">Back to list</a>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header">Planting records on this farm (<?= count($plantings) ?>)</div>
    <div class="table-responsive">
        <table class="table table-striped mb-0">
            <thead><tr><th>ID</th><th>Crop</th><th>Planted</th><th>Area (ha)</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($plantings as $p): ?>
                <tr>
                    <td><a href="<?= e(url('plantings/view.php?id=' . $p['planting_id'])) ?>">#<?= (int) $p['planting_id'] ?></a></td>
                    <td><?= e($p['crop_name'] . ' - ' . $p['variety']) ?></td>
                    <td><?= e($p['planting_date']) ?></td>
                    <td><?= num($p['area_planted']) ?></td>
                    <td><?= e($p['status_name']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$plantings): ?><tr><td colspan="5" class="text-center text-muted">No plantings yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include ROOT_PATH . '/includes/footer.php'; ?>
