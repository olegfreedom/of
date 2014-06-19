<?php
namespace Application\Base;

abstract class Model extends \Base\Mvc\Model
{

    // Table constant
    const TABLE_SQL_UPDATES = '_sql_updates';
    const TABLE_USER = 'vb_user';
    const TABLE_USER_OLD = 'users';
    const TABLE_USERS_FIELDS = 'users_fields';
    const TABLE_USERS_RATING = 'users_rating';
    const TABLE_USERS_COMMENTS = 'users_comments';
    const TABLE_USERS_COMMENT_RATING = 'users_comment_rating';
    const TABLE_USER_KEY_TEMP = 'users_key_temp';
    const TABLE_PAGES = 'pages';
    const TABLE_TESTIMONIALS = 'testimonials';
    const TABLE_EMAIL_NOTIFICATIONS = 'email_notifications';
    const TABLE_HELPS = 'helps';
    const TABLE_ADVERTS = 'adverts';
    const TABLE_USER_PHONE = 'user_phone';
    const TABLE_PHONE_MASK = 'phone_mask';
    const TABLE_OPTIONS = 'options';
    const TABLE_ADVERTS_CATEGORIES = 'adverts_categories';
    const TABLE_ADVERTS_COMMENTS = 'adverts_comments';
    const TABLE_ADVERTS_COMMENT_RATING = 'adverts_comment_rating';
    const TABLE_ADVERTS_CURRENCY = 'adverts_currency';
    const TABLE_ADVERTS_UNIT_TYPES = 'adverts_unit_types';
    const TABLE_ADVERTS_DATES = 'adverts_dates';
    const TABLE_ADVERTS_LOCATION = 'adverts_location';
    const TABLE_ADVERTS_RATING = 'adverts_rating';
    const TABLE_ADVERTS_OPTIONS = 'adverts_options';
    const TABLE_LOCATION = 'location';
    const TABLE_LOCATION_REGIONS = 'location_region';
    const TABLE_LOCATION_AREAS = 'location_area';
    const TABLE_LOCATION_CITIES = 'location_city';
    const TABLE_LOCATION_ZIP = 'location_zip';
    const TABLE_ADVERTS_TYPE = 'adverts_type';
    const TABLE_SEARCH_LOG = 'search_log';
    const TABLE_ADVERTS_PHONE = 'adverts_phone';
    const TABLE_ADVERTS_GALLERY = 'adverts_gallery';
    const TABLE_FAVORITES = 'favorite';
    const TABLE_SUBSCRIBE_EMAILS = 'subscribe_emails';
    const TABLE_FAQ = 'faq';
    const TABLE_BANNERS = 'banners';
    const TABLE_MESSAGES = 'messages';
    const TABLE_CONTACT_US = 'contact_us';
    const TABLE_CATEGORY_TO_OPTION = 'category_to_option';
    const TABLE_TESTIMONIALS_TO_ADVERT = 'testimonials_to_advert';
    const TABLE_REQUIRE_PARAMS = 'require_params';
    const TABLE_ADVERT_TO_MESSAGE = 'advert_to_message';
    const TABLE_ADVERTS_LOCATION_REGIONS = 'adverts_location_regions';



    // Users level
    const USERS_LEVEL_ADMIN = '6';
    const USERS_LEVEL_USER = '2';

    // Email Notifications
    const EMAIL_REGISTRATION = 'registration';
    const EMAIL_ACTIVATION_USER = 'activation_user';
    const EMAIL_CHANGE_DATA = 'change_data';
    const EMAIL_CHANGE_EMAIL = 'change_email';
    const EMAIL_CHANGE_EMAIL_SUCCESS = 'change_email_success';
    const EMAIL_ADD_ADVERT = 'add_advert';
    const EMAIL_DELETE_ADVERT = 'delete_advert';
    const EMAIL_ACTIVATION_ADVERT = 'activation_advert';
    const EMAIL_EXTEND_TIME = 'extend_time';
    const EMAIL_EXPIRY_TIME = 'expiry_time';
    const EMAIL_REFILL = 'refill';
    const EMAIL_FORGOT = 'forgot';
    const EMAIL_FOR_GUEST = 'email_for_guest';
    const EMAIL_CONFIRM_RECOVERY = 'confirm_recovery';

    const STARS_PROLONG = 5;
    const STARS_TOP = 10;

    /**
     * Get limiter
     * @param array $params search params
     * @return null|array
     */
    public function getLimiter($params = array()){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        return $this->limiter($this->getLimit($params));
    }

    /**
     * Get limit
     * @param array $params search params
     * @return int|string
     */
    protected function getLimit($params = array()){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $limit = isset($params['limit']) ? $params['limit'] : 10;
        return $limit;
    }

    /**
     * Check for exist `table` property in child class
     */
    protected function checkStaticTable()
    {
        if ( empty(static::$table) )
        {
            die(get_called_class() . '\\' . __FUNCTION__ . ' no `table` property defined.');
        }
    }

    /**
     * Get list
     * @param array $whereAr
     * @return array|null
     */
    public function get($whereAr = array()){
        $this->log(get_called_class() . '\\' . __FUNCTION__);
        $this->checkStaticTable();

        $ret = null;

        $select = $this->select();
        $select->from(static::$table);

        if ( !empty(static::$columns) )
        {
            $select->columns(static::$columns);
        }

        if ( !empty(static::$order) )
        {
            $select->order(static::$order);
        }

        if ( !empty($whereAr) )
        {
            $select->where($whereAr);
        }

        $result = $this->fetchSelect($select);

        if($result){
            $ret = $result;
        }

        return $ret;
    }

    /**
     * get one
     * @param int $id
     * @return array
     */
    public function getOne($id = array()){
        $this->log(get_called_class() . '\\' . __FUNCTION__);
        $this->checkStaticTable();

        $res = null;

        if($id > 0){
            $select = $this->select();
            $select->from(static::$table);

            if ( !empty(static::$columns) )
            {
                $select->columns(static::$columns);
            }

            $select
                ->where(array('id' => $id))
                ->limit(1);

            $result = $this->fetchRowSelect($select);

            if($result){
                $res = $result;
            }
        }

        return $res;
    }

    /**
     * Edit
     * @param int $id
     * @param array $params
     * @return bool
     */
    public function edit($id = 0, $params = null){
        $this->log(get_called_class() . '\\' . __FUNCTION__);
        $this->checkStaticTable();

        $ret = false;

        if($id > 0 && $params !== null){
            if ( empty(static::$table) )
            {
                die(get_called_class() . '\\' . __FUNCTION__ . ' no `table` property defined.');
            }

            $update = $this->update(static::$table)
                ->set($params)
                ->where(array('id' => $id));

            $ret = $this->execute($update);
        }

        return (bool)$ret;
    }

    /**
     * Add
     * @param array $params
     * @return bool
     */
    public function add($params = null){
        $this->log(get_called_class() . '\\' . __FUNCTION__);
        $this->checkStaticTable();

        $ret = false;

        if($params !== null){
            $insert = $this->insert(static::$table)
                ->values($params);

            $ret = $this->execute($insert);
        }

        return (bool)$ret;
    }

    /**
     * Remove check
     * @param int $id
     * @return bool
     */
    public function checkKeys($id = 0){
        $this->log(get_called_class() . '\\' . __FUNCTION__);
        $this->checkStaticTable();

        return $this->load('ForeignKeys', 'admin')->check(static::$table, $id);
    }

    /**
     * Remove records by where condition
     * @param array $whereAr
     * @return bool
     */
    public function remove($whereAr = array())
    {
        $this->log(get_called_class() . '\\' . __FUNCTION__);

        if ( !empty($whereAr) && is_array($whereAr) )
        {
            $this->checkStaticTable();

            $ret = false;
            if ( !isset($whereAr ['id']) || ($whereAr ['id'] > 0 && $this->checkKeys($whereAr ['id']) == false) )
            {
                $delete = $this->delete(static::$table)->where($whereAr);

                $ret = $this->execute($delete);
            }
        }

        return (bool)$ret;
    }

    /**
     * Remove record with childs by where condition
     * @param array $whereAr
     */
    public function removeWithChilds($whereAr)
    {
        $rs = $this->get($whereAr);

        if ( !empty($rs) && !empty($rs [0]) )
        {
            $rec = $rs [0];
            $this->removeTree($rec ['id']);

            $this->remove(array('id' => $rec ['id']));
        }

    }

    /**
     * Remove tree with child records by parent id
     * @param int $id
     */
    public function removeTree($id)
    {
        $result = $this->get(array('parent_id' => $id));

        if ( !empty($result) )
        {
            foreach ( $result as $rec )
            {
                $this->removeTree($rec ['id']);
            }

            $this->remove(array('parent_id' => $id));
        }
    }

}
