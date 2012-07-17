<?php

/**
 * Default entry point in the application
 *
 * @package frontend_controllers
 * @copyright company
 */
class IndexController extends App_Frontend_Controller {

    /**
     * Overrides Zend_Controller_Action::init()
     *
     * @access public
     * @return void
     */
    public function init() {
        // init the parent
        parent::init();

        $this->_addCommand(new App_Command_SendEmail());
    }

    /**
     * Controller's entry point
     *
     * @access public
     * @return void
     */
    public function indexAction() {
        App_Logger::log('test');
    }

    /**
     * Static Pages
     *
     * @return void
     */
    public function staticAction() {
        $page = $this->getRequest()->getParam('page');

        if (empty($page)) {
            throw new Exception('Invalid static page identifier');
        } else {
            $this->render($page);
        }
    }

}