<?php
declare(strict_types=1);

/**
 * Reads the lookup/reference tables that feed the dropdown lists.
 * The user sees the label; the database stores the numeric ID.
 */
class Lookup
{
    /** table => [id column, label column] */
    private const TABLES = [
        'soil_type'       => ['soil_type_id',       'soil_type_name'],
        'irrigation_type' => ['irrigation_type_id', 'irrigation_type_name'],
        'crop_category'   => ['crop_category_id',   'category_name'],
        'season'          => ['season_id',          'season_name'],
        'planting_status' => ['status_id',          'status_name'],
        'quality_grade'   => ['grade_id',           'grade_name'],
        'harvest_remark'  => ['remark_id',          'remark_description'],
    ];

    /** @return array<int,array{id:int,label:string}> */
    public static function all(string $table): array
    {
        [$id, $label] = self::TABLES[$table];
        return Database::connection()
            ->query("SELECT $id AS id, $label AS label FROM $table ORDER BY $id")
            ->fetchAll();
    }

    public static function exists(string $table, int $id): bool
    {
        [$idCol] = self::TABLES[$table];
        $stmt = Database::connection()->prepare("SELECT 1 FROM $table WHERE $idCol = :id");
        $stmt->execute(['id' => $id]);
        return (bool) $stmt->fetchColumn();
    }
}
