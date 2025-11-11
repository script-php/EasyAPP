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
     * Get first record
     */
    public static function first() {
        return static::query()->limit(1)->get()[0] ?? null;
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
        $this->queryWhere[] = [
            'type' => 'AND',
            'column' => $column,
            'operator' => 'IN',
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
        return $result[0]->count ?? 0;
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
     * Execute query and get results
     */
    public function get() {
        $sql = $this->buildSelectQuery();
        $db = static::getConnection();
        $result = $db->query($sql, $this->queryParams);

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

        return $instances;
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
                
                if ($where['operator'] === 'IN') {
                    $placeholders = implode(', ', array_fill(0, count($where['value']), '?'));
                    $whereClauses[] = "{$type}`{$where['column']}` IN ({$placeholders})";
                    $this->queryParams = array_merge($this->queryParams, $where['value']);
                } elseif ($where['operator'] === 'IS NULL' || $where['operator'] === 'IS NOT NULL') {
                    $whereClauses[] = "{$type}`{$where['column']}` {$where['operator']}";
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
     * Delete records matching query
     */
    public function delete() {
        if (empty($this->queryWhere) && !$this->exists) {
            throw new \Exception('Cannot delete without WHERE clause or on non-existing instance.');
        }

        // Soft delete if enabled
        if (static::$softDelete && $this->exists) {
            $this->fireEvent('deleting');
            $this->attributes[static::$deletedAtColumn] = date('Y-m-d H:i:s');
            $this->save();
            $this->fireEvent('deleted');
            return 1;
        } elseif (static::$softDelete && !$this->exists) {
            // Soft delete via query
            return $this->update([static::$deletedAtColumn => date('Y-m-d H:i:s')]);
        }

        // Hard delete
        $this->fireEvent('deleting');

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
        }

        $db = static::getConnection();
        $db->query($sql, $params);
        
        $affected = $db->countAffected();
        
        if ($this->exists) {
            $this->exists = false;
            $this->attributes = [];
            $this->fireEvent('deleted');
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
     * Perform INSERT
     */
    protected function performInsert() {
        $this->fireEvent('creating');
        $this->fireEvent('saving');

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

        $this->fireEvent('created');
        $this->fireEvent('saved');

        return true;
    }

    /**
     * Perform UPDATE
     */
    protected function performUpdate() {
        $this->fireEvent('updating');
        $this->fireEvent('saving');

        $dirty = $this->getDirty();
        
        if (empty($dirty)) {
            return true; // Nothing to update
        }

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

        $this->fireEvent('updated');
        $this->fireEvent('saved');

        return true;
    }

    /**
     * Fire model event
     */
    protected function fireEvent($event) {
        $method = $event;
        if (method_exists($this, $method)) {
            $this->$method();
        }
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

}
