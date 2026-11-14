<?php
require __DIR__ . '/../../includes/bootstrap.php';
Auth::requireLogin();

$model = new Crop();
$id    = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$c     = $id ? $model->find($id) : null;
if (!$c) {
    flash('danger', 'Crop not found.');
    redirect('crops/index.php');
}

$pageTitle = 'Crop Details';
$nav = 'crops';
include ROOT_PATH . '/includes/header.php';
?>
<div class="card shadow-sm">
    <div class="card-header bg-success text-white"><h5><?= e($c['crop_name']) ?> &ndash; <?= e($c['variety']) ?></h5></div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Crop ID</dt><dd class="col-sm-9"><?= (int) $c['crop_id'] ?></dd>
            <dt class="col-sm-3">Category</dt><dd class="col-sm-9"><?= e($c['category_name']) ?></dd>
            <dt class="col-sm-3">Growing period</dt><dd class="col-sm-9"><?= (int) $c['growing_period_days'] ?> days</dd>
            <dt class="col-sm-3">Recommended season</dt><dd class="col-sm-9"><?= e($c['season_name']) ?></dd>
        </dl>
        <?= audit_html($model->audit($id)) ?>
        <div class="mt-3 d-flex gap-2">
            <?php if (Auth::isAdmin()): ?><a href="form.php?id=<?= $id ?>" class="btn btn-warning">Edit</a><?php endif; ?>
            <a href="index.php" class="btn btn-secondary">Back to list</a>
        </div>
    </div>
</div>
<?php include ROOT_PATH . '/includes/footer.php'; ?>
