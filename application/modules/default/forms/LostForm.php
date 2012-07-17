<?php

class Form_LostForm extends Twitter_Form {

    public function __construct($option = null) {
        parent::__construct($option);

        $this->setName('login')->setAttrib('accept-charset', 'utf-8');

        $user_mail = new Zend_Form_Element_Text('user_email');
        $user_mail->setLabel('E-Mail')->setRequired()->setAttrib('class', 'span6');

        $lostbuttton = new Zend_Form_Element_Submit('lost');
        $lostbuttton->setLabel('Reset Password')->setAttrib('class', 'btn btn-success');

        $this->addElements(array($user_mail, $lostbuttton));
        $this->setMethod('post');
    }

}
