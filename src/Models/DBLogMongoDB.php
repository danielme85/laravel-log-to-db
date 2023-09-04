<?php

namespace danielme85\LaravelLogToDB\Models;

use MongoDB\Laravel\Eloquent\Model as Eloquent;

/**
 * Class DbLog
 *
 * @package danielme85\LaravelLogToDB
 */
class DBLogMongoDB extends Eloquent
{
    use LogToDbCreateObject;
    use BindsDynamically;
}