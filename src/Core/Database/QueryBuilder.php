<?php
namespace JiFramework\Core\Database;

use PDO;
use PDOException;
use JiFramework\Core\Database\DatabaseConnection;
use JiFramework\Exceptions\DatabaseException;

class QueryBuilder
{
    /**
     * The PDO instance.
     *
     * @var PDO
     */
    protected $pdo;

    /**
     * The name of the database connection.
     *
     * @var string
     */
    protected $connectionName;

    /**
     * The table to execute queries on.
     *
     * @var string
     */
    protected $table;

    /**
     * The columns to select.
     *
     * @var array
     */
    protected $columns = ['*'];

    /**
     * The where clauses for the query.
     *
     * @var array
     */
    protected $wheres = [];

    /**
     * The join clauses for the query.
     *
     * @var array
     */
    protected $joins = [];

    /**
     * The values for bindings.
     *
     * @var array
     */
    protected $bindings = [];

    /**
     * The limit for the query.
     *
     * @var int|null
     */
    protected $limit;

    /**
     * The offset for the query.
     *
     * @var int|null
     */
    protected $offset;

    /**
     * The order by clauses for the query.
     *
     * @var array
     */
    protected $orderBys = [];

    /**
     * The group by clauses for the query.
     *
     * @var array
     */
    protected $groupBys = [];

    /**
     * The having clauses for the query.
     *
     * @var array
     */
    protected $havings = [];

    /**
     * The raw SQL query.
     *
     * @var string|null
     */
    protected $rawSql = null;

    /**
     * The raw bindings.
     *
     * @var array
     */
    protected $rawBindings = [];

    /**
     * Whether to use SELECT DISTINCT.
     *
     * @var bool
     */
    protected $distinct = false;

    /**
     * Create a new QueryBuilder instance.
     *
     * @param string $connectionName
     */
    public function __construct($connectionName = 'primary')
    {
        $this->connectionName = $connectionName;
        $this->pdo = DatabaseConnection::getConnection($connectionName);
    }

    /**
     * Begin a transaction.
     *
     * @return void
     */
    public function beginTransaction()
    {
        $this->pdo->beginTransaction();
    }

    /**
     * Commit a transaction.
     *
     * @return void
     */
    public function commit()
    {
        $this->pdo->commit();
    }

    /**
     * Roll back a transaction.
     *
     * @return void
     */
    public function rollBack()
    {
        $this->pdo->rollBack();
    }

    /**
     * Set the table for the query.
     *
     * @param string $table
     * @return static
     */
    public function table($table)
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Set the columns to select.
     *
     * @param array|string $columns
     * @return static
     */
    public function select($columns = ['*'])
    {
        $this->columns = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    /**
     * Add a where clause to the query.
     *
     * @param string|array $column
     * @param string|null $operator
     * @param mixed|null $value
     * @param string $boolean
     * @return static
     */
    public function where($column, $operator = null, $value = null, $boolean = 'AND')
    {
        // Handle array of conditions
        if (is_array($column)) {
            foreach ($column as $key => $val) {
                $this->where($key, '=', $val, $boolean);
            }
            return $this;
        }

        // Swap parameters if only two are provided
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $param = $this->generateParameterName($column);

        $this->wheres[] = [
            'column'   => $column,
            'operator' => $operator,
            'param'    => $param,
            'boolean'  => $boolean,
        ];

        $this->bindings[$param] = $value;

        return $this;
    }

    /**
     * Add an OR where clause to the query.
     *
     * @param string|array $column
     * @param string|null $operator
     * @param mixed|null $value
     * @return static
     */
    public function orWhere($column, $operator = null, $value = null)
    {
        if (func_num_args() === 2) {
            $value    = $operator;
            $operator = '=';
        }
        return $this->where($column, $operator, $value, 'OR');
    }

    /**
     * Add a whereIn clause to the query.
     *
     * @param string $column
     * @param array $values
     * @param string $boolean
     * @param bool $not
     * @return static
     */
    public function whereIn($column, array $values, $boolean = 'AND', $not = false)
    {
        if (empty($values)) {
            // If no values, make the condition always false/true
            $sql = $not ? '1=1' : '1=0';
            $this->wheres[] = [
                'column'   => $sql,
                'operator' => '',
                'param'    => '',
                'boolean'  => $boolean,
            ];
            return $this;
        }

        $params = [];
        foreach ($values as $i => $value) {
            $param = $this->generateParameterName($column . $i);
            $params[] = $param;
            $this->bindings[$param] = $value;
        }
        $inClause = implode(', ', $params);
        $operator = $not ? 'NOT IN' : 'IN';

        $this->wheres[] = [
            'column'   => $column,
            'operator' => $operator . ' (' . $inClause . ')',
            'param'    => '',
            'boolean'  => $boolean,
        ];
        return $this;
    }

    /**
     * Add a whereNotIn clause to the query.
     *
     * @param string $column
     * @param array $values
     * @param string $boolean
     * @return static
     */
    public function whereNotIn($column, array $values, $boolean = 'AND')
    {
        return $this->whereIn($column, $values, $boolean, true);
    }


    /**
     * Add a join clause to the query.
     *
     * @param string $table
     * @param string $first
     * @param string $operator
     * @param string $second
     * @param string $type
     * @return static
     */
    public function join($table, $first, $operator, $second, $type = 'INNER')
    {
        $this->joins[] = compact('type', 'table', 'first', 'operator', 'second');
        return $this;
    }

    /**
     * Add a left join clause to the query.
     *
     * @param string $table
     * @param string $first
     * @param string $operator
     * @param string $second
     * @return static
     */
    public function leftJoin($table, $first, $operator, $second)
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    /**
     * Add a right join clause to the query.
     *
     * @param string $table
     * @param string $first
     * @param string $operator
     * @param string $second
     * @return static
     */
    public function rightJoin($table, $first, $operator, $second)
    {
        return $this->join($table, $first, $operator, $second, 'RIGHT');
    }

    /**
     * Set the limit for the query.
     *
     * @param int $limit
     * @return static
     */
    public function limit($limit)
    {
        $this->limit = (int)$limit;
        return $this;
    }

    /**
     * Set the offset for the query.
     *
     * @param int $offset
     * @return static
     */
    public function offset($offset)
    {
        $this->offset = (int)$offset;
        return $this;
    }

    /**
     * Add an order by clause to the query.
     *
     * @param string $column
     * @param string $direction
     * @return static
     */
    public function orderBy($column, $direction = 'ASC')
    {
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->orderBys[] = "{$column} {$direction}";
        return $this;
    }

    /**
     * Add a group by clause to the query.
     *
     * @param array|string $columns
     * @return static
     */
    public function groupBy($columns)
    {
        $this->groupBys = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    /**
     * Add a having clause to the query.
     *
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @param string $boolean
     * @return static
     */
    public function having($column, $operator, $value, $boolean = 'AND')
    {
        $param = $this->generateParameterName($column);

        $this->havings[] = [
            'column'   => $column,
            'operator' => $operator,
            'param'    => $param,
            'boolean'  => $boolean,
        ];

        $this->bindings[$param] = $value;

        return $this;
    }

    /**
     * Add an OR having clause to the query.
     *
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @return static
     */
    public function orHaving($column, $operator, $value)
    {
        return $this->having($column, $operator, $value, 'OR');
    }

    /**
     * Execute the query and get all results by get method.
     *
     * @return array
     * @throws \Exception
     */
    public function get()
    {
        $sql = '';
        try {
            if ($this->rawSql !== null) {
                $sql = $this->rawSql;
                $bindings = $this->rawBindings;
            } else {
                $sql = $this->toSql();
                $bindings = $this->bindings;
            }

            $stmt = $this->pdo->prepare($sql);
            $this->bindValues($stmt, $bindings);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new DatabaseException("Database query error: " . $e->getMessage() . " | SQL: " . $sql, 0, $e);
        } finally {
            $this->reset();
        }
    }

    /**
     * Execute the query and get the first result by first method.
     *
     * @return array|false
     * @throws \Exception
     */
    public function first(): ?array
    {
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? null;
    }

    /** @see get() */
    public function fetchAll(): array
    {
        return $this->get();
    }

    /** @see first() */
    public function fetch(): ?array
    {
        return $this->first();
    }

    /**
     * Fetch a single column value from the first result.
     *
     * @param string $column
     * @return mixed
     * @throws \Exception
     */
    public function value($column)
    {
        $this->select($column);
        $result = $this->fetch();
        return $result[$column] ?? null;
    }

    /**
     * Count the number of records.
     *
     * @return int
     * @throws \Exception
     */
    public function count()
    {
        $this->select("COUNT(*) AS count");
        $result = $this->fetch();
        return (int)($result['count'] ?? 0);
    }

    /**
     * Pluck values of a single column from the result set.
     *
     * @param string $column
     * @return array
     * @throws \Exception
     */
    public function pluck($column)
    {
        $this->select($column);

        $results = $this->get();

        // Support for alias (e.g. 'id as timeline_id')
        $columnKey = $column;
        if (stripos($column, ' as ') !== false) {
            $parts = preg_split('/\s+as\s+/i', $column);
            $columnKey = trim(end($parts));
        }

        // Return only the values of the selected column as a flat array
        return array_column($results, $columnKey);
    }


    /**
     * Insert a new record into the database.
     *
     * @param array $data
     * @return bool
     * @throws \Exception
     */
    public function insert(array $data): bool
    {
        $sql = '';
        try {
            $columns = array_keys($data);
            $params = array_map(function($column) {
                return ':' . $column;
            }, $columns);

            $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") ";
            $sql .= "VALUES (" . implode(', ', $params) . ")";

            $stmt = $this->pdo->prepare($sql);

            $bindings = [];
            foreach ($data as $column => $value) {
                $param = ':' . $column;
                $bindings[$param] = $value;
            }

            $this->bindValues($stmt, $bindings);
            $result = $stmt->execute();

            $this->reset();

            return $result;
        } catch (PDOException $e) {
            throw new DatabaseException("Database insert error: " . $e->getMessage() . " | SQL: " . $sql, 0, $e);
        }
    }

    /**
     * Update records in the database.
     *
     * @param array $data
     * @return int The number of affected rows.
     * @throws \Exception
     */
    public function update(array $data): int
    {
        $sql = '';
        try {
            $setClauses = [];
            $updateBindings = [];
            foreach ($data as $column => $value) {
                $param = ':' . $column;
                $setClauses[] = "{$column} = {$param}";
                $updateBindings[$param] = $value;
            }

            $sql = "UPDATE {$this->table} SET " . implode(', ', $setClauses);

            if ($this->wheres) {
                $sql .= ' WHERE ' . $this->buildWheres();
                $bindings = array_merge($updateBindings, $this->bindings);
            } else {
                $bindings = $updateBindings;
            }

            $stmt = $this->pdo->prepare($sql);
            $this->bindValues($stmt, $bindings);
            $stmt->execute();

            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new DatabaseException("Database update error: " . $e->getMessage() . " | SQL: " . $sql, 0, $e);
        } finally {
            $this->reset();
        }
    }

    /**
     * Delete records from the database.
     *
     * @return int The number of affected rows.
     * @throws \Exception
     */
    public function delete(): int
    {
        $sql = '';
        try {
            $sql = "DELETE FROM {$this->table}";

            if ($this->wheres) {
                $sql .= ' WHERE ' . $this->buildWheres();
                $bindings = $this->bindings;
            } else {
                $bindings = [];
            }

            $stmt = $this->pdo->prepare($sql);
            $this->bindValues($stmt, $bindings);
            $stmt->execute();

            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new DatabaseException("Database delete error: " . $e->getMessage() . " | SQL: " . $sql, 0, $e);
        } finally {
            $this->reset();
        }
    }

    /**
     * Execute the query without fetching results.
     *
     * @return int The number of affected rows.
     * @throws \Exception
     */
    public function execute()
    {
        $sql = '';
        try {
            if ($this->rawSql !== null) {
                $sql = $this->rawSql;
                $bindings = $this->rawBindings;
            } else {
                $sql = $this->toSql();
                $bindings = $this->bindings;
            }

            $stmt = $this->pdo->prepare($sql);
            $this->bindValues($stmt, $bindings);
            $stmt->execute();

            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new DatabaseException("Database execute error: " . $e->getMessage() . " | SQL: " . $sql, 0, $e);
        } finally {
            $this->reset();
        }
    }

    /**
     * Get the SQL representation of the query.
     *
     * @return string
     */
    public function toSql()
    {
        // If raw SQL is set, return it.
        if ($this->rawSql !== null) {
            return $this->rawSql;
        }

        $sql = 'SELECT ' . ($this->distinct ? 'DISTINCT ' : '') . implode(', ', $this->columns) . " FROM {$this->table}";

        if ($this->joins) {
            foreach ($this->joins as $join) {
                $sql .= " {$join['type']} JOIN {$join['table']} ON {$join['first']} {$join['operator']} {$join['second']}";
            }
        }

        if ($this->wheres) {
            $sql .= ' WHERE ' . $this->buildWheres();
        }

        if ($this->groupBys) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groupBys);
        }

        if ($this->havings) {
            $sql .= ' HAVING ' . $this->buildHavings();
        }

        if ($this->orderBys) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orderBys);
        }

        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . $this->limit;
        }

        if ($this->offset !== null) {
            $sql .= ' OFFSET ' . $this->offset;
        }

        return $sql;
    }

    // =========================================================================
    // Select modifiers
    // =========================================================================

    /**
     * Add a raw expression to the SELECT clause.
     * $app->db->table('orders')->selectRaw('COUNT(*) as total, SUM(amount) as revenue')->get();
     */
    public function selectRaw(string $expression): static
    {
        $this->columns = [$expression];
        return $this;
    }

    /**
     * Force SELECT DISTINCT.
     */
    public function distinct(): static
    {
        $this->distinct = true;
        return $this;
    }

    // =========================================================================
    // Additional where clauses
    // =========================================================================

    /**
     * WHERE column IS NULL
     */
    public function whereNull(string $column, string $boolean = 'AND'): static
    {
        $this->wheres[] = [
            'column'   => $column,
            'operator' => 'IS NULL',
            'param'    => '',
            'boolean'  => $boolean,
        ];
        return $this;
    }

    /**
     * WHERE column IS NOT NULL
     */
    public function whereNotNull(string $column, string $boolean = 'AND'): static
    {
        $this->wheres[] = [
            'column'   => $column,
            'operator' => 'IS NOT NULL',
            'param'    => '',
            'boolean'  => $boolean,
        ];
        return $this;
    }

    /**
     * WHERE column BETWEEN min AND max
     */
    public function whereBetween(string $column, $min, $max, string $boolean = 'AND'): static
    {
        $paramMin = $this->generateParameterName($column . '_min');
        $paramMax = $this->generateParameterName($column . '_max');

        $this->bindings[$paramMin] = $min;
        $this->bindings[$paramMax] = $max;

        $this->wheres[] = [
            'column'   => $column,
            'operator' => 'BETWEEN ' . $paramMin . ' AND',
            'param'    => $paramMax,
            'boolean'  => $boolean,
        ];
        return $this;
    }

    /**
     * WHERE column NOT BETWEEN min AND max
     */
    public function whereNotBetween(string $column, $min, $max, string $boolean = 'AND'): static
    {
        $paramMin = $this->generateParameterName($column . '_min');
        $paramMax = $this->generateParameterName($column . '_max');

        $this->bindings[$paramMin] = $min;
        $this->bindings[$paramMax] = $max;

        $this->wheres[] = [
            'column'   => $column,
            'operator' => 'NOT BETWEEN ' . $paramMin . ' AND',
            'param'    => $paramMax,
            'boolean'  => $boolean,
        ];
        return $this;
    }

    /**
     * Add a raw WHERE expression.
     * ->whereRaw('YEAR(created_at) = :year', ['year' => 2024])
     */
    public function whereRaw(string $sql, array $bindings = [], string $boolean = 'AND'): static
    {
        $this->wheres[] = [
            'column'   => $sql,
            'operator' => '',
            'param'    => '',
            'boolean'  => $boolean,
        ];

        foreach ($bindings as $key => $value) {
            $param = strpos($key, ':') === 0 ? $key : ':' . $key;
            $this->bindings[$param] = $value;
        }

        return $this;
    }

    // =========================================================================
    // Order / limit shorthands
    // =========================================================================

    /** ORDER BY column DESC */
    public function orderByDesc(string $column): static
    {
        return $this->orderBy($column, 'DESC');
    }

    /** ORDER BY column DESC — shorthand for newest rows first */
    public function latest(string $column = 'created_at'): static
    {
        return $this->orderBy($column, 'DESC');
    }

    /** ORDER BY column ASC — shorthand for oldest rows first */
    public function oldest(string $column = 'created_at'): static
    {
        return $this->orderBy($column, 'ASC');
    }

    /** Alias for limit() */
    public function take(int $limit): static
    {
        return $this->limit($limit);
    }

    /** Alias for offset() */
    public function skip(int $offset): static
    {
        return $this->offset($offset);
    }

    // =========================================================================
    // Aggregate methods
    // =========================================================================

    /** SELECT MAX(column) */
    public function max(string $column)
    {
        $result = $this->select("MAX($column) AS aggregate")->fetch();
        return $result['aggregate'] ?? null;
    }

    /** SELECT MIN(column) */
    public function min(string $column)
    {
        $result = $this->select("MIN($column) AS aggregate")->fetch();
        return $result['aggregate'] ?? null;
    }

    /** SELECT SUM(column) */
    public function sum(string $column)
    {
        $result = $this->select("SUM($column) AS aggregate")->fetch();
        return $result['aggregate'] ?? 0;
    }

    /** SELECT AVG(column) */
    public function avg(string $column)
    {
        $result = $this->select("AVG($column) AS aggregate")->fetch();
        return $result['aggregate'] ?? null;
    }

    /**
     * Returns true if any rows match the current query.
     */
    public function exists(): bool
    {
        return $this->count() > 0;
    }

    /**
     * Returns true if no rows match the current query.
     */
    public function doesntExist(): bool
    {
        return !$this->exists();
    }

    // =========================================================================
    // Write helpers
    // =========================================================================

    /**
     * Insert a row and return the last inserted ID.
     *
     * @return string|false
     */
    public function insertGetId(array $data)
    {
        $success = $this->insert($data);
        return $success ? $this->pdo->lastInsertId() : false;
    }

    /**
     * Increment a column value by the given amount.
     * ->table('posts')->where('id', 1)->increment('views')
     */
    public function increment(string $column, int $amount = 1): int
    {
        return $this->adjustColumn($column, abs($amount), '+');
    }

    /**
     * Decrement a column value by the given amount.
     * ->table('products')->where('id', 1)->decrement('stock')
     */
    public function decrement(string $column, int $amount = 1): int
    {
        return $this->adjustColumn($column, abs($amount), '-');
    }

    /**
     * Truncate the table — removes all rows, resets auto-increment.
     */
    public function truncate(): void
    {
        $this->pdo->exec("TRUNCATE TABLE {$this->table}");
        $this->reset();
    }

    /**
     * Insert a row, silently skipping if a duplicate unique key exists.
     *
     * INSERT IGNORE INTO table (...) VALUES (...)
     */
    public function insertOrIgnore(array $data): bool
    {
        $sql = '';
        try {
            $columns = array_keys($data);
            $params  = array_map(fn($col) => ':' . $col, $columns);

            $sql  = "INSERT IGNORE INTO {$this->table} (";
            $sql .= implode(', ', $columns) . ") VALUES (";
            $sql .= implode(', ', $params) . ")";

            $stmt     = $this->pdo->prepare($sql);
            $bindings = [];
            foreach ($data as $column => $value) {
                $bindings[':' . $column] = $value;
            }

            $this->bindValues($stmt, $bindings);
            $result = $stmt->execute();
            $this->reset();
            return $result;
        } catch (\PDOException $e) {
            throw new DatabaseException("Database insertOrIgnore error: " . $e->getMessage() . " | SQL: " . $sql, 0, $e);
        }
    }

    /**
     * Insert a row, or update it if a duplicate unique key is found.
     *
     * INSERT INTO table (...) VALUES (...)
     * ON DUPLICATE KEY UPDATE col = val, ...
     *
     * @param array $data       Full row data to insert
     * @param array $uniqueKeys Columns that identify a duplicate (used to exclude from UPDATE)
     */
    public function upsert(array $data, array $uniqueKeys = []): bool
    {
        $sql = '';
        try {
            $columns = array_keys($data);
            $params  = array_map(fn($col) => ':' . $col, $columns);

            $sql  = "INSERT INTO {$this->table} (";
            $sql .= implode(', ', $columns) . ") VALUES (";
            $sql .= implode(', ', $params) . ")";

            // Build ON DUPLICATE KEY UPDATE for all non-unique columns
            $updateColumns = empty($uniqueKeys)
                ? $columns
                : array_filter($columns, fn($col) => !in_array($col, $uniqueKeys));

            $updates = array_map(fn($col) => "{$col} = :{$col}", $updateColumns);
            $sql .= " ON DUPLICATE KEY UPDATE " . implode(', ', $updates);

            $stmt     = $this->pdo->prepare($sql);
            $bindings = [];
            foreach ($data as $column => $value) {
                $bindings[':' . $column] = $value;
            }

            $this->bindValues($stmt, $bindings);
            $result = $stmt->execute();
            $this->reset();
            return $result;
        } catch (\PDOException $e) {
            throw new DatabaseException("Database upsert error: " . $e->getMessage() . " | SQL: " . $sql, 0, $e);
        }
    }

    /**
     * Process large result sets in small batches to keep memory usage low.
     *
     * $app->db->table('users')->chunk(500, function($rows) {
     *     foreach ($rows as $row) { ... }
     * });
     *
     * Return false from the callback to stop early.
     */
    public function chunk(int $size, callable $callback): void
    {
        $offset = 0;

        // Snapshot current wheres/bindings — reused for each batch
        $wheres   = $this->wheres;
        $bindings = $this->bindings;
        $columns  = $this->columns;
        $orderBys = $this->orderBys;

        while (true) {
            // Restore state for each batch (get() resets after executing)
            $this->wheres   = $wheres;
            $this->bindings = $bindings;
            $this->columns  = $columns;
            $this->orderBys = $orderBys;

            $rows = $this->limit($size)->offset($offset)->get();

            if (empty($rows)) {
                break;
            }

            if ($callback($rows) === false) {
                break;
            }

            $offset += $size;

            // If we got fewer rows than the chunk size we've reached the end
            if (count($rows) < $size) {
                break;
            }
        }
    }

    /**
     * Paginate the query results.
     *
     * Runs two queries automatically:
     *   1. COUNT(*) with the same WHERE / JOIN / GROUP BY conditions.
     *   2. SELECT with LIMIT + OFFSET for the requested page.
     *
     * The returned object has the same shape as Paginator::paginate() plus
     * a ->data property, so it works directly with Paginator::renderLinks().
     *
     * @param int      $perPage Number of rows per page.
     * @param int|null $page    Current page number. Reads $_GET['page'] when null.
     * @return object{data:array, currentPage:int, totalPages:int, totalItems:int,
     *                perPage:int, offset:int, hasNext:bool, hasPrevious:bool,
     *                nextPage:int, previousPage:int, queryParams:string}
     */
    public function paginate(int $perPage, ?int $page = null): object
    {
        $perPage     = max(1, $perPage);
        $currentPage = $page ?? (isset($_GET['page']) ? (int) $_GET['page'] : 1);
        $currentPage = max(1, $currentPage);

        // Snapshot query state — count() resets wheres/bindings/joins after executing
        $wheres   = $this->wheres;
        $bindings = $this->bindings;
        $columns  = $this->columns;
        $joins    = $this->joins;
        $orderBys = $this->orderBys;
        $groupBys = $this->groupBys;
        $havings  = $this->havings;

        // Query 1: total count (shares all WHERE conditions)
        $total = $this->count();

        // Restore state for the data query
        $this->wheres   = $wheres;
        $this->bindings = $bindings;
        $this->columns  = $columns;
        $this->joins    = $joins;
        $this->orderBys = $orderBys;
        $this->groupBys = $groupBys;
        $this->havings  = $havings;

        // Page math
        $totalPages  = max(1, (int) ceil($total / $perPage));
        $currentPage = min($currentPage, $totalPages);
        $offset      = ($currentPage - 1) * $perPage;

        // Query 2: paginated data
        $data = $this->limit($perPage)->offset($offset)->get();

        // Carry through any extra $_GET params (e.g. search/filter) for link building
        $qp = $_GET;
        unset($qp['page']);
        $queryParamString = !empty($qp)
            ? http_build_query($qp, '', '&amp;') . '&amp;'
            : '';

        return (object) [
            'data'         => $data,
            'currentPage'  => $currentPage,
            'totalPages'   => $totalPages,
            'totalItems'   => $total,
            'perPage'      => $perPage,
            'offset'       => $offset,
            'hasNext'      => $currentPage < $totalPages,
            'hasPrevious'  => $currentPage > 1,
            'nextPage'     => min($currentPage + 1, $totalPages),
            'previousPage' => max($currentPage - 1, 1),
            'queryParams'  => $queryParamString,
        ];
    }

    // =========================================================================
    // Conditional query building
    // =========================================================================

    /**
     * Apply the callback only when condition is true.
     *
     * ->when($request['active'], fn($q) => $q->where('status', 'active'))
     */
    public function when($condition, callable $callback): static
    {
        if ($condition) {
            $callback($this);
        }
        return $this;
    }

    // =========================================================================
    // Internal helpers
    // =========================================================================

    /**
     * Shared logic for increment() and decrement().
     */
    private function adjustColumn(string $column, int $amount, string $operator): int
    {
        $param = $this->generateParameterName('adj');
        $this->bindings[$param] = $amount;

        $sql = "UPDATE {$this->table} SET {$column} = {$column} {$operator} {$param}";

        if ($this->wheres) {
            $sql .= ' WHERE ' . $this->buildWheres();
        }

        try {
            $stmt = $this->pdo->prepare($sql);
            $this->bindValues($stmt, $this->bindings);
            $stmt->execute();
            $affected = $stmt->rowCount();
            $this->reset();
            return $affected;
        } catch (\PDOException $e) {
            throw new DatabaseException("Database error: " . $e->getMessage() . " | SQL: " . $sql, 0, $e);
        }
    }

    /**
     * Build the where clause.
     *
     * @return string
     */
    protected function buildWheres()
    {
        $clauses = [];
        foreach ($this->wheres as $index => $where) {
            $prefix = $index === 0 ? '' : $where['boolean'] . ' ';
            $clauses[] = $prefix . "{$where['column']} {$where['operator']} {$where['param']}";
        }
        return implode(' ', $clauses);
    }

    /**
     * Build the having clause.
     *
     * @return string
     */
    protected function buildHavings()
    {
        $clauses = [];
        foreach ($this->havings as $index => $having) {
            $prefix = $index === 0 ? '' : $having['boolean'] . ' ';
            $clauses[] = $prefix . "{$having['column']} {$having['operator']} {$having['param']}";
        }
        return implode(' ', $clauses);
    }

    /**
     * Reset the query builder state.
     */
    protected function reset()
    {
        $this->columns = ['*'];
        $this->wheres = [];
        $this->joins = [];
        $this->bindings = [];
        $this->limit = null;
        $this->offset = null;
        $this->orderBys = [];
        $this->groupBys = [];
        $this->havings = [];
        $this->rawSql = null;
        $this->rawBindings = [];
        $this->distinct = false;
    }

    /**
     * Execute a raw SQL query.
     *
     * @param string $sql
     * @return static
     */
    public function query($sql)
    {
        $this->rawSql = $sql;
        return $this;
    }

    /**
     * Bind a parameter to the query.
     *
     * @param string|array $param
     * @param mixed|null $value
     * @param int|null $type
     * @return static
     */
    public function bind($param, $value = null, $type = null)
    {
        if (is_array($param)) {
            foreach ($param as $key => $val) {
                // Automatically add ':' if not present
                if (strpos($key, ':') !== 0) {
                    $key = ':' . $key;
                }
                $detectedType = is_null($type) ? $this->detectParamType($val) : $type;
                $this->rawBindings[$key] = ['value' => $val, 'type' => $detectedType];
            }
        } else {
            // Automatically add ':' if not present
            if (strpos($param, ':') !== 0) {
                $param = ':' . $param;
            }
            if (is_null($type)) {
                $type = $this->detectParamType($value);
            }
            $this->rawBindings[$param] = ['value' => $value, 'type' => $type];
        }
        return $this;
    }

    /**
     * Detect the PDO parameter type based on the value.
     *
     * @param mixed $value
     * @return int
     */
    protected function detectParamType($value)
    {
        switch (true) {
            case is_int($value):
                return PDO::PARAM_INT;
            case is_bool($value):
                return PDO::PARAM_BOOL;
            case is_null($value):
                return PDO::PARAM_NULL;
            default:
                return PDO::PARAM_STR;
        }
    }

    /**
     * Bind values to the PDO statement.
     *
     * @param \PDOStatement $stmt
     * @param array $bindings
     * @return void
     */
    protected function bindValues($stmt, $bindings)
    {
        foreach ($bindings as $param => $value) {
            if (is_array($value) && isset($value['value'], $value['type'])) {
                $stmt->bindValue($param, $value['value'], $value['type']);
            } else {
                $type = $this->detectParamType($value);
                $stmt->bindValue($param, $value, $type);
            }
        }
    }

    /**
     * Get the last inserted ID.
     *
     * @return string
     */
    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * Generate a unique parameter name.
     *
     * @param string $column
     * @return string
     */
    protected function generateParameterName($column)
    {
        return ':' . str_replace('.', '_', $column) . '_' . count($this->bindings);
    }
}