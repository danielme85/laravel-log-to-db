<?php

namespace TestModels;

use Illuminate\Database\Eloquent\Model;

/**
 * Simulates a custom user model/table that predates the unix_time column,
 * used to test that log:fix-datetime skips rather than corrupts such records.
 */
class LogNoUnixTime extends Model
{
    protected $table = 'log_no_unixtime';

    public $timestamps = true;
}
