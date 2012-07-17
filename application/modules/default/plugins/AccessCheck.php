<?php

class Plugin_AccessCheck extends Zend_Controller_Plugin_Abstract {

    private $_acl = null;

    public function __construct(Zend_Acl $acl) {
        $this->_acl = $acl;
    }

    public function preDispatch(Zend_Controller_Request_Abstract $request) {

        $module = $request->getModuleName();
        $resource = $request->getControllerName();
        $action = $request->getActionName();

        if (!$this->_acl->isAllowed(Zend_Registry::get('role'), $module . ':' . $resource, $action)) {
            // Login and not signup (BETA)
            $request->setModuleName('default')->setControllerName('auth')->setActionName('login')->setParam('redirect', $this->getRequest()->getRequestUri());
        }
        if (!$this->_acl->has('checkpoint')) {
            $this->_acl->setDynamicPermissions();
        }
    }

}
