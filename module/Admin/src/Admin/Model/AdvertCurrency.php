<?php
namespace Admin\Model;

class AdvertCurrency extends \Application\Base\Model
{

    static public $table = self::TABLE_ADVERTS_CURRENCY;
    static public $columns = array('id', 'name');
    static public $order = 'name asc';

}
