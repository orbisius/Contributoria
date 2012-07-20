<?php

class User_ViewController extends Zend_Controller_Action {

    public $_user = null;
    public $_userMetaData = null;
    public $_userModel = null;

    public function init() {
        
        $this->_userModel = new User_Model_UserMapper();
        $this->_user = $this->_userModel->findUserOn(array('user_login = ?' => $this->_getParam('id')));

        $this->view->user = $this->_user;

        $this->_userMetaData = $this->_userModel->getMeta($this->_user->user_id);

        if (isset($this->_userMetaData['small_bio'])) {
            $this->view->small_bio = $this->_userMetaData['small_bio'];
        }
        if (isset($this->_userMetaData['twitter_username'])) { // TODO Profile mapper does this as well?
            $this->view->twitter_username = $this->_userMetaData['twitter_username'];
        }

        $cache = Zend_Registry::get('5minCache');
        $cacheId = 'profilepicture' . str_replace(' ', '_', preg_replace("/[^a-zA-Z0-9\s]/", "", $this->_user->user_login));
        if (!$image = $cache->load($cacheId)) {
            $image = "default";
            if ($this->_userMetaData) {
                foreach ($this->_userMetaData as $key => $value) {
                    if ($key == 'profile_picture') {
                        $image = $value;
                    }
                }
            }
            $cache->save($image, $cacheId);
        }
        if (!isset($this->_userMetaData['small_bio'])) {
            $this->_userMetaData['small_bio'] = '';
        }
        
        /*
        $this->view->doctype(Zend_View_Helper_Doctype::XHTML1_RDFA);
        $fb_config = Zend_Registry::get('facebook_config');
        $this->view->headMeta()->setProperty('fb:app_id', $fb_config->clientid);
        $this->view->headMeta()->setProperty('og:type', 'notice-nearby:user');
        $this->view->headMeta()->setProperty('og:url', Zend_Registry::get('domain_name') . '/user/' . $this->_user->user_login);
        $this->view->headMeta()->setProperty('og:title', $this->_user->user_login);
        $this->view->headMeta()->setProperty('og:description', strip_tags($this->view->htmlpurify($this->_userMetaData['small_bio'])));
        $this->view->headMeta()->setProperty('og:image', $this->view->staticUrl("/images/profile/large/{$image}.jpg"));

        $this->view->headMeta()->setProperty('noticeapp:following', $this->view->user_following_count);
        $this->view->headMeta()->setProperty('noticeapp:followers', $this->view->user_followers_count);
         * 
         */
    }

    public function indexAction() {


        
        $form = new User_Form_MessageForm();
        $this->view->form = $form;
        if ($this->getRequest()->isPost()) {
            if ($form->isValid($this->_request->getPost())) {

                $form_data = $this->_request->getPost();

                $seq = 1;
                $uids = explode(',', $form_data['uids'] . ',');
                $uids[] = $this->_user->user_id;
                $uids[] = Zend_Auth::getInstance()->getIdentity()->user_id;
                $uids = array_unique(array_filter($uids));

                $rows = array();
                foreach ($uids as $uid) {
                    $rows[] = array('uid' => $uid);
                }

                $db = Zend_Registry::get('db');

                if (count($rows)) {
                    $data = array(
                        'seq' => $seq,
                        'created_on_ip' => $_SERVER['REMOTE_ADDR'],
                        'created_by' => Zend_Auth::getInstance()->getIdentity()->user_id,
                        'body' => $form_data['body']
                    );
                    $db->insert('users_messages', $data);
                    $message_id = $db->lastInsertId();
                    foreach ($rows as $row) {
                        $data = array(
                            'message_id' => $message_id,
                            'seq' => $seq,
                            'uid' => $row['uid']
                        );
                        $data['status'] = $row['uid'] == Zend_Auth::getInstance()->getIdentity()->user_id ? 'A' : 'N';
                        $db->insert('users_messages_recips', $data);

                        $this->_userModel = new User_Model_UserMapper();

                        if ($row['uid'] !== Zend_Auth::getInstance()->getIdentity()->user_id) {
                            $user = $this->_userModel->find($row['uid']);

                            if ($this->_userModel->findMeta($user->getUser_id(), 'notification_message') && $user->getUser_id() !== Zend_Auth::getInstance()->getIdentity()->user_id) {
                                // Send email notification about message
                                $subject = 'New private message from ' . Zend_Auth::getInstance()->getIdentity()->display_name;
                                $message = '<p>You have just received a new private message from ' . Zend_Auth::getInstance()->getIdentity()->display_name . ' (' . Zend_Auth::getInstance()->getIdentity()->user_login . '), please login to view.</p><p><a href="' . Zend_Registry::get('domain_name') . $this->view->url(array('id' => $user->getUser_login()), 'user_inbox') . '">My Messages</a>';
                                $email_model = new Model_Email();
                                $email_model->email($user->getUser_email(), $user->getDisplay_name(), $subject, $message);
                            }

                            $this->_userModel->saveNotification($user->getUser_id(), Zend_Auth::getInstance()->getIdentity()->display_name . ' (' . Zend_Auth::getInstance()->getIdentity()->user_login . ') sent you a message', Zend_Registry::get('domain_name') . $this->view->url(array('id' => $user->getUser_login()), 'user_inbox'), date("Y-m-d H:i:s", time()));
                        }
                    }

                    $this->_helper->FlashMessenger(array('success' => "Your message has been sent."));
                    $this->_redirect($this->_helper->url->url());
                } else {
                    $this->_helper->FlashMessenger(array('error' => "Please check the form for errors."));
                }
            } else {
                $this->_helper->FlashMessenger(array('error' => "Could not send the message, please check the form."));
            }
        }
    }
    
    /**
     * User notification page
     * 
     * @return type 
     */
    public function notificationsAction() {

        if ($this->_user->user_id == Zend_Auth::getInstance()->getIdentity()->user_id) {
            // Allowed to view
        } else {
            new Zend_Exception('Not allowed');
        }

        if ($this->_getParam('callback')) {

            $this->_helper->layout->disableLayout();
            $this->_helper->viewRenderer->setNoRender(true);

            if ($this->_getParam('clear') && $this->_getParam('notification_id')) {
                $this->_userModel->setUnreadNotifications($this->_user->user_id, $this->_getParam('notification_id'));
                echo $this->_getParam('callback') . '(' . json_encode('1') . ')';
                return;
            }

            if ($this->_getParam('clear') == 'all') {
                $this->_userModel->setUnreadNotifications($this->_user->user_id);
                echo $this->_getParam('callback') . '(' . json_encode('1') . ')';
                return;
            }

            $notifications = $this->_userModel->getNotifications($this->_user->user_id);
            echo $this->_getParam('callback') . '(' . json_encode($notifications) . ')';
            return;
        }

        $notifications = $this->_userModel->getNotifications($this->_user->user_id);
        $this->view->notifications = $notifications;

        $this->_userModel->setUnreadNotifications($this->_user->user_id);
    }

    /**
     * 
     * Users inbox
     * @throws Exception
     */
    public function inboxAction() {

        $page = $this->_getParam('page');

        if (!$this->view->edit_page) {
            throw new Exception('Not allowed.', 401);
        }

        $message_model = new User_Model_Messages();

        $db = Zend_Registry::get('db');

        $form = new User_Form_MessageForm();
        $this->view->form = $form;
        if ($this->getRequest()->isPost()) {
            if ($form->isValid($this->_request->getPost())) {

                $form_data = $this->_request->getPost();

                $seq = 1;
                $uids = explode(',', $form_data['uids'] . ',');
                $uids[] = Zend_Auth::getInstance()->getIdentity()->user_id;
                $uids = array_unique(array_filter($uids));

                $rows = array();
                foreach ($uids as $uid) {
                    $rows[] = array('uid' => $uid);
                }

                if (count($rows)) {
                    $data = array(
                        'seq' => $seq,
                        'created_on_ip' => $_SERVER['REMOTE_ADDR'],
                        'created_by' => $this->_user->user_id,
                        'body' => $form_data['body']
                    );
                    $db->insert('users_messages', $data);
                    $message_id = $db->lastInsertId();
                    foreach ($rows as $row) {
                        $data = array(
                            'message_id' => $message_id,
                            'seq' => $seq,
                            'uid' => $row['uid']
                        );
                        $data['status'] = $row['uid'] == Zend_Auth::getInstance()->getIdentity()->user_id ? 'A' : 'N';
                        $db->insert('users_messages_recips', $data);

                        if ($row['uid'] !== Zend_Auth::getInstance()->getIdentity()->user_id) {
                            $this->_userModel = new User_Model_UserMapper();
                            $user = $this->_userModel->find($row['uid']);

                            if ($this->_userModel->findMeta($user->getUser_id(), 'notification_message') && $user->getUser_id() !== Zend_Auth::getInstance()->getIdentity()->user_id) {
                                $subject = 'New private message from ' . Zend_Auth::getInstance()->getIdentity()->display_name;
                                $message = '<p>You have just received a new private message from ' . Zend_Auth::getInstance()->getIdentity()->display_name . ' (' . Zend_Auth::getInstance()->getIdentity()->user_login . '), please login to view.</p><p><a href="' . Zend_Registry::get('domain_name') . $this->view->url(array('id' => $user->getUser_login()), 'user_inbox') . '">My Messages</a>';
                                $email_model = new Model_Email();
                                $email_model->email($user->getUser_email(), $user->getDisplay_name(), $subject, $message);
                            }

                            $this->_userModel->saveNotification($user->getUser_id(), Zend_Auth::getInstance()->getIdentity()->display_name . ' (' . Zend_Auth::getInstance()->getIdentity()->user_login . ') sent you a message', Zend_Registry::get('domain_name') . $this->view->url(array('id' => $user->getUser_login()), 'user_inbox'), date("Y-m-d H:i:s", time()));
                        }
                    }

                    $this->_helper->FlashMessenger(array('success' => "Your message has been sent."));
                    $this->_redirect($this->_helper->url->url(array('id' => $this->_user->user_login, 'page' => $page), 'user_inbox'));
                } else {
                    $this->_helper->FlashMessenger(array('error' => "Please check the form for errors."));
                }
            } else {
                $this->_helper->FlashMessenger(array('error' => "Could not send the message, please check the form."));
            }
        }

        /* Inbox */
        $messages = $message_model->getInbox($this->_user->user_id);

        $i = 0;
        foreach ($messages as $message) {
            $messages[$i]['participants'] = $message_model->messageParticipants($message['message_id'], $this->_user->user_login);
            $i++;
        }
        $this->view->messages = $messages;

        /* Sent */
        $sent_messages = $message_model->getOutbox($this->_user->user_id);
        $i = 0;
        foreach ($sent_messages as $sent_message) {
            $sent_messages[$i]['participants'] = "To: " . $message_model->messageParticipants($sent_message['message_id'], $this->_user->user_login);
            $i++;
        }
        $this->view->sent_messages = $sent_messages;
    }

    /**
     * 
     * Read a thread
     * @throws Exception
     */
    public function readAction() {

        if (!$this->view->edit_page) {
            throw new Exception('Not allowed.', 401);
        }

        $message_id = $this->_getParam('message_id');

        $message_model = new User_Model_Messages();

        $db = Zend_Registry::get('db');

        $form = new User_Form_MessageForm(array(), 0);
        $this->view->form = $form;

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($this->_request->getPost())) {

                $form_data = $this->_request->getPost();

                /** get the recips first * */
                $rows = $message_model->getRecips($message_id);

                /** get seq # * */
                $row = $message_model->getSeq($message_id);
                $seq = $row[0]['seq'];

                if (count($rows)) {
                    $data = array(
                        'message_id' => $message_id,
                        'seq' => $seq,
                        'created_on_ip' => $_SERVER['REMOTE_ADDR'],
                        'created_by' => Zend_Auth::getInstance()->getIdentity()->user_id,
                        'body' => $form_data['body']
                    );
                    $db->insert('users_messages', $data);

                    foreach ($rows as $row) {
                        $data = array(
                            'message_id' => $message_id,
                            'seq' => $seq,
                            'uid' => $row['uid']
                        );
                        $data['status'] = $row['uid'] == Zend_Auth::getInstance()->getIdentity()->user_id ? 'A' : 'N';
                        $db->insert('users_messages_recips', $data);

                        if ($row['uid'] !== Zend_Auth::getInstance()->getIdentity()->user_id) {
                            $this->_userModel = new User_Model_UserMapper();
                            $user = $this->_userModel->find($row['uid']);

                            if ($this->_userModel->findMeta($user->getUser_id(), 'notification_message') && $user->getUser_id() !== Zend_Auth::getInstance()->getIdentity()->user_id) {
                                $subject = 'New private message from ' . Zend_Auth::getInstance()->getIdentity()->display_name;
                                $message = '<p>You have just received a new private message from ' . Zend_Auth::getInstance()->getIdentity()->display_name . ' (' . Zend_Auth::getInstance()->getIdentity()->user_login . '), please login to view.</p><p><a href="' . Zend_Registry::get('domain_name') . $this->view->url(array('id' => $user->getUser_login()), 'user_inbox') . '">My Messages</a>';
                                $email_model = new Model_Email();
                                $email_model->email($user->getUser_email(), $user->getDisplay_name(), $subject, $message);
                            }

                            $this->_userModel->saveNotification($user->getUser_id(), Zend_Auth::getInstance()->getIdentity()->display_name . ' (' . Zend_Auth::getInstance()->getIdentity()->user_login . ') sent you a message', Zend_Registry::get('domain_name') . $this->view->url(array('id' => $user->getUser_login()), 'user_inbox'), date("Y-m-d H:i:s", time()));
                        }
                    }
                    $this->_helper->FlashMessenger(array('success' => "Your message has been sent."));
                    $this->_redirect($this->_helper->url->url());
                } else {
                    $this->_helper->FlashMessenger(array('error' => "Please check the form for errors."));
                }
            } else {
                $this->_helper->FlashMessenger(array('error' => "Could not send the message, please check the form."));
            }
        }

        $this->view->messages = $message_model->getThread($message_id, $this->_user->user_id);
        $this->view->information = $message_model->messageParticipants($message_id);
        $message_model->updateStatus($message_id, $this->_user->user_id);
    }

}