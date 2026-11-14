<?php
require __DIR__ . '/../../includes/bootstrap.php';
Auth::requireLogin();

$model = new Planting();
$id    = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT) ?: null;

$record = null;
if ($id) {
    $record = $model->find($id);
    if (!$record) {
        flash('danger', 'Planting record not found.');
        redirect('plantings/index.php');
    }
}

$data = $record ?? [
    'farm_id' => '', 'crop_id' => '', 'planting_date' => date('Y-m-d'), 'area_planted' => '',
    'seed_quantity' => '', 'expected_harvest_date' => '', 'status_id' => '',
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    [$data, $errors] = $model->validate($_POST, $id);
    if (!$errors) {
        try {
            if ($id) {
                $model->update($id, $data, Auth::id());
                flash('success', 'Planting record updated successfully.');
                redirect('plantings/view.php?id=' . $id);
            }
            $newId = $model->create($data, Auth::id());
            flash('success', "Planting record created successfully (ID $newId).");
            redirect('plantings/index.php');
        } catch (PDOException $e) {
            error_log('Planting save failed: ' . $e->getMessage());
            $errors['form'] = 'The planting record could not be saved. Please check your entries and try again.';
        }
    }
}

$pageTitle = $id ? 'Edit Planting' : 'Add Planting';
$nav = 'plantings';
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
                    <label class="form-label" for="farm_id">Farm *</label>
                    <select id="farm_id" name="farm_id" class="form-select<?= invalid($errors, 'farm_id') ?>">
                        <?= options((new Farm())->dropdown(), $data['farm_id'], '-- Select farm --') ?>
                    </select>
                    <?= feedback($errors, 'farm_id') ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="crop_id">Crop *</label>
                    <select id="crop_id" name="crop_id" class="form-select<?= invalid($errors, 'crop_id') ?>">
                        <?= options((new Crop())->dropdown(), $data['crop_id'], '-- Select crop --') ?>
                    </select>
                    <?= feedback($errors, 'crop_id') ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="planting_date">Planting date *</label>
                    <input type="date" id="planting_date" name="planting_date"
                           class="form-control<?= invalid($errors, 'planting_date') ?>" value="<?= e($data['planting_date']) ?>">
                    <?= feedback($errors, 'planting_date') ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="expected_harvest_date">Expected harvest date</label>
                    <input type="date" id="expected_harvest_date" name="expected_harvest_date"
                           class="form-control<?= invalid($errors, 'expected_harvest_date') ?>" value="<?= e($data['expected_harvest_date']) ?>">
                    <div class="form-text">Leave blank to calculate it from the crop's growing period.</div>
                    <?= feedback($errors, 'expected_harvest_date') ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="area_planted">Area planted (ha) *</label>
                    <input type="number" id="area_planted" name="area_planted" step="0.01" min="0.01"
                           class="form-control<?= invalid($errors, 'area_planted') ?>" value="<?= e($data['area_planted']) ?>">
                    <?= feedback($errors, 'area_planted') ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="seed_quantity">Seed quantity *</label>
                    <input type="number" id="seed_quantity" name="seed_quantity" step="0.01" min="0"
                           class="form-control<?= invalid($errors, 'seed_quantity') ?>" value="<?= e($data['seed_quantity']) ?>">
                    <?= feedback($errors, 'seed_quantity') ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="status_id">Status *</label>
                    <select id="status_id" name="status_id" class="form-select<?= invalid($errors, 'status_id') ?>">
                        <?= options(Lookup::all('planting_status'), $data['status_id'], '-- Select status --') ?>
                    </select>
                    <?= feedback($errors, 'status_id') ?>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-success"><?= $id ? 'Save changes' : 'Create planting' ?></button>
                <a href="<?= $id ? 'view.php?id=' . $id : 'index.php' ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php include ROOT_PATH . '/includes/footer.php'; ?>
