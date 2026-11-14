<?php
require __DIR__ . '/../../includes/bootstrap.php';
Auth::requireAdmin();          // administrators only

$model = new Crop();
$id    = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT) ?: null;

$record = null;
if ($id) {
    $record = $model->find($id);
    if (!$record) {
        flash('danger', 'Crop not found.');
        redirect('crops/index.php');
    }
}

$data = $record ?? ['crop_name' => '', 'variety' => '', 'crop_category_id' => '', 'growing_period_days' => '', 'season_id' => ''];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    [$data, $errors] = $model->validate($_POST, $id);
    if (!$errors) {
        try {
            if ($id) {
                $model->update($id, $data, Auth::id());
                flash('success', 'Crop updated successfully.');
                redirect('crops/view.php?id=' . $id);
            }
            $newId = $model->create($data, Auth::id());
            flash('success', "Crop created successfully (ID $newId).");
            redirect('crops/index.php');
        } catch (PDOException $e) {
            error_log('Crop save failed: ' . $e->getMessage());
            $errors['form'] = 'The crop could not be saved. Please check your entries and try again.';
        }
    }
}

$pageTitle = $id ? 'Edit Crop' : 'Add Crop';
$nav = 'crops';
include ROOT_PATH . '/includes/header.php';
?>
<div class="card shadow-sm">
    <div class="card-header bg-success text-white"><h5><?= e($pageTitle) ?></h5></div>
    <div class="card-body">
        <?php if (isset($errors['form'])): ?><div class="alert alert-danger"><?= e($errors['form']) ?></div><?php endif; ?>
        <form method="post" novalidate>
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="crop_name">Crop name *</label>
                    <input type="text" id="crop_name" name="crop_name" maxlength="80"
                           class="form-control<?= invalid($errors, 'crop_name') ?>" value="<?= e($data['crop_name']) ?>">
                    <?= feedback($errors, 'crop_name') ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="variety">Variety *</label>
                    <input type="text" id="variety" name="variety" maxlength="80"
                           class="form-control<?= invalid($errors, 'variety') ?>" value="<?= e($data['variety']) ?>">
                    <?= feedback($errors, 'variety') ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="crop_category_id">Category *</label>
                    <select id="crop_category_id" name="crop_category_id" class="form-select<?= invalid($errors, 'crop_category_id') ?>">
                        <?= options(Lookup::all('crop_category'), $data['crop_category_id'], '-- Select category --') ?>
                    </select>
                    <?= feedback($errors, 'crop_category_id') ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="growing_period_days">Growing period (days) *</label>
                    <input type="number" id="growing_period_days" name="growing_period_days" min="1" max="730" step="1"
                           class="form-control<?= invalid($errors, 'growing_period_days') ?>" value="<?= e($data['growing_period_days']) ?>">
                    <?= feedback($errors, 'growing_period_days') ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="season_id">Recommended season *</label>
                    <select id="season_id" name="season_id" class="form-select<?= invalid($errors, 'season_id') ?>">
                        <?= options(Lookup::all('season'), $data['season_id'], '-- Select season --') ?>
                    </select>
                    <?= feedback($errors, 'season_id') ?>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-success"><?= $id ? 'Save changes' : 'Create crop' ?></button>
                <a href="<?= $id ? 'view.php?id=' . $id : 'index.php' ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php include ROOT_PATH . '/includes/footer.php'; ?>
