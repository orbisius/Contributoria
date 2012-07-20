<?php

/**
 * Model for the User Roles
 *
 * @author Dan
 * @version
 */
class Model_StaticAcl extends Zend_Acl {

    public function __construct() {

        // Define the roles
        $this->addRole(new Zend_Acl_Role('guestgroup')); // Guests
        $this->addRole(new Zend_Acl_Role('member'), 'guestgroup'); // Members
        $this->addRole(new Zend_Acl_Role('admin'), 'member', 'guestgroup'); // Admins

        $this->add(new Zend_Acl_Resource('default'))
                ->add(new Zend_Acl_Resource('default:index'), 'default')
                ->add(new Zend_Acl_Resource('default:error'), 'default')
                ->add(new Zend_Acl_Resource('default:auth'), 'default');

        $this->add(new Zend_Acl_Resource('admin'))
                ->add(new Zend_Acl_Resource('admin:index'), 'admin');
        
        $this->add(new Zend_Acl_Resource('user'))
                ->add(new Zend_Acl_Resource('user:index'), 'default')
                ->add(new Zend_Acl_Resource('user:view'), 'default')
                ->add(new Zend_Acl_Resource('user:edit'), 'default')
                ->add(new Zend_Acl_Resource('user:social'), 'default');

        // Permissions
        $this->allow(array('guestgroup'), 'default:index', array('index'));
        $this->allow(array('guestgroup'), 'default:error', array('error'));
        
        $this->allow(array('guestgroup'), 'default:auth', array('login', 'signup', 'lost', 'reset', 'confirmemail', 'resendemail', 'ajaxformvalidation'));
        
        
        $this->allow(array('member'), 'user:index', array('index'));
        $this->allow(array('member'), 'user:view', array('index'));
        $this->allow(array('member'), 'user:edit', array('index'));
        $this->allow(array('member'), 'user:social', array('index'));
        
        $this->allow(array('admin'), 'admin:index', array('index'));
    }

    // To be called in PreDispatch function (Bootstrap)
    // because this file does not know MVC structure yet, PreDispatch does
    public function setDynamicPermissions() {
        $this->addResource('checkpoint');
        $this->allow(array('member'), 'checkpoint', 'edit', new Model_Assertion());
    }

}

