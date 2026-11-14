<?php
require __DIR__ . '/../../includes/bootstrap.php';
Auth::requireLogin();

$model = new Harvest();
$id    = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT) ?: null;

$record = null;
if ($id) {
    $record = $model->find($id);
    if (!$record) {
        flash('danger', 'Harvest record not found.');
        redirect('harvests/index.php');
    }
}

$data = $record ?? [
    'planting_id' => $_GET['planting_id'] ?? '',   // pre-selected when coming from a planting's page
    'harvest_date' => date('Y-m-d'), 'quantity_kg' => '', 'selling_price_per_kg' => '',
    'grade_id' => '', 'remark_id' => '',
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    [$data, $errors] = $model->validate($_POST, $id);
    if (!$errors) {
        try {
            if ($id) {
                $model->update($id, $data, Auth::id());
                flash('success', 'Harvest record updated successfully.');
                redirect('harvests/view.php?id=' . $id);
            }
            $newId = $model->create($data, Auth::id());
            flash('success', "Harvest record created successfully (ID $newId).");
            redirect('harvests/index.php');
        } catch (PDOException $e) {
            error_log('Harvest save failed: ' . $e->getMessage());
            $errors['form'] = 'The harvest record could not be saved. Please check your entries and try again.';
        }
    }
}

$pageTitle = $id ? 'Edit Harvest' : 'Add Harvest';
$nav = 'harvests';
include ROOT_PATH . '/includes/header.php';
?>
<div class="card shadow-sm">
    <div class="card-header bg-success text-white"><h5><?= e($pageTitle) ?></h5></div>
    <div class="card-body">
        <?php if (isset($errors['form'])): ?><div class="alert alert-danger"><?= e($errors['form']) ?></div><?php endif; ?>
        <form method="post" novalidate>
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label" for="planting_id">Planting record *</label>
                    <select id="planting_id" name="planting_id" class="form-select<?= invalid($errors, 'planting_id') ?>">
                        <?= options((new Planting())->dropdown(), $data['planting_id'], '-- Select planting --') ?>
                    </select>
                    <?= feedback($errors, 'planting_id') ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="harvest_date">Harvest date *</label>
                    <input type="date" id="harvest_date" name="harvest_date"
                           class="form-control<?= invalid($errors, 'harvest_date') ?>" value="<?= e($data['harvest_date']) ?>">
                    <?= feedback($errors, 'harvest_date') ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="quantity_kg">Quantity (kg) *</label>
                    <input type="number" id="quantity_kg" name="quantity_kg" step="0.01" min="0"
                           class="form-control<?= invalid($errors, 'quantity_kg') ?>" value="<?= e($data['quantity_kg']) ?>">
                    <?= feedback($errors, 'quantity_kg') ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="selling_price_per_kg">Selling price per kg (₱) *</label>
                    <input type="number" id="selling_price_per_kg" name="selling_price_per_kg" step="0.01" min="0"
                           class="form-control<?= invalid($errors, 'selling_price_per_kg') ?>" value="<?= e($data['selling_price_per_kg']) ?>">
                    <?= feedback($errors, 'selling_price_per_kg') ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="grade_id">Quality grade *</label>
                    <select id="grade_id" name="grade_id" class="form-select<?= invalid($errors, 'grade_id') ?>">
                        <?= options(Lookup::all('quality_grade'), $data['grade_id'], '-- Select grade --') ?>
                    </select>
                    <?= feedback($errors, 'grade_id') ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="remark_id">Remarks *</label>
                    <select id="remark_id" name="remark_id" class="form-select<?= invalid($errors, 'remark_id') ?>">
                        <?= options(Lookup::all('harvest_remark'), $data['remark_id'], '-- Select remark --') ?>
                    </select>
                    <?= feedback($errors, 'remark_id') ?>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-success"><?= $id ? 'Save changes' : 'Create harvest' ?></button>
                <a href="<?= $id ? 'view.php?id=' . $id : 'index.php' ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php include ROOT_PATH . '/includes/footer.php'; ?>
