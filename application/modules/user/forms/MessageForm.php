<?php

class User_Form_MessageForm extends Zend_Form {

    public function __construct($option = null, $to = 1) {

        parent::__construct($option);

        $this->setName('message_form');

        $uids = new Zend_Form_Element_Text('uids');
        $uids->setLabel('To:')->setAttrib('class', 'message_form')->setRequired();

        $message_content = new Zend_Form_Element_Textarea('body');
        $message_content->setLabel('Message:')->setAttrib('class', 'message_form span6')->setRequired();

        $send = new Zend_Form_Element_Submit('send');
        $send->setLabel('Send')->setAttrib('class', 'btn btn-primary btn-large');

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