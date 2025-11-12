<?php

/**
* @package      Collection - Array manipulation wrapper
* @author       YoYo
* @copyright    Copyright (c) 2025, script-php.ro
* @link         https://script-php.ro
*/

namespace System\Framework;

class Collection implements \ArrayAccess, \Countable, \IteratorAggregate {
    
    /**
     * The items contained in the collection
     */
    protected $items = [];
    
    /**
     * Create a new collection
     */
    public function __construct($items = []) {
        $this->items = $this->getArrayableItems($items);
    }
    
    /**
     * Create a new collection instance
     */
    public static function make($items = []) {
        return new static($items);
    }
    
    /**
     * Get all items in the collection
     */
    public function all() {
        return $this->items;
    }
    
    /**
     * Get the average value of a given key
     */
    public function avg($key = null) {
        $count = $this->count();
        
        if ($count === 0) {
            return null;
        }
        
        return $this->sum($key) / $count;
    }
    
    /**
     * Chunk the collection into smaller collections
     */
    public function chunk($size) {
        $chunks = [];
        
        foreach (array_chunk($this->items, $size, true) as $chunk) {
            $chunks[] = new static($chunk);
        }
        
        return new static($chunks);
    }
    
    /**
     * Collapse a collection of arrays into a single, flat collection
     */
    public function collapse() {
        $results = [];
        
        foreach ($this->items as $item) {
            if ($item instanceof self) {
                $results = array_merge($results, $item->all());
            } elseif (is_array($item)) {
                $results = array_merge($results, $item);
            } else {
                $results[] = $item;
            }
        }
        
        return new static($results);
    }
    
    /**
     * Determine if an item exists in the collection
     */
    public function contains($key, $operator = null, $value = null) {
        if (func_num_args() === 1) {
            if ($this->useAsCallable($key)) {
                return !is_null($this->first($key));
            }
            return in_array($key, $this->items);
        }
        
        return $this->contains($this->operatorForWhere(...func_get_args()));
    }
    
    /**
     * Count the number of items in the collection
     */
    public function count(): int {
        return count($this->items);
    }
    
    /**
     * Get the items in the collection that are not present in the given items
     */
    public function diff($items) {
        return new static(array_diff($this->items, $this->getArrayableItems($items)));
    }
    
    /**
     * Execute a callback over each item
     */
    public function each(callable $callback) {
        foreach ($this->items as $key => $item) {
            if ($callback($item, $key) === false) {
                break;
            }
        }
        
        return $this;
    }
    
    /**
     * Determine if all items pass the given truth test
     */
    public function every(callable $callback) {
        foreach ($this->items as $key => $item) {
            if (!$callback($item, $key)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Run a filter over each of the items
     */
    public function filter(callable $callback = null) {
        if ($callback) {
            return new static(array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH));
        }
        
        return new static(array_filter($this->items));
    }
    
    /**
     * Get the first item from the collection
     */
    public function first(callable $callback = null, $default = null) {
        if (is_null($callback)) {
            if (empty($this->items)) {
                return $default;
            }
            
            foreach ($this->items as $item) {
                return $item;
            }
        }
        
        foreach ($this->items as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }
        
        return $default;
    }
    
    /**
     * Get a flattened array of the items in the collection
     */
    public function flatten($depth = INF) {
        $result = [];
        
        foreach ($this->items as $item) {
            if (!is_array($item) && !($item instanceof self)) {
                $result[] = $item;
            } else {
                $values = $depth === 1
                    ? array_values($this->getArrayableItems($item))
                    : (new static($item))->flatten($depth - 1)->all();
                
                foreach ($values as $value) {
                    $result[] = $value;
                }
            }
        }
        
        return new static($result);
    }
    
    /**
     * Remove an item from the collection by key
     */
    public function forget($keys) {
        foreach ((array) $keys as $key) {
            $this->offsetUnset($key);
        }
        
        return $this;
    }
    
    /**
     * Get an item from the collection by key
     */
    public function get($key, $default = null) {
        if ($this->offsetExists($key)) {
            return $this->items[$key];
        }
        
        return $default;
    }
    
    /**
     * Group the collection by a given key
     */
    public function groupBy($groupBy, $preserveKeys = false) {
        $results = [];
        
        $groupBy = $this->valueRetriever($groupBy);
        
        foreach ($this->items as $key => $value) {
            $groupKeys = $groupBy($value, $key);
            
            if (!is_array($groupKeys)) {
                $groupKeys = [$groupKeys];
            }
            
            foreach ($groupKeys as $groupKey) {
                $groupKey = is_bool($groupKey) ? (int) $groupKey : $groupKey;
                
                if (!array_key_exists($groupKey, $results)) {
                    $results[$groupKey] = new static();
                }
                
                $results[$groupKey]->offsetSet($preserveKeys ? $key : null, $value);
            }
        }
        
        return new static($results);
    }
    
    /**
     * Determine if a key exists in the collection
     */
    public function has($key) {
        $keys = is_array($key) ? $key : func_get_args();
        
        foreach ($keys as $value) {
            if (!$this->offsetExists($value)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Concatenate values of a given key as a string
     */
    public function implode($value, $glue = null) {
        if ($this->isArrayable($this->first())) {
            return implode($glue, $this->pluck($value)->all());
        }
        
        return implode($value, $this->items);
    }
    
    /**
     * Determine if the collection is empty
     */
    public function isEmpty() {
        return empty($this->items);
    }
    
    /**
     * Determine if the collection is not empty
     */
    public function isNotEmpty() {
        return !$this->isEmpty();
    }
    
    /**
     * Get the keys of the collection items
     */
    public function keys() {
        return new static(array_keys($this->items));
    }
    
    /**
     * Get the last item from the collection
     */
    public function last(callable $callback = null, $default = null) {
        if (is_null($callback)) {
            return empty($this->items) ? $default : end($this->items);
        }
        
        return $this->reverse()->first($callback, $default);
    }
    
    /**
     * Run a map over each of the items
     */
    public function map(callable $callback) {
        $keys = array_keys($this->items);
        
        $items = array_map($callback, $this->items, $keys);
        
        return new static(array_combine($keys, $items));
    }
    
    /**
     * Get the max value of a given key
     */
    public function max($key = null) {
        return $this->reduce(function ($result, $item) use ($key) {
            $value = $this->dataGet($item, $key);
            return is_null($result) || $value > $result ? $value : $result;
        });
    }
    
    /**
     * Merge the collection with the given items
     */
    public function merge($items) {
        return new static(array_merge($this->items, $this->getArrayableItems($items)));
    }
    
    /**
     * Get the min value of a given key
     */
    public function min($key = null) {
        return $this->reduce(function ($result, $item) use ($key) {
            $value = $this->dataGet($item, $key);
            return is_null($result) || $value < $result ? $value : $result;
        });
    }
    
    /**
     * Get the items with the specified keys
     */
    public function only($keys) {
        if (is_null($keys)) {
            return new static($this->items);
        }
        
        $keys = is_array($keys) ? $keys : func_get_args();
        
        return new static(array_intersect_key($this->items, array_flip($keys)));
    }
    
    /**
     * Get the values of a given key
     */
    public function pluck($value, $key = null) {
        $results = [];
        
        $value = $this->valueRetriever($value);
        $key = is_null($key) ? null : $this->valueRetriever($key);
        
        foreach ($this->items as $item) {
            $itemValue = $value($item);
            
            if (is_null($key)) {
                $results[] = $itemValue;
            } else {
                $itemKey = $key($item);
                $results[$itemKey] = $itemValue;
            }
        }
        
        return new static($results);
    }
    
    /**
     * Push an item onto the end of the collection
     */
    public function push($value) {
        $this->offsetSet(null, $value);
        
        return $this;
    }
    
    /**
     * Reduce the collection to a single value
     */
    public function reduce(callable $callback, $initial = null) {
        return array_reduce($this->items, $callback, $initial);
    }
    
    /**
     * Create a collection of all elements that do not pass a given truth test
     */
    public function reject($callback) {
        if ($this->useAsCallable($callback)) {
            return $this->filter(function ($value, $key) use ($callback) {
                return !$callback($value, $key);
            });
        }
        
        return $this->filter(function ($item) use ($callback) {
            return $item != $callback;
        });
    }
    
    /**
     * Reverse items order
     */
    public function reverse() {
        return new static(array_reverse($this->items, true));
    }
    
    /**
     * Search the collection for a given value
     */
    public function search($value, $strict = false) {
        if (!$this->useAsCallable($value)) {
            return array_search($value, $this->items, $strict);
        }
        
        foreach ($this->items as $key => $item) {
            if ($value($item, $key)) {
                return $key;
            }
        }
        
        return false;
    }
    
    /**
     * Get and remove the first item from the collection
     */
    public function shift() {
        return array_shift($this->items);
    }
    
    /**
     * Slice the underlying collection array
     */
    public function slice($offset, $length = null) {
        return new static(array_slice($this->items, $offset, $length, true));
    }
    
    /**
     * Sort through each item with a callback
     */
    public function sort(callable $callback = null) {
        $items = $this->items;
        
        $callback ? uasort($items, $callback) : asort($items);
        
        return new static($items);
    }
    
    /**
     * Sort the collection by the given key
     */
    public function sortBy($callback, $options = SORT_REGULAR, $descending = false) {
        $results = [];
        
        $callback = $this->valueRetriever($callback);
        
        foreach ($this->items as $key => $value) {
            $results[$key] = $callback($value, $key);
        }
        
        $descending ? arsort($results, $options) : asort($results, $options);
        
        foreach (array_keys($results) as $key) {
            $results[$key] = $this->items[$key];
        }
        
        return new static($results);
    }
    
    /**
     * Sort the collection in descending order by the given key
     */
    public function sortByDesc($callback, $options = SORT_REGULAR) {
        return $this->sortBy($callback, $options, true);
    }
    
    /**
     * Get the sum of the given values
     */
    public function sum($callback = null) {
        if (is_null($callback)) {
            return array_sum($this->items);
        }
        
        $callback = $this->valueRetriever($callback);
        
        return $this->reduce(function ($result, $item) use ($callback) {
            return $result + $callback($item);
        }, 0);
    }
    
    /**
     * Take the first or last {$limit} items
     */
    public function take($limit) {
        if ($limit < 0) {
            return $this->slice($limit, abs($limit));
        }
        
        return $this->slice(0, $limit);
    }
    
    /**
     * Transform each item in the collection using a callback
     */
    public function transform(callable $callback) {
        $this->items = $this->map($callback)->all();
        
        return $this;
    }
    
    /**
     * Return only unique items from the collection
     */
    public function unique($key = null, $strict = false) {
        if (is_null($key)) {
            return new static(array_unique($this->items, SORT_REGULAR));
        }
        
        $key = $this->valueRetriever($key);
        
        $exists = [];
        
        return $this->reject(function ($item) use ($key, $strict, &$exists) {
            $id = $key($item);
            
            if (in_array($id, $exists, $strict)) {
                return true;
            }
            
            $exists[] = $id;
        });
    }
    
    /**
     * Reset the keys on the underlying array
     */
    public function values() {
        return new static(array_values($this->items));
    }
    
    /**
     * Filter items by the given key value pair
     */
    public function where($key, $operator = null, $value = null) {
        return $this->filter($this->operatorForWhere(...func_get_args()));
    }
    
    /**
     * Filter items by the given key value pair using strict comparison
     */
    public function whereStrict($key, $value) {
        return $this->where($key, '===', $value);
    }
    
    /**
     * Filter items where the value is in the given array
     */
    public function whereIn($key, $values, $strict = false) {
        $values = $this->getArrayableItems($values);
        
        return $this->filter(function ($item) use ($key, $values, $strict) {
            return in_array($this->dataGet($item, $key), $values, $strict);
        });
    }
    
    /**
     * Filter items where the value is not in the given array
     */
    public function whereNotIn($key, $values, $strict = false) {
        $values = $this->getArrayableItems($values);
        
        return $this->filter(function ($item) use ($key, $values, $strict) {
            return !in_array($this->dataGet($item, $key), $values, $strict);
        });
    }
    
    /**
     * Get the collection of items as a plain array
     */
    public function toArray() {
        return array_map(function ($value) {
            if ($value instanceof Orm) {
                return $value->toArray();
            }
            return $value instanceof self ? $value->toArray() : $value;
        }, $this->items);
    }
    
    /**
     * Get the collection of items as JSON
     */
    public function toJson($options = 0) {
        return json_encode($this->toArray(), $options);
    }
    
    /**
     * Get an iterator for the items
     */
    public function getIterator(): \Traversable {
        return new \ArrayIterator($this->items);
    }
    
    /**
     * Determine if an item exists at an offset
     */
    public function offsetExists($key): bool {
        return array_key_exists($key, $this->items);
    }
    
    /**
     * Get an item at a given offset
     */
    public function offsetGet($key): mixed {
        return $this->items[$key];
    }
    
    /**
     * Set the item at a given offset
     */
    public function offsetSet($key, $value): void {
        if (is_null($key)) {
            $this->items[] = $value;
        } else {
            $this->items[$key] = $value;
        }
    }
    
    /**
     * Unset the item at a given offset
     */
    public function offsetUnset($key): void {
        unset($this->items[$key]);
    }
    
    /**
     * Convert the collection to its string representation
     */
    public function __toString() {
        return $this->toJson();
    }
    
    /**
     * Results array of items from Collection or Arrayable
     */
    protected function getArrayableItems($items) {
        if (is_array($items)) {
            return $items;
        } elseif ($items instanceof self) {
            return $items->all();
        } elseif ($items instanceof \JsonSerializable) {
            return $items->jsonSerialize();
        } elseif ($items instanceof \Traversable) {
            return iterator_to_array($items);
        }
        
        return (array) $items;
    }
    
    /**
     * Get a value retrieving callback
     */
    protected function valueRetriever($value) {
        if ($this->useAsCallable($value)) {
            return $value;
        }
        
        return function ($item) use ($value) {
            return $this->dataGet($item, $value);
        };
    }
    
    /**
     * Determine if the given value is callable
     */
    protected function useAsCallable($value) {
        return !is_string($value) && is_callable($value);
    }
    
    /**
     * Get an item from an array or object using "dot" notation
     */
    protected function dataGet($target, $key, $default = null) {
        if (is_null($key)) {
            return $target;
        }
        
        foreach (explode('.', $key) as $segment) {
            if (is_array($target) && array_key_exists($segment, $target)) {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                return $default;
            }
        }
        
        return $target;
    }
    
    /**
     * Determine if the given item is "arrayable"
     */
    protected function isArrayable($value) {
        return is_array($value) || $value instanceof self;
    }
    
    /**
     * Get an operator checker callback
     */
    protected function operatorForWhere($key, $operator = null, $value = null) {
        if (func_num_args() === 1) {
            $value = true;
            $operator = '=';
        }
        
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        
        return function ($item) use ($key, $operator, $value) {
            $retrieved = $this->dataGet($item, $key);
            
            $strings = array_filter([$retrieved, $value], function ($value) {
                return is_string($value) || (is_object($value) && method_exists($value, '__toString'));
            });
            
            if (count($strings) < 2 && count(array_filter([$retrieved, $value], 'is_object')) == 1) {
                return in_array($operator, ['!=', '<>', '!==']);
            }
            
            switch ($operator) {
                default:
                case '=':
                case '==':  return $retrieved == $value;
                case '!=':
                case '<>':  return $retrieved != $value;
                case '<':   return $retrieved < $value;
                case '>':   return $retrieved > $value;
                case '<=':  return $retrieved <= $value;
                case '>=':  return $retrieved >= $value;
                case '===': return $retrieved === $value;
                case '!==': return $retrieved !== $value;
            }
        };
    }
}
