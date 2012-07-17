<?php

class User_Form_MessageForm extends Zend_Form {

    public function __construct($option = null, $to = 1) {

        parent::__construct($option);

        $this->setName('message_form')->setAttrib('accept-charset', 'utf-8')->addDecorator("HtmlTag", array('tag' => "fieldset"));

        $this->addElementPrefixPath('My_Decorator', 'My/Decorator/', 'decorator');

        $uids = new Zend_Form_Element_Text('uids');
        $uids->setLabel('To:')->setDecorators(array('textinput'))->setAttrib('class', 'message_form')->setRequired();

        $message_content = new Zend_Form_Element_Textarea('body');
        $message_content->setLabel('Message:')->setDecorators(array('textinput'))->setAttrib('class', 'message_form span6')->setRequired();

        $send = new Zend_Form_Element_Submit('send');
        $send->setLabel('Send')->setDecorators(array('ViewHelper'))->addDecorator('htmlTag', array('tag' => 'div', 'class' => 'modal-footer'))->setAttrib('class', 'btn btn-primary btn-large');

        $elements = array();

        if ($to) {
            $elements[] = $uids;
        }
        $elements[] = $message_content;
        $elements[] = $send;

        $this->addElements($elements);

        $this->setMethod('post');
    }

}