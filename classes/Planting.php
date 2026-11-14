<?php
declare(strict_types=1);

class Planting extends BaseModel
{
    protected string $table = 'planting';
    protected string $pk    = 'planting_id';

    private const SORT = [
        'id'       => 'p.planting_id',
        'farm'     => 'fm.farm_name',
        'crop'     => 'c.crop_name',
        'date'     => 'p.planting_date',
        'area'     => 'p.area_planted',
        'expected' => 'p.expected_harvest_date',
        'status'   => 'ps.status_name',
    ];

    private const FROM = 'planting p
        JOIN farm fm            ON fm.farm_id = p.farm_id
        JOIN farmer fr          ON fr.farmer_id = fm.farmer_id
        JOIN crop c             ON c.crop_id = p.crop_id
        JOIN planting_status ps ON ps.status_id = p.status_id';

    public function search(array $in): array
    {
        $where = [];
        $params = [];
        if (($q = $this->s($in, 'q')) !== '') {
            $where[] = "(fm.farm_name || ' ' || c.crop_name || ' ' || c.variety || ' ' || fr.first_name || ' ' || fr.last_name) ILIKE :q";
            $params['q'] = $this->like($q);
        }
        if (Validator::integer($this->s($in, 'status_id'), 1, 2147483647)) {
            $where[] = 'p.status_id = :status';
            $params['status'] = (int) $in['status_id'];
        }
        if (Validator::integer($this->s($in, 'crop_id'), 1, 2147483647)) {
            $where[] = 'p.crop_id = :crop';
            $params['crop'] = (int) $in['crop_id'];
        }
        return $this->paginate(
            "p.planting_id, p.planting_date, p.area_planted, p.seed_quantity, p.expected_harvest_date,
             fm.farm_name, fr.first_name || ' ' || fr.last_name AS farmer_name,
             c.crop_name, c.variety, ps.status_name",
            self::FROM, $where, $params,
            $this->orderBy(self::SORT, $in, 'id', 'p.planting_id'),
            (int) ($in['page'] ?? 1)
        );
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT p.*, fm.farm_name, fm.area_hectares, fr.first_name || ' ' || fr.last_name AS farmer_name,
                    c.crop_name, c.variety, c.growing_period_days, ps.status_name
               FROM " . self::FROM . ' WHERE p.planting_id = :id'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function dropdown(): array
    {
        return $this->pdo->query(
            "SELECT p.planting_id AS id,
                    '#' || p.planting_id || ' - ' || fm.farm_name || ' - ' || c.crop_name
                        || ' (planted ' || p.planting_date || ')' AS label
               FROM " . self::FROM . ' ORDER BY p.planting_id DESC'
        )->fetchAll();
    }

    public function harvests(int $id): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT h.harvest_id, h.harvest_date, h.quantity_kg, h.selling_price_per_kg,
                    g.grade_name, r.remark_description
               FROM harvest h
               JOIN quality_grade g  ON g.grade_id = h.grade_id
               JOIN harvest_remark r ON r.remark_id = h.remark_id
              WHERE h.planting_id = :id ORDER BY h.harvest_date'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetchAll();
    }

    public function validate(array $in, ?int $id = null): array
    {
        $d = [
            'farm_id'               => $this->s($in, 'farm_id'),
            'crop_id'               => $this->s($in, 'crop_id'),
            'planting_date'         => $this->s($in, 'planting_date'),
            'area_planted'          => $this->s($in, 'area_planted'),
            'seed_quantity'         => $this->s($in, 'seed_quantity'),
            'expected_harvest_date' => $this->s($in, 'expected_harvest_date'),
            'status_id'             => $this->s($in, 'status_id'),
        ];
        $e = [];
        $farmArea = null;
        $growDays = null;

        // Foreign keys must point to real records
        if (!Validator::integer($d['farm_id'], 1, 2147483647)) {
            $e['farm_id'] = 'Please choose a farm.';
        } else {
            $stmt = $this->pdo->prepare('SELECT area_hectares FROM farm WHERE farm_id = :id');
            $stmt->execute(['id' => (int) $d['farm_id']]);
            $farmArea = $stmt->fetchColumn();
            if ($farmArea === false) $e['farm_id'] = 'The selected farm does not exist.';
        }
        if (!Validator::integer($d['crop_id'], 1, 2147483647)) {
            $e['crop_id'] = 'Please choose a crop.';
        } else {
            $stmt = $this->pdo->prepare('SELECT growing_period_days FROM crop WHERE crop_id = :id');
            $stmt->execute(['id' => (int) $d['crop_id']]);
            $growDays = $stmt->fetchColumn();
            if ($growDays === false) $e['crop_id'] = 'The selected crop does not exist.';
        }
        if (!Validator::integer($d['status_id'], 1, 2147483647) || !Lookup::exists('planting_status', (int) $d['status_id'])) {
            $e['status_id'] = 'Please choose a status.';
        }

        // Dates
        if ($d['planting_date'] === '') {
            $e['planting_date'] = 'Planting date is required.';
        } elseif (!Validator::date($d['planting_date'])) {
            $e['planting_date'] = 'Enter a valid date.';
        }

        if ($d['expected_harvest_date'] !== '' && !Validator::date($d['expected_harvest_date'])) {
            $e['expected_harvest_date'] = 'Enter a valid date.';
        } elseif (!isset($e['planting_date'])) {
            if ($d['expected_harvest_date'] === '' && $growDays !== null && $growDays !== false) {
                // Not typed in: calculate from the crop's growing period
                $d['expected_harvest_date'] = (new DateTime($d['planting_date']))
                    ->modify('+' . (int) $growDays . ' days')->format('Y-m-d');
            } elseif ($d['expected_harvest_date'] !== '' && $d['expected_harvest_date'] < $d['planting_date']) {
                $e['expected_harvest_date'] = 'Expected harvest date cannot be before the planting date.';
            }
        }

        // Numbers
        if ($d['area_planted'] === '') {
            $e['area_planted'] = 'Area planted is required.';
        } elseif (!Validator::decimal($d['area_planted'], 0.01, 10000)) {
            $e['area_planted'] = 'Area planted must be a number greater than 0 (max 2 decimals).';
        } elseif ($farmArea !== null && $farmArea !== false && (float) $d['area_planted'] > (float) $farmArea) {
            $e['area_planted'] = 'Area planted cannot be larger than the farm size (' . $farmArea . ' ha).';
        }
        if ($d['seed_quantity'] === '') {
            $e['seed_quantity'] = 'Seed quantity is required.';
        } elseif (!Validator::decimal($d['seed_quantity'], 0, 999999)) {
            $e['seed_quantity'] = 'Seed quantity must be a number of 0 or more (max 2 decimals).';
        }
        return [$d, $e];
    }

    public function create(array $d, int $userId): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO planting (farm_id, crop_id, planting_date, area_planted, seed_quantity,
                                   expected_harvest_date, status_id, created_by, updated_by)
             VALUES (:farm_id, :crop_id, :planting_date, :area_planted, :seed_quantity,
                     :expected_harvest_date, :status_id, :u, :u2) RETURNING planting_id'
        );
        $stmt->execute($this->bind($d) + ['u' => $userId, 'u2' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public function update(int $id, array $d, int $userId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE planting SET farm_id = :farm_id, crop_id = :crop_id, planting_date = :planting_date,
                    area_planted = :area_planted, seed_quantity = :seed_quantity,
                    expected_harvest_date = :expected_harvest_date, status_id = :status_id, updated_by = :u
              WHERE planting_id = :id'
        );
        $stmt->execute($this->bind($d) + ['u' => $userId, 'id' => $id]);
    }

    /** Empty expected date becomes NULL. */
    private function bind(array $d): array
    {
        $d['expected_harvest_date'] = $d['expected_harvest_date'] === '' ? null : $d['expected_harvest_date'];
        return $d;
    }
}
