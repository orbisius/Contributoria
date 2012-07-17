<?php

class Form_TwitterForm extends Twitter_Form {

    public function __construct($option = null) {
        parent::__construct($option);

        $this->setName('signup');

        $user_name = new Zend_Form_Element_Text('user_login');
        $user_name->setLabel('Username')->setRequired()->addValidator(new Zend_Validate_Alnum())->setAttrib('class', 'span5')->setAttrib('autocomplete', 'off');

        $user_mail = new Zend_Form_Element_Text('user_email');
        $user_mail->setLabel('E-Mail')->setRequired()->addValidator('EmailAddress')->addErrorMessage("Please Enter Valid Email Address")->setAttrib('class', 'span5')->setAttrib('autocomplete', 'off');
        
        $terms = new Zend_Form_Element_Checkbox('terms');
        $terms->setLabel('<b>I have read, understood and agree to the <a href="http://about.n0tice.com/terms-and-conditions" target="_blank">Terms of service</a> of the site')->addErrorMessage("You must agree to the Terms and Conditions to continue.");
        
        $signup = new Zend_Form_Element_Submit('signup');
        $signup->setLabel('Create My Account')->setAttrib('class', 'btn btn-success btn-large');

        $this->addElements(array($user_name, $user_mail, $terms, $signup));
        $this->setMethod('post');
    }

}
