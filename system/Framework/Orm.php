<?php

/**
* @package      ORM - Active Record & Query Builder
* @author       YoYo
* @copyright    Copyright (c) 2025, script-php.ro
* @link         https://script-php.ro
*/

namespace System\Framework;

abstract class Orm {

    /**
     * Database connection
     */
    protected static $db = null;

    /**
     * Table name (override in child class)
     */
    protected static $table = '';

    /**
     * Primary key column name
     */
    protected static $primaryKey = 'id';

    /**
     * Timestamps columns
     */
    protected static $timestamps = true;
    protected static $createdAtColumn = 'created_at';
    protected static $updatedAtColumn = 'updated_at';

    /**
     * Fillable columns (whitelist for mass assignment)
     */
    protected static $fillable = [];

    /**
     * Guarded columns (blacklist for mass assignment)
     */
    protected static $guarded = ['id'];

    /**
     * Hidden columns (excluded from toArray/toJson)
     */
    protected static $hidden = [];

    /**
     * Casts for attributes
     */
    protected static $casts = [];

    /**
     * Soft delete column
     */
    protected static $softDelete = false;
    protected static $deletedAtColumn = 'deleted_at';

    /**
     * Instance data
     */
    protected $attributes = [];
    protected $original = [];
    protected $exists = false;
    protected $relations = [];

    /**
     * Query builder state
     */
    protected $querySelect = ['*'];
    protected $queryWhere = [];
    protected $queryParams = [];
    protected $queryOrderBy = [];
    protected $queryLimit = null;
    protected $queryOffset = null;
    protected $queryJoins = [];
    protected $queryGroupBy = [];
    protected $queryHaving = [];
    protected $queryWith = [];
    protected $withTrashed = false;
    protected $onlyTrashed = false;
    protected $asArray = false;

    /**
     * Constructor
     */
    public function __construct(array $attributes = []) {
        if (!empty($attributes)) {
            $this->fill($attributes);
        }
    }

    /**
     * Set database connection
     */
    public static function setConnection($db) {
        static::$db = $db;
    }

    /**
     * Get database connection
     */
    protected static function getConnection() {
        if (static::$db === null) {
            // Try to get from global registry
            $registry = \System\Framework\Registry::getInstance();
            if ($registry->has('db')) {
                static::$db = $registry->get('db');
            } else {
                throw new \Exception('No database connection available. Set connection using Orm::setConnection() or configure database in Framework.');
            }
        }
        return static::$db;
    }

    /**
     * Get table name
     */
    protected static function getTable() {
        if (empty(static::$table)) {
            // Auto-generate table name from class name
            $className = (new \ReflectionClass(static::class))->getShortName();
            static::$table = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className)) . 's';
        }
        return static::$table;
    }

    /**
     * Create new query builder instance
     */
    public static function query() {
        return new static();
    }

    /**
     * Find record by ID
     */
    public static function find($id) {
        return static::query()->where(static::$primaryKey, '=', $id)->first();
    }

    /**
     * Find record by ID or throw exception
     */
    public static function findOrFail($id) {
        $result = static::find($id);
        if (!$result) {
            throw new \Exception('Record not found with ' . static::$primaryKey . ' = ' . $id);
        }
        return $result;
    }

    /**
     * Get all records
     */
    public static function all() {
        return static::query()->get();
    }

    /**
     * Find records using raw SQL
     * 
     * @param string $sql SQL query with placeholders
     * @param array $params Parameters to bind
     * @return Collection
     */
    public static function findBySql($sql, $params = []) {
        $db = static::getConnection();
        $result = $db->query($sql, $params);

        $instances = [];
        if (!empty($result->rows)) {
            foreach ($result->rows as $row) {
                $instance = new static();
                $instance->setRawAttributes($row);
                $instance->exists = true;
                $instance->original = $row;
                $instances[] = $instance;
            }
        }

        return new Collection($instances);
    }

    /**
     * Get first record
     */
    public static function first() {
        return static::query()->limit(1)->get()->first();
    }

    /**
     * Check if any records exist
     */
    public function exists() {
        return $this->count() > 0;
    }

    /**
     * Create new record
     */
    public static function create(array $attributes) {
        $instance = new static($attributes);
        $instance->save();
        return $instance;
    }

    /**
     * Find or create a record
     */
    public static function firstOrCreate(array $attributes, array $values = []) {
        $instance = static::query();
        foreach ($attributes as $key => $value) {
            $instance->where($key, $value);
        }
        
        $result = $instance->first();
        
        if ($result) {
            return $result;
        }
        
        return static::create(array_merge($attributes, $values));
    }

    /**
     * Update or create a record
     */
    public static function updateOrCreate(array $attributes, array $values = []) {
        $instance = static::query();
        foreach ($attributes as $key => $value) {
            $instance->where($key, $value);
        }
        
        $result = $instance->first();
        
        if ($result) {
            $result->fill($values);
            $result->save();
            return $result;
        }
        
        return static::create(array_merge($attributes, $values));
    }

    /**
     * Find or return new instance
     */
    public static function findOrNew($id) {
        $result = static::find($id);
        return $result ?: new static();
    }

    /**
     * Bulk insert records
     */
    public static function insert(array $records) {
        if (empty($records)) {
            return false;
        }

        $table = static::getTable();
        $columns = array_keys($records[0]);
        
        // Build INSERT query
        $sql = "INSERT INTO `{$table}` (`" . implode('`, `', $columns) . "`) VALUES ";
        
        $valuePlaceholders = [];
        $params = [];
        
        foreach ($records as $record) {
            $placeholders = array_fill(0, count($columns), '?');
            $valuePlaceholders[] = '(' . implode(', ', $placeholders) . ')';
            
            foreach ($columns as $column) {
                $params[] = $record[$column] ?? null;
            }
        }
        
        $sql .= implode(', ', $valuePlaceholders);
        
        $db = static::getConnection();
        return $db->query($sql, $params);
    }

    /**
     * WHERE clause
     */
    public function where($column, $operator = null, $value = null) {
        // Handle where($column, $value) shorthand
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->queryWhere[] = [
            'type' => 'AND',
            'column' => $column,
            'operator' => $operator,
            'value' => $value
        ];

        return $this;
    }

    /**
     * AND WHERE clause (alias for where)
     */
    public function andWhere($column, $operator = null, $value = null) {
        return $this->where($column, $operator, $value);
    }

    /**
     * Filter WHERE clause - ignores null and empty string values
     */
    public function filterWhere($column, $operator = null, $value = null) {
        // Handle shorthand
        if ($value === null && $operator !== null) {
            $value = $operator;
            $operator = '=';
        }

        // Skip if value is null or empty string
        if ($value === null || $value === '') {
            return $this;
        }

        return $this->where($column, $operator, $value);
    }

    /**
     * AND Filter WHERE clause
     */
    public function andFilterWhere($column, $operator = null, $value = null) {
        return $this->filterWhere($column, $operator, $value);
    }

    /**
     * OR WHERE clause
     */
    public function orWhere($column, $operator = null, $value = null) {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->queryWhere[] = [
            'type' => 'OR',
            'column' => $column,
            'operator' => $operator,
            'value' => $value
        ];

        return $this;
    }

    /**
     * WHERE IN clause
     */
    public function whereIn($column, array $values) {
        if (empty($values)) {
            // Handle empty array - always false condition
            $this->queryWhere[] = [
                'type' => 'AND',
                'column' => $column,
                'operator' => 'IN',
                'value' => [null] // Will never match
            ];
        } else {
            $this->queryWhere[] = [
                'type' => 'AND',
                'column' => $column,
                'operator' => 'IN',
                'value' => $values
            ];
        }

        return $this;
    }

    /**
     * WHERE NOT IN clause
     */
    public function whereNotIn($column, array $values) {
        if (empty($values)) {
            // Empty NOT IN means all records match
            return $this;
        }

        $this->queryWhere[] = [
            'type' => 'AND',
            'column' => $column,
            'operator' => 'NOT IN',
            'value' => $values
        ];

        return $this;
    }

    /**
     * WHERE BETWEEN clause
     */
    public function whereBetween($column, array $values) {
        if (count($values) !== 2) {
            throw new \Exception('whereBetween requires exactly 2 values [min, max]');
        }

        $this->queryWhere[] = [
            'type' => 'AND',
            'column' => $column,
            'operator' => 'BETWEEN',
            'value' => $values
        ];

        return $this;
    }

    /**
     * WHERE NOT BETWEEN clause
     */
    public function whereNotBetween($column, array $values) {
        if (count($values) !== 2) {
            throw new \Exception('whereNotBetween requires exactly 2 values [min, max]');
        }

        $this->queryWhere[] = [
            'type' => 'AND',
            'column' => $column,
            'operator' => 'NOT BETWEEN',
            'value' => $values
        ];

        return $this;
    }

    /**
     * WHERE NULL clause
     */
    public function whereNull($column) {
        $this->queryWhere[] = [
            'type' => 'AND',
            'column' => $column,
            'operator' => 'IS NULL',
            'value' => null
        ];

        return $this;
    }

    /**
     * WHERE NOT NULL clause
     */
    public function whereNotNull($column) {
        $this->queryWhere[] = [
            'type' => 'AND',
            'column' => $column,
            'operator' => 'IS NOT NULL',
            'value' => null
        ];

        return $this;
    }

    /**
     * WHERE DATE clause - match date part only
     */
    public function whereDate($column, $operator, $value = null) {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->queryWhere[] = [
            'type' => 'AND',
            'column' => $column,
            'operator' => 'DATE_' . $operator,
            'value' => $value
        ];

        return $this;
    }

    /**
     * WHERE MONTH clause - match month part
     */
    public function whereMonth($column, $operator, $value = null) {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->queryWhere[] = [
            'type' => 'AND',
            'column' => $column,
            'operator' => 'MONTH_' . $operator,
            'value' => $value
        ];

        return $this;
    }

    /**
     * WHERE YEAR clause - match year part
     */
    public function whereYear($column, $operator, $value = null) {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->queryWhere[] = [
            'type' => 'AND',
            'column' => $column,
            'operator' => 'YEAR_' . $operator,
            'value' => $value
        ];

        return $this;
    }

    /**
     * WHERE TIME clause - match time part
     */
    public function whereTime($column, $operator, $value = null) {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->queryWhere[] = [
            'type' => 'AND',
            'column' => $column,
            'operator' => 'TIME_' . $operator,
            'value' => $value
        ];

        return $this;
    }

    /**
     * SELECT columns
     */
    public function select(...$columns) {
        $this->querySelect = $columns;
        return $this;
    }

    /**
     * ORDER BY clause
     */
    public function orderBy($column, $direction = 'ASC') {
        $this->queryOrderBy[] = [$column, strtoupper($direction)];
        return $this;
    }

    /**
     * LIMIT clause
     */
    public function limit($limit) {
        $this->queryLimit = $limit;
        return $this;
    }

    /**
     * OFFSET clause
     */
    public function offset($offset) {
        $this->queryOffset = $offset;
        return $this;
    }

    /**
     * JOIN clause
     */
    public function join($table, $first, $operator = null, $second = null, $type = 'INNER') {
        if ($second === null) {
            $second = $operator;
            $operator = '=';
        }

        $this->queryJoins[] = [
            'type' => $type,
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second
        ];

        return $this;
    }

    /**
     * LEFT JOIN clause
     */
    public function leftJoin($table, $first, $operator = null, $second = null) {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    /**
     * RIGHT JOIN clause
     */
    public function rightJoin($table, $first, $operator = null, $second = null) {
        return $this->join($table, $first, $operator, $second, 'RIGHT');
    }

    /**
     * COUNT query
     */
    public function count($column = '*') {
        $this->querySelect = ["COUNT({$column}) as count"];
        $result = $this->get();
        return $result->first()->count ?? 0;
    }

    /**
     * Get single column values
     */
    public function pluck($column, $key = null) {
        $this->querySelect = $key ? [$column, $key] : [$column];
        $results = $this->get();
        
        $plucked = [];
        foreach ($results as $result) {
            if ($key) {
                $plucked[$result->$key] = $result->$column;
            } else {
                $plucked[] = $result->$column;
            }
        }
        
        return $plucked;
    }

    /**
     * Increment a column value
     */
    public function increment($column, $amount = 1) {
        return $this->incrementOrDecrement($column, $amount, 'increment');
    }

    /**
     * Decrement a column value
     */
    public function decrement($column, $amount = 1) {
        return $this->incrementOrDecrement($column, $amount, 'decrement');
    }

    /**
     * Increment or decrement column
     */
    protected function incrementOrDecrement($column, $amount, $type) {
        if (empty($this->queryWhere)) {
            throw new \Exception("Cannot {$type} without WHERE clause.");
        }

        $table = static::getTable();
        $operator = $type === 'increment' ? '+' : '-';
        
        $sql = "UPDATE `{$table}` SET `{$column}` = `{$column}` {$operator} ?";
        $params = [$amount];

        // Add WHERE clauses
        if (!empty($this->queryWhere)) {
            $whereClauses = [];
            foreach ($this->queryWhere as $index => $where) {
                $type = $index === 0 ? '' : $where['type'] . ' ';
                
                if ($where['operator'] === 'IN') {
                    $placeholders = implode(', ', array_fill(0, count($where['value']), '?'));
                    $whereClauses[] = "{$type}`{$where['column']}` IN ({$placeholders})";
                    $params = array_merge($params, $where['value']);
                } else {
                    $whereClauses[] = "{$type}`{$where['column']}` {$where['operator']} ?";
                    $params[] = $where['value'];
                }
            }
            $sql .= " WHERE " . implode(' ', $whereClauses);
        }

        $db = static::getConnection();
        $db->query($sql, $params);
        
        return $db->countAffected();
    }

    /**
     * GROUP BY clause
     */
    public function groupBy(...$columns) {
        $this->queryGroupBy = array_merge($this->queryGroupBy, $columns);
        return $this;
    }

    /**
     * HAVING clause
     */
    public function having($column, $operator, $value) {
        $this->queryHaving[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value
        ];
        return $this;
    }

    /**
     * Eager load relationships
     */
    public function with(...$relations) {
        $this->queryWith = array_merge($this->queryWith, $relations);
        return $this;
    }

    /**
     * Paginate results
     */
    public function paginate($perPage = 15, $page = null) {
        $page = $page ?: (isset($_GET['page']) ? (int)$_GET['page'] : 1);
        $page = max(1, $page);
        
        // Get total count
        $countQuery = clone $this;
        $total = $countQuery->count();
        
        // Calculate pagination
        $lastPage = max((int) ceil($total / $perPage), 1);
        $page = min($page, $lastPage);
        $offset = ($page - 1) * $perPage;
        
        // Get paginated results
        $results = $this->limit($perPage)->offset($offset)->get();
        
        return (object) [
            'data' => $results,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => $lastPage,
            'from' => $total > 0 ? $offset + 1 : null,
            'to' => $total > 0 ? min($offset + $perPage, $total) : null,
            'has_more_pages' => $page < $lastPage,
            'next_page' => $page < $lastPage ? $page + 1 : null,
            'prev_page' => $page > 1 ? $page - 1 : null
        ];
    }

    /**
     * Process records in chunks to avoid memory issues
     * 
     * @param int $count Number of records per chunk
     * @param callable $callback Function to call for each chunk
     * @return bool
     */
    public function chunk($count, callable $callback) {
        $page = 1;
        
        do {
            // Clone the query to avoid state pollution
            $query = clone $this;
            
            // Get chunk of results
            $results = $query->limit($count)->offset(($page - 1) * $count)->get();
            
            // If no results, we're done
            if (empty($results)) {
                break;
            }
            
            // Call the callback with the chunk
            // If callback returns false, stop processing
            if ($callback($results, $page) === false) {
                return false;
            }
            
            $page++;
            
            // Continue while we have a full chunk (meaning there might be more)
        } while (count($results) === $count);
        
        return true;
    }

    /**
     * Execute query and get results
     */
    public function get() {
        $sql = $this->buildSelectQuery();
        $db = static::getConnection();
        $result = $db->query($sql, $this->queryParams);

        if ($this->asArray) {
            // Return as plain arrays
            return new Collection($result->rows ?? []);
        }

        $instances = [];
        if (!empty($result->rows)) {
            foreach ($result->rows as $row) {
                $instance = new static();
                $instance->setRawAttributes($row);
                $instance->exists = true;
                $instance->original = $row;
                $instances[] = $instance;
            }
        }

        // Load eager relationships
        if (!empty($this->queryWith) && !empty($instances)) {
            $this->eagerLoadRelations($instances);
        }

        return new Collection($instances);
    }

    /**
     * Return results as arrays instead of model instances
     */
    public function asArray() {
        $this->asArray = true;
        return $this;
    }

    /**
     * Get a single column's value from the first result
     */
    public function scalar() {
        $result = $this->limit(1)->get();
        
        if ($result->isEmpty()) {
            return null;
        }

        $first = $result->first();
        
        if (is_array($first)) {
            return reset($first);
        }
        
        // Get first attribute from model
        $attributes = $first->attributes;
        return reset($attributes);
    }

    /**
     * Get all values of a single column
     */
    public function column() {
        $results = $this->asArray()->get();
        
        if ($results->isEmpty()) {
            return [];
        }

        $first = $results->first();
        $columnName = is_array($first) ? array_key_first($first) : null;
        
        if (!$columnName) {
            return [];
        }

        return $results->pluck($columnName)->all();
    }

    /**
     * Eager load relationships
     */
    protected function eagerLoadRelations(array $instances) {
        foreach ($this->queryWith as $relation) {
            // Call the relationship method
            if (method_exists($instances[0], $relation)) {
                $relationInstance = $instances[0]->$relation();
                
                if ($relationInstance instanceof Orm) {
                    // Collect all foreign keys
                    $keys = [];
                    foreach ($instances as $instance) {
                        $keys[] = $instance->{$relationInstance->foreignKey};
                    }
                    $keys = array_unique(array_filter($keys));
                    
                    if (!empty($keys)) {
                        // Load all related records at once
                        $related = $relationInstance->whereIn($relationInstance->ownerKey, $keys)->get();
                        
                        // Map related records to instances
                        $relatedMap = [];
                        foreach ($related as $rel) {
                            $relatedMap[$rel->{$relationInstance->ownerKey}][] = $rel;
                        }
                        
                        // Attach to instances
                        foreach ($instances as $instance) {
                            $foreignKeyValue = $instance->{$relationInstance->foreignKey};
                            $instance->setRelation($relation, $relatedMap[$foreignKeyValue] ?? []);
                        }
                    }
                }
            }
        }
    }

    /**
     * Set a relationship
     */
    public function setRelation($name, $value) {
        $this->relations[$name] = $value;
        return $this;
    }

    /**
     * Get a relationship
     */
    public function getRelation($name) {
        return $this->relations[$name] ?? null;
    }

    /**
     * Build SELECT query
     */
    protected function buildSelectQuery() {
        $table = static::getTable();
        $select = implode(', ', $this->querySelect);
        
        $sql = "SELECT {$select} FROM `{$table}`";

        // Add JOINs
        foreach ($this->queryJoins as $join) {
            $sql .= " {$join['type']} JOIN `{$join['table']}` ON {$join['first']} {$join['operator']} {$join['second']}";
        }

        // Build WHERE clause
        $whereClauses = [];
        
        // Add soft delete filter
        if (static::$softDelete && !$this->withTrashed && !$this->onlyTrashed) {
            $whereClauses[] = "`{$table}`.`" . static::$deletedAtColumn . "` IS NULL";
        } elseif (static::$softDelete && $this->onlyTrashed) {
            $whereClauses[] = "`{$table}`.`" . static::$deletedAtColumn . "` IS NOT NULL";
        }

        // Add WHERE
        if (!empty($this->queryWhere)) {
            foreach ($this->queryWhere as $index => $where) {
                $type = (empty($whereClauses) && $index === 0) ? '' : $where['type'] . ' ';
                
                if ($where['operator'] === 'IN' || $where['operator'] === 'NOT IN') {
                    $placeholders = implode(', ', array_fill(0, count($where['value']), '?'));
                    $whereClauses[] = "{$type}`{$where['column']}` {$where['operator']} ({$placeholders})";
                    $this->queryParams = array_merge($this->queryParams, $where['value']);
                } elseif ($where['operator'] === 'BETWEEN' || $where['operator'] === 'NOT BETWEEN') {
                    $whereClauses[] = "{$type}`{$where['column']}` {$where['operator']} ? AND ?";
                    $this->queryParams[] = $where['value'][0];
                    $this->queryParams[] = $where['value'][1];
                } elseif ($where['operator'] === 'IS NULL' || $where['operator'] === 'IS NOT NULL') {
                    $whereClauses[] = "{$type}`{$where['column']}` {$where['operator']}";
                } elseif (strpos($where['operator'], 'DATE_') === 0) {
                    $op = str_replace('DATE_', '', $where['operator']);
                    $whereClauses[] = "{$type}DATE(`{$where['column']}`) {$op} ?";
                    $this->queryParams[] = $where['value'];
                } elseif (strpos($where['operator'], 'MONTH_') === 0) {
                    $op = str_replace('MONTH_', '', $where['operator']);
                    $whereClauses[] = "{$type}MONTH(`{$where['column']}`) {$op} ?";
                    $this->queryParams[] = $where['value'];
                } elseif (strpos($where['operator'], 'YEAR_') === 0) {
                    $op = str_replace('YEAR_', '', $where['operator']);
                    $whereClauses[] = "{$type}YEAR(`{$where['column']}`) {$op} ?";
                    $this->queryParams[] = $where['value'];
                } elseif (strpos($where['operator'], 'TIME_') === 0) {
                    $op = str_replace('TIME_', '', $where['operator']);
                    $whereClauses[] = "{$type}TIME(`{$where['column']}`) {$op} ?";
                    $this->queryParams[] = $where['value'];
                } else {
                    $whereClauses[] = "{$type}`{$where['column']}` {$where['operator']} ?";
                    $this->queryParams[] = $where['value'];
                }
            }
        }

        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(' ', $whereClauses);
        }

        // Add GROUP BY
        if (!empty($this->queryGroupBy)) {
            $sql .= " GROUP BY " . implode(', ', array_map(function($col) {
                return "`{$col}`";
            }, $this->queryGroupBy));
        }

        // Add HAVING
        if (!empty($this->queryHaving)) {
            $havingClauses = [];
            foreach ($this->queryHaving as $having) {
                $havingClauses[] = "`{$having['column']}` {$having['operator']} ?";
                $this->queryParams[] = $having['value'];
            }
            $sql .= " HAVING " . implode(' AND ', $havingClauses);
        }

        // Add ORDER BY
        if (!empty($this->queryOrderBy)) {
            $orderClauses = [];
            foreach ($this->queryOrderBy as $order) {
                $orderClauses[] = "`{$order[0]}` {$order[1]}";
            }
            $sql .= " ORDER BY " . implode(', ', $orderClauses);
        }

        // Add LIMIT
        if ($this->queryLimit !== null) {
            $sql .= " LIMIT {$this->queryLimit}";
        }

        // Add OFFSET
        if ($this->queryOffset !== null) {
            $sql .= " OFFSET {$this->queryOffset}";
        }

        return $sql;
    }

    /**
     * Update records matching query
     */
    public function update(array $attributes) {
        if (empty($this->queryWhere)) {
            throw new \Exception('Cannot update without WHERE clause. Use updateAll() to update all records.');
        }

        $table = static::getTable();
        $setClauses = [];
        $params = [];

        foreach ($attributes as $column => $value) {
            $setClauses[] = "`{$column}` = ?";
            $params[] = $value;
        }

        // Add timestamps
        if (static::$timestamps) {
            $setClauses[] = "`" . static::$updatedAtColumn . "` = ?";
            $params[] = date('Y-m-d H:i:s');
        }

        $sql = "UPDATE `{$table}` SET " . implode(', ', $setClauses);

        // Add WHERE
        if (!empty($this->queryWhere)) {
            $whereClauses = [];
            foreach ($this->queryWhere as $index => $where) {
                $type = $index === 0 ? '' : $where['type'] . ' ';
                
                if ($where['operator'] === 'IN' || $where['operator'] === 'NOT IN') {
                    $placeholders = implode(', ', array_fill(0, count($where['value']), '?'));
                    $whereClauses[] = "{$type}`{$where['column']}` {$where['operator']} ({$placeholders})";
                    $params = array_merge($params, $where['value']);
                } elseif ($where['operator'] === 'BETWEEN' || $where['operator'] === 'NOT BETWEEN') {
                    $whereClauses[] = "{$type}`{$where['column']}` {$where['operator']} ? AND ?";
                    $params[] = $where['value'][0];
                    $params[] = $where['value'][1];
                } else {
                    $whereClauses[] = "{$type}`{$where['column']}` {$where['operator']} ?";
                    $params[] = $where['value'];
                }
            }
            $sql .= " WHERE " . implode(' ', $whereClauses);
        }

        $db = static::getConnection();
        $db->query($sql, $params);
        
        return $db->countAffected();
    }

    /**
     * Delete records matching query
     */
    public function delete() {
        if (empty($this->queryWhere) && !$this->exists) {
            throw new \Exception('Cannot delete without WHERE clause or on non-existing instance.');
        }

        // Soft delete if enabled
        if (static::$softDelete && $this->exists) {
            if ($this->fireEvent('beforeDelete', true) === false) {
                return false;
            }
            $this->attributes[static::$deletedAtColumn] = date('Y-m-d H:i:s');
            $result = $this->save();
            $this->fireEvent('afterDelete');
            return $result;
        } elseif (static::$softDelete && !$this->exists) {
            // Soft delete via query
            return $this->update([static::$deletedAtColumn => date('Y-m-d H:i:s')]);
        }

        // Hard delete
        if ($this->exists && $this->fireEvent('beforeDelete', true) === false) {
            return false;
        }

        $table = static::getTable();
        $sql = "DELETE FROM `{$table}`";
        $params = [];

        // If this is an existing instance, delete by primary key
        if ($this->exists) {
            $sql .= " WHERE `" . static::$primaryKey . "` = ?";
            $params[] = $this->attributes[static::$primaryKey];
        } else {
            // Delete by query
            if (!empty($this->queryWhere)) {
                $whereClauses = [];
                foreach ($this->queryWhere as $index => $where) {
                    $type = $index === 0 ? '' : $where['type'] . ' ';
                    
                    if ($where['operator'] === 'IN' || $where['operator'] === 'NOT IN') {
                        $placeholders = implode(', ', array_fill(0, count($where['value']), '?'));
                        $whereClauses[] = "{$type}`{$where['column']}` {$where['operator']} ({$placeholders})";
                        $params = array_merge($params, $where['value']);
                    } elseif ($where['operator'] === 'BETWEEN' || $where['operator'] === 'NOT BETWEEN') {
                        $whereClauses[] = "{$type}`{$where['column']}` {$where['operator']} ? AND ?";
                        $params[] = $where['value'][0];
                        $params[] = $where['value'][1];
                    } else {
                        $whereClauses[] = "{$type}`{$where['column']}` {$where['operator']} ?";
                        $params[] = $where['value'];
                    }
                }
                $sql .= " WHERE " . implode(' ', $whereClauses);
            }
        }

        $db = static::getConnection();
        $db->query($sql, $params);
        
        $affected = $db->countAffected();
        
        if ($this->exists) {
            $this->exists = false;
            $this->attributes = [];
            $this->fireEvent('afterDelete');
        }
        
        return $affected;
    }

    /**
     * Force delete (hard delete even if soft delete is enabled)
     */
    public function forceDelete() {
        $softDelete = static::$softDelete;
        static::$softDelete = false;
        
        $result = $this->delete();
        
        static::$softDelete = $softDelete;
        return $result;
    }

    /**
     * Restore soft deleted record
     */
    public function restore() {
        if (!static::$softDelete) {
            throw new \Exception('Soft delete is not enabled for this model.');
        }

        if ($this->exists) {
            $this->attributes[static::$deletedAtColumn] = null;
            return $this->save();
        }

        // Restore via query
        return $this->update([static::$deletedAtColumn => null]);
    }

    /**
     * Include soft deleted records in query
     */
    public function withTrashed() {
        $this->withTrashed = true;
        return $this;
    }

    /**
     * Get only soft deleted records
     */
    public function onlyTrashed() {
        $this->onlyTrashed = true;
        return $this;
    }

    /**
     * Save the model (insert or update)
     */
    public function save() {
        if ($this->exists) {
            return $this->performUpdate();
        } else {
            return $this->performInsert();
        }
    }

    /**
     * Refresh the model from the database
     * Discards any unsaved changes
     * 
     * @return bool
     */
    public function refresh() {
        if (!$this->exists) {
            return false;
        }

        $primaryKey = static::$primaryKey;
        $id = $this->attributes[$primaryKey] ?? null;

        if (!$id) {
            return false;
        }

        $fresh = static::find($id);

        if (!$fresh) {
            return false;
        }

        $this->attributes = $fresh->attributes;
        $this->original = $fresh->original;
        $this->relations = [];

        return true;
    }

    /**
     * Perform INSERT
     */
    protected function performInsert() {
        // Fire before events
        if ($this->fireEvent('beforeSave', true) === false) {
            return false;
        }
        if ($this->fireEvent('beforeInsert', true) === false) {
            return false;
        }

        $attributes = $this->attributes;

        // Add timestamps
        if (static::$timestamps) {
            $now = date('Y-m-d H:i:s');
            if (!isset($attributes[static::$createdAtColumn])) {
                $attributes[static::$createdAtColumn] = $now;
            }
            if (!isset($attributes[static::$updatedAtColumn])) {
                $attributes[static::$updatedAtColumn] = $now;
            }
        }

        $table = static::getTable();
        $columns = array_keys($attributes);
        $placeholders = array_fill(0, count($columns), '?');

        $sql = "INSERT INTO `{$table}` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $placeholders) . ")";

        $db = static::getConnection();
        $db->query($sql, array_values($attributes));

        // Set primary key
        $this->attributes[static::$primaryKey] = $db->getLastId();
        $this->attributes = array_merge($this->attributes, $attributes);
        $this->original = $this->attributes;
        $this->exists = true;

        // Fire after events
        $this->fireEvent('afterInsert');
        $this->fireEvent('afterSave');

        return true;
    }

    /**
     * Perform UPDATE
     */
    protected function performUpdate() {
        $dirty = $this->getDirty();
        
        if (empty($dirty)) {
            return true; // Nothing to update
        }

        // Fire before events
        if ($this->fireEvent('beforeSave', true) === false) {
            return false;
        }
        if ($this->fireEvent('beforeUpdate', true) === false) {
            return false;
        }

        // Store changed attributes for afterUpdate event
        $changedAttributes = $dirty;

        // Add timestamps
        if (static::$timestamps) {
            $dirty[static::$updatedAtColumn] = date('Y-m-d H:i:s');
        }

        $table = static::getTable();
        $setClauses = [];
        $params = [];

        foreach ($dirty as $column => $value) {
            $setClauses[] = "`{$column}` = ?";
            $params[] = $value;
        }

        $sql = "UPDATE `{$table}` SET " . implode(', ', $setClauses) . " WHERE `" . static::$primaryKey . "` = ?";
        $params[] = $this->attributes[static::$primaryKey];

        $db = static::getConnection();
        $db->query($sql, $params);

        // Update original
        $this->original = array_merge($this->original, $dirty);
        $this->attributes = array_merge($this->attributes, $dirty);

        // Fire after events with changed attributes
        $this->fireEvent('afterUpdate', false, $changedAttributes);
        $this->fireEvent('afterSave');

        return true;
    }

    /**
     * Fire model event
     * 
     * @param string $event Event name
     * @param bool $checkReturn Whether to check return value (false = cancel operation)
     * @param mixed $data Additional data to pass to event
     * @return mixed
     */
    protected function fireEvent($event, $checkReturn = false, $data = null) {
        $method = $event;
        
        if (method_exists($this, $method)) {
            $result = $data !== null ? $this->$method($data) : $this->$method();
            
            if ($checkReturn && $result === false) {
                return false;
            }
            
            return $result;
        }
        
        return null;
    }

    /**
     * Get dirty attributes (changed since load)
     */
    protected function getDirty() {
        $dirty = [];
        
        foreach ($this->attributes as $key => $value) {
            if (!isset($this->original[$key]) || $this->original[$key] !== $value) {
                $dirty[$key] = $value;
            }
        }

        return $dirty;
    }

    /**
     * Fill model with attributes (mass assignment)
     */
    public function fill(array $attributes) {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            }
        }
        return $this;
    }

    /**
     * Check if attribute is fillable
     */
    protected function isFillable($key) {
        // If fillable is defined, only those columns are allowed
        if (!empty(static::$fillable)) {
            return in_array($key, static::$fillable);
        }

        // If guarded is defined, all except those are allowed
        if (!empty(static::$guarded)) {
            return !in_array($key, static::$guarded);
        }

        return true;
    }

    /**
     * Set attribute value
     */
    public function setAttribute($key, $value) {
        // Check for mutator method (setNameAttribute)
        $method = 'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $key))) . 'Attribute';
        if (method_exists($this, $method)) {
            $this->$method($value);
        } else {
            $this->attributes[$key] = $value;
        }
        return $this;
    }

    /**
     * Get attribute value
     */
    public function getAttribute($key) {
        // Check for accessor method (getNameAttribute)
        $method = 'get' . str_replace(' ', '', ucwords(str_replace('_', ' ', $key))) . 'Attribute';
        if (method_exists($this, $method)) {
            return $this->$method();
        }

        $value = $this->attributes[$key] ?? null;

        // Apply casts
        if (isset(static::$casts[$key])) {
            $value = $this->castAttribute($key, $value);
        }

        return $value;
    }

    /**
     * Cast attribute to specific type
     */
    protected function castAttribute($key, $value) {
        $cast = static::$casts[$key];

        if ($value === null) {
            return null;
        }

        switch ($cast) {
            case 'int':
            case 'integer':
                return (int) $value;
            case 'float':
            case 'double':
                return (float) $value;
            case 'string':
                return (string) $value;
            case 'bool':
            case 'boolean':
                return (bool) $value;
            case 'array':
            case 'json':
                return is_string($value) ? json_decode($value, true) : $value;
            case 'object':
                return is_string($value) ? json_decode($value) : $value;
            case 'date':
            case 'datetime':
                return $value instanceof \DateTime ? $value : new \DateTime($value);
            default:
                return $value;
        }
    }

    /**
     * Set raw attributes (from database)
     */
    protected function setRawAttributes(array $attributes) {
        $this->attributes = $attributes;
        return $this;
    }

    /**
     * Convert model to array
     */
    public function toArray() {
        $array = [];
        
        foreach ($this->attributes as $key => $value) {
            if (!in_array($key, static::$hidden)) {
                $array[$key] = $this->getAttribute($key);
            }
        }

        // Include loaded relations
        foreach ($this->relations as $key => $value) {
            if (is_array($value)) {
                $array[$key] = array_map(function($item) {
                    return $item instanceof Orm ? $item->toArray() : $item;
                }, $value);
            } elseif ($value instanceof Orm) {
                $array[$key] = $value->toArray();
            } else {
                $array[$key] = $value;
            }
        }

        return $array;
    }

    /**
     * Convert model to JSON
     */
    public function toJson($options = 0) {
        return json_encode($this->toArray(), $options);
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * Define a one-to-many relationship
     */
    protected function hasMany($related, $foreignKey = null, $localKey = null) {
        $instance = new $related();
        $foreignKey = $foreignKey ?: strtolower((new \ReflectionClass($this))->getShortName()) . '_id';
        $localKey = $localKey ?: static::$primaryKey;

        $query = $instance->where($foreignKey, $this->attributes[$localKey]);
        $query->foreignKey = $foreignKey;
        $query->ownerKey = $localKey;
        
        return $query;
    }

    /**
     * Define a belongs-to relationship
     */
    protected function belongsTo($related, $foreignKey = null, $ownerKey = null) {
        $instance = new $related();
        $foreignKey = $foreignKey ?: strtolower((new \ReflectionClass($instance))->getShortName()) . '_id';
        $ownerKey = $ownerKey ?: $instance::$primaryKey;

        if (!isset($this->attributes[$foreignKey])) {
            return null;
        }

        return $instance->where($ownerKey, $this->attributes[$foreignKey])->first();
    }

    /**
     * Define a one-to-one relationship
     */
    protected function hasOne($related, $foreignKey = null, $localKey = null) {
        $instance = new $related();
        $foreignKey = $foreignKey ?: strtolower((new \ReflectionClass($this))->getShortName()) . '_id';
        $localKey = $localKey ?: static::$primaryKey;

        return $instance->where($foreignKey, $this->attributes[$localKey])->first();
    }

    /**
     * Define a many-to-many relationship
     */
    protected function belongsToMany($related, $pivotTable = null, $foreignPivotKey = null, $relatedPivotKey = null, $parentKey = null, $relatedKey = null) {
        $instance = new $related();
        
        // Auto-generate pivot table name
        if (!$pivotTable) {
            $tables = [
                strtolower((new \ReflectionClass($this))->getShortName()),
                strtolower((new \ReflectionClass($instance))->getShortName())
            ];
            sort($tables);
            $pivotTable = implode('_', $tables);
        }

        $foreignPivotKey = $foreignPivotKey ?: strtolower((new \ReflectionClass($this))->getShortName()) . '_id';
        $relatedPivotKey = $relatedPivotKey ?: strtolower((new \ReflectionClass($instance))->getShortName()) . '_id';
        $parentKey = $parentKey ?: static::$primaryKey;
        $relatedKey = $relatedKey ?: $instance::$primaryKey;

        $relatedTable = $instance::getTable();
        
        return $instance
            ->select("{$relatedTable}.*")
            ->join($pivotTable, "{$relatedTable}.{$relatedKey}", '=', "{$pivotTable}.{$relatedPivotKey}")
            ->where("{$pivotTable}.{$foreignPivotKey}", $this->attributes[$parentKey]);
    }

    /**
     * Magic getter - check for relations
     */
    public function __get($key) {
        // Check if it's a loaded relation
        if (isset($this->relations[$key])) {
            return $this->relations[$key];
        }

        // Check if it's a relationship method
        if (method_exists($this, $key)) {
            $relation = $this->$key();
            $this->relations[$key] = $relation;
            return $relation;
        }

        return $this->getAttribute($key);
    }

    /**
     * Magic setter
     */
    public function __set($key, $value) {
        $this->setAttribute($key, $value);
    }

    /**
     * Magic isset
     */
    public function __isset($key) {
        return isset($this->attributes[$key]);
    }

    /**
     * Magic unset
     */
    public function __unset($key) {
        unset($this->attributes[$key]);
    }

    /**
     * Magic toString
     */
    public function __toString() {
        return $this->toJson(JSON_PRETTY_PRINT);
    }

    /**
     * Handle dynamic static method calls
     * This allows calling query builder methods statically: User::where()->get()
     */
    public static function __callStatic($method, $parameters) {
        return (new static())->$method(...$parameters);
    }

    // ==================== TRANSACTION HELPERS ====================

    /**
     * Execute a callback within a database transaction
     * 
     * @param callable $callback Function to execute within transaction
     * @return mixed Result of the callback
     * @throws \Exception
     */
    public static function transaction(callable $callback) {
        $db = static::getConnection();
        
        $db->beginTransaction();
        
        try {
            $result = $callback();
            $db->commit();
            return $result;
        } catch (\Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Begin a database transaction
     */
    public static function beginTransaction() {
        return static::getConnection()->beginTransaction();
    }

    /**
     * Commit the active database transaction
     */
    public static function commit() {
        return static::getConnection()->commit();
    }

    /**
     * Rollback the active database transaction
     */
    public static function rollBack() {
        return static::getConnection()->rollBack();
    }

}
