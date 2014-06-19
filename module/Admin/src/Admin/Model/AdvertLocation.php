<?php
namespace Admin\Model;

class AdvertLocation extends \Application\Base\Model
{

    /**
     * Get locations
     * @return array|null
     */
    public function get($whereAr = array()){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = null;
                     
        $select = $this->select()
                       ->from(self::TABLE_ADVERTS_LOCATION)
                       ->columns(array('id', 'name'))
                       ->order('name asc')
                       ->where($whereAr);

        $result = $this->fetchSelect($select);

        if($result){
            $ret = $result;
        }
        
        return $ret;
    }


    /**
     * get one location
     * @param int $id
     * @return array
     */
    public function getOne($id = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $res = null;

        if($id > 0){
            $select = $this->select()
                        ->from(self::TABLE_ADVERTS_LOCATION)
                        ->columns(array('id', 'name', 'region_id'))
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
     * Edit location
     * @param int $id
     * @param array $params
     * @return bool
     */
    public function edit($id = 0, $params = null){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;

        if($id > 0 && $params !== null){
            $update = $this->update(self::TABLE_ADVERTS_LOCATION)
                           ->set($params)
                           ->where(array('id' => $id));

            $ret = $this->execute($update);
        }

        return (bool)$ret;
    }

    /**
     * Add location
     * @param array $params
     * @return bool
     */
    public function add($params = null){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;

        if($params !== null){
            $insert = $this->insert(self::TABLE_ADVERTS_LOCATION)
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
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        return $this->load('ForeignKeys', 'admin')->check(self::TABLE_ADVERTS_LOCATION, $id);
    }

    /**
     * Remove location
     * @param int $id
     * @return bool
     */
    public function remove($id = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;

        if($id > 0 && $this->checkKeys($id) == false){
            $delete = $this->delete(self::TABLE_ADVERTS_LOCATION)
                           ->where(array('id' => $id));

            $ret = $this->execute($delete);
        }

        return (bool)$ret;
    }

    /**
     * Get regions
     * @return array|null
     */
    public function getRegions(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = null;

        $select = $this->select()
                    ->from(self::TABLE_LOCATION_REGIONS)
                    ->columns(array('id', 'name'))
                    ->order('name asc');

        $result = $this->fetchSelect($select);

        if($result){
            $ret = $result;
        }

        return $ret;
    }

    /**
     * Get regions
     * @return array|null
     */
    /*
    public function getRegions(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = null;

        $select = $this->select()
                    ->from(self::TABLE_ADVERTS_LOCATION_REGIONS)
                    ->columns(array('id', 'name'))
                    ->order('name asc');

        $result = $this->fetchSelect($select);

        if($result){
            $ret = $result;
        }

        return $ret;
    }
    */

    /**
     * get one region
     * @param int $id
     * @return array
     */
    public function getOneRegion($id = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $res = null;

        if($id > 0){
            $select = $this->select()
                        ->from(self::TABLE_LOCATION_REGIONS)
                        ->columns(array('id', 'name'))
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
     * Add location
     * @param array $params
     * @return bool
     */
    public function addRegion($params = null){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;

        if($params !== null){
            $insert = $this->insert(self::TABLE_LOCATION_REGIONS)
                        ->values($params);

            $ret = $this->execute($insert);
        }

        return (bool)$ret;
    }

    /**
     * Edit location
     * @param int $id
     * @param array $params
     * @return bool
     */
    public function editRegion($id = 0, $params = null){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;

        if($id > 0 && $params !== null){
            $update = $this->update(self::TABLE_LOCATION_REGIONS)
                    ->set($params)
                    ->where(array('id' => $id));

            $ret = $this->execute($update);
        }

        return (bool)$ret;
    }

    /**
     * Remove check
     * @param int $id
     * @return bool
     */
    public function checkKeysRegion($id = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        return $this->load('ForeignKeys', 'admin')->check(self::TABLE_ADVERTS_LOCATION_REGIONS, $id);
    }

    /**
     * Remove type
     * @param int $id
     * @return bool
     */
    public function removeRegion($id = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;

        if($id > 0 && $this->checkKeysRegion($id) == false){
            $delete = $this->delete(self::TABLE_LOCATION_REGIONS)
                        ->where(array('id' => $id));

            $ret = $this->execute($delete);
        }

        return (bool)$ret;
    }

    /**
     * Get list of cities by region ID
     * @param $region_id
     * @return array|bool
     */
    /*
    public function getCitiesByRegion($region_id){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;

        if ($region_id > 0){
            $select = $this->select()
                        ->from(self::TABLE_ADVERTS_LOCATION)
                        ->where(array('region_id' => $region_id));
            $result = $this->fetchSelect($select);

            if ($result){
                $ret = $result;
            }

        }
        return $ret;
    }
    */

    /**
     * Get list of areas by region ID
     * @param int $region_id
     * @return array|bool
     */
    public function getAreasByRegion($region_id = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;

        if ($region_id > 0){
            $select = $this->select()
                ->from(self::TABLE_LOCATION_AREAS)
                ->where(array('region_id' => $region_id))
                ->order('name ASC');
            $result = $this->fetchSelect($select);

            if ($result){
                $ret = $result;
            }

        }
        return $ret;
    }

    /**
     * Get list of cities by area ID
     * @param int $area_id
     * @return array|bool
     */
    public function getCitiesByArea($area_id = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;

        if ($area_id > 0){
            $select = $this->select()
                ->from(self::TABLE_LOCATION_CITIES)
                ->where(array('area_id' => $area_id))
                ->order('name ASC')
                ->group('name');
            $result = $this->fetchSelect($select);

            if ($result){
                $ret = $result;
            }

        }
        return $ret;
    }

    /**
     * Get list of zip by city ID
     * @param int $city_id
     * @return array|bool
     */
    public function getZipByCity($city_id = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;

        if ($city_id > 0){
            $select = $this->select()
                ->from(self::TABLE_LOCATION_ZIP)
                ->where(array('city_id' => $city_id))
                ->order('name ASC')
                ->group('name');
            $result = $this->fetchSelect($select);

            if ($result){
                $ret = $result;
            }

        }
        return $ret;
    }

    /**
     * Get city by ZIP
     * @param string $zip
     * @return int|bool
     */
    public function getCityByZIP($zip){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;

        if ( (int) $zip > 0 )
        {
            $select = $this->select()
                ->from(self::TABLE_LOCATION_ZIP)
                ->columns(array('city_id'))
                ->where(array('name' => $zip))
                ->limit(1);
            $result = $this->fetchOneSelect($select);

            if ($result){
                $ret = $result;
            }

        }

        return $ret;
    }


    /**
     * Get area by city
     * @param $city_id
     * @return int|bool
     */
    public function getAreaByCity($city_id){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;

        if ($city_id > 0){
            $select = $this->select()
                        ->from(self::TABLE_LOCATION_CITIES)
                        ->columns(array('area_id'))
                        ->where(array('id' => $city_id))
                        ->limit(1);
            $result = $this->fetchOneSelect($select);

            if ($result){
                $ret = $result;
            }

        }
        return $ret;
    }

    /**
     * Get region by area
     * @param $area_id
     * @return int|bool
     */
    public function getRegionByArea($area_id){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;

        if ($area_id > 0){
            $select = $this->select()
                ->from(self::TABLE_LOCATION_AREAS)
                ->columns(array('region_id'))
                ->where(array('id' => $area_id))
                ->limit(1);
            $result = $this->fetchOneSelect($select);

            if ($result){
                $ret = $result;
            }

        }
        return $ret;
    }

}
