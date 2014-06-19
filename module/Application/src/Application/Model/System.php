<?php
/**
 * Created by PhpStorm.
 * User: work
 * Date: 3/31/14
 * Time: 10:33 AM
 */
namespace Application\Model;

class System extends \Application\Base\Model
{
    public function test()
    {
        $Controller = $this->getController();
        $this->debug($Controller->easyUrl(array('module' => 'application', 'controller' => 'image', 'action' => 'user-avatar', 'id' => 40, 'w' => 72, 'h' => 72, 'crop' => 'y')));
    }

    public function importLocations()
    {
        die('STOP');
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $select = $this->select()
            ->from(array('l' => self::TABLE_LOCATION))
            ->columns(array('id', 'region', 'area', 'city', 'idx'))
            ->order('l.region ASC, l.area ASC, l.city ASC, l.idx ASC');

        $result = $this->fetchSelect($select);
        $dataAr = array();
        $cntAr = array(
            'location' => 0,
            'region' => 0,
            'area' => 0,
            'city' => 0,
            'zip' => 0,
        );



        if ( !empty($result) )
        {
            foreach ( $result as $row )
            {
                $region_id = 0;
                $area_id = 0;
                $city_id = 0;
                $zip_id = 0;

                // Region set
                if ( empty($dataAr [$row ['region']]) )
                {
                    $valuesAr = array(
                        'name' => $row ['region'],
                    );

                    $insert = $this->insert(self::TABLE_LOCATION_REGIONS)
                        ->values($valuesAr);
                    $ret = $this->execute($insert);

                    $region_id = $this->insertId();
                    $cntAr ['region']++;

                    $dataAr [$row ['region']] = array(
                        'id' => $region_id,
                        'areas' => array()
                    );

                    $update = $this->update('location');
                    $update->set(array(
                        'r' => new \Zend\Db\Sql\Expression('r + 1')
                    ));
                    $update->where(array('id' => $row ['id']));
                }
                else
                {
                    $region_id = $dataAr [$row ['region']] ['id'];
                }

                // Area set
                if ( empty($dataAr [$row ['region']] ['areas'] [$row ['area']]) )
                {
                    $valuesAr = array(
                        'region_id' => $region_id,
                        'name' => $row ['area'],
                    );

                    $insert = $this->insert(self::TABLE_LOCATION_AREAS)
                        ->values($valuesAr);
                    $ret = $this->execute($insert);

                    $area_id = $this->insertId();
                    $cntAr ['area']++;

                    $dataAr [$row ['region']] ['areas'] [$row ['area']] = array(
                        'id' => $area_id,
                        'cities' => array(),
                    );

                    $update = $this->update('location');
                    $update->set(array(
                        'a' => new \Zend\Db\Sql\Expression('a + 1')
                    ));
                    $update->where(array('id' => $row ['id']));
                }
                else
                {
                    $area_id = $dataAr [$row ['region']] ['areas'] [$row ['area']] ['id'];
                }

                // City set
                if ( empty($dataAr [$row ['region']] ['areas'] [$row ['area']] ['cities'] [$row ['city']]) )
                {
                    $valuesAr = array(
                        'area_id' => $area_id,
                        'name' => $row ['city'],
                    );

                    $insert = $this->insert(self::TABLE_LOCATION_CITIES)
                        ->values($valuesAr);
                    $ret = $this->execute($insert);

                    $city_id = $this->insertId();
                    $cntAr ['city']++;

                    $dataAr [$row ['region']] ['areas'] [$row ['area']] ['cities'] [$row ['city']] = array(
                        'id' => $city_id,
                        'zip' => array(),
                    );

                    $update = $this->update('location');
                    $update->set(array(
                        'c' => new \Zend\Db\Sql\Expression('c + 1')
                    ));
                    $update->where(array('id' => $row ['id']));
                }
                else
                {
                    $city_id = $dataAr [$row ['region']] ['areas'] [$row ['area']] ['cities'] [$row ['city']] ['id'];
                }

                // Zip set
                if ( empty($dataAr [$row ['region']] ['areas'] [$row ['area']] ['cities'] [$row ['city']] ['zip'] [$row ['idx']]) )
                {
                    $valuesAr = array(
                        'city_id' => $city_id,
                        'name' => $row ['idx'],
                    );

                    $insert = $this->insert(self::TABLE_LOCATION_ZIP)
                        ->values($valuesAr);
                    $ret = $this->execute($insert);

                    $zip_id = $this->insertId();
                    $cntAr ['zip']++;

                    $dataAr [$row ['region']] ['areas'] [$row ['area']] ['cities'] [$row ['city']] ['zip'] [$row ['idx']] = $zip_id;

                    $update = $this->update('location');
                    $update->set(array(
                        'z' => new \Zend\Db\Sql\Expression('z + 1')
                    ));
                    $update->where(array('id' => $row ['id']));
                }

                $cntAr ['location']++;
            }
        }

        return $cntAr;
    }
}
