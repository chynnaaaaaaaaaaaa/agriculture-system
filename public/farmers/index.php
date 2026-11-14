<?php
require __DIR__ . '/../../includes/bootstrap.php';
Auth::requireLogin();

$model     = new Farmer();
$result    = $model->search($_GET);
$addresses = $model->addresses();

$pageTitle = 'Farmers';
$nav = 'farmers';
include ROOT_PATH . '/includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h2 class="mb-0">Farmers</h2>
    <a href="form.php" class="btn btn-success">+ Add Farmer</a>
</div>

<form method="get" class="row g-2 mb-3">
    <div class="col-12 col-md-5">
        <input type="text" name="q" class="form-control" placeholder="Search name, contact or address"
               value="<?= e($_GET['q'] ?? '') ?>">
    </div>
    <div class="col-12 col-md-4">
        <select name="address" class="form-select">
            <?= options($addresses, $_GET['address'] ?? '', 'All addresses') ?>
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
                    <?= sort_th('Name', 'name') ?>
                    <?= sort_th('Contact', 'contact') ?>
                    <?= sort_th('Address', 'address') ?>
                    <?= sort_th('Farms', 'farms') ?>
                    <?= sort_th('Registered', 'date') ?>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($result['rows'] as $r): ?>
                <tr>
                    <td><?= (int) $r['farmer_id'] ?></td>
                    <td><?= e($r['last_name'] . ', ' . $r['first_name']) ?></td>
                    <td><?= e($r['contact_number']) ?></td>
                    <td><?= e($r['address']) ?></td>
                    <td><?= (int) $r['farm_count'] ?></td>
                    <td><?= e($r['date_registered']) ?></td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-primary" href="view.php?id=<?= (int) $r['farmer_id'] ?>">View</a>
                        <a class="btn btn-sm btn-outline-warning" href="form.php?id=<?= (int) $r['farmer_id'] ?>">Edit</a>
                        <?= delete_button('farmers/delete.php', (int) $r['farmer_id'], $r['first_name'] . ' ' . $r['last_name']) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$result['rows']): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No farmers found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer"><?= render_pagination($result) ?></div>
</div>
<?php include ROOT_PATH . '/includes/footer.php'; ?>
