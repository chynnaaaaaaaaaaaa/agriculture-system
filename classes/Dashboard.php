<?php
declare(strict_types=1);

/** All dashboard numbers come from live database queries - nothing is hard-coded. */
class Dashboard
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function counts(): array
    {
        return $this->pdo->query(
            'SELECT (SELECT COUNT(*) FROM farmer)   AS farmers,
                    (SELECT COUNT(*) FROM farm)     AS farms,
                    (SELECT COUNT(*) FROM crop)     AS crops,
                    (SELECT COUNT(*) FROM planting) AS plantings,
                    (SELECT COUNT(*) FROM harvest)  AS harvests'
        )->fetch();
    }

    public function harvestTotals(): array
    {
        return $this->pdo->query(
            'SELECT COALESCE(SUM(quantity_kg), 0) AS total_kg,
                    COALESCE(SUM(quantity_kg * selling_price_per_kg), 0) AS total_value
               FROM harvest'
        )->fetch();
    }

    public function recentPlantings(int $limit = 5): array
    {
        return $this->pdo->query(
            "SELECT p.planting_id, p.planting_date, fm.farm_name, c.crop_name, ps.status_name
               FROM planting p
               JOIN farm fm            ON fm.farm_id = p.farm_id
               JOIN crop c             ON c.crop_id = p.crop_id
               JOIN planting_status ps ON ps.status_id = p.status_id
              ORDER BY p.planting_date DESC, p.planting_id DESC LIMIT " . (int) $limit
        )->fetchAll();
    }

    public function recentHarvests(int $limit = 5): array
    {
        return $this->pdo->query(
            "SELECT h.harvest_id, h.harvest_date, h.quantity_kg, fm.farm_name, c.crop_name, g.grade_name
               FROM harvest h
               JOIN planting p      ON p.planting_id = h.planting_id
               JOIN farm fm         ON fm.farm_id = p.farm_id
               JOIN crop c          ON c.crop_id = p.crop_id
               JOIN quality_grade g ON g.grade_id = h.grade_id
              ORDER BY h.harvest_date DESC, h.harvest_id DESC LIMIT " . (int) $limit
        )->fetchAll();
    }

    /** Every status is listed, even those with 0 plantings. */
    public function plantingsByStatus(): array
    {
        return $this->pdo->query(
            'SELECT ps.status_name, COUNT(p.planting_id) AS total
               FROM planting_status ps
          LEFT JOIN planting p ON p.status_id = ps.status_id
              GROUP BY ps.status_id, ps.status_name ORDER BY ps.status_id'
        )->fetchAll();
    }

    public function harvestByCrop(): array
    {
        return $this->pdo->query(
            'SELECT c.crop_name, SUM(h.quantity_kg) AS total_kg
               FROM harvest h
               JOIN planting p ON p.planting_id = h.planting_id
               JOIN crop c     ON c.crop_id = p.crop_id
              GROUP BY c.crop_id, c.crop_name ORDER BY total_kg DESC'
        )->fetchAll();
    }
}
