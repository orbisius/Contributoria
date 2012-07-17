<?php

class Form_LoginForm extends Zend_Form {

    public function __construct($option = null) {
        parent::__construct($option);
        
        $this->addElementPrefixPath('My_Decorator', 'My/Decorator/', 'decorator');
        
        $this->setName('small_login')->setAttrib('accept-charset', 'utf-8')->addDecorator("HtmlTag", array('tag' => "fieldset"));

        $user_login = new Zend_Form_Element_Text('user_login');
        $user_login->setDecorators(array('textinput'))->setAttrib('placeholder', 'Username or E-Mail')->setRequired()->setAttrib('class', 'span4')->removeDecorator('label');

        $user_pass = new Zend_Form_Element_Password('user_pass');
        $user_pass->setDecorators(array('textinput'))->setRequired()->setAttrib('placeholder', 'Password')->setAttrib('class', 'span4')->removeDecorator('label');
        
        $login_redirect = new Zend_Form_Element_Hidden('login_redirect');
        
        $checkinput = "checkinput.phtml";
        $browser = new Zend_Session_Namespace('browser');
        if ($browser->type !== 'desktop') {
            $checkinput = "checkinput.mobile.phtml";
        }
        
        $forget = new Zend_Form_Element_Checkbox('remember_me');
        $forget->setLabel('Keep me logged in')->setAttrib('checked', '1')->setDecorators(array('Errors', array('ViewScript', array('viewScript' => $checkinput))));

        $login = new Zend_Form_Element_Submit('login');
        $login->setLabel('Log In ')->setDecorators(array('ViewHelper'))->addDecorator('htmlTag', array('tag' => 'div', 'class' => 'form-actions'))->setAttrib('class', 'btn btn-success btn-large')->setAttrib('data-theme', 'e');

        $this->addElements(array($user_login, $user_pass, $forget, $login_redirect, $login));
        $this->setMethod('post');
    }

}