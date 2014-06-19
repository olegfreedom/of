<?php
/**
 * Created by PhpStorm.
 * User: work
 * Date: 5/19/14
 * Time: 3:22 PM
 */

namespace Admin\Model;

class UsersFields extends \Application\Base\Model
{
    const VISIBILITY_ALL = 0;
    const VISIBILITY_FRIENDS = 1;
    const VISIBILITY_NOBODY = 2;

    public $customFieldsAr = array(
        'avatar' => self::VISIBILITY_ALL,
        'phone1' => self::VISIBILITY_FRIENDS,
        'phone2' => self::VISIBILITY_FRIENDS,
        'fax' => self::VISIBILITY_FRIENDS,
        'website' => self::VISIBILITY_FRIENDS,
        'key' => self::VISIBILITY_NOBODY,
        'zip' => self::VISIBILITY_FRIENDS,
        'secondname' => self::VISIBILITY_FRIENDS,
        'firstname' => self::VISIBILITY_FRIENDS,
        'lastname' => self::VISIBILITY_FRIENDS,
        'address' => self::VISIBILITY_FRIENDS,
        'type' => self::VISIBILITY_ALL,
        'passport_series' => self::VISIBILITY_NOBODY,
        'passport_data' => self::VISIBILITY_NOBODY,
        'passport_receive_date' => self::VISIBILITY_NOBODY,
        'inn' => self::VISIBILITY_NOBODY,
    );


    public function set($userId, $fieldName, $value = NULL)
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $setAr = $fieldName;
        if ( !is_null($value) )
        {
            $setAr = array($fieldName => $value);
        }

        $ret = TRUE;
        $select = $this->select()
            ->from(self::TABLE_USERS_FIELDS)
            ->columns(array('name', 'value'))
            ->where(array(
                'user_id' => $userId,
                // 'name' => $fieldName,
            ));

        $usersFieldsAr = $this->fetchAssocSelect($select, 'name', 'value');

        foreach ( $setAr as $setField => $setValue )
        {
            if ( in_array($setField, array_keys($this->customFieldsAr)) )
            {
                $execFlag = FALSE;
                if ( !isset($usersFieldsAr [$setField]) )
                {
                    // INSERT
                    $execFlag = TRUE;
                    $set = $this->insert(self::TABLE_USERS_FIELDS)
                        ->values(array(
                            'user_id' => $userId,
                            'name' => $setField,
                            'visibility' => isset($this->customFieldsAr [$setField]) ? $this->customFieldsAr [$setField] : self::VISIBILITY_NOBODY,
                            'value' => $setValue,
                        ));
                }
                elseif ( $setAr != $usersFieldsAr [$setField] )
                {
                    // UPDATE
                    $execFlag = TRUE;
                    $set = $this->update(self::TABLE_USERS_FIELDS)
                        ->set(array(
                            'value' => $setValue,
                        ))->where(array(
                            'user_id' => $userId,
                            'name' => $setField,
                        ));

                }

                if ($execFlag)
                {
                    $result = $this->execute($set);
                    if ( empty($result) )
                    {
                        $ret = FALSE;
                    }
                }
            }

        }

        return $ret;
    }
/*
 * array(
                'user_id' => $userId,
                'name' => $fieldName,
            )
 */
    public function get($whereAr = array())
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $select = $this->select()
            ->from(self::TABLE_USERS_FIELDS)
            ->columns(array('value'))
            ->where($whereAr)
            ->limit(1);

        $res = $this->fetchOneSelect($select);

        return $res;
    }


}