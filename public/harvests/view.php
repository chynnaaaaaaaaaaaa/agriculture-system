<?php
require __DIR__ . '/../../includes/bootstrap.php';
Auth::requireLogin();

$model = new Harvest();
$id    = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$h     = $id ? $model->find($id) : null;
if (!$h) {
    flash('danger', 'Harvest record not found.');
    redirect('harvests/index.php');
}

$pageTitle = 'Harvest Details';
$nav = 'harvests';
include ROOT_PATH . '/includes/header.php';
?>
<div class="card shadow-sm">
    <div class="card-header bg-success text-white"><h5>Harvest #<?= (int) $h['harvest_id'] ?></h5></div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Planting record</dt>
            <dd class="col-sm-9"><a href="<?= e(url('plantings/view.php?id=' . $h['planting_id'])) ?>">#<?= (int) $h['planting_id'] ?></a>
                &ndash; <?= e($h['farm_name']) ?>, <?= e($h['crop_name'] . ' - ' . $h['variety']) ?> (planted <?= e($h['planting_date']) ?>)</dd>
            <dt class="col-sm-3">Harvest date</dt><dd class="col-sm-9"><?= e($h['harvest_date']) ?></dd>
            <dt class="col-sm-3">Quantity</dt><dd class="col-sm-9"><?= num($h['quantity_kg']) ?> kg</dd>
            <dt class="col-sm-3">Selling price</dt><dd class="col-sm-9"><?= money($h['selling_price_per_kg']) ?> per kg</dd>
            <dt class="col-sm-3">Estimated value</dt><dd class="col-sm-9"><strong><?= money($h['total_value']) ?></strong></dd>
            <dt class="col-sm-3">Quality grade</dt><dd class="col-sm-9"><?= e($h['grade_name']) ?></dd>
            <dt class="col-sm-3">Remarks</dt><dd class="col-sm-9"><?= e($h['remark_description']) ?> <span class="text-muted small">(stored as remark ID <?= (int) $h['remark_id'] ?>)</span></dd>
        </dl>
        <?= audit_html($model->audit($id)) ?>
        <div class="mt-3 d-flex gap-2">
            <a href="form.php?id=<?= $id ?>" class="btn btn-warning">Edit</a>
            <a href="index.php" class="btn btn-secondary">Back to list</a>
        </div>
    </div>
</div>
<?php include ROOT_PATH . '/includes/footer.php'; ?>
