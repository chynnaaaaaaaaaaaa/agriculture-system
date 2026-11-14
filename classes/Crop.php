<?php
declare(strict_types=1);

class Crop extends BaseModel
{
    protected string $table = 'crop';
    protected string $pk    = 'crop_id';

    private const SORT = [
        'id'       => 'c.crop_id',
        'name'     => 'c.crop_name',
        'variety'  => 'c.variety',
        'category' => 'cc.category_name',
        'days'     => 'c.growing_period_days',
        'season'   => 's.season_name',
    ];

    private const FROM = 'crop c
        JOIN crop_category cc ON cc.crop_category_id = c.crop_category_id
        JOIN season s         ON s.season_id = c.season_id';

    public function search(array $in): array
    {
        $where = [];
        $params = [];
        if (($q = $this->s($in, 'q')) !== '') {
            $where[] = "(c.crop_name || ' ' || c.variety) ILIKE :q";
            $params['q'] = $this->like($q);
        }
        if (Validator::integer($this->s($in, 'crop_category_id'), 1, 2147483647)) {
            $where[] = 'c.crop_category_id = :cat';
            $params['cat'] = (int) $in['crop_category_id'];
        }
        if (Validator::integer($this->s($in, 'season_id'), 1, 2147483647)) {
            $where[] = 'c.season_id = :season';
            $params['season'] = (int) $in['season_id'];
        }
        return $this->paginate(
            'c.crop_id, c.crop_name, c.variety, c.growing_period_days, cc.category_name, s.season_name',
            self::FROM, $where, $params,
            $this->orderBy(self::SORT, $in, 'id', 'c.crop_id'),
            (int) ($in['page'] ?? 1)
        );
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.*, cc.category_name, s.season_name FROM ' . self::FROM . ' WHERE c.crop_id = :id'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function dropdown(): array
    {
        return $this->pdo->query(
            "SELECT crop_id AS id, crop_name || ' - ' || variety AS label FROM crop ORDER BY crop_name, variety"
        )->fetchAll();
    }

    public function validate(array $in, ?int $id = null): array
    {
        $d = [
            'crop_name'           => $this->s($in, 'crop_name'),
            'variety'             => $this->s($in, 'variety'),
            'crop_category_id'    => $this->s($in, 'crop_category_id'),
            'growing_period_days' => $this->s($in, 'growing_period_days'),
            'season_id'           => $this->s($in, 'season_id'),
        ];
        $e = [];

        if ($d['crop_name'] === '')              $e['crop_name'] = 'Crop name is required.';
        elseif (mb_strlen($d['crop_name']) > 80)  $e['crop_name'] = 'Crop name must be 80 characters or fewer.';

        if ($d['variety'] === '')                $e['variety'] = 'Variety is required.';
        elseif (mb_strlen($d['variety']) > 80)    $e['variety'] = 'Variety must be 80 characters or fewer.';

        if (!isset($e['crop_name']) && !isset($e['variety'])
            && $this->duplicate($d['crop_name'], $d['variety'], $id)) {
            $e['variety'] = 'This crop and variety combination already exists.';
        }

        if (!Validator::integer($d['crop_category_id'], 1, 2147483647) || !Lookup::exists('crop_category', (int) $d['crop_category_id'])) {
            $e['crop_category_id'] = 'Please choose a category.';
        }
        if (!Validator::integer($d['growing_period_days'], 1, 730)) {
            $e['growing_period_days'] = 'Growing period must be a whole number of days from 1 to 730.';
        }
        if (!Validator::integer($d['season_id'], 1, 2147483647) || !Lookup::exists('season', (int) $d['season_id'])) {
            $e['season_id'] = 'Please choose a recommended season.';
        }
        return [$d, $e];
    }

    private function duplicate(string $name, string $variety, ?int $ignoreId): bool
    {
        $sql = 'SELECT 1 FROM crop WHERE LOWER(crop_name) = LOWER(:n) AND LOWER(variety) = LOWER(:v)';
        $p = ['n' => $name, 'v' => $variety];
        if ($ignoreId !== null) {
            $sql .= ' AND crop_id <> :id';
            $p['id'] = $ignoreId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($p);
        return (bool) $stmt->fetchColumn();
    }

    public function create(array $d, int $userId): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO crop (crop_name, variety, crop_category_id, growing_period_days, season_id, created_by, updated_by)
             VALUES (:crop_name, :variety, :crop_category_id, :growing_period_days, :season_id, :u, :u2)
             RETURNING crop_id'
        );
        $stmt->execute($d + ['u' => $userId, 'u2' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public function update(int $id, array $d, int $userId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE crop SET crop_name = :crop_name, variety = :variety, crop_category_id = :crop_category_id,
                    growing_period_days = :growing_period_days, season_id = :season_id, updated_by = :u
              WHERE crop_id = :id'
        );
        $stmt->execute($d + ['u' => $userId, 'id' => $id]);
    }
}
