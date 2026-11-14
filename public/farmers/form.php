<?php
require __DIR__ . '/../../includes/bootstrap.php';
Auth::requireLogin();

$model = new Farmer();
$id    = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT) ?: null;

$record = null;
if ($id) {
    $record = $model->find($id);
    if (!$record) {
        flash('danger', 'Farmer not found.');
        redirect('farmers/index.php');
    }
}

$data   = $record ?? ['first_name' => '', 'last_name' => '', 'contact_number' => '', 'address' => ''];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    [$data, $errors] = $model->validate($_POST, $id);
    if (!$errors) {
        try {
            if ($id) {
                $model->update($id, $data, Auth::id());
                flash('success', 'Farmer updated successfully.');
                redirect('farmers/view.php?id=' . $id);
            }
            $newId = $model->create($data, Auth::id());
            flash('success', "Farmer created successfully (ID $newId).");
            redirect('farmers/index.php');
        } catch (PDOException $e) {
            error_log('Farmer save failed: ' . $e->getMessage());
            $errors['form'] = 'The farmer could not be saved. Please check your entries and try again.';
        }
    }
}

$pageTitle = $id ? 'Edit Farmer' : 'Add Farmer';
$nav = 'farmers';
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
                    <label class="form-label" for="first_name">First name *</label>
                    <input type="text" id="first_name" name="first_name" maxlength="50"
                           class="form-control<?= invalid($errors, 'first_name') ?>" value="<?= e($data['first_name']) ?>">
                    <?= feedback($errors, 'first_name') ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="last_name">Last name *</label>
                    <input type="text" id="last_name" name="last_name" maxlength="50"
                           class="form-control<?= invalid($errors, 'last_name') ?>" value="<?= e($data['last_name']) ?>">
                    <?= feedback($errors, 'last_name') ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="contact_number">Contact number *</label>
                    <input type="text" id="contact_number" name="contact_number" maxlength="20"
                           class="form-control<?= invalid($errors, 'contact_number') ?>" value="<?= e($data['contact_number']) ?>">
                    <?= feedback($errors, 'contact_number') ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="address">Address *</label>
                    <input type="text" id="address" name="address" maxlength="150"
                           class="form-control<?= invalid($errors, 'address') ?>" value="<?= e($data['address']) ?>">
                    <?= feedback($errors, 'address') ?>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-success"><?= $id ? 'Save changes' : 'Create farmer' ?></button>
                <a href="<?= $id ? 'view.php?id=' . $id : 'index.php' ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php include ROOT_PATH . '/includes/footer.php'; ?>
