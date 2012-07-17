<?php

class Form_LoginForm extends Twitter_Form {

    public function __construct($option = null) {
        parent::__construct($option);
                
        $this->setName('login')->setAttrib('accept-charset', 'utf-8')->setAttrib('class', 'span5');

        $user_login = new Zend_Form_Element_Text('user_login');
        $user_login->setAttrib('placeholder', 'Username or E-Mail')->setRequired()->setAttrib('class', 'span4 xl-input');

        $user_pass = new Zend_Form_Element_Password('user_pass');
        $user_pass->setRequired()->setAttrib('placeholder', 'Password')->setAttrib('class', 'span4 xl-input');
        
        $login_redirect = new Zend_Form_Element_Hidden('login_redirect');
        
        $forget = new Zend_Form_Element_Checkbox('remember_me');
        $forget->setLabel('Keep me logged in')->setAttrib('checked', '1');

        $login = new Zend_Form_Element_Submit('login');
        $login->setLabel('Log In ')->setAttrib('class', 'btn btn-success btn-large');

        $this->addElements(array($user_login, $user_pass, $forget, $login_redirect, $login));
        $this->setMethod('post');
    }

}