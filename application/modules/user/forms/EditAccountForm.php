<?php

class User_Form_EditAccountForm extends Twitter_Form {

    public function __construct($action, $option = null) {
        parent::__construct($option);

        $this->setName('user_edit')->setAttrib('class', 'form-horizontal');

        $user_name = new Zend_Form_Element_Text('user_login');
        $user_name->setLabel('Username')->setRequired()->setDescription('Alphanumerics only.')->setAttrib('class', 'span6');

        $user_realname = new Zend_Form_Element_Text('user_realname');
        $user_realname->setLabel('Real Name')
                ->setRequired()
                ->setDescription('Your full name please.')->setAttrib('class', 'span6');

        $user_email = new Zend_Form_Element_Text('user_email');
        $user_email->setLabel('E-mail')
                ->addValidator('EmailAddress')->addErrorMessage("Please Enter Valid Email Address")->setAttrib('class', 'span6')
                ->setDescription('The email you want associated with us.')
                ->setRequired();

        $user_pass = new Zend_Form_Element_Password('user_pass');
        $user_pass->setLabel('Password')
                ->setDescription('Leave blank if you don\'t want to change your password.')->setAttrib('class', 'span6')
                ->addValidators(array(array('stringLength', false, array(6, 20)),));

        $confirm_pass = new Zend_Form_Element_Password('confirm_pass');
        $confirm_pass->setLabel('Confirm Password')->setAttrib('class', 'span6')
                ->setDescription('Ditto above.')
                ->addValidator('Identical', false, array('token' => 'user_pass'))->addErrorMessage("Your passwords do not match!");

        $save = new Zend_Form_Element_Submit('save');
        $save->setLabel('Save')->setAttrib('class', 'btn btn-primary btn-large');

        $this->addElements(array($user_name, $user_realname, $user_email, $user_pass, $confirm_pass, $save));
        $this->setMethod('post');
        $this->setAction($action);
    }

}
