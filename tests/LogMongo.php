<?php
/**
 * Created by PhpStorm.
 * User: dmellum
 * Date: 4/11/19
 * Time: 2:35 PM
 */

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class LogMongo extends Eloquent
{
    protected $collection = 'log';
    protected $connection = 'mongodb';

}