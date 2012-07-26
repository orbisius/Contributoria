<?php

class User_Form_EditNotificationsForm extends Twitter_Form {

    public function __construct($option = null) {
        parent::__construct($option);
        
        $this->setName('user_edit')->setAttrib('class', 'form-horizontal');
        
        $not_1 = new Zend_Form_Element_Checkbox('notification_message');
        $not_1->setLabel('Sends me a private message')->setAttrib('checked', '1');
        
        $save = new Zend_Form_Element_Submit('save');
        $save->setLabel('Save')->setAttrib('class', 'btn btn-primary btn-large');

        $this->addElements(array($not_1, $save));
        $this->setMethod('post');
    }

}
