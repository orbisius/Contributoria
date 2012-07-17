<?php

class User_Form_EmailinviteForm extends Zend_Form {

    public function __construct($option = null, $user = null) {

        parent::__construct($option);

        $this->setName('invite_form')->setAttrib('accept-charset', 'utf-8')->addDecorator("HtmlTag", array('tag' => "fieldset"));

        $this->addElementPrefixPath('My_Decorator', 'My/Decorator/', 'decorator');

        $emails = new Zend_Form_Element_Text('emails');
        $emails->setLabel('Emails:')->setDecorators(array('textinput'))->setAttrib('placeholder', 'Email addresses separated by commas')->setAttrib('class', 'span8')->setRequired();

        $message_content = new Zend_Form_Element_Textarea('body_message');
        $message_content->setLabel('Message:')->setDecorators(array('textinput'))->setAttrib('class', 'span8')->setValue('Signup and join me at n0tice.com for news, events and happenings going on around you.')->setRequired();

        $send = new Zend_Form_Element_Submit('send');
        $send->setLabel('Send')->setDecorators(array('ViewHelper'))->addDecorator('htmlTag', array('tag' => 'div', 'class' => 'action-footer'))->setAttrib('class', 'btn btn-primary btn-large');

        $this->addElements(array($emails, $message_content, $send));

        $this->setMethod('post');
    }

}