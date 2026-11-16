
<?php
declare(strict_types=1);

class Farm extends BaseModel
{
    protected string $table = 'farm';
    protected string $pk    = 'farm_id';

    private const SORT = [
        'id'         => 'fm.farm_id',
        'name'       => 'fm.farm_name',
        'farmer'     => 'fr.last_name',
        'location'   => 'fm.location',
        'area'       => 'fm.area_hectares',
        'soil'       => 'st.soil_type_name',
        'irrigation' => 'it.irrigation_type_name',
    ];

    private const FROM = 'farm fm
        JOIN farmer fr          ON fr.farmer_id = fm.farmer_id
        JOIN soil_type st       ON st.soil_type_id = fm.soil_type_id
        JOIN irrigation_type it ON it.irrigation_type_id = fm.irrigation_type_id';

    public function search(array $in): array
    {
        $where = [];
        $params = [];
        if (($q = $this->s($in, 'q')) !== '') {
            $where[] = "(fm.farm_name || ' ' || fm.location || ' ' || fr.first_name || ' ' || fr.last_name) ILIKE :q";
            $params['q'] = $this->like($q);
        }
        if (Validator::integer($this->s($in, 'irrigation_type_id'), 1, 2147483647)) {
            $where[] = 'fm.irrigation_type_id = :irr';
            $params['irr'] = (int) $in['irrigation_type_id'];
        }
        if (Validator::integer($this->s($in, 'soil_type_id'), 1, 2147483647)) {
            $where[] = 'fm.soil_type_id = :soil';
            $params['soil'] = (int) $in['soil_type_id'];
        }
        return $this->paginate(
            "fm.farm_id, fm.farm_name, fm.location, fm.area_hectares,
             fr.first_name || ' ' || fr.last_name AS farmer_name,
             st.soil_type_name, it.irrigation_type_name",
            self::FROM, $where, $params,
            $this->orderBy(self::SORT, $in, 'id', 'fm.farm_id'),
            (int) ($in['page'] ?? 1)
        );
    }
 
    /** One farm with all joined names (for View) plus raw IDs (for Edit). */
    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT fm.*, fr.first_name || ' ' || fr.last_name AS farmer_name,
                    st.soil_type_name, it.irrigation_type_name
               FROM " . self::FROM . ' WHERE fm.farm_id = :id'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function dropdown(): array
    {
        return $this->pdo->query(
            "SELECT fm.farm_id AS id, fm.farm_name || ' (' || fr.last_name || ')' AS label
               FROM farm fm JOIN farmer fr ON fr.farmer_id = fm.farmer_id ORDER BY fm.farm_name"
        )->fetchAll();
    }

    public function plantings(int $id): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.planting_id, p.planting_date, p.area_planted, c.crop_name, c.variety, ps.status_name
               FROM planting p
               JOIN crop c ON c.crop_id = p.crop_id
               JOIN planting_status ps ON ps.status_id = p.status_id
              WHERE p.farm_id = :id ORDER BY p.planting_date DESC'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetchAll();
    }

    public function validate(array $in, ?int $id = null): array
    {
        $d = [
            'farmer_id'          => $this->s($in, 'farmer_id'),
            'farm_name'          => $this->s($in, 'farm_name'),
            'location'           => $this->s($in, 'location'),
            'area_hectares'      => $this->s($in, 'area_hectares'),
            'soil_type_id'       => $this->s($in, 'soil_type_id'),
            'irrigation_type_id' => $this->s($in, 'irrigation_type_id'),
        ];
        $e = [];

        if (!Validator::integer($d['farmer_id'], 1, 2147483647)) {
            $e['farmer_id'] = 'Please choose the farmer who owns this farm.';
        } elseif (!$this->exists('farmer', 'farmer_id', (int) $d['farmer_id'])) {
            $e['farmer_id'] = 'The selected farmer does not exist.';
        }

        if ($d['farm_name'] === '')              $e['farm_name'] = 'Farm name is required.';
        elseif (mb_strlen($d['farm_name']) > 100) $e['farm_name'] = 'Farm name must be 100 characters or fewer.';
        elseif (!isset($e['farmer_id']) && $this->duplicateName((int) $d['farmer_id'], $d['farm_name'], $id)) {
            $e['farm_name'] = 'This farmer already has a farm with that name.';
        }

        if ($d['location'] === '')               $e['location'] = 'Location is required.';
        elseif (mb_strlen($d['location']) > 150)  $e['location'] = 'Location must be 150 characters or fewer.';

        if ($d['area_hectares'] === '') {
            $e['area_hectares'] = 'Area is required.';
        } elseif (!Validator::decimal($d['area_hectares'], 0.01, 10000)) {
            $e['area_hectares'] = 'Area must be a number from 0.01 to 10,000 hectares (max 2 decimals).';
        }

        if (!Validator::integer($d['soil_type_id'], 1, 2147483647) || !Lookup::exists('soil_type', (int) $d['soil_type_id'])) {
            $e['soil_type_id'] = 'Please choose a soil type.';
        }
        if (!Validator::integer($d['irrigation_type_id'], 1, 2147483647) || !Lookup::exists('irrigation_type', (int) $d['irrigation_type_id'])) {
            $e['irrigation_type_id'] = 'Please choose an irrigation type.';
        }
        return [$d, $e];
    }

    private function duplicateName(int $farmerId, string $name, ?int $ignoreId): bool
    {
        $sql = 'SELECT 1 FROM farm WHERE farmer_id = :f AND LOWER(farm_name) = LOWER(:n)';
        $p = ['f' => $farmerId, 'n' => $name];
        if ($ignoreId !== null) {
            $sql .= ' AND farm_id <> :id';
            $p['id'] = $ignoreId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($p);
        return (bool) $stmt->fetchColumn();
    }

    public function create(array $d, int $userId): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO farm (farmer_id, farm_name, location, area_hectares, soil_type_id, irrigation_type_id, created_by, updated_by)
             VALUES (:farmer_id, :farm_name, :location, :area_hectares, :soil_type_id, :irrigation_type_id, :u, :u2)
             RETURNING farm_id'
        );
        $stmt->execute($d + ['u' => $userId, 'u2' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public function update(int $id, array $d, int $userId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE farm SET farmer_id = :farmer_id, farm_name = :farm_name, location = :location,
                    area_hectares = :area_hectares, soil_type_id = :soil_type_id,
                    irrigation_type_id = :irrigation_type_id, updated_by = :u
              WHERE farm_id = :id'
        );
        $stmt->execute($d + ['u' => $userId, 'id' => $id]);
    }
}
