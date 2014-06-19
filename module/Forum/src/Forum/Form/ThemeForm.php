<?php 

namespace Forum\Form;
use Zend\Form\Element;
use Zend\Form\Form;
class ThemeForm extends Form
{   
  
    public function __construct($name = null){
        parent::__construct('theme');
        
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
            'type' => 'Zend\Form\Element\Select',
            'name' => 'visibility',
            'attributes' => array(
                'class' => 'input-xlarge'
            ),
            'options' => array(
                'label' => _('Видимість'),
                'value_options' => array(
                    '0'=>_('Видимий'),
                    '1'=>_('Невидимий'),
                )
            ),
        ));
        $this->add(array(
            'type' => 'Zend\Form\Element\Select',
            'name' => 'type',
            'attributes' => array(
                'class' => 'input-xlarge'
            ),
            'options' => array(
                'label' => _('Обов’язкове'),
                'value_options' => array(
                    '0'=>_('Не обов’язкове'),
                    '1'=>_('Обов’язкове'),
                )
            ),
        ));
        $this->add(array(
            'type' => 'Zend\Form\Element\Text',
            'name' => 'begin_date',
            'attributes' => array(
                'class' => 'input-xlarge'
            ),
            'options' => array(
                'label' => _('Відкрита тема від дати'),
            ),
        ));
        $this->add(array(
            'type' => 'Zend\Form\Element\Text',
            'name' => 'end_date',
            'attributes' => array(
                'class' => 'input-xlarge'
            ),
            'options' => array(
                'label' => _('Відкрита тема до дати'),
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
