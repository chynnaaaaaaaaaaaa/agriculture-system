<?php
declare(strict_types=1);

/** Shared database helpers for all entity classes. */
abstract class BaseModel
{
    protected PDO $pdo;
    protected string $table;
    protected string $pk;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    /** Returns a trimmed string from user input (arrays and missing keys become ''). */
    protected function s(array $in, string $key): string
    {
        return isset($in[$key]) && is_string($in[$key]) ? trim($in[$key]) : '';
    }

    /** Is there a row in $table where $col = $id?  (identifiers are fixed strings in our code) */
    protected function exists(string $table, string $col, int $id): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM $table WHERE $col = :id");
        $stmt->execute(['id' => $id]);
        return (bool) $stmt->fetchColumn();
    }

    /** Is $value already used in $col by a different row? */
    protected function taken(string $col, string $value, ?int $ignoreId): bool
    {
        $sql = "SELECT 1 FROM {$this->table} WHERE LOWER($col) = LOWER(:v)";
        $params = ['v' => $value];
        if ($ignoreId !== null) {
            $sql .= " AND {$this->pk} <> :id";
            $params['id'] = $ignoreId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE {$this->pk} = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /** Who created / last changed a record, and when (shown on View pages). */
    public function audit(int $id): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT t.created_at, t.updated_at, cu.full_name AS created_by_name, uu.full_name AS updated_by_name
               FROM {$this->table} t
          LEFT JOIN app_user cu ON cu.user_id = t.created_by
          LEFT JOIN app_user uu ON uu.user_id = t.updated_by
              WHERE t.{$this->pk} = :id"
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: [];
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE {$this->pk} = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /** Only allow sorting by columns we list ourselves (prevents SQL injection via ?sort=). */
    protected function orderBy(array $allowed, array $in, string $defaultKey, string $tieBreaker): string
    {
        $key = $in['sort'] ?? $defaultKey;
        $col = $allowed[$key] ?? $allowed[$defaultKey];
        $dir = (($in['dir'] ?? 'asc') === 'desc') ? 'DESC' : 'ASC';
        return "$col $dir, $tieBreaker ASC";
    }

    /**
     * Generic paginated query.
     * @return array{rows:array,total:int,page:int,pages:int}
     */
    protected function paginate(string $select, string $from, array $where, array $params,
                                string $orderBy, int $page, int $perPage = 10): array
    {
        $w = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM $from $w");
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        $pages  = max(1, (int) ceil($total / $perPage));
        $page   = min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;

        $stmt = $this->pdo->prepare(
            "SELECT $select FROM $from $w ORDER BY $orderBy LIMIT $perPage OFFSET $offset"
        );
        $stmt->execute($params);

        return ['rows' => $stmt->fetchAll(), 'total' => $total, 'page' => $page, 'pages' => $pages];
    }

    protected function like(string $term): string
    {
        return '%' . addcslashes($term, '%_\\') . '%';
    }
}
