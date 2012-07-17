<?php

class User_FollowController extends Zend_Controller_Action {

    public function init() {
        
    }

    public function suggestnoticeboardAction() {
        $model = new Noticeboard_Model_NoticeboardMapper();
        $apiModel = new Model_Noticeapi();

        $options = array();
        if ($this->_getParam('q')) {
            $options['q'] = $this->_getParam('q');
        }

        $noticeboards = array();
        $i = 0;

        if ($this->_getParam('name')) {
            $noticeboard_data = $model->findNoticeboardlike($this->_getParam('name'));
            foreach ($noticeboard_data as $noticeboard) {
                $noticeboards[$i]['details'] = $noticeboard;
                $noticeboards[$i]['following'] = $model->isUserFollowingNoticeboard(Zend_Auth::getInstance()->getIdentity()->user_id, $noticeboard['noticeboard_url']);
                $i++;
            }
        } else {
            $data = $apiModel->search($options);
        }

        if (isset($data) && $data['refinements']) {
            $refine_noticeboards = $data['refinements']['noticeboard'];
            arsort($refine_noticeboards);
            foreach ($refine_noticeboards as $key => $value) {
                $noticeboard = $model->findNoticeboard($key);
                if ($noticeboard) {
                    $noticeboards[$i]['details'] = $noticeboard;
                    $noticeboards[$i]['following'] = $model->isUserFollowingNoticeboard(Zend_Auth::getInstance()->getIdentity()->user_id, $noticeboard['noticeboard_url']);
                    $i++;
                }
            }
        }

        if ($noticeboards) {

            $output = array();
            $output[] = "<table class=\"table\">";
            $output[] = "<tbody>";
            foreach ($noticeboards as $noticeboard) {
                $following = $noticeboard['following'];
                $noticeboard = $noticeboard['details'];
                $output[] = "<tr>";
                $output[] = "   <td>{$this->view->noticeboardhover($noticeboard['noticeboard_url'], 'suggest_n_fllw')} {$this->view->htmlpurify($noticeboard['noticeboard_tagline'])}</p></td>";
                if ($following) {
                    $output[] = "   <td style=\"width:150px\"><a style=\"margin:0;float: right;\" href=\"{$this->view->url(array('domain' => $noticeboard['noticeboard_url'], 'act' => 'unfollow'), 'user_follow_noticeboard')}\" data-noticeboard=\"{$noticeboard['noticeboard_url']}\" class=\"btn btn-primary unfllw_n_btn minusone\"><i class=\"icon-ok icon-white\"></i> Following</a></td>";
                } else {
                    $output[] = "   <td style=\"width:150px\"><a style=\"margin:0;float: right;\" href=\"{$this->view->url(array('domain' => $noticeboard['noticeboard_url'], 'act' => 'follow'), 'user_follow_noticeboard')}\" data-noticeboard=\"{$noticeboard['noticeboard_url']}\" class=\"btn fllw_n_btn plusone\">Follow</a></td>";
                }
                $output[] = "</tr>";
            }
            $output[] = "</tbody>";
            $output[] = "</table>";

            $noticeboards = implode("\n", $output);
        }
        $this->view->noticeboards_table = $noticeboards;
    }

    public function suggestuserAction() {
        
        $api = false;
        
        $user_model = new User_Model_UserMapper();
        
        $action = $this->_getParam('section', 'most-followed');
        $page = $this->_getParam('page', 1);
        $name = $this->_getParam('name');
        
        $data = array();
        
        if($action == 'people' && $name) {
            $data = $user_model->findUserlike($page, $name, Zend_Auth::getInstance()->getIdentity()->user_login);
            $load_more_link = "<a class=\"ld_act\" style=\"text-align:center\" href=\"{$this->view->url(array('section' => 'name'), 'user_suggest_users')}?name={$name}&page=" . ($page + 1) . "\">Load more</a>";
        }
        
        if($action == 'topic' && $name) {
            
            $apiModel = new Model_Noticeapi();
            $api_data = $apiModel->search(array('q' => $name));
                        
            if (isset($api_data) && $api_data['refinements']) {
                $api = true;
                $i=0;
                foreach ($api_data['refinements']['user'] as $key => $value) {
                    $data[$i] = $user_model->findUserOn(array('user_login = ?' => $key));
                    $i++;
                }
            }
        }
        
        if($action == 'most-followed') {
            $data = $user_model->findUserSuggestions($page, 16, array(Zend_Auth::getInstance()->getIdentity()->user_id));
            $load_more_link = "<a class=\"ld_act\" style=\"text-align:center\" href=\"{$this->view->url(array('section' => 'most-followed'), 'user_suggest_users')}?page=" . ($page + 1) . "\">Load more</a>";
        }
        
        $users = array();
        
        if($data) {
            $i = 0;
            foreach ($data as $user) {
                if(!$api) {
                    $user = new User_Model_User($user);
                }
                $users[$i]['details'] = $user;
                $users[$i]['following'] = $user_model->isUserFollowingUser(Zend_Auth::getInstance()->getIdentity()->user_id, $user->getUser_id());
                $i++;
            }
            
            $output = array();
            $output[] = "<table class=\"table user_data\">";
            $output[] = "<tbody>";
            foreach ($users as $user) {
                $following = $user['following'];
                $user = $user['details'];
                $output[] = "<tr>";
                $output[] = "   <td class=\"glossy_thumbs\" style=\"width:80px\">{$this->view->profilepicture($user->getUser_login(), 'small', 1)}</td>";
                $output[] = "   <td><a title=\"{$user->getDisplay_name()}\" href=\"{$this->view->url(array('id' => $user->getUser_login()), 'user_view')}\" style=\"font-weight:bold;font-size:125%;display:block;margin:5px 0 7px;\">{$user->getUser_login()}</a> {$this->view->htmlpurify($user->getSmall_bio(), 80, 0)}</td>";
                if ($following) {
                    $output[] = "   <td style=\"width:150px\"><a style=\"margin:20px 0 0 0;float: right;\" href=\"{$this->view->url(array('user' => $user->getUser_login(), 'act' => 'unfollow'), 'user_follow_user')}\" data-user=\"{$user->getUser_login()}\" class=\"btn btn-primary unfllw_u_btn minusone\"><i class=\"icon-ok icon-white\"></i> Following</a></td>";
                } else {
                    $output[] = "   <td style=\"width:150px\"><a style=\"margin:20px 0 0 0;float: right;\" href=\"{$this->view->url(array('user' => $user->getUser_login(), 'act' => 'follow'), 'user_follow_user')}\" data-user=\"{$user->getUser_login()}\" class=\"btn fllw_u_btn plusone\">Follow</a></td>";
                }
                $output[] = "</tr>";
            }
            $output[] = "</tbody>";
            $output[] = "</table>";
            
            $output[] = $load_more_link;
            
            $users = implode("\n", $output);
        }
        
        $this->view->users_table = $users;
        
        if($action == 'most-followed') {
            $this->render('suggestusermostfollowed');
        }
        if($action == 'people') {
            $this->render('suggestusername');
        }
        if($action == 'topic') {
            $this->render('suggestusertopic');
        }
        
    }

    public function noticeboardAction() {

        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $this->clearNoticeboardFollowingCache();

        $user = Zend_Auth::getInstance()->getIdentity();

        if ($this->_getParam('domain')) {
            $model = new Noticeboard_Model_NoticeboardMapper();
            $noticeboard = $model->findNoticeboard($this->_getParam('domain'));
            if ($noticeboard) {
                $following = $model->isUserFollowingNoticeboard($user->user_id, $noticeboard['noticeboard_url']);
                if ($this->_getParam('act') == 'follow') {
                    if (!$following) {
                        $model->userFollowNoticeboard($user->user_id, $noticeboard['noticeboard_id']);

                        $admins = $model->getNoticeboardAdmins($noticeboard['noticeboard_url']);
                        if ($admins) {
                            foreach ($admins as $admin) {
                                $userModel = new User_Model_UserMapper();

                                $adminDetails = $userModel->find($admin['user_id']);

                                if ($adminDetails->getUser_id() !== $user->user_id && $userModel->findMeta($adminDetails->getUser_id(), 'notification_noticeboard')) {
                                    $subject = $user->display_name . ' (' . $user->user_login . ') follows ' . $noticeboard['noticeboard_url'] . '.n0tice.com';
                                    $message = '<p>Hi ' . $adminDetails->getDisplay_name() . ',</p><p><a href="' . Zend_Registry::get('domain_name') . $this->view->url(array('id' => $user->user_login), 'user_view') . '">' . $user->display_name . '(' . $user->user_login . ')</a> started following your noticeboard ' . $noticeboard['noticeboard_url'] . '.n0tice.com.</p>';
                                    $email_model = new Model_Email();
                                    $email_model->email($adminDetails->getUser_email(), $adminDetails->getDisplay_name(), $subject, $message);
                                }

                                $userModel->saveNotification($adminDetails->getUser_id(), $user->display_name . ' now follows ' . $noticeboard['noticeboard_url'] . '.n0tice.com', Zend_Registry::get('domain_name') . $this->view->url(array('id' => $user->user_login), 'user_view'), date("Y-m-d H:i:s", time()));
                            }
                        }

                        if ($this->_getParam('redirect')) {
                            $this->_helper->FlashMessenger(array('success' => "You are now following {$noticeboard['noticeboard_id']}."));
                            $this->_redirect($this->_getParam('redirect'));
                        }
                        echo 1;
                    }
                }
                if ($this->_getParam('act') == 'unfollow') {
                    if ($following) {
                        $model->userUnfollowNoticeboard($user->user_id, $noticeboard['noticeboard_id']);
                        if ($this->_getParam('redirect')) {
                            $this->_helper->FlashMessenger(array('info' => "You are no longer following {$noticeboard['noticeboard_id']}."));
                            $this->_redirect($this->_getParam('redirect'));
                        }
                        echo 1;
                    }
                }
            }
        }
        return;
    }

    public function userAction() {

        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $current_user = Zend_Auth::getInstance()->getIdentity();

        $this->clearUserFollowingCache();

        if ($this->_getParam('user')) {
            $model = new User_Model_UserMapper();
            $user = $model->findUserOn(array('user_login = ?' => $this->_getParam('user')));
            if ($user) {
                $following = $model->isUserFollowingUser($current_user->user_id, $user->user_id);
                if ($this->_getParam('act') == 'follow') {
                    if (!$following) {

                        $model->userFollowUser($current_user->user_id, $user->user_id);

                        $fb_token = $current_user->facebook_oauth; // FB POST
                        if ($fb_token && $model->findMeta($current_user->user_id, 'facebook_sharing')) {
                            $this->_helper->facebookpost->push($fb_token, 'follow', 'user', Zend_Registry::get('domain_name') . $this->view->url(array('id' => $user->user_login), 'user_view'));
                        }

                        if ($model->findMeta($user->user_id, 'notification_follow')) { // EMAIL notification
                            $subject = $current_user->display_name . ' (' . $current_user->user_login . ') follows you on n0tice';
                            $message = '<p>Hi ' . $user->display_name . ',</p><p><a href="' . Zend_Registry::get('domain_name') . $this->view->url(array('id' => $current_user->user_login), 'user_view') . '">' . $current_user->display_name . '(' . $current_user->user_login . ')</a> started following your posts on n0tice.com</p>';
                            $email_model = new Model_Email();
                            $email_model->email($user->user_email, $user->display_name, $subject, $message);
                        }

                        $model->saveNotification($user->user_id, $current_user->display_name . ' now follows you', Zend_Registry::get('domain_name') . $this->view->url(array('id' => $current_user->user_login), 'user_view'), date("Y-m-d H:i:s", time()));

                        if ($this->_getParam('redirect')) {
                            $this->_helper->FlashMessenger(array('success' => "You are now following {$user->user_login}."));
                            $this->_redirect($this->_getParam('redirect'));
                        }
                        echo 1;
                    }
                }
                if ($this->_getParam('act') == 'unfollow') {
                    if ($following) {
                        $model->userUnfollowUser($current_user->user_id, $user->user_id);
                        if ($this->_getParam('redirect')) {
                            $this->_helper->FlashMessenger(array('info' => "You are no longer following {$user->user_login}."));
                            $this->_redirect($this->_getParam('redirect'));
                        }
                        echo 1;
                    }
                }
            }
        }
        return;
    }

    function clearUserFollowingCache() {
        $cache = Zend_Registry::get('15minCache');
        $user_id = Zend_Auth::getInstance()->getIdentity()->user_id;
        $cache->remove('cu_follow_users_' . $user_id);
        $cache->remove('cu_follow_users_count_' . $user_id);
        $cache->remove('getFollowSuggestions' . $user_id);
    }

    function clearNoticeboardFollowingCache() {
        $cache = Zend_Registry::get('15minCache');
        $user_id = Zend_Auth::getInstance()->getIdentity()->user_id;
        $cache->remove('cu_follow_noticeboards_' . $user_id);
    }

}