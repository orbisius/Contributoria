<?php

class User_Form_EditNotificationsForm extends Zend_Form {

    public function __construct($option = null) {
        parent::__construct($option);
        
        $this->setName('user_edit')->setAttrib('accept-charset', 'utf-8')->addDecorator("HtmlTag", array('tag' => "fieldset"))->setAttrib('class', 'form-horizontal');

        $this->addElementPrefixPath('My_Decorator', 'My/Decorator/', 'decorator');
        
        $not_1 = new Zend_Form_Element_Checkbox('notification_message');
        $not_1->setLabel('Sends me a private message')->setAttrib('checked', '1')->setDecorators(array('Errors', array('ViewScript', array('viewScript' => 'checkinput.phtml'))));
        
        $not_2 = new Zend_Form_Element_Checkbox('notification_update');
        $not_2->setLabel('Updates one of my posts')->setAttrib('checked', '1')->setDecorators(array('Errors', array('ViewScript', array('viewScript' => 'checkinput.phtml'))));
        
        $not_3 = new Zend_Form_Element_Checkbox('notification_interesting');
        $not_3->setLabel('Likes one of my posts')->setAttrib('checked', '1')->setDecorators(array('Errors', array('ViewScript', array('viewScript' => 'checkinput.phtml'))));
        
        $not_4 = new Zend_Form_Element_Checkbox('notification_repost');
        $not_4->setLabel('Reposts one of my posts')->setAttrib('checked', '1')->setDecorators(array('Errors', array('ViewScript', array('viewScript' => 'checkinput.phtml'))));
        
        $not_5 = new Zend_Form_Element_Checkbox('notification_follow');
        $not_5->setLabel('Follows me')->setAttrib('checked', '1')->setDecorators(array('Errors', array('ViewScript', array('viewScript' => 'checkinput.phtml'))));
        
        $not_6 = new Zend_Form_Element_Checkbox('notification_noticeboard');
        $not_6->setLabel('Follows my noticeboard')->setAttrib('checked', '1')->setDecorators(array('Errors', array('ViewScript', array('viewScript' => 'checkinput.phtml'))));
        
        $save = new Zend_Form_Element_Submit('save');
        $save->setLabel('Save')->setDecorators(array('ViewHelper'))->addDecorator('htmlTag', array('tag' => 'div', 'class' => 'form-actions'))->setAttrib('class', 'btn btn-primary btn-large');

        $this->addElements(array($not_1, $not_2, $not_3, $not_4, $not_5, $not_6, $save));
        $this->setMethod('post');
    }

}
