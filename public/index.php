<?php
require __DIR__ . '/../includes/bootstrap.php';
Auth::requireLogin();

$dash       = new Dashboard();
$counts     = $dash->counts();
$totals     = $dash->harvestTotals();
$byStatus   = $dash->plantingsByStatus();
$byCrop     = $dash->harvestByCrop();
$recentPl   = $dash->recentPlantings();
$recentHv   = $dash->recentHarvests();

$pageTitle = 'Dashboard';
$nav = 'dashboard';

$cards = [
    ['Farmers',          $counts['farmers'],   'farmers/index.php'],
    ['Farms',            $counts['farms'],     'farms/index.php'],
    ['Crops',            $counts['crops'],     'crops/index.php'],
    ['Planting Records', $counts['plantings'], 'plantings/index.php'],
    ['Harvest Records',  $counts['harvests'],  'harvests/index.php'],
];

// Chart data (from the database) handed to JavaScript
$statusJson = json_encode(['labels' => array_column($byStatus, 'status_name'),
                           'data'   => array_map('intval', array_column($byStatus, 'total'))]);
$cropJson   = json_encode(['labels' => array_column($byCrop, 'crop_name'),
                           'data'   => array_map('floatval', array_column($byCrop, 'total_kg'))]);

$extraScripts = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
const statusData = ' . $statusJson . ';
const cropData   = ' . $cropJson . ';
new Chart(document.getElementById("statusChart"), {
    type: "doughnut",
    data: { labels: statusData.labels, datasets: [{ data: statusData.data,
            backgroundColor: ["#0d6efd", "#198754", "#ffc107", "#dc3545"] }] },
    options: { plugins: { legend: { position: "bottom" } } }
});
new Chart(document.getElementById("cropChart"), {
    type: "bar",
    data: { labels: cropData.labels, datasets: [{ label: "Harvested (kg)", data: cropData.data,
            backgroundColor: "#198754" }] },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});
</script>';

include ROOT_PATH . '/includes/header.php';
?>
<h2 class="mb-4">Dashboard</h2>

<div class="row g-3 mb-4">
    <?php foreach ($cards as [$label, $value, $link]): ?>
        <div class="col-6 col-md-4 col-xl">
            <a href="<?= e(url($link)) ?>" class="text-decoration-none text-reset">
                <div class="card stat-card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small text-uppercase"><?= e($label) ?></div>
                        <div class="stat-number"><?= num($value, 0) ?></div>
                    </div>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm h-100"><div class="card-body">
            <div class="text-muted small text-uppercase">Total harvested quantity</div>
            <div class="stat-number"><?= num($totals['total_kg']) ?> kg</div>
        </div></div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm h-100"><div class="card-body">
            <div class="text-muted small text-uppercase">Estimated harvest value</div>
            <div class="stat-number"><?= money($totals['total_value']) ?></div>
        </div></div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-5">
        <div class="card shadow-sm h-100">
            <div class="card-header">Plantings by status</div>
            <div class="card-body">
                <canvas id="statusChart" height="220"></canvas>
                <ul class="list-group list-group-flush mt-3">
                    <?php foreach ($byStatus as $s): ?>
                        <li class="list-group-item d-flex justify-content-between">
                            <?= e($s['status_name']) ?> <span class="badge bg-secondary"><?= (int) $s['total'] ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card shadow-sm h-100">
            <div class="card-header">Harvested quantity per crop (kg)</div>
            <div class="card-body"><canvas id="cropChart" height="160"></canvas></div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between">
                <span>Recent planting records</span>
                <a href="<?= e(url('plantings/index.php')) ?>" class="small">View all</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-striped mb-0">
                    <thead><tr><th>Date</th><th>Farm</th><th>Crop</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentPl as $r): ?>
                        <tr>
                            <td><?= e($r['planting_date']) ?></td>
                            <td><?= e($r['farm_name']) ?></td>
                            <td><?= e($r['crop_name']) ?></td>
                            <td><?= e($r['status_name']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$recentPl): ?><tr><td colspan="4" class="text-center text-muted">No records yet.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between">
                <span>Recent harvest records</span>
                <a href="<?= e(url('harvests/index.php')) ?>" class="small">View all</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-striped mb-0">
                    <thead><tr><th>Date</th><th>Farm</th><th>Crop</th><th class="text-end">Qty (kg)</th><th>Grade</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentHv as $r): ?>
                        <tr>
                            <td><?= e($r['harvest_date']) ?></td>
                            <td><?= e($r['farm_name']) ?></td>
                            <td><?= e($r['crop_name']) ?></td>
                            <td class="text-end"><?= num($r['quantity_kg']) ?></td>
                            <td><?= e($r['grade_name']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$recentHv): ?><tr><td colspan="5" class="text-center text-muted">No records yet.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include ROOT_PATH . '/includes/footer.php'; ?>
