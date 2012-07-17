<?php

class User_Form_EditAccountForm extends Zend_Form {

    public function __construct($action, $option = null) {
        parent::__construct($option);

        $this->setName('user_edit')->setAttrib('accept-charset', 'utf-8')->addDecorator("HtmlTag", array('tag' => "fieldset"))->setAttrib('class', 'form-horizontal');

        $this->addElementPrefixPath('My_Decorator', 'My/Decorator/', 'decorator');

        $user_name = new Zend_Form_Element_Text('user_login');
        $user_name->setDecorators(array('textinput'))->setLabel('Username')->setRequired()->setDescription('Alphanumerics only.')->setAttrib('class', 'span6');

        $user_realname = new Zend_Form_Element_Text('user_realname');
        $user_realname->setDecorators(array('textinput'))->setLabel('Real Name')
                ->setRequired()
                ->setDescription('Your full name please.')->setAttrib('class', 'span6');

        $user_email = new Zend_Form_Element_Text('user_email');
        $user_email->setDecorators(array('textinput'))->setLabel('E-mail')
                ->addValidator('EmailAddress')->addErrorMessage("Please Enter Valid Email Address")->setAttrib('class', 'span6')
                ->setDescription('The email you want associated with us.')
                ->setRequired();

        $user_pass = new Zend_Form_Element_Password('user_pass');
        $user_pass->setDecorators(array('textinput'))->setLabel('Password')
                ->setDescription('Leave blank if you don\'t want to change your password.')->setAttrib('class', 'span6')
                ->addValidators(array(array('stringLength', false, array(6, 20)),));

        $confirm_pass = new Zend_Form_Element_Password('confirm_pass');
        $confirm_pass->setDecorators(array('textinput'))->setLabel('Confirm Password')->setAttrib('class', 'span6')
                ->setDescription('Ditto above.')
                ->addValidator('Identical', false, array('token' => 'user_pass'))->addErrorMessage("Your passwords do not match!");

        $save = new Zend_Form_Element_Submit('save');
        $save->setLabel('Save')->setDecorators(array('ViewHelper'))->addDecorator('htmlTag', array('tag' => 'div', 'class' => 'form-actions'))->setAttrib('class', 'btn btn-primary btn-large');

        $this->addElements(array($user_name, $user_realname, $user_email, $get_emails, $user_pass, $confirm_pass, $save));
        $this->setMethod('post');
        $this->setAction($action);
    }

}
