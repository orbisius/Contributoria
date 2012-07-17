<?php

class Model_UserRole implements Zend_Acl_Role_Interface {

    public $role = null;
    public $user_id = null;

    public function __construct() {

        if (Zend_Auth::getInstance()->getIdentity()) {
            $this->user_id = Zend_Auth::getInstance()->getIdentity()->user_id;
            $this->role = Zend_Auth::getInstance()->getIdentity()->role;
            if ($this->role == '') {
                $this->role = 'guestgroup';
            }
        } else {
            $this->user_id = 0;
            $this->role = 'guestgroup';
        }
    }

    public function getRoleId() {
        return $this->role;
    }

}