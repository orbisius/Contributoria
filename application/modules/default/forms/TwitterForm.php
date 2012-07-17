<?php

class Form_TwitterForm extends Zend_Form {

    public function __construct($option = null) {
        parent::__construct($option);

        $this->addElementPrefixPath('My_Decorator', 'My/Decorator/', 'decorator');

        $this->setName('signup')->setAttrib('accept-charset', 'utf-8')->addDecorator("HtmlTag", array('tag' => "fieldset"));

        $user_name = new Zend_Form_Element_Text('user_login');
        $user_name->setDecorators(array('textinput'))->setLabel('Username')->setRequired()->addValidator(new Zend_Validate_Alnum())->setAttrib('class', 'span5')->setAttrib('autocomplete', 'off');

        $user_mail = new Zend_Form_Element_Text('user_email');
        $user_mail->setDecorators(array('textinput'))->setLabel('E-Mail')->setRequired()->addValidator('EmailAddress')->addErrorMessage("Please Enter Valid Email Address")->setAttrib('class', 'span5')->setAttrib('autocomplete', 'off');
        
        $terms = new Zend_Form_Element_Checkbox('terms');
        $terms->setLabel('<b>I have read, understood and agree to the <a href="http://about.n0tice.com/terms-and-conditions" target="_blank">Terms of service</a> of the site');
        $terms->setDecorators(array('Errors', array('ViewScript', array('viewScript' => 'checkinput.phtml'))));
        $terms->addErrorMessage("You must agree to the Terms and Conditions to continue.");
        
        $signup = new Zend_Form_Element_Submit('signup');
        $signup->setLabel('Create My Account')->setDecorators(array('ViewHelper'))->addDecorator('htmlTag', array('tag' => 'div', 'class' => 'form-actions'))->setAttrib('class', 'btn btn-success btn-large')->setAttrib('data-theme', 'e');

        $this->addElements(array($user_name, $user_mail, $terms, $signup));
        $this->setMethod('post');
    }

}
