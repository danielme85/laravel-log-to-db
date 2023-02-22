<?php

use danielme85\LaravelLogToDB\Models\LogToDbCreateObject;
use Illuminate\Database\Eloquent\Model;

/**
 * Class CustomEloquentModel
 *
 * @package danielme85\LaravelLogToDB
 */
class CustomEloquentModel extends Model
{
    use LogToDbCreateObject;

    protected $connection = 'mysql';
    protected $table = 'log';

}