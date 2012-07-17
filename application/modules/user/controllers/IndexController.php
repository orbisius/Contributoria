<?php

class User_IndexController extends Zend_Controller_Action {

    public function init() {
        
    }

    /**
     * Default index page
     */
    public function indexAction() {
        $page = 1;
        if ($this->_getParam('page')) {
            $page = (int) $this->_getParam('page');
        }

        $reg_board = Zend_Registry::get('reg_board');
        if ($reg_board['domain_url']) {
            $activeUsersForDomain = $this->_helper->contentitemdao->getActiveUsers(null, $reg_board['domain_url']);
            if (count($activeUsersForDomain)) {
                $pagination = Zend_Paginator::factory(count($activeUsersForDomain));
                $pagination->setCurrentPageNumber($page);
                $pagination->setItemCountPerPage(10);
                $pagination->setPageRange(7);
                $this->view->pagination = $pagination;
            } else {
                $pagination = Zend_Paginator::factory(array());
                $this->view->pagination = $pagination;
            }

            if (count($activeUsersForDomain > 10)) {
                $activeUsersForDomain = array_slice($activeUsersForDomain, ($page - 1) * 10, 10);
            }

            $loadedUsers = array();
            $user_model = new User_Model_UserMapper();
            foreach ($activeUsersForDomain as $activeUserLogin) {
                $user = $user_model->findUserOn(array('user_login = ?' => $activeUserLogin));
                $loadedUsers[] = $user;
            }
            $this->view->users = $loadedUsers;
        } else {
            $model = new User_Model_UserMapper();
            $userHashes = $model->getStream($page);

            $loadedUsers = array();
            foreach ($userHashes as $userHash) {
                $user = new User_Model_User();
                $user->setUser_login($userHash['user_login']);
                $user->setDisplay_name($userHash['display_name']);
                $user->setSmall_bio($userHash['small_bio']);
                $loadedUsers[] = $user;
            }
            $this->view->users = $loadedUsers;
            $this->view->pagination = $model->returnPagination();
        }
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
     * Users activity
     * 
     * @return type 
     */
    public function activityAction() {
        $page = (int) $this->_getParam('page');
        if (!$page) {
            $page = 1;
        }

        $session = new Zend_Session_Namespace('location');
        $reg_board = Zend_Registry::get('reg_board');

        $contentItems = $this->_helper->contentitemdao->getActivity($session->location_data, $page, null, $reg_board['domain_url']);
        if ($this->_getParam('jquery')) {
            $this->_helper->layout->disableLayout();
            $this->_helper->viewRenderer->setNoRender(true);
            echo $this->view->useractivity($contentItems);
        } else {
            $this->view->contentitems = $contentItems;
            //$this->view->rssUrl = $this->_helper->rssurlbuilder->getActivity();
            if ($reg_board['domain_url']) {
                $this->view->activeusers = $this->_helper->contentitemdao->getActiveUsers(null, $reg_board['domain_url']);
            } else {
                $this->view->activeusers = $this->_helper->contentitemdao->getActiveUsers();
            }
        }

        return;
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