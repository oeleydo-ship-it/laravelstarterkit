<?php

namespace App\Support;

/**
 * Literal substring matching with LIKE.
 *
 * Escaping `%` and `_` is not enough on its own: SQLite has no default escape
 * character, so `LIKE '%100\%%'` looks for a literal backslash and matches
 * nothing. The ESCAPE clause has to be stated, and MySQL and Postgres accept
 * the same clause, so this is portable across all three.
 */
class LikeSearch
{
    public static function pattern(string $term): string
    {
        // Backslash first, or the escapes added below get escaped again.
        return '%'.str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $term).'%';
    }

    /**
     * @param  string  $column  A column name from application code — never user input.
     */
    public static function clause(string $column): string
    {
        return "{$column} LIKE ? ESCAPE '\\'";
    }
}
