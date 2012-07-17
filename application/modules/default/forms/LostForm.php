<?php

class Form_LostForm extends Zend_Form {

    public function __construct($option = null) {
        parent::__construct($option);

        $this->addElementPrefixPath('My_Decorator', 'My/Decorator/', 'decorator');

        $this->setName('login')->setAttrib('accept-charset', 'utf-8')->addDecorator("HtmlTag", array('tag' => "fieldset"));

        $user_mail = new Zend_Form_Element_Text('user_email');
        $user_mail->setDecorators(array('textinput'))->setLabel('E-Mail')->setRequired()->setAttrib('class', 'span6');

        $lostbuttton = new Zend_Form_Element_Submit('lost');
        $lostbuttton->setLabel('Reset Password')->setDecorators(array('ViewHelper'))->addDecorator('htmlTag', array('tag' => 'div', 'class' => 'form-actions'))->setAttrib('class', 'btn btn-success');

        $this->addElements(array($user_mail, $lostbuttton));
        $this->setMethod('post');
    }

}
