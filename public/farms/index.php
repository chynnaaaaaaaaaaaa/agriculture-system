<?php
require __DIR__ . '/../../includes/bootstrap.php';
Auth::requireLogin();

$model  = new Farm();
$result = $model->search($_GET);

$pageTitle = 'Farms';
$nav = 'farms';
include ROOT_PATH . '/includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h2 class="mb-0">Farms</h2>
    <a href="form.php" class="btn btn-success">+ Add Farm</a>
</div>

<form method="get" class="row g-2 mb-3">
    <div class="col-12 col-md-4">
        <input type="text" name="q" class="form-control" placeholder="Search farm, location or farmer"
               value="<?= e($_GET['q'] ?? '') ?>">
    </div>
    <div class="col-6 col-md-2">
        <select name="irrigation_type_id" class="form-select">
            <?= options(Lookup::all('irrigation_type'), $_GET['irrigation_type_id'] ?? '', 'All irrigation') ?>
        </select>
    </div>
    <div class="col-6 col-md-3">
        <select name="soil_type_id" class="form-select">
            <?= options(Lookup::all('soil_type'), $_GET['soil_type_id'] ?? '', 'All soil types') ?>
        </select>
    </div>
    <div class="col-12 col-md-3 d-flex gap-2">
        <button class="btn btn-success flex-fill">Search</button>
        <a href="index.php" class="btn btn-outline-secondary flex-fill">Reset</a>
    </div>
</form>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle mb-0">
            <thead class="table-success">
                <tr>
                    <?= sort_th('ID', 'id') ?>
                    <?= sort_th('Farm', 'name') ?>
                    <?= sort_th('Farmer', 'farmer') ?>
                    <?= sort_th('Location', 'location') ?>
                    <?= sort_th('Area (ha)', 'area') ?>
                    <?= sort_th('Soil', 'soil') ?>
                    <?= sort_th('Irrigation', 'irrigation') ?>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($result['rows'] as $r): ?>
                <tr>
                    <td><?= (int) $r['farm_id'] ?></td>
                    <td><?= e($r['farm_name']) ?></td>
                    <td><?= e($r['farmer_name']) ?></td>
                    <td><?= e($r['location']) ?></td>
                    <td><?= num($r['area_hectares']) ?></td>
                    <td><?= e($r['soil_type_name']) ?></td>
                    <td><?= e($r['irrigation_type_name']) ?></td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-primary" href="view.php?id=<?= (int) $r['farm_id'] ?>">View</a>
                        <a class="btn btn-sm btn-outline-warning" href="form.php?id=<?= (int) $r['farm_id'] ?>">Edit</a>
                        <?= delete_button('farms/delete.php', (int) $r['farm_id'], $r['farm_name']) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$result['rows']): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No farms found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer"><?= render_pagination($result) ?></div>
</div>
<?php include ROOT_PATH . '/includes/footer.php'; ?>
