<?php

namespace App\Models;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Builder;

class UserBuilder extends Builder
{
    /**
     * Intercept WHERE clauses targeting 'email' to query via 'email_bidx' blind index hash.
     *
     * @param  \Closure|string|array|Expression  $column
     * @param  mixed  $operator
     * @param  mixed  $value
     * @param  string  $boolean
     * @return $this
     */
    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        if ($this->isEmailColumn($column)) {
            $emailVal = func_num_args() === 2 ? $operator : $value;

            return parent::where('users.email_bidx', '=', User::hashEmail($emailVal), $boolean);
        }

        return parent::where($column, $operator, $value, $boolean);
    }

    /**
     * Intercept OR WHERE clauses targeting 'email' to query via 'email_bidx' blind index hash.
     *
     * @param  \Closure|string|array|Expression  $column
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return $this
     */
    public function orWhere($column, $operator = null, $value = null)
    {
        if ($this->isEmailColumn($column)) {
            $emailVal = func_num_args() === 2 ? $operator : $value;

            return parent::orWhere('users.email_bidx', '=', User::hashEmail($emailVal));
        }

        return parent::orWhere($column, $operator, $value);
    }

    /**
     * Intercept WHERE IN clauses targeting 'email'.
     *
     * @param  string  $column
     * @param  mixed  $values
     * @param  string  $boolean
     * @param  bool  $not
     * @return $this
     */
    public function whereIn($column, $values, $boolean = 'and', $not = false)
    {
        if ($this->isEmailColumn($column)) {
            $hashedValues = array_map(fn ($email) => User::hashEmail($email), (array) $values);

            return parent::whereIn('users.email_bidx', $hashedValues, $boolean, $not);
        }

        return parent::whereIn($column, $values, $boolean, $not);
    }

    /**
     * Check if the specified column refers to user email.
     */
    protected function isEmailColumn(mixed $column): bool
    {
        return is_string($column) && in_array(strtolower($column), ['email', 'users.email'], true);
    }
}
