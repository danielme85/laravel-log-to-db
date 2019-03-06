<?php
/**
 * Created by Daniel Mellum <mellum@gmail.com>
 * Date: 3/5/2019
 * Time: 10:42 PM
 *
 * Source: https://stackoverflow.com/a/45914381
 */

namespace danielme85\LaravelLogToDB\Models;

trait BindsDynamically
{
    protected $connection = null;
    protected $table = null;

    public function bind(string $connection, string $table)
    {
        $this->setConnection($connection);
        $this->setTable($table);
    }

    public function newInstance($attributes = [], $exists = false)
    {
        // Overridden in order to allow for late table binding.

        $model = parent::newInstance($attributes, $exists);
        $model->setTable($this->table);

        return $model;
    }
}