<?php
namespace Profile\Model;

class Messages extends \Application\Base\Model
{

    /**
     * All messages
     * @param int $userId
     * @return array|bool
     */
    public function getMessagesByAdverts($userId = 0)
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;
        if ($userId > 0) {
            $select = $this->select()
                ->from(array('a' => self::TABLE_ADVERTS))
                ->columns(array(
                    'id',
                    'name',
                    'price',
                    'image' => $this->subQuery(
                        $this->select()
                             ->from(array('g' => self::TABLE_ADVERTS_GALLERY))
                             ->columns(array('url'))
                             ->where(array('g.advert_id' => $this->expr('a.id')))
                             ->limit(1)
                    ),
                    'count' => $this->subQuery(
                        $this->select()
                             ->from(array('a2m' => self::TABLE_ADVERT_TO_MESSAGE))
                             ->columns(array('count' => $this->expr('count(message_id)')))
                             ->join(array('m' => self::TABLE_MESSAGES),
                                    'm.id = a2m.message_id',
                                    array())
                             ->where(array(
                                    'a2m.advert_id' => $this->expr('a.id'),
                                    $this->where()
                                        ->addPredicate(
                                        $this->where()
                                            ->equalTo('m.from_user_id', $userId)
                                            ->or
                                            ->equalTo('m.to_user_id', $userId)
                                        )
                             ))
                             ->limit(1)
                    )
                ))
                ->join(
                    array('a2m' => self::TABLE_ADVERT_TO_MESSAGE),
                    'a2m.advert_id = a.id',
                    array()
                )
                ->join(
                    array('m' => self::TABLE_MESSAGES),
                    'm.id = a2m.message_id',
                    array()
                )
                ->where(array(
                    $this->where()
                         ->equalTo('m.to_user_id', $userId)
                         ->or
                         ->equalTo('m.from_user_id', $userId)
                ))
                ->having('count > 0')
                ->group('a.id');
            
            $result = $this->fetchSelect($select);

            if ($result) {
                foreach ($result as &$item) {
                    $select = $this->select()
                                   ->from(array('m' => self::TABLE_MESSAGES))
                                   ->columns(array(
                                        'id',
                                        'title',
                                        'text',
                                        'timestamp',
                                        'f_user_id' => $this->expr('case ? when m.from_user_id then m.to_user_id else m.from_user_id end', $userId),
                                        'username' => $this->subQuery(
                                                           $this->select()
                                                                ->from(array('u' => self::TABLE_USER))
                                                                ->columns(array(
                                                                   'uname' => $this->expr('case name when "" then username else name end')
                                                               ))
                                                                ->where(array(
                                                                        'u.id' => $this->expr('f_user_id')
                                                                ))
                                                     ),
                                        'last_id' => $this->subQuery(
                                                            $this->select()
                                                                ->from(array('sub_m' => self::TABLE_MESSAGES))
                                                                ->columns(array('id'))
                                                                ->join(
                                                                    array('sub_a2m' => self::TABLE_ADVERT_TO_MESSAGE),
                                                                    'sub_a2m.message_id = sub_m.id',
                                                                    array()
                                                                )
                                                                ->where(array(
                                                                    $this->where()
                                                                         ->equalTo('sub_a2m.advert_id', $item['id'])
                                                                         ->andPredicate(
                                                                            $this->where()
                                                                                 ->equalTo('sub_m.from_user_id', $this->expr('f_user_id'))
                                                                                 ->or
                                                                                 ->equalTo('sub_m.to_user_id', $this->expr('f_user_id'))
                                                                        )

                                                                ))
                                                                ->order('sub_m.id desc')
                                                                ->limit(1)
                                                    )
                                   ))
                                   ->join(
                                        array('a2m' => self::TABLE_ADVERT_TO_MESSAGE),
                                        'a2m.message_id = m.id',
                                        array()
                                   )
                                   ->where(array(
                                        $this->where()
                                            ->equalTo('a2m.advert_id', $item['id'])
                                            ->andPredicate(
                                                $this->where()
                                                    ->equalTo('m.from_user_id', $userId)
                                                    ->or
                                                    ->equalTo('m.to_user_id', $userId)
                                            )

                                    ))
                                   ->having('m.id = last_id')
                                   ->order('timestamp desc');

                    $subResult = $this->fetchSelect($select);
                    if ($subResult) {
                        $item['messages'] = $subResult;
                    }
                }
                $ret = $result;
            }
        }

        return $ret;
    }

    /**
     * Dialog list
     * @param int $advert_id
     * @param int $from_user_id
     * @param int $user_id
     * @return array|bool
     */
    public function getTalkList($advert_id = 0, $from_user_id = 0, $user_id = 0)
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;

        if ($advert_id > 0 && $from_user_id > 0 && $user_id > 0) {
            $select = $this->select()
                        ->from(array('m' => self::TABLE_MESSAGES))
                        ->columns(array(
                            'id',
                            'title',
                            'text',
                            'timestamp',
                            'from_user_id',
                            'to_username' => $this->subQuery(
                                $this->select()
                                     ->from(array('u' => self::TABLE_USER))
                                     ->columns(array('username'))
                                     ->where(array('u.id' => $this->expr('m.to_user_id')))
                                     ->limit(1)
                            ),
                            'from_username' => $this->subQuery(
                                $this->select()
                                     ->from(array('u' => self::TABLE_USER))
                                     ->columns(array('username'))
                                     ->where(array('u.id' => $this->expr('m.from_user_id')))
                                     ->limit(1)
                            ),

                        ))
                        ->join(
                            array('a2m' => self::TABLE_ADVERT_TO_MESSAGE),
                            'a2m.message_id = m.id',
                            array()
                        )
                        ->where(array(
                               'a2m.advert_id' => $advert_id,
                               $this->where()
                                    ->andPredicate(
                                        $this->where()
                                             ->addPredicate(
                                                 $this->where()
                                                      ->equalTo('m.from_user_id', $from_user_id)
                                                      ->and
                                                      ->equalTo('to_user_id', $user_id)
                                             )
                                             ->orPredicate(
                                                 $this->where()
                                                      ->equalTo('m.from_user_id', $user_id)
                                                      ->and
                                                      ->equalTo('to_user_id', $from_user_id)
                                             )
                                    )
                            )
                        )
                        ->order('timestamp asc');
            
            $result = $this->fetchSelect($select);
            
            if ($result) {
                foreach ($result as &$item) {
                    if ($item['from_user_id'] == $user_id) {
                        $item['username'] = $item['from_username'];
                    } else {
                        $item['username'] = $item['to_username'];
                    }
                }
                $ret = $result;
            }
        }
        return $ret;
    }

    /**
     * Get count unread messages
     * @param int $userId
     * @return bool|int
     */
    public function getCount($userId = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;

        if ($userId > 0){
            $select = $this->select()
                           ->from(array('m' => self::TABLE_MESSAGES))
                           ->columns(array('count' => $this->expr('count(id)')))
                           ->where(array(
                                'to_user_id' => $userId,
                                'was_read' => 'n'
                           ));
            
            $result = (int)$this->fetchOneSelect($select);
            
            if ($result){
                $ret = $result;
            }
        }
        return $ret;
    }

    /**
     * Inbox list
     * @param int $userId
     * @return array|bool
     */
    public function getInboxList($userId = 0)
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;
        if ($userId > 0) {
            $select = $this->select()
                ->from(array('m' => self::TABLE_MESSAGES))
                ->columns(array(
                    'id',
                    'title',
                    'text',
                    'from_user_id',
                    'to_user_id',
                    'username' => $this->subQuery(
                        $this->select()
                             ->from(array('u' => self::TABLE_USER))
                             ->columns(array('username'))
                             ->where(array('u.id' => $this->expr('m.from_user_id')))
                             ->limit(1)
                    ),
                    'timestamp'
                ))
                ->where(array(
                    'm.to_user_id' => $userId,
                    'm.archive_to' => 'n',
                    'm.delete_to' => 'n'
                ))
                ->order('m.timestamp desc');
            
            $result = $this->fetchSelect($select);
            
            if ($result) {
                $ret = $result;
            }
        }

        return $ret;
    }

    /**
     * Outbox list
     * @param int $userId
     * @return array|bool
     */
    public function getOutboxList($userId = 0)
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;
        if ($userId > 0) {
            $select = $this->select()
                ->from(array('m' => self::TABLE_MESSAGES))
                ->columns(array(
                    'id',
                    'title',
                    'text',
                    'from_user_id',
                    'to_user_id',
                    'username' => $this->subQuery(
                        $this->select()
                             ->from(array('u' => self::TABLE_USER))
                             ->columns(array('username'))
                             ->where(array('u.id' => $this->expr('m.to_user_id')))
                             ->limit(1)
                    ),
                    'timestamp'
                ))
                ->where(array(
                    'm.from_user_id' => $userId,
                    'm.archive_from' => 'n',
                    'm.delete_from' => 'n'
                ))
                ->order('m.timestamp desc');
            
            $result = $this->fetchSelect($select);
            
            if ($result) {
                $ret = $result;
            }
        }

        return $ret;
    }

    /**
     * Archive list
     * @param int $userId
     * @return array|bool
     */
    public function getArchiveList($userId = 0)
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;
        if ($userId > 0) {
            $select = $this->select()
                ->from(array('m' => self::TABLE_MESSAGES))
                ->columns(array(
                    'id',
                    'title',
                    'text',
                    'from_user_id',
                    'to_user_id',
                    'to_username' => $this->subQuery(
                        $this->select()
                             ->from(array('tu' => self::TABLE_USER))
                             ->columns(array('username'))
                             ->where(array('tu.id' => $this->expr('m.to_user_id')))
                             ->limit(1)
                    ),
                    'from_username' => $this->subQuery(
                        $this->select()
                             ->from(array('fu' => self::TABLE_USER))
                             ->columns(array('username'))
                             ->where(array('fu.id' => $this->expr('m.from_user_id')))
                             ->limit(1)
                    ),
                    'timestamp'
                ))
                ->where(array(
                        $this->where()
                            ->addPredicate(
                                $this->where()
                                     ->equalTo('m.from_user_id', $userId)
                                     ->and
                                     ->equalTo('m.archive_from', 'y')
                                     ->and
                                     ->equalTo('m.delete_from', 'n')
                            )
                            ->orPredicate(
                                $this->where()
                                     ->equalTo('m.to_user_id', $userId)
                                     ->and
                                     ->equalTo('m.archive_to', 'y')
                                     ->and
                                     ->equalTo('m.delete_to', 'n')
                            )
                ))
                ->order('m.timestamp desc');
            
            $result = $this->fetchSelect($select);
            
            if ($result) {
                foreach ($result as &$item) {
                    if ($item['from_user_id'] == $userId) {
                        $item['username'] = 'Кому: '.$item['to_username'];
                        $item['type'] = 'from';
                    } else {
                        $item['username'] = 'От: '.$item['from_username'];
                        $item['type'] = 'to';
                    }
                }
            }
            if ($result) {
                $ret = $result;
            }
        }

        return $ret;
    }

    /**
     * Get one message
     * @param int $id
     * @return array|bool
     */
    public function getOne($id = 0)
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;

        if ($id > 0) {
            $select = $this->select()
                ->from(array('m' => self::TABLE_MESSAGES))
                ->join(
                    array('u' => self::TABLE_USER),
                    'u.id = m.from_user_id',
                    array('username')
                )
                ->where(array('m.id' => $id));
            
            $result = $this->fetchRowSelect($select);

            if ($result) {
                $ret = $result;
            }
        }
        return $ret;
    }

    /**
     * Add message
     * @param null $params
     * @param int $advertId
     * @return bool
     */
    public function addMessage($params = null, $advertId = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;
        
        if($params !== null){
            if(
                isset($params['from_user_id']) && isset($params['to_user_id']) && 
                (int)$params['from_user_id'] > 0 && (int)$params['to_user_id'] > 0
              ){                
                $params['timestamp'] = $this->load('Date', 'admin')->getDateTime();
                $insert = $this->insert(self::TABLE_MESSAGES)
                               ->values($params);

                $result = (bool)$this->execute($insert);
                $messageId = $this->insertId($result);

                if ($result && $messageId > 0){
                    $insert = $this->insert(self::TABLE_ADVERT_TO_MESSAGE)
                                   ->values(array(
                                       'advert_id' => $advertId,
                                       'message_id' => $messageId,
                                   ));

                    $this->execute($insert);
                    $ret = true;
                }
            }
        }

        return $ret;
    }

    /**
     * Read message
     * @param int $advert_id
     * @param int user_id
     * @return bool
     */
    public function readMessage($advert_id = 0, $user_id = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;

        if ($advert_id > 0 && $user_id > 0){
            $update = $this->update(self::TABLE_MESSAGES)
                           ->set(array('was_read' => 'y'))
                           ->where(array(
                                'to_user_id' => $user_id,
                                $this->where()
                                     ->in(
                                         'id', 
                                         $this->select()
                                              ->from(self::TABLE_ADVERT_TO_MESSAGE)
                                              ->columns(array('message_id'))
                                              ->where(array('advert_id' => $advert_id))
                                     )
                           ));
            
            $result = (bool)$this->execute($update);

            if ($result){
                $ret = $result;
            }
        }

        return $ret;
    }

    /**
     * Delete message
     * @param int $message_id
     * @param string $action
     * @param string $type
     * @return bool
     */
    public function deleteMessage($message_id = 0, $action = '', $type = ''){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;

        if ($message_id > 0 && !empty($action)){
            $update = $this->update(self::TABLE_MESSAGES);
            
            if ($action === 'inbox'){
                $update->set(array('archive_to' => 'y'));
            } elseif ($action === 'outbox'){
                $update->set(array('archive_from' => 'y'));
            } else {
                if (!empty($type)){
                    if ($type ==='to'){
                        $update->set(array('delete_to' => 'y'));
                    }else{
                        $update->set(array('delete_from' => 'y'));
                    }
                }
            }
            
            $update->where(array('id' => $message_id));
            
            $result = (bool)$this->execute($update);
            
            if ($result){
                $ret = $result;
            }
        }
        return $ret;
    }
}