<?php
namespace JiFramework\Core\Database;

use PDO;
use PDOException;
use Exception;
use JiFramework\Core\Database\DatabaseConnection;

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
        return $this->where($column, $operator, $value, 'OR');
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

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->reset();

            return $results;
        } catch (PDOException $e) {
            // Handle exception
            throw new \Exception("Database query error: " . $e->getMessage());
        }
    }

    /**
     * Execute the query and get the first result by first method.
     *
     * @return array|false
     * @throws \Exception
     */
    public function first() 
    {
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? false;
    }

    /**
     * Execute the query and get all results.
     *
     * @return array
     * @throws \Exception
     */
    public function fetchAll()
    {
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

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->reset();

            return $results;
        } catch (PDOException $e) {
            // Handle exception
            throw new \Exception("Database query error: " . $e->getMessage());
        }
    }

    /**
     * Execute the query and get the first result.
     *
     * @return array|false
     * @throws \Exception
     */
    public function fetch()
    {
        $this->limit(1);
        $results = $this->fetchAll();
        return $results[0] ?? false;
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
     * Insert a new record into the database.
     *
     * @param array $data
     * @return bool
     * @throws \Exception
     */
    public function insert(array $data)
    {
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
            // Handle exception
            throw new \Exception("Database insert error: " . $e->getMessage());
        }
    }

    /**
     * Update records in the database.
     *
     * @param array $data
     * @return int The number of affected rows.
     * @throws \Exception
     */
    public function update(array $data)
    {
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

            $affectedRows = $stmt->rowCount();

            $this->reset();

            return $affectedRows;
        } catch (PDOException $e) {
            // Handle exception
            throw new \Exception("Database update error: " . $e->getMessage());
        }
    }

    /**
     * Delete records from the database.
     *
     * @return int The number of affected rows.
     * @throws \Exception
     */
    public function delete()
    {
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

            $affectedRows = $stmt->rowCount();

            $this->reset();

            return $affectedRows;
        } catch (PDOException $e) {
            // Handle exception
            throw new \Exception("Database delete error: " . $e->getMessage());
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

            $affectedRows = $stmt->rowCount();

            $this->reset();

            return $affectedRows;
        } catch (PDOException $e) {
            // Handle exception
            throw new \Exception("Database execute error: " . $e->getMessage());
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

        $sql = 'SELECT ' . implode(', ', $this->columns) . " FROM {$this->table}";

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


