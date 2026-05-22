<?php

use Illuminate\Support\Facades\DB;

/**
 * Returns the correct case-insensitive LIKE operator for the active DB driver.
 * PostgreSQL uses ILIKE; MySQL/MariaDB LIKE is case-insensitive by default.
 */
function db_like(): string
{
    return DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
}
