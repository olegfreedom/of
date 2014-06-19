<?php
/**
 * Created by PhpStorm.
 * User: work
 * Date: 3/19/14
 * Time: 12:13 PM
 */

namespace Admin\Model;

class AdvertUnitType extends \Application\Base\Model
{

    static public $table = self::TABLE_ADVERTS_UNIT_TYPES;
    static public $columns = array('id', 'shortname', 'name');
    static public $order = 'shortname asc';

}
