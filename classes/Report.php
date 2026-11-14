<?php
declare(strict_types=1);

/**
 * Report queries. Every report returns the same shape so one page can display them all:
 *   title, columns [key => label], formats [key => decimals|'money'], rows, summary [label => value]
 */
class Report
{
    public const TYPES = [
        'farmers_farms'   => 'Farmers and their Farms',
        'crops_per_farm'  => 'Crops Planted per Farm',
        'plantings_status' => 'Planting Records by Status',
        'harvests'        => 'Harvest Records',
        'harvest_summary' => 'Total Harvest Quantity & Estimated Value',
    ];

    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    private function id(array $f, string $key): ?int
    {
        $v = isset($f[$key]) && is_string($f[$key]) ? trim($f[$key]) : '';
        return Validator::integer($v, 1, 2147483647) ? (int) $v : null;
    }

    private function dateFilter(array $f, string $column, array &$where, array &$params): void
    {
        $from = isset($f['date_from']) && is_string($f['date_from']) ? trim($f['date_from']) : '';
        $to   = isset($f['date_to'])   && is_string($f['date_to'])   ? trim($f['date_to'])   : '';
        if ($from !== '' && Validator::date($from)) {
            $where[] = "$column >= :date_from";
            $params['date_from'] = $from;
        }
        if ($to !== '' && Validator::date($to)) {
            $where[] = "$column <= :date_to";
            $params['date_to'] = $to;
        }
    }

    private function fetch(string $sql, array $where, array $params, string $tail = ''): array
    {
        $sql .= $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $stmt = $this->pdo->prepare($sql . ' ' . $tail);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function run(string $type, array $f): array
    {
        return match ($type) {
            'crops_per_farm'   => $this->cropsPerFarm($f),
            'plantings_status' => $this->plantingsStatus($f),
            'harvests'         => $this->harvests($f),
            'harvest_summary'  => $this->harvestSummary($f),
            default            => $this->farmersFarms($f),
        };
    }

    private function farmersFarms(array $f): array
    {
        $where = []; $params = [];
        $this->dateFilter($f, 'fr.date_registered', $where, $params);
        if ($irr = $this->id($f, 'irrigation_type_id')) {
            $where[] = 'fm.irrigation_type_id = :irr';
            $params['irr'] = $irr;
        }
        $rows = $this->fetch(
            "SELECT fr.first_name || ' ' || fr.last_name AS farmer, fr.contact_number, fr.address,
                    fm.farm_name, fm.location, fm.area_hectares,
                    st.soil_type_name AS soil, it.irrigation_type_name AS irrigation
               FROM farmer fr
               JOIN farm fm            ON fm.farmer_id = fr.farmer_id
               JOIN soil_type st       ON st.soil_type_id = fm.soil_type_id
               JOIN irrigation_type it ON it.irrigation_type_id = fm.irrigation_type_id",
            $where, $params, 'ORDER BY fr.last_name, fr.first_name, fm.farm_name'
        );
        return [
            'title'   => self::TYPES['farmers_farms'],
            'columns' => ['farmer' => 'Farmer', 'contact_number' => 'Contact', 'address' => 'Farmer Address',
                          'farm_name' => 'Farm', 'location' => 'Farm Location', 'area_hectares' => 'Area (ha)',
                          'soil' => 'Soil', 'irrigation' => 'Irrigation'],
            'formats' => ['area_hectares' => 2],
            'rows'    => $rows,
            'summary' => ['Farms listed' => (string) count($rows),
                          'Total area (ha)' => num(array_sum(array_column($rows, 'area_hectares')))],
        ];
    }

    private function cropsPerFarm(array $f): array
    {
        $where = []; $params = [];
        $this->dateFilter($f, 'p.planting_date', $where, $params);
        if ($farm = $this->id($f, 'farm_id')) { $where[] = 'p.farm_id = :farm'; $params['farm'] = $farm; }
        if ($crop = $this->id($f, 'crop_id')) { $where[] = 'p.crop_id = :crop'; $params['crop'] = $crop; }
        $rows = $this->fetch(
            "SELECT fm.farm_name, fr.first_name || ' ' || fr.last_name AS farmer,
                    c.crop_name, c.variety, cc.category_name,
                    COUNT(*) AS plantings, SUM(p.area_planted) AS total_area, SUM(p.seed_quantity) AS total_seed
               FROM planting p
               JOIN farm fm           ON fm.farm_id = p.farm_id
               JOIN farmer fr         ON fr.farmer_id = fm.farmer_id
               JOIN crop c            ON c.crop_id = p.crop_id
               JOIN crop_category cc  ON cc.crop_category_id = c.crop_category_id",
            $where, $params,
            'GROUP BY fm.farm_id, fm.farm_name, fr.first_name, fr.last_name, c.crop_id, c.crop_name, c.variety, cc.category_name
             ORDER BY fm.farm_name, c.crop_name'
        );
        return [
            'title'   => self::TYPES['crops_per_farm'],
            'columns' => ['farm_name' => 'Farm', 'farmer' => 'Farmer', 'crop_name' => 'Crop', 'variety' => 'Variety',
                          'category_name' => 'Category', 'plantings' => 'Plantings',
                          'total_area' => 'Area Planted (ha)', 'total_seed' => 'Seed Qty'],
            'formats' => ['plantings' => 0, 'total_area' => 2, 'total_seed' => 2],
            'rows'    => $rows,
            'summary' => ['Rows' => (string) count($rows),
                          'Total plantings' => num(array_sum(array_column($rows, 'plantings')), 0)],
        ];
    }

    private function plantingsStatus(array $f): array
    {
        $where = []; $params = [];
        $this->dateFilter($f, 'p.planting_date', $where, $params);
        if ($st = $this->id($f, 'status_id')) { $where[] = 'p.status_id = :st'; $params['st'] = $st; }
        $rows = $this->fetch(
            "SELECT ps.status_name, p.planting_id, p.planting_date, fm.farm_name, c.crop_name,
                    p.area_planted, p.expected_harvest_date
               FROM planting p
               JOIN planting_status ps ON ps.status_id = p.status_id
               JOIN farm fm            ON fm.farm_id = p.farm_id
               JOIN crop c             ON c.crop_id = p.crop_id",
            $where, $params, 'ORDER BY ps.status_id, p.planting_date, p.planting_id'
        );
        $summary = [];
        foreach ($rows as $r) {
            $summary[$r['status_name']] = (string) (((int) ($summary[$r['status_name']] ?? 0)) + 1);
        }
        $summary = ['Total plantings' => (string) count($rows)] + $summary;
        return [
            'title'   => self::TYPES['plantings_status'],
            'columns' => ['status_name' => 'Status', 'planting_id' => 'ID', 'planting_date' => 'Planted',
                          'farm_name' => 'Farm', 'crop_name' => 'Crop', 'area_planted' => 'Area (ha)',
                          'expected_harvest_date' => 'Expected Harvest'],
            'formats' => ['area_planted' => 2],
            'rows'    => $rows,
            'summary' => $summary,
        ];
    }

    private function harvests(array $f): array
    {
        $where = []; $params = [];
        $this->dateFilter($f, 'h.harvest_date', $where, $params);
        if ($g = $this->id($f, 'grade_id')) { $where[] = 'h.grade_id = :g'; $params['g'] = $g; }
        if ($c = $this->id($f, 'crop_id'))  { $where[] = 'p.crop_id = :c';  $params['c'] = $c; }
        $rows = $this->fetch(
            "SELECT h.harvest_id, h.harvest_date, fm.farm_name, c.crop_name, g.grade_name,
                    r.remark_description, h.quantity_kg, h.selling_price_per_kg,
                    (h.quantity_kg * h.selling_price_per_kg) AS total_value
               FROM harvest h
               JOIN planting p       ON p.planting_id = h.planting_id
               JOIN farm fm          ON fm.farm_id = p.farm_id
               JOIN crop c           ON c.crop_id = p.crop_id
               JOIN quality_grade g  ON g.grade_id = h.grade_id
               JOIN harvest_remark r ON r.remark_id = h.remark_id",
            $where, $params, 'ORDER BY h.harvest_date DESC, h.harvest_id DESC'
        );
        return [
            'title'   => self::TYPES['harvests'],
            'columns' => ['harvest_id' => 'ID', 'harvest_date' => 'Date', 'farm_name' => 'Farm', 'crop_name' => 'Crop',
                          'grade_name' => 'Grade', 'remark_description' => 'Remark', 'quantity_kg' => 'Qty (kg)',
                          'selling_price_per_kg' => 'Price/kg', 'total_value' => 'Est. Value'],
            'formats' => ['quantity_kg' => 2, 'selling_price_per_kg' => 'money', 'total_value' => 'money'],
            'rows'    => $rows,
            'summary' => ['Harvest records' => (string) count($rows),
                          'Total quantity (kg)' => num(array_sum(array_column($rows, 'quantity_kg'))),
                          'Estimated value' => money(array_sum(array_column($rows, 'total_value')))],
        ];
    }

    private function harvestSummary(array $f): array
    {
        $where = []; $params = [];
        $this->dateFilter($f, 'h.harvest_date', $where, $params);
        if ($c = $this->id($f, 'crop_id')) { $where[] = 'p.crop_id = :c'; $params['c'] = $c; }
        $rows = $this->fetch(
            'SELECT c.crop_name, COUNT(*) AS harvests, SUM(h.quantity_kg) AS total_qty,
                    SUM(h.quantity_kg * h.selling_price_per_kg) AS total_value,
                    CASE WHEN SUM(h.quantity_kg) = 0 THEN 0
                         ELSE SUM(h.quantity_kg * h.selling_price_per_kg) / SUM(h.quantity_kg) END AS avg_price
               FROM harvest h
               JOIN planting p ON p.planting_id = h.planting_id
               JOIN crop c     ON c.crop_id = p.crop_id',
            $where, $params, 'GROUP BY c.crop_id, c.crop_name ORDER BY total_value DESC'
        );
        return [
            'title'   => self::TYPES['harvest_summary'],
            'columns' => ['crop_name' => 'Crop', 'harvests' => 'Harvests', 'total_qty' => 'Total Qty (kg)',
                          'avg_price' => 'Avg Price/kg', 'total_value' => 'Estimated Value'],
            'formats' => ['harvests' => 0, 'total_qty' => 2, 'avg_price' => 'money', 'total_value' => 'money'],
            'rows'    => $rows,
            'summary' => ['Total quantity (kg)' => num(array_sum(array_column($rows, 'total_qty'))),
                          'Estimated value' => money(array_sum(array_column($rows, 'total_value')))],
        ];
    }
}
