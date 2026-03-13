<?php
namespace JiFramework\Core\Database;

/**
 * Base Model class.
 *
 * Extend this class to create a model for a database table.
 *
 * Usage:
 *   class User extends Model {
 *       protected static string $table = 'users';
 *   }
 *
 *   User::all();
 *   User::find(1);
 *   User::create(['name' => 'John']);   // insert + return new ID
 *   User::exists(1);                    // bool PK check
 *   User::where('status', 'active')->get();
 *   User::insert(['name' => 'John']);
 *   User::update(['name' => 'John'], 1);
 *   User::destroy(1);
 */
abstract class Model
{
    /** Database table name — must be set in child class. */
    protected static string $table;

    /** Primary key column name. */
    protected static string $primaryKey = 'id';

    /** Named database connection to use. Defaults to primary. */
    protected static string $connection = 'primary';

    // =========================================================================
    // Internal
    // =========================================================================

    /**
     * Return a fresh QueryBuilder scoped to this model's table and connection.
     * A new instance is created each time to avoid state accumulation.
     * The underlying PDO connection is still cached by DatabaseConnection.
     */
    private static function qb(): QueryBuilder
    {
        return (new QueryBuilder(static::$connection))->table(static::$table);
    }

    // =========================================================================
    // Query methods
    // =========================================================================

    /**
     * Get all rows from the table.
     */
    public static function all(): array
    {
        return static::qb()->get();
    }

    /**
     * Find a single row by primary key.
     * Returns null if not found.
     */
    public static function find($id): ?array
    {
        return static::qb()
            ->where(static::$primaryKey, '=', $id)
            ->first();
    }

    /**
     * Start a query with a where clause.
     * Returns a QueryBuilder so you can chain further conditions.
     *
     * // 2-argument shorthand (assumes = operator)
     * User::where('status', 'active')->get();
     *
     * // 3-argument with explicit operator
     * User::where('age', '>', 18)->get();
     */
    public static function where(string $column, $operatorOrValue, $value = null): QueryBuilder
    {
        if ($value === null) {
            return static::qb()->where($column, '=', $operatorOrValue);
        }
        return static::qb()->where($column, $operatorOrValue, $value);
    }

    /**
     * Get the first row from the table.
     */
    public static function first(): ?array
    {
        return static::qb()->first();
    }

    /**
     * Count all rows in the table.
     */
    public static function count(): int
    {
        return static::qb()->count();
    }

    // =========================================================================
    // Write methods
    // =========================================================================

    /**
     * Insert a new row.
     */
    public static function insert(array $data): bool
    {
        return static::qb()->insert($data);
    }

    /**
     * Insert a new row and return the new primary key.
     * Returns false if the insert fails.
     *
     * $id = User::create(['name' => 'John', 'email' => 'john@example.com']);
     *
     * @return int|false
     */
    public static function create(array $data): int|false
    {
        $qb = static::qb();
        $success = $qb->insert($data);
        return $success ? (int) $qb->lastInsertId() : false;
    }

    /**
     * Check whether a row with the given primary key exists.
     *
     * User::exists(1); // true / false
     */
    public static function exists($id): bool
    {
        return static::qb()
            ->where(static::$primaryKey, '=', $id)
            ->exists();
    }

    /**
     * Update a row by primary key.
     *
     * User::update(['name' => 'John'], 1);
     */
    public static function update(array $data, $id): bool
    {
        return static::qb()
            ->where(static::$primaryKey, '=', $id)
            ->update($data);
    }

    /**
     * Delete a row by primary key.
     *
     * User::destroy(1);
     */
    public static function destroy($id): bool
    {
        return static::qb()
            ->where(static::$primaryKey, '=', $id)
            ->delete();
    }
}
