<?php

namespace danielme85\LaravelLogToDB;

use Illuminate\Database\Eloquent\Model;

/**
 * Class DbLog
 *
 * @package danielme85\LaravelLogToDB
 */
class DbLog extends Model
{
    use LogToDbCreateObject;
}