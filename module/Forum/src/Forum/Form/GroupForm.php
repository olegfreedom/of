<?php 

namespace Forum\Form;
use Zend\Form\Element;
use Zend\Form\Form;
class GroupForm extends Form
{   
  
    public function __construct($name = null){
        parent::__construct('group');
        
        $this->setAttribute('method', 'post');
        $this->setAttribute('class', 'form-horizontal');

        $this->add(array(
            'name' => 'id',
            'type' => 'Zend\Form\Element\Hidden',
            'options' => array(
                'label' => _('id'),
            ),
        ));
        $this->add(array(
            'type' => 'Zend\Form\Element\Select',
            'name' => 'type_organization_id',
            'attributes' => array(
                'class' => 'input-xlarge'
            ),
            'options' => array(
                'label' => _('Тип організації'),
                'value_options' => array(
                    '1'=>_('Група'),
                    '2'=>_('Фірма'),
                    '3'=>_('Кооператив'),
                )
            ),
        ));
        $this->add(array(
            'type' => 'Zend\Form\Element\Text',
            'name' => 'title',
            'attributes' => array(
                'class' => 'input-xlarge'
            ),
            'options' => array(
                'label' => _('Назва організації'),
            ),
        ));
        $this->add(array(
            'type' => 'Zend\Form\Element\Text',
            'name' => 'location',
            'attributes' => array(
                'class' => 'input-xlarge'
            ),
            'options' => array(
                'label' => _('Локація'),
            ),
        ));
        $this->add(array(
            'type' => 'Zend\Form\Element\Textarea',
            'name' => 'description',
            'attributes' => array(
                'class' => 'input-xlarge'
            ),
            'options' => array(
                'label' => _('Локація'),
            ),
        ));
        $this->add(array(
            'type' => 'Zend\Form\Element\Textarea',
            'name' => 'description',
            'attributes' => array(
                'class' => 'input-xlarge'
            ),
            'options' => array(
                'label' => _('Опис'),
            ),
        ));
        $this->add(array(
            'type' => 'Zend\Form\Element\File',
            'name' => 'avatar',
            'attributes' => array(
                'class' => 'input-xlarge'
            ),
            'options' => array(
                'label' => _('Фото'),
            ),
        ));
        $this->add(array(
            'type' => 'Zend\Form\Element\Select',
            'name' => 'active',
            'attributes' => array(
                'class' => 'input-xlarge'
            ),
            'options' => array(
                'label' => _('Автивність'),
                'value_options' => array(
                    '0'=>_('Не активна'),
                    '1'=>_('Активна'),
                )
            ),
        ));
        $this->add(array(
            'type' => 'Zend\Form\Element\Select',
            'name' => 'avaliable',
            'attributes' => array(
                'class' => 'input-xlarge'
            ),
            'options' => array(
                'label' => _('Автивність'),
                'value_options' => array(
                    '0'=>_('Тільки творцю групи'),
                    '1'=>_('Запрошеним учасникам'),
                    '2'=>_('Учасникам регіону(вибір регіону)'),
                    '3'=>_('Всім'),
                )
            ),
        ));
        
        
        $this->add(array(
            'name' => 'send',
            'attributes' => array(
                'type'  => 'submit',
                'value' => 'Submit',
            ),
        ));
    }
}
