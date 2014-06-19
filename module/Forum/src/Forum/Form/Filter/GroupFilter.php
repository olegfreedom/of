<?php

namespace Forum\Form\Filter;

 // Add these import statements
 use Zend\InputFilter\InputFilter;
 use Zend\InputFilter\InputFilterAwareInterface;
 use Zend\InputFilter\InputFilterInterface;

class GroupFilter implements InputFilterAwareInterface 
{
    public function setInputFilter(InputFilterInterface $inputFilter)
    {
        throw new \Exception("Not used");
    }

    public function getInputFilter() {
        if (!$this->inputFilter) {
            $inputFilter = new InputFilter();

            $inputFilter->add(array(
                'name' => 'id',
                'required' => true,
                'filters' => array(
                    array('name' => 'Int'),
                ),
            ));

            $inputFilter->add(array(
                'name' => 'artist',
                'required' => true,
                'filters' => array(
                    array('name' => 'StripTags'),
                    array('name' => 'StringTrim'),
                ),
                'validators' => array(
                    array(
                        'name' => 'StringLength',
                        'options' => array(
                            'encoding' => 'UTF-8',
                            'min' => 1,
                            'max' => 100,
                        ),
                    ),
                ),
            ));

            $inputFilter->add(array(
                'name' => 'title',
                'required' => true,
                'filters' => array(
                    array('name' => 'StripTags'),
                    array('name' => 'StringTrim'),
                ),
                'validators' => array(
                    array(
                        'name' => 'StringLength',
                        'options' => array(
                            'encoding' => 'UTF-8',
                            'min' => 1,
                            'max' => 100,
                        ),
                    ),
                ),
            ));

            $this->inputFilter = $inputFilter;
        }

        return $this->inputFilter;
    }

    public function uploadFile(){
        $this->params()->fromHead();
        
//        $size = new Size(array('min'=>2000000)); //minimum bytes filesize
//        $adapter = new \Zend\File\Transfer\Adapter\Http();
//        $adapter->setValidators(array($size), $File['name']);
//        if (!$adapter->isValid()){
//            $dataError = $adapter->getMessages();
//            $error = array();
//            foreach($dataError as $key=>$row){
//                $error[] = $row;
//            }
//            return false;//array('fileupload'=>$error );
//        } else {
//            $adapter->setDestination(dirname(__DIR__).'/data/forum/avatars/');
//            if ($adapter->receive($File['name'])) {
//                return $File['name'];
//            }
//        }  
        return true;
    }
}
