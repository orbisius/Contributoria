<?php

class User_IndexController extends Zend_Controller_Action {

    public function init() {
        
    }

    /**
     * Default index page
     */
    public function indexAction() {
        
    }

    /**
     * Autocomplete search function
     * 
     * Used to add new admin users
     */
    public function searchAction() {

        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $page = $this->_getParam('page', 1);
        
        $q = $this->_getParam('term');

        $model = new User_Model_UserMapper();
        $users = $model->findUserFuzzy($page, $q, Zend_Auth::getInstance()->getIdentity()->user_login);

        $output = array();
        foreach ($users as $user) {
            $output[] = $user['user_login'];
        }
        echo json_encode($output);
    }
    
    /**
     * 
     * JSON output for Jquery Autocomplete
     */
    public function personautocompleteAction() {

        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        $id = $this->_getParam('q');
        if ($id) {
            $db = Zend_Registry::get('db');
            $stmt = $db->query('select user_login, user_id from users where user_login LIKE ? ', array('%' . $id . '%'));
            $people = $stmt->fetchAll();
            $output = array();
            $i = 0;
            foreach ($people as $person) {
                $output[$i]['id'] = $person['user_id'];
                $output[$i]['name'] = $person['user_login'];
                $i++;
            }
            $output = json_encode($output);
            echo $output;
        }
    }

}