
<?php
declare(strict_types=1);

class Farmer extends BaseModel
{
    protected string $table = 'farmer';
    protected string $pk    = 'farmer_id';

    private const SORT = [
        'id'      => 'f.farmer_id',
        'name'    => 'f.last_name',
        'contact' => 'f.contact_number',
        'address' => 'f.address',
        'farms'   => 'farm_count',
        'date'    => 'f.date_registered',
    ];

    /** List with search ?q=, filter ?address=, sorting and pagination. */
    public function search(array $in): array
    {
        $where = [];
        $params = [];
        if (($q = $this->s($in, 'q')) !== '') {
            $where[] = "(f.first_name || ' ' || f.last_name || ' ' || f.contact_number || ' ' || f.address) ILIKE :q";
            $params['q'] = $this->like($q);
        }
        if (($a = $this->s($in, 'address')) !== '') {
            $where[] = 'f.address = :address';
            $params['address'] = $a;
        }
        return $this->paginate(
            'f.*, (SELECT COUNT(*) FROM farm x WHERE x.farmer_id = f.farmer_id) AS farm_count',
            'farmer f',
            $where, $params,
            $this->orderBy(self::SORT, $in, 'id', 'f.farmer_id'),
            (int) ($in['page'] ?? 1)
        );
    }

    /** Distinct addresses for the filter dropdown. */
    public function addresses(): array
    {
        return $this->pdo->query(
            'SELECT DISTINCT address AS id, address AS label FROM farmer ORDER BY address'
        )->fetchAll();
    }

    public function dropdown(): array
    {
        return $this->pdo->query(
            "SELECT farmer_id AS id, last_name || ', ' || first_name AS label FROM farmer ORDER BY last_name, first_name"
        )->fetchAll();
    }

    /** Farms owned by one farmer (shown on the View page). */
    public function farms(int $id): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT fm.farm_id, fm.farm_name, fm.location, fm.area_hectares, st.soil_type_name, it.irrigation_type_name
               FROM farm fm
               JOIN soil_type st       ON st.soil_type_id = fm.soil_type_id
               JOIN irrigation_type it ON it.irrigation_type_id = fm.irrigation_type_id
              WHERE fm.farmer_id = :id ORDER BY fm.farm_name'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetchAll();
    }

    /** @return array{0:array,1:array} [clean values, errors] */
    public function validate(array $in, ?int $id = null): array
    {
        $d = [
            'first_name'     => $this->s($in, 'first_name'),
            'last_name'      => $this->s($in, 'last_name'),
            'contact_number' => $this->s($in, 'contact_number'),
            'address'        => $this->s($in, 'address'),
        ];
        $e = [];

        foreach (['first_name' => 'First name', 'last_name' => 'Last name'] as $f => $label) {
            if ($d[$f] === '')               $e[$f] = "$label is required.";
            elseif (mb_strlen($d[$f]) > 50)  $e[$f] = "$label must be 50 characters or fewer.";
        }
        if ($d['contact_number'] === '') {
            $e['contact_number'] = 'Contact number is required.';
        } elseif (!preg_match('/^[0-9+() -]{7,20}$/', $d['contact_number'])) {
            $e['contact_number'] = 'Use 7-20 digits (you may use + - ( ) and spaces).';
        } elseif ($this->taken('contact_number', $d['contact_number'], $id)) {
            $e['contact_number'] = 'This contact number is already registered to another farmer.';
        }
        if ($d['address'] === '')              $e['address'] = 'Address is required.';
        elseif (mb_strlen($d['address']) > 150) $e['address'] = 'Address must be 150 characters or fewer.';

        return [$d, $e];
    }

    public function create(array $d, int $userId): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO farmer (first_name, last_name, contact_number, address, created_by, updated_by)
             VALUES (:first_name, :last_name, :contact_number, :address, :u, :u2) RETURNING farmer_id'
        );
        $stmt->execute($d + ['u' => $userId, 'u2' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public function update(int $id, array $d, int $userId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE farmer SET first_name = :first_name, last_name = :last_name,
                    contact_number = :contact_number, address = :address, updated_by = :u
              WHERE farmer_id = :id'
        );
        $stmt->execute($d + ['u' => $userId, 'id' => $id]);
    }
}
