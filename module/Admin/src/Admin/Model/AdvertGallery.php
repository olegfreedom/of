<?php
namespace Admin\Model;

class AdvertGallery extends \Application\Base\Model
{

    private function getSQL(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $select = $this->select()
                       ->from(array('g' => self::TABLE_ADVERTS_GALLERY))
                       ->columns(array())
                       ->join(
                           array('a' => self::TABLE_ADVERTS),
                           'g.advert_id = a.id',
                           array()
                       )
                       ->join(
                           array('u' => self::TABLE_USER),
                           'a.user_id = u.userid',
                           array()
                       )
                       ->order('g.id asc');
        
        return $select;
    }

    /**
     * Get all list
     * @param int $advert
     * @return null|array
     */
    public function get($advert = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = null;
        
        if((int)$advert > 0){            
            $select = $this->getSQL();

            if($select instanceof \Zend\Db\Sql\Select){
                $select->columns(array(
                           'id',
                           'url'
                       ))
                       ->where(array(
                           'g.advert_id' => $advert
                       ));

                $result = $this->fetchSelect($select);

                if($result){                     
                   $ret = $result; 
                }
            }
        }
        
        return $ret;
    }
    
    /**
     * Get one
     * @param int $id
     * @return null|array
     */
    public function one($id = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
         
        $ret = null;
        
        $select = $this->getSQL();
        
        if($select instanceof \Zend\Db\Sql\Select){
            if((int)$id > 0){                
                $select->columns(array('url'))
                       ->where(array('g.id' => $id))
                       ->limit(1);

                $result = $this->fetchOneSelect($select);

                if($result){
                   $ret = $result; 
                }
            }
        }
        return $ret;
    }

    /**
     * Add gallery
     * @param int $advert
     * @param mixed $gallery
     * @return bool
     */
    public function add($advert = 0, $gallery = null){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;
        
        if((int)$advert > 0 && isset($gallery['name'])){
            $size = sizeof($gallery['name']);
            
            if($size > 0){
                $params = array(
                    'advert_id' => $advert
                );
                
                for($i=0; $i<$size; $i++){        
                    $img = array(
                        'name' => $gallery['name'][$i],
                        'type' => $gallery['type'][$i],
                        'tmp_name' => $gallery['tmp_name'][$i],
                        'error' => $gallery['error'][$i]
                    );

                    $url = $this->load('Upload', 'admin')->save($img, array('gif', 'png', 'jpg', 'jpeg'), 'adverts/'.date('Y_m').'/'.date('d'));

                    if($url !== null){
                        $params['url'] = $url;
                        
                        $insert = $this->insert(self::TABLE_ADVERTS_GALLERY)
                                       ->values($params);

                        $this->execute($insert);

                        if($ret == false){
                            $ret = true;
                        }
                    }

                }
                
            }            
        }
        
        return (bool)$ret;
    }
    
    /**
     * Remove gallery
     * @param int $advert
     * @param int $id
     * @param int $userId
     * @return bool
     */
    public function remove($advert = 0, $id = 0, $userId = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = false;
        
        if((int)$advert > 0){                
            $url = $this->one($id);

            $delete = $this->delete(self::TABLE_ADVERTS_GALLERY)
                           ->where(array(
                               'advert_id' => $advert
                           ));

            if((int)$id > 0){
                $delete->where(array('id' => $id));
            }

            if((int)$userId > 0){
                if($this->checkUserAccess($advert, $userId) == true){
                    $ret = $this->execute($delete);
                }
            }else{
                $ret = $this->execute($delete);
            }

            if($ret && $url){
                $this->load('Upload', 'admin')->unlink($url);
            }
        }
        
        return (bool)$ret;
    }

    /**
     * Remove access
     * @param int $advert
     * @param int $userId
     * @return bool
     */
    private function checkUserAccess($advert = 0, $userId = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = false;
        
            if((int)$advert > 0 && (int)$userId > 0){
                $select = $this->select()
                               ->from(self::TABLE_ADVERTS)
                               ->columns(array('id'))
                               ->where(array(
                                   'id' => $advert,
                                   'user_id' => $userId
                               ))
                               ->limit(1);

                $result = (int)$this->fetchOneSelect($select);

                if($result > 0){
                    $ret = true;
                }
            }
        
        return $ret;
    }
    
    /**
     * Generate Gallery URL
     * @param int $id
     * @param \Base\Mvc\Controller $controller
     * @return null|array
     */
    public function generateURL($id = 0, \Base\Mvc\Controller $controller){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $gallery = null;
        
        if((int)$id > 0){            
            $gallery = $this->get($id);

            if(is_array($gallery)){
                foreach($gallery as &$item){
                    $exp = explode('/', $item['url']);
                    $imgName = end($exp);
                    
                    $item['url'] = $controller->easyUrl(array('module' => 'application', 'controller' => 'image', 'action' => 'adverts-gallery', 'id' => $item['id']));
                    $item['slider'] = array(
                        'small' => $controller->easyUrl(array('module' => 'application', 'controller' => 'image', 'action' => 'adverts-gallery', 'id' => $item['id'], 'w' => 122, 'h' => 122, 'crop' => 'y')),
                        'small_gallery' => $controller->easyUrl(array('module' => 'application', 'controller' => 'image', 'action' => 'adverts-gallery', 'id' => $item['id'], 'w' => 166, 'h' => 88, 'crop' => 'y')),
                        'medium' => $controller->easyUrl(array('module' => 'application', 'controller' => 'image', 'action' => 'adverts-gallery', 'id' => $item['id'], 'w' => 712, 'h' => 404)),
                    );
                    $item['name'] = $imgName;
                }            
            }
        }
        
        return $gallery;
    }
}