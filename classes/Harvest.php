<?php
declare(strict_types=1);

class Harvest extends BaseModel
{
    protected string $table = 'harvest';
    protected string $pk    = 'harvest_id';

    private const SORT = [
        'id'       => 'h.harvest_id',
        'planting' => 'p.planting_id',
        'farm'     => 'fm.farm_name',
        'crop'     => 'c.crop_name',
        'date'     => 'h.harvest_date',
        'qty'      => 'h.quantity_kg',
        'price'    => 'h.selling_price_per_kg',
        'value'    => 'total_value',
        'grade'    => 'g.grade_name',
        'remark'   => 'r.remark_description',
    ];

    private const FROM = 'harvest h
        JOIN planting p       ON p.planting_id = h.planting_id
        JOIN farm fm          ON fm.farm_id = p.farm_id
        JOIN crop c           ON c.crop_id = p.crop_id
        JOIN quality_grade g  ON g.grade_id = h.grade_id
        JOIN harvest_remark r ON r.remark_id = h.remark_id';

    public function search(array $in): array
    {
        if (!isset($in['sort'])) {          // newest harvests first by default
            $in['sort'] = 'date';
            $in['dir']  = 'desc';
        }
        $where = [];
        $params = [];
        if (($q = $this->s($in, 'q')) !== '') {
            $where[] = "(fm.farm_name || ' ' || c.crop_name || ' ' || r.remark_description) ILIKE :q";
            $params['q'] = $this->like($q);
        }
        if (Validator::integer($this->s($in, 'grade_id'), 1, 2147483647)) {
            $where[] = 'h.grade_id = :grade';
            $params['grade'] = (int) $in['grade_id'];
        }
        if (Validator::integer($this->s($in, 'remark_id'), 1, 2147483647)) {
            $where[] = 'h.remark_id = :remark';
            $params['remark'] = (int) $in['remark_id'];
        }
        if (Validator::integer($this->s($in, 'crop_id'), 1, 2147483647)) {
            $where[] = 'p.crop_id = :crop';
            $params['crop'] = (int) $in['crop_id'];
        }
        return $this->paginate(
            'h.harvest_id, h.harvest_date, h.quantity_kg, h.selling_price_per_kg,
             (h.quantity_kg * h.selling_price_per_kg) AS total_value,
             p.planting_id, fm.farm_name, c.crop_name, g.grade_name, r.remark_description',
            self::FROM, $where, $params,
            $this->orderBy(self::SORT, $in, 'date', 'h.harvest_id'),
            (int) ($in['page'] ?? 1)
        );
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT h.*, (h.quantity_kg * h.selling_price_per_kg) AS total_value,
                    fm.farm_name, c.crop_name, c.variety, p.planting_date,
                    g.grade_name, r.remark_description
               FROM " . self::FROM . ' WHERE h.harvest_id = :id'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function validate(array $in, ?int $id = null): array
    {
        $d = [
            'planting_id'          => $this->s($in, 'planting_id'),
            'harvest_date'         => $this->s($in, 'harvest_date'),
            'quantity_kg'          => $this->s($in, 'quantity_kg'),
            'selling_price_per_kg' => $this->s($in, 'selling_price_per_kg'),
            'grade_id'             => $this->s($in, 'grade_id'),
            'remark_id'            => $this->s($in, 'remark_id'),
        ];
        $e = [];
        $plantingDate = null;

        if (!Validator::integer($d['planting_id'], 1, 2147483647)) {
            $e['planting_id'] = 'Please choose the planting record that was harvested.';
        } else {
            $stmt = $this->pdo->prepare('SELECT planting_date FROM planting WHERE planting_id = :id');
            $stmt->execute(['id' => (int) $d['planting_id']]);
            $plantingDate = $stmt->fetchColumn();
            if ($plantingDate === false) {
                $e['planting_id'] = 'The selected planting record does not exist.';
                $plantingDate = null;
            }
        }

        if ($d['harvest_date'] === '') {
            $e['harvest_date'] = 'Harvest date is required.';
        } elseif (!Validator::date($d['harvest_date'])) {
            $e['harvest_date'] = 'Enter a valid date.';
        } elseif ($plantingDate !== null && $d['harvest_date'] < $plantingDate) {
            $e['harvest_date'] = "Harvest date cannot be before the planting date ($plantingDate).";
        }

        if ($d['quantity_kg'] === '') {
            $e['quantity_kg'] = 'Quantity is required.';
        } elseif (!Validator::decimal($d['quantity_kg'], 0, 99999999)) {
            $e['quantity_kg'] = 'Quantity must be a number of 0 or more (max 2 decimals).';
        }
        if ($d['selling_price_per_kg'] === '') {
            $e['selling_price_per_kg'] = 'Selling price is required.';
        } elseif (!Validator::decimal($d['selling_price_per_kg'], 0, 99999999)) {
            $e['selling_price_per_kg'] = 'Selling price must be a number of 0 or more (max 2 decimals).';
        }
        if (!Validator::integer($d['grade_id'], 1, 2147483647) || !Lookup::exists('quality_grade', (int) $d['grade_id'])) {
            $e['grade_id'] = 'Please choose a quality grade.';
        }
        if (!Validator::integer($d['remark_id'], 1, 2147483647) || !Lookup::exists('harvest_remark', (int) $d['remark_id'])) {
            $e['remark_id'] = 'Please choose a remark.';
        }
        return [$d, $e];
    }

    public function create(array $d, int $userId): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO harvest (planting_id, harvest_date, quantity_kg, selling_price_per_kg, grade_id, remark_id,
                                  created_by, updated_by)
             VALUES (:planting_id, :harvest_date, :quantity_kg, :selling_price_per_kg, :grade_id, :remark_id, :u, :u2)
             RETURNING harvest_id'
        );
        $stmt->execute($d + ['u' => $userId, 'u2' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public function update(int $id, array $d, int $userId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE harvest SET planting_id = :planting_id, harvest_date = :harvest_date, quantity_kg = :quantity_kg,
                    selling_price_per_kg = :selling_price_per_kg, grade_id = :grade_id, remark_id = :remark_id,
                    updated_by = :u
              WHERE harvest_id = :id'
        );
        $stmt->execute($d + ['u' => $userId, 'id' => $id]);
    }
}
