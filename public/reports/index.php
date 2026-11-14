<?php
require __DIR__ . '/../../includes/bootstrap.php';
Auth::requireLogin();

$type = isset($_GET['type']) && is_string($_GET['type']) && isset(Report::TYPES[$_GET['type']])
    ? $_GET['type'] : 'farmers_farms';

$report = (new Report())->run($type, $_GET);

/** Format one cell using the report's 'formats' map. */
function cell(array $report, string $key, mixed $value): string
{
    $fmt = $report['formats'][$key] ?? null;
    if ($value === null || $value === '') return '-';
    if ($fmt === 'money') return money($value);
    if (is_int($fmt))     return num($value, $fmt);
    return (string) $value;
}

// CSV export (same filters, same query)
if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $type . '_' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, array_values($report['columns']));
    foreach ($report['rows'] as $row) {
        $line = [];
        foreach (array_keys($report['columns']) as $k) $line[] = $row[$k] ?? '';
        fputcsv($out, $line);
    }
    exit;
}

$showIrrigation = $type === 'farmers_farms';
$showFarm       = $type === 'crops_per_farm';
$showCrop       = in_array($type, ['crops_per_farm', 'harvests', 'harvest_summary'], true);
$showStatus     = $type === 'plantings_status';
$showGrade      = $type === 'harvests';
$dateLabel = match ($type) {
    'farmers_farms'    => 'Farmer registered',
    'harvests', 'harvest_summary' => 'Harvest date',
    default            => 'Planting date',
};

$pageTitle = 'Reports';
$nav = 'reports';
include ROOT_PATH . '/includes/header.php';
?>
<h2 class="mb-3 no-print">Reports</h2>

<ul class="nav nav-pills mb-3 no-print">
    <?php foreach (Report::TYPES as $key => $label): ?>
        <li class="nav-item">
            <a class="nav-link <?= $key === $type ? 'active bg-success' : 'text-success' ?>"
               href="?type=<?= e($key) ?>"><?= e($label) ?></a>
        </li>
    <?php endforeach; ?>
</ul>

<form method="get" class="card card-body shadow-sm mb-3 no-print">
    <input type="hidden" name="type" value="<?= e($type) ?>">
    <div class="row g-2 align-items-end">
        <div class="col-6 col-md-2">
            <label class="form-label small mb-0"><?= e($dateLabel) ?> from</label>
            <input type="date" name="date_from" class="form-control" value="<?= e($_GET['date_from'] ?? '') ?>">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-0">to</label>
            <input type="date" name="date_to" class="form-control" value="<?= e($_GET['date_to'] ?? '') ?>">
        </div>
        <?php if ($showIrrigation): ?>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-0">Irrigation</label>
            <select name="irrigation_type_id" class="form-select"><?= options(Lookup::all('irrigation_type'), $_GET['irrigation_type_id'] ?? '', 'All') ?></select>
        </div>
        <?php endif; ?>
        <?php if ($showFarm): ?>
        <div class="col-6 col-md-3">
            <label class="form-label small mb-0">Farm</label>
            <select name="farm_id" class="form-select"><?= options((new Farm())->dropdown(), $_GET['farm_id'] ?? '', 'All farms') ?></select>
        </div>
        <?php endif; ?>
        <?php if ($showCrop): ?>
        <div class="col-6 col-md-3">
            <label class="form-label small mb-0">Crop</label>
            <select name="crop_id" class="form-select"><?= options((new Crop())->dropdown(), $_GET['crop_id'] ?? '', 'All crops') ?></select>
        </div>
        <?php endif; ?>
        <?php if ($showStatus): ?>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-0">Status</label>
            <select name="status_id" class="form-select"><?= options(Lookup::all('planting_status'), $_GET['status_id'] ?? '', 'All') ?></select>
        </div>
        <?php endif; ?>
        <?php if ($showGrade): ?>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-0">Quality grade</label>
            <select name="grade_id" class="form-select"><?= options(Lookup::all('quality_grade'), $_GET['grade_id'] ?? '', 'All') ?></select>
        </div>
        <?php endif; ?>
        <div class="col-12 col-md-auto d-flex gap-2">
            <button class="btn btn-success">Generate</button>
            <a href="?type=<?= e($type) ?>" class="btn btn-outline-secondary">Reset</a>
        </div>
    </div>
</form>

<div class="card shadow-sm">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h5><?= e($report['title']) ?> <small class="text-muted">(generated <?= date('M d, Y h:i A') ?>)</small></h5>
        <div class="no-print">
            <a class="btn btn-sm btn-outline-success" href="?<?= e(query_with(['export' => 'csv'])) ?>">Export CSV</a>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">Print</button>
        </div>
    </div>
    <div class="card-body border-bottom">
        <div class="d-flex flex-wrap gap-4">
            <?php foreach ($report['summary'] as $label => $value): ?>
                <div><div class="text-muted small"><?= e($label) ?></div><div class="fs-5 fw-bold"><?= e($value) ?></div></div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0">
            <thead class="table-success">
                <tr><?php foreach ($report['columns'] as $label): ?><th><?= e($label) ?></th><?php endforeach; ?></tr>
            </thead>
            <tbody>
            <?php foreach ($report['rows'] as $row): ?>
                <tr>
                    <?php foreach (array_keys($report['columns']) as $key): ?>
                        <?php $isNum = isset($report['formats'][$key]); ?>
                        <td class="<?= $isNum ? 'text-end' : '' ?>"><?= e(cell($report, $key, $row[$key] ?? null)) ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
            <?php if (!$report['rows']): ?>
                <tr><td colspan="<?= count($report['columns']) ?>" class="text-center text-muted py-4">No data matches the selected filters.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include ROOT_PATH . '/includes/footer.php'; ?>
