<?php

class Form_SignupForm extends Twitter_Form {

    public function __construct($option = null) {
        parent::__construct($option);

        $this->setName('signup');

        $user_name = new Zend_Form_Element_Text('user_login');
        $user_name->setLabel('Username')->setRequired()->setDescription('')->addValidator(new Zend_Validate_Alnum())->setAttrib('class', 'span5')->setAttrib('autocomplete', 'off');

        $user_mail = new Zend_Form_Element_Text('user_email');
        $user_mail->setLabel('E-Mail')->setRequired()->addValidator('EmailAddress')->addErrorMessage("Please Enter Valid Email Address")->setDescription('')->setAttrib('class', 'span5')->setAttrib('autocomplete', 'off');

        $user_pass = new Zend_Form_Element_Password('user_pass');
        $user_pass->setLabel('Password')->setRequired()->addValidators(array(array('NotEmpty', true), array('stringLength', false, array(6, 20)),))->setDescription('')->setAttrib('class', 'span5')->addErrorMessage("Please choose a password that is a minimum of 6 characters.");
        
        $terms = new Zend_Form_Element_Checkbox('terms');
        $terms->setLabel('<b>I have read, understood and agree to the <a href="#" target="_blank">Terms of service</a> of the site')->setAttrib('checked', 0)->addErrorMessage("You must agree to the Terms and Conditions to continue.");
        
        $signup = new Zend_Form_Element_Submit('signup');
        $signup->setLabel('Create My Account')->setAttrib('class', 'btn btn-success btn-large');
        
        $this->addElements(array($user_name, $user_mail, $user_pass, $terms, $signup));
        $this->setMethod('post');
    }

}
