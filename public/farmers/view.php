<?php
require __DIR__ . '/../../includes/bootstrap.php';
Auth::requireLogin();

$model = new Farmer();
$id    = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$f     = $id ? $model->find($id) : null;
if (!$f) {
    flash('danger', 'Farmer not found.');
    redirect('farmers/index.php');
}
$farms = $model->farms($id);

$pageTitle = 'Farmer Details';
$nav = 'farmers';
include ROOT_PATH . '/includes/header.php';
?>
<div class="card shadow-sm mb-4">
    <div class="card-header bg-success text-white"><h5>Farmer #<?= (int) $f['farmer_id'] ?></h5></div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Name</dt><dd class="col-sm-9"><?= e($f['first_name'] . ' ' . $f['last_name']) ?></dd>
            <dt class="col-sm-3">Contact number</dt><dd class="col-sm-9"><?= e($f['contact_number']) ?></dd>
            <dt class="col-sm-3">Address</dt><dd class="col-sm-9"><?= e($f['address']) ?></dd>
            <dt class="col-sm-3">Date registered</dt><dd class="col-sm-9"><?= e($f['date_registered']) ?></dd>
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
        <span>Farms owned (<?= count($farms) ?>)</span>
        <a href="<?= e(url('farms/form.php?farmer_id=' . $id)) ?>" class="btn btn-sm btn-success">+ Add farm</a>
    </div>
    <div class="table-responsive">
        <table class="table table-striped mb-0">
            <thead><tr><th>Farm</th><th>Location</th><th>Area (ha)</th><th>Soil</th><th>Irrigation</th></tr></thead>
            <tbody>
            <?php foreach ($farms as $fm): ?>
                <tr>
                    <td><a href="<?= e(url('farms/view.php?id=' . $fm['farm_id'])) ?>"><?= e($fm['farm_name']) ?></a></td>
                    <td><?= e($fm['location']) ?></td>
                    <td><?= num($fm['area_hectares']) ?></td>
                    <td><?= e($fm['soil_type_name']) ?></td>
                    <td><?= e($fm['irrigation_type_name']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$farms): ?><tr><td colspan="5" class="text-center text-muted">This farmer has no farms yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include ROOT_PATH . '/includes/footer.php'; ?>
