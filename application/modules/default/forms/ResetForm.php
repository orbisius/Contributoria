<?php

class Form_ResetForm extends Twitter_Form {

    public function __construct($option = null) {
        parent::__construct($option);

        $this->setName('login');

        $user_pass = new Zend_Form_Element_Password('user_password');
        $user_pass->setLabel('Password')->addValidators(array(array('NotEmpty', true), array('stringLength', false, array(6, 20)),))->setDescription('Must be between 6 and 20 characters')->setRequired()->setAttrib('class', 'span7');

        $user_pass2 = new Zend_Form_Element_Password('user_password_confirm');
        $user_pass2->setLabel('Confirm Password')->setRequired()->setAttrib('class', 'span7');

        $email = new Zend_Form_Element_Hidden('email');
        $email->setRequired();

        $code = new Zend_Form_Element_Hidden('code');
        $code->setRequired();

        $buttton = new Zend_Form_Element_Submit('lost');
        $buttton->setLabel('Reset Password')->setAttrib('class', 'btn btn-success');

        $this->addElements(array($user_pass, $user_pass2, $email, $code, $buttton));
        $this->setMethod('post');
    }

}
