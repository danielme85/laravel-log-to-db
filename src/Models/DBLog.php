<?php

namespace danielme85\LaravelLogToDB\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class DbLog
 *
 * @package danielme85\LaravelLogToDB
 */
class DBLog extends Model
{
    use LogToDbCreateObject;
    use BindsDynamically;

}