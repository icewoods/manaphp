<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/12/28
 * Time: 0:01
 */
namespace Tests\Models;

use ManaPHP\Mvc\Model;

class Country extends Model
{
    public $country_id;
    public $country;
    public $last_update;
}