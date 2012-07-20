<?php

class User_Form_EditProfileForm extends Zend_Form {

    public function __construct($action, $option = null) {
        parent::__construct($option);

        $this->setName('user_edit')->setAttrib('class', 'form-horizontal');

        $display_name = new Zend_Form_Element_Text('display_name');
        $display_name->setLabel('Display Name')->setAttrib('class', 'span6')
                ->setRequired()
                ->setDescription('How you want your name to be displayed across the website');
        
        $profile_basic = new Zend_Form_Element_Textarea('small_bio');
        $profile_basic->setLabel('About Me')->setAttrib('class', 'span6')
                ->setDescription('The about me field ...');
        
        $user_url = new Zend_Form_Element_Text('user_url');
        $user_url->setLabel('Website')->setAttrib('class', 'span6')
                ->setDescription('Whats your blog/website/blog address?');

        $save = new Zend_Form_Element_Submit('save');
        $save->setLabel('Save')->setAttrib('class', 'btn btn-primary btn-large');

        $this->addElements(array($display_name, $profile_basic, $user_url, $save));
        $this->setMethod('post');
        $this->setAction($action);
    }

}
