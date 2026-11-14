<?php
require __DIR__ . '/../../includes/bootstrap.php';
Auth::requireLogin();

$model = new Farm();
$id    = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT) ?: null;

$record = null;
if ($id) {
    $record = $model->find($id);
    if (!$record) {
        flash('danger', 'Farm not found.');
        redirect('farms/index.php');
    }
}

$data = $record ?? [
    'farmer_id' => $_GET['farmer_id'] ?? '',      // pre-selected when coming from a farmer's page
    'farm_name' => '', 'location' => '', 'area_hectares' => '', 'soil_type_id' => '', 'irrigation_type_id' => '',
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    [$data, $errors] = $model->validate($_POST, $id);
    if (!$errors) {
        try {
            if ($id) {
                $model->update($id, $data, Auth::id());
                flash('success', 'Farm updated successfully.');
                redirect('farms/view.php?id=' . $id);
            }
            $newId = $model->create($data, Auth::id());
            flash('success', "Farm created successfully (ID $newId).");
            redirect('farms/index.php');
        } catch (PDOException $e) {
            error_log('Farm save failed: ' . $e->getMessage());
            $errors['form'] = 'The farm could not be saved. Please check your entries and try again.';
        }
    }
}

$pageTitle = $id ? 'Edit Farm' : 'Add Farm';
$nav = 'farms';
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
                    <label class="form-label" for="farmer_id">Farmer (owner) *</label>
                    <select id="farmer_id" name="farmer_id" class="form-select<?= invalid($errors, 'farmer_id') ?>">
                        <?= options((new Farmer())->dropdown(), $data['farmer_id'], '-- Select farmer --') ?>
                    </select>
                    <?= feedback($errors, 'farmer_id') ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="farm_name">Farm name *</label>
                    <input type="text" id="farm_name" name="farm_name" maxlength="100"
                           class="form-control<?= invalid($errors, 'farm_name') ?>" value="<?= e($data['farm_name']) ?>">
                    <?= feedback($errors, 'farm_name') ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="location">Location *</label>
                    <input type="text" id="location" name="location" maxlength="150"
                           class="form-control<?= invalid($errors, 'location') ?>" value="<?= e($data['location']) ?>">
                    <?= feedback($errors, 'location') ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="area_hectares">Area (hectares) *</label>
                    <input type="number" id="area_hectares" name="area_hectares" step="0.01" min="0.01" max="10000"
                           class="form-control<?= invalid($errors, 'area_hectares') ?>" value="<?= e($data['area_hectares']) ?>">
                    <?= feedback($errors, 'area_hectares') ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="soil_type_id">Soil type *</label>
                    <select id="soil_type_id" name="soil_type_id" class="form-select<?= invalid($errors, 'soil_type_id') ?>">
                        <?= options(Lookup::all('soil_type'), $data['soil_type_id'], '-- Select soil type --') ?>
                    </select>
                    <?= feedback($errors, 'soil_type_id') ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="irrigation_type_id">Irrigation type *</label>
                    <select id="irrigation_type_id" name="irrigation_type_id" class="form-select<?= invalid($errors, 'irrigation_type_id') ?>">
                        <?= options(Lookup::all('irrigation_type'), $data['irrigation_type_id'], '-- Select irrigation --') ?>
                    </select>
                    <?= feedback($errors, 'irrigation_type_id') ?>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-success"><?= $id ? 'Save changes' : 'Create farm' ?></button>
                <a href="<?= $id ? 'view.php?id=' . $id : 'index.php' ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php include ROOT_PATH . '/includes/footer.php'; ?>
