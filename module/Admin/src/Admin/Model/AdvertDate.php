<?php
/**
 * Created by PhpStorm.
 * User: work
 * Date: 3/21/14
 * Time: 1:35 PM
 */


namespace Admin\Model;

class AdvertDate extends \Application\Base\Model
{
    static public $table = self::TABLE_ADVERTS_DATES;
    static public $columns = array('id', 'date_from', 'date_to');
    static public $order = 'date_from asc';
}
