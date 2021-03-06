<?php

declare(strict_types=1);

/**
 * @author      Wizacha DevTeam <dev@wizacha.com>
 * @copyright   Copyright (c) Wizacha
 * @copyright   Copyright (c) Leonardo Marquine
 * @license     MIT
 */

namespace Wizaplace\Etl\Database;

class Query
{
    /**
     * The database connection.
     *
     * @var \PDO
     */
    protected $pdo;

    /**
     * The bindings for the query.
     *
     * @var array
     */
    protected $bindings = [];

    /**
     * The sql query components.
     *
     * @var array
     */
    protected $query = [];

    /**
     * The where constraints for the query.
     *
     * @var array
     */
    protected $wheres = [];

    /**
     * Create a new Query instance.
     *
     * @return void
     */
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Execute the query.
     */
    public function execute(): \PDOStatement
    {
        $statement = $this->pdo->prepare($this->toSql());

        $statement->execute($this->bindings);

        return $statement;
    }

    /**
     * Get the sql query string.
     */
    public function toSql(): string
    {
        $this->compileWheres();

        return implode(' ', $this->query);
    }

    /**
     * Get the query bindings.
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }

    /**
     * Select statement.
     *
     * @return $this
     */
    public function select(string $table, array $columns = ['*']): Query
    {
        $columns = $this->implode($columns);

        $this->query[] = "select $columns from $table";

        return $this;
    }

    /**
     * Insert statement.
     *
     * @return $this
     */
    public function insert(string $table, array $columns): Query
    {
        $this->bindings = array_merge($this->bindings, array_values($columns));

        $values = $this->implode($columns, '?');

        $columns = $this->implode(array_keys($columns));

        $this->query[] = "insert into $table ($columns) values ($values)";

        return $this;
    }

    /**
     * Update statement.
     *
     * @return $this
     */
    public function update(string $table, array $columns): Query
    {
        $this->bindings = array_merge($this->bindings, array_values($columns));

        $columns = $this->implode(array_keys($columns), '{column} = ?');

        $this->query[] = "update $table set $columns";

        return $this;
    }

    /**
     * Delete statement.
     *
     * @return $this
     */
    public function delete(string $table): Query
    {
        $this->query[] = "delete from {$table}";

        return $this;
    }

    /**
     * Where statement.
     *
     * @return $this
     */
    public function where(array $columns): Query
    {
        foreach ($columns as $column => $value) {
            $this->wheres[] = [
                'type' => 'Where', 'column' => $column, 'value' => $value, 'operator' => '=', 'boolean' => 'and',
            ];
        }

        return $this;
    }

    /**
     * Where In statement.
     *
     * @param array|string $column
     *
     * @return $this
     */
    public function whereIn($column, array $values, string $operator = 'in'): Query
    {
        if (is_string($column)) {
            $this->wheres[] = ['type' => 'WhereIn', 'column' => $column, 'values' => $values, 'operator' => $operator, 'boolean' => 'and'];
        } else {
            $this->wheres[] = ['type' => 'CompositeWhereIn', 'columns' => $column, 'values' => $values, 'operator' => $operator, 'boolean' => 'and'];
        }

        return $this;
    }

    /**
     * Where Not In statement.
     *
     * @param array|string $column
     *
     * @return $this
     */
    public function whereNotIn($column, array $values): Query
    {
        return $this->whereIn($column, $values, 'not in');
    }

    /**
     * Compile all where statements.
     */
    protected function compileWheres(): void
    {
        if (empty($this->wheres)) {
            return;
        }

        $this->query[] = 'where';

        foreach ($this->wheres as $index => $condition) {
            $method = 'compile' . $condition['type'];

            if (0 == $index) {
                $condition['boolean'] = '';
            }

            $this->query[] = trim($this->{$method}($condition));
        }
    }

    /**
     * Compile the basic where statement.
     *
     * @return string
     */
    protected function compileWhere(array $where)
    {
        extract($where);

        // @TODO refactor this code as the use of extract() is a bad practice, prone to create bugs

        $this->bindings[] = $value;

        return "$boolean $column $operator ?";
    }

    /**
     * Compile the where in statement.
     *
     * @return string
     */
    protected function compileWhereIn(array $where)
    {
        extract($where);

        // @TODO refactor this code as the use of extract() is a bad practice, prone to create bugs

        $this->bindings = array_merge($this->bindings, $values);

        $parameters = $this->implode($values, '?');

        return "$boolean $column $operator ($parameters)";
    }

    /**
     * Compile the composite where in statement.
     *
     * @return string
     */
    protected function compileCompositeWhereIn(array $where)
    {
        extract($where);

        // @TODO refactor this code as the use of extract() is a bad practice, prone to create bugs

        sort($columns);

        $parameters = [];

        foreach ($values as $value) {
            ksort($value);

            $this->bindings = array_merge($this->bindings, array_values($value));

            $parameters[] = "({$this->implode($value, '?')})";
        }

        $parameters = $this->implode($parameters);

        $columns = $this->implode($columns);

        return "$boolean ($columns) $operator ($parameters)";
    }

    /**
     * Join array elements using a string mask.
     */
    protected function implode(array $columns, string $mask = '{column}'): string
    {
        $columns = array_map(function ($column) use ($mask) {
            return str_replace('{column}', $column, $mask);
        }, $columns);

        return implode(', ', $columns);
    }
}
