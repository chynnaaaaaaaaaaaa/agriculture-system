<?php
declare(strict_types=1);

/** Small reusable input checks used by every model's validate() method. */
class Validator
{
    /** Real calendar date in YYYY-MM-DD format (rejects 2026-02-31). */
    public static function date(string $value): bool
    {
        $d = DateTime::createFromFormat('Y-m-d', $value);
        return $d !== false && $d->format('Y-m-d') === $value;
    }

    /** Positive/zero decimal with at most $places decimals, inside [min, max]. */
    public static function decimal(string $value, float $min, float $max, int $places = 2): bool
    {
        if (!preg_match('/^\d+(\.\d{1,' . $places . '})?$/', $value)) {
            return false;
        }
        $n = (float) $value;
        return $n >= $min && $n <= $max;
    }

    public static function integer(string $value, int $min, int $max): bool
    {
        if (!preg_match('/^\d+$/', $value)) {
            return false;
        }
        $n = (int) $value;
        return $n >= $min && $n <= $max;
    }
}
