<?php

class User_EditController extends Zend_Controller_Action {

    private $imageScalingService;

    public function init() {
        $this->imageScalingService = $this->_helper->imagescalingservice;
    }

    public function indexAction() {

        $id = $this->_getParam('id');
        $editing = $this->_getParam('edit');

        $user_model = new User_Model_UserMapper();
        if (is_numeric($id)) {
            $user = $user_model->find($id);
        } else {
            $user = $user_model->findUserOn(array('user_login = ?' => $id));
        }

        if ($user->user_id == Zend_Auth::getInstance()->getIdentity()->user_id || Zend_Auth::getInstance()->getIdentity()->role == 'admin') {
            // Allowed to edit
        } else {
            new Zend_Exception('Not allowed');
        }

        $this->view->user = $user;

        $this->view->picture = $user_model->findMeta($user->user_id, 'profile_picture');

        $form_url = $this->_helper->url->url(array('id' => $user->user_id, 'edit' => $editing), 'user_edit');

        if (empty($editing) || $editing == 'account') {

            $this->view->tab = 'account';

            $this->view->title = "Editing your account settings";
            $form = new User_Form_EditAccountForm($form_url);
            if ($this->getRequest()->isPost()) {
                if ($form->isValid($this->_getAllParams())) {

                    $form_data = $this->_request->getPost();
                    $form_data['user_id'] = $user->user_id;

                    $errors = $this->validUserAccount($form_data, $user->user_id);

                    if (!$errors) {

                        if ($form_data['user_login'] !== $user->user_login) {
                            if(Zend_Auth::getInstance()->getIdentity()->user_id == $user->user_id) {
                                $identity = Zend_Auth::getInstance()->getIdentity();
                                $identity->user_login = $form_data['user_login'];
                                $identity->user_realname = $form_data['user_realname'];
                                $identity->user_email = $form_data['user_email'];
                                $identity->user_login = $form_data['user_login'];
                            }
                        }

                        // Save ACCOUNT
                        $user->user_login = $form_data['user_login'];
                        $user->user_realname = $form_data['user_realname'];
                        $user->user_email = $form_data['user_email'];

                        if ($form_data['user_pass']) {
                            $user->user_pass = $this->hashPassword($form_data['user_pass']);
                        }
                        $new_user = new User_Model_User($user);
                        $user_model->save($user);

                        $redirect = 1;
                        $this->_helper->FlashMessenger(array('success' => "New account settings saved."));
                    } else {
                        $this->_helper->FlashMessenger(array('error' => "Please check the form for errors."));
                        $this->view->message = $errors;
                    }
                } else {
                    $this->_helper->FlashMessenger(array('error' => "Please check the form for errors."));
                }
            }

            $form_data = array(
                'user_login' => $user->user_login,
                'user_realname' => $user->user_realname,
                'user_email' => $user->user_email
            );
        }

        if ($editing == 'profile') {
            $this->view->tab = 'profile';
            $this->view->title = "Editing your profile";

            $form = new User_Form_EditProfileForm($form_url);
            if ($this->getRequest()->isPost()) {
                if ($form->isValid($this->_getAllParams())) {

                    $form_data = $this->_request->getPost();

                    // Save META
                    $user_model->saveMeta($user->user_id, 'small_bio', $form_data['small_bio']);

                    // Save ACCOUNT
                    $user->display_name = $form_data['display_name'];
                    $user->user_url = $form_data['user_url'];
                    $new_user = new User_Model_User($user);
                    $user_model->save($user);

                    $redirect = 1;
                    $this->_helper->FlashMessenger(array('success' => "New profile information saved."));
                } else {
                    $this->_helper->FlashMessenger(array('error' => "Please check the form for errors."));
                }
            }

            $small_bio = $user_model->findMeta($user->user_id, 'small_bio');

            $form_data = array(
                'small_bio' => $small_bio,
                'display_name' => $user->display_name,
                'user_url' => $user->user_url,
            );
        }

        $form->populate($form_data);
        $this->view->form = $form;
    }

    public function pictureAction() {

        $this->view->title = "Editing your profile picture";

        $id = $this->_getParam('id');

        $user_model = new User_Model_UserMapper();
        if (is_numeric($id)) {
            $user = $user_model->find($id);
        } else {
            $user = $user_model->findUserOn(array('user_login = ?' => $id));
        }

        if ($user->user_id == Zend_Auth::getInstance()->getIdentity()->user_id || Zend_Auth::getInstance()->getIdentity()->role == 'admin') {
            // Allowed to edit
        } else {
            new Zend_Exception('Not allowed');
        }

        $this->view->user = $user;
        
        $this->view->twitter = $user_model->findMeta($user->user_id, 'twitter_id');
        $this->view->facebook = $user_model->findMeta($user->user_id, 'facebook_id');
        
        $form = new Zend_Form(array('id' => "user_edit"));
        $form->setEnctype(Zend_Form::ENCTYPE_MULTIPART);

        $temp_uploads = Zend_Registry::get('temp_uploads');

        $image = new Zend_Form_Element_File('image');
        $image->setLabel('Select an image file on your computer (max 4 MB):')
                ->setDestination($temp_uploads)
                ->setRequired(true)
                ->setMaxFileSize(2097152)
                ->setDecorators(array('File', array('ViewScript', array('viewScript' => 'fileinput.phtml', 'placement' => false))))
                ->setAttrib('class', 'input-file')
                ->addValidator('Count', false, 1)
                ->addValidator('Size', false, 2097152)
                ->addValidator('Extension', false, 'jpg,jpeg,png,gif')
                ->setDescription('Maximum size of 2MB. JPG, GIF, PNG.');

        $submit = new Zend_Form_Element_Submit('save');
        $submit->setLabel('Save new picture')->setDecorators(array('ViewHelper'))->addDecorator('htmlTag', array('tag' => 'div', 'class' => 'form-actions'))->setAttrib('class', 'btn btn-primary btn-large');

        $form->addElements(array($image, $submit));

        $this->view->form = $form;

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($this->getRequest()->getParams())) {

                if (!$form->image->receive()) {
                    $this->_helper->FlashMessenger(array('error' => "Whoops, a few errors uploading file, are you sure its an image file?"));
                    return;
                }

                if ($form->image->isUploaded()) {
                    $values = $form->getValues();
                    $source = $form->image->getFileName();
                    
                    $imagename = substr(md5(uniqid() . time()), 0, 16);
                    $image_saved = $this->imageScalingService->createSizedImages($source, 'images/profile/', $imagename);
                    
                    $cache = Zend_Registry::get('5minCache');
                    $cacheId = 'profilepicture' . str_replace(' ', '_', preg_replace("/[^a-zA-Z0-9\s]/", "", $user->user_login));
                    
                    if ($image_saved) {
                        @unlink($source);

                        if (!$old_imagename = $cache->load($cacheId)) {
                            $db = Zend_Registry::get("db");
                            $select = $db->select()->from(array('users'), array())->join('users_meta', 'users.user_id = users_meta.user_id', array('meta_value'))->where(' meta_key = ? ', 'profile_picture')->where(' user_login = ? ', $user->user_login);
                            $old_imagename = $db->fetchOne($select);
                            $cache->save($old_imagename, $cacheId);
                        }
                        if ($old_imagename && $old_imagename !== 'default') {
                            $this->_helper->docroothelper->removeFile('images/profile/small/' . $old_imagename . '.jpg');
                            $this->_helper->docroothelper->removeFile('images/profile/medium/' . $old_imagename . '.jpg');
                            $this->_helper->docroothelper->removeFile('images/profile/large/' . $old_imagename . '.jpg');
                        }

                        $user_model->saveMeta($user->user_id, 'profile_picture', $imagename);
                        $cache->remove($cacheId);

                        $form->reset(); //only do this if it saved ok and you want to re-display the fresh empty form

                        $this->_helper->FlashMessenger(array('success' => "Your new picture has been saved."));
                    } else {
                        $this->_helper->FlashMessenger(array('error' => "Error saving the picture, please try again later."));
                    }
                }
            } else {
                $this->_helper->FlashMessenger(array('error' => "Could not save the picture, please check below for errors."));
            }
        }

        $this->view->picture = $user_model->findMeta($user->user_id, 'profile_picture');
    }

    public function socialAction() {

        $this->view->title = "Connect social networks";

        $id = $this->_getParam('id');

        $user_model = new User_Model_UserMapper();
        if (is_numeric($id)) {
            $user = $user_model->find($id);
        } else {
            $user = $user_model->findUserOn(array('user_login = ?' => $id));
        }

        if ($user->user_id == Zend_Auth::getInstance()->getIdentity()->user_id || Zend_Auth::getInstance()->getIdentity()->role == 'admin') {
            // Allowed to edit
        } else {
            new Zend_Exception('Not allowed');
        }
        
        $this->view->user = $user;

        $this->view->twitter = $user_model->findMeta($user->user_id, 'twitter_id');
        $this->view->facebook = $user_model->findMeta($user->user_id, 'facebook_id');
        
        $this->view->facebook_app_status = $user_model->findMeta($user->user_id, 'facebook_sharing');
    }

    public function inviteAction() {

        $this->view->title = "Invite friends to n0tice";

        $id = $this->_getParam('id');
        $page = $this->_getParam('page', 1);
        $this->view->page = $page;

        $show_per_page = 20;

        $page = $page < 1 ? 1 : $page;

        $start = ($page - 1) * ($show_per_page + 1);
        $offset = $show_per_page + 1;

        $user_model = new User_Model_UserMapper();
        if (is_numeric($id)) {
            $user = $user_model->find($id);
        } else {
            $user = $user_model->findUserOn(array('user_login = ?' => $id));
        }

        if ($user->user_id == Zend_Auth::getInstance()->getIdentity()->user_id || Zend_Auth::getInstance()->getIdentity()->role == 'admin') {
            // Allowed to edit
        } else {
            new Zend_Exception('Not allowed');
        }

        $this->view->user = $user;
        
        $cache = Zend_Registry::get('15minCache');

        if ($this->_getParam('service') == 'facebook') {
            $this->view->facebook = $user_model->findMeta($user->user_id, 'facebook_id');
            if ($this->view->facebook) {
                if ($this->_getParam('post_to_wall')) {
                    
                    $this->_helper->layout->disableLayout();
                    $this->_helper->viewRenderer->setNoRender(true);
                    
                    $url = "https://graph.facebook.com/{$this->_getParam('post_to_wall')}/feed";
                    $ch = curl_init();
                    $attachment = array(
                        'access_token' => $user->facebook_oauth,
                        'message' => "Join everyone at n0tice and see what is happening near you.",
                        'name' => "n0tice",
                        'link' => "www.n0tice.com",
                    );
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $attachment);
                    $result = curl_exec($ch);
                    curl_close($ch);
                    
                    return;
                }

                $json = $cache->load('facebookfriends' . $user->user_id);
                if (!$json) {
                    $json = file_get_contents("https://graph.facebook.com/me/friends?access_token=" . $user->facebook_oauth);
                    $cache->save($json, 'facebookfriends' . $user->user_id);
                }
                $friends = json_decode($json, true);

                $output = array();
                if ($friends['data']) {
                    $friends = array_slice($friends['data'], $start, $offset);
                    $query_ids = array();
                    foreach ($friends as $f) {
                        $output[] = array('id' => $f['id'], 'name' => $f['name'], 'image' => "https://graph.facebook.com/{$f['id']}/picture");
                        $query_ids[] = $f['id'];
                    }
                    $registered_users = $user_model->findSocialRegistered('facebook', $query_ids);
                }
                $friends = $output;
            }
            if ($page > 1) {
                $this->_helper->layout->disableLayout();
                $this->_helper->viewRenderer->setNoRender(true);
                echo $this->view->userinvitefb($friends, $registered_users);
                return;
            }
            $this->view->registered_users = $registered_users;
            $this->view->friends = $friends;
            $this->render('invite-facebook');
            return;
        }

        if ($this->_getParam('service') == 'twitter') {
            $this->view->twitter = $user_model->findMeta($user->user_id, 'twitter_id');
            if ($this->view->twitter) {

                $json = $cache->load('twitterfriends' . $user->user_id);
                if (!$json) {
                    $json = file_get_contents("http://api.twitter.com/1/friends/ids.json?user_id=" . $this->view->twitter);
                    $cache->save($json, 'twitterfriends' . $user->user_id);
                }
                $friends = json_decode($json, true);

                $output = array();
                if ($friends['ids']) {
                    $friends = array_slice($friends['ids'], $start, $offset);
                    if($friends) {
                        $query_ids = array();
                            $friends = json_decode(file_get_contents("http://api.twitter.com/1/users/lookup.json?include_entities=false&user_id=" . implode(',', $friends)), true);
                            foreach ($friends as $f) {
                                $output[] = array('id' => $f['id'], 'screen_name' => $f['screen_name'], 'name' => $f['name'], 'image' => $f['profile_image_url']);
                                $query_ids[] = $f['id'];
                            }
                            $registered_users = $user_model->findSocialRegistered('twitter', $query_ids);
                        }
                        $friends = $output;
                    }
                }
            if ($page > 1) {
                $this->_helper->layout->disableLayout();
                $this->_helper->viewRenderer->setNoRender(true);
                echo $this->view->userinvitetw($friends, $registered_users);
                return;
            }
            $this->view->registered_users = $registered_users;
            $this->view->friends = $friends;
            $this->render('invite-twitter');
            return;
        }
        
        $form = new User_Form_EmailinviteForm(null, $user->display_name);
        $this->view->form = $form;
        if ($this->getRequest()->isPost()) {
            if ($form->isValid($this->_getAllParams())) {
                $form_data = $this->_request->getPost();
                
                $emails = explode(',', $form_data['emails']);
                $emails = array_unique($emails);
                
                $valid_addresses = array();
                $invalid = array();
                
                foreach($emails as $address) {
                    $validator = new Zend_Validate_EmailAddress();
                    if ($validator->isValid(trim($address))) {
                        $valid_addresses[] = trim($address);
                    } else {
                        $invalid[] = trim($address);
                    }
                }
                $valid_addresses = array_unique($valid_addresses);
                $invalid = array_unique($invalid);
                
                if($invalid) {
                    $this->_helper->FlashMessenger(array('error' => "Email invites could not be sent to: ".  implode(', ', $invalid)));
                }
                
                if($valid_addresses) {
                    foreach($valid_addresses as $valid) {
                        $mail = new Zend_Mail();
                        $mail->setBodyText($form_data['body_message']);
                        $mail->setFrom($user->user_email, $user->display_name);
                        $mail->addTo($valid);
                        $mail->setSubject($user->display_name.' has sent you an invitation to join n0tice.com');
                        $mail->send();
                    }
                    $this->_helper->FlashMessenger(array('success' => "Email invites have been sent to: ".  implode(', ', $valid_addresses)));
                }                
                $this->_redirect($this->view->url());
            }
        }
        
    }
    
    public function notificationsAction() {
                
        $this->view->title = "Notifications";

        $id = $this->_getParam('id');

        $user_model = new User_Model_UserMapper();
        if (is_numeric($id)) {
            $user = $user_model->find($id);
        } else {
            $user = $user_model->findUserOn(array('user_login = ?' => $id));
        }

        if ($user->user_id == Zend_Auth::getInstance()->getIdentity()->user_id || Zend_Auth::getInstance()->getIdentity()->role == 'admin') {
            // Allowed to edit
        } else {
            new Zend_Exception('Not allowed');
        }
        
        $this->view->user = $user;
        
        $form = new User_Form_EditNotificationsForm();
        if ($this->getRequest()->isPost()) {
            if ($form->isValid($this->_getAllParams())) {

                $form_data = $this->_request->getPost();
                                
                // Save META
                if(isset($form_data['notification_message'])) {
                    $user_model->saveMeta($user->user_id, 'notification_message', 1);
                } else {
                    $user_model->saveMeta($user->user_id, 'notification_message', 0);
                }
                if(isset($form_data['notification_update'])) {
                    $user_model->saveMeta($user->user_id, 'notification_update', 1);
                } else {
                    $user_model->saveMeta($user->user_id, 'notification_update', 0);
                }
                if(isset($form_data['notification_interesting'])) {
                    $user_model->saveMeta($user->user_id, 'notification_interesting', 1);
                } else {
                    $user_model->saveMeta($user->user_id, 'notification_interesting', 0);
                }
                if(isset($form_data['notification_repost'])) {
                    $user_model->saveMeta($user->user_id, 'notification_repost', 1);
                } else {
                    $user_model->saveMeta($user->user_id, 'notification_repost', 0);
                }
                if(isset($form_data['notification_follow'])) {
                    $user_model->saveMeta($user->user_id, 'notification_follow', 1);
                } else {
                    $user_model->saveMeta($user->user_id, 'notification_follow', 0);
                }
                if(isset($form_data['notification_noticeboard'])) {
                    $user_model->saveMeta($user->user_id, 'notification_noticeboard', 1);
                } else {
                    $user_model->saveMeta($user->user_id, 'notification_noticeboard', 0);
                }
                
                $this->_helper->FlashMessenger(array('success' => "New profile information saved."));
                
                $this->_redirect($this->view->url());
            } else {
                $this->_helper->FlashMessenger(array('error' => "Please check the form for errors."));
            }
        }
                
        $form_data = array(
            'notification_message' => $user_model->findMeta($user->user_id, 'notification_message'),
            'notification_update' => $user_model->findMeta($user->user_id, 'notification_update'),
            'notification_interesting' => $user_model->findMeta($user->user_id, 'notification_interesting'),
            'notification_repost' => $user_model->findMeta($user->user_id, 'notification_repost'),
            'notification_follow' => $user_model->findMeta($user->user_id, 'notification_follow'),
            'notification_noticeboard' => $user_model->findMeta($user->user_id, 'notification_noticeboard')
        );

        $form->populate($form_data);
        $this->view->form = $form;
        
    }
    
    public function deactivateAction() {
        
        $session = new Zend_Session_Namespace('deactivate_code');
        
        $id = $this->_getParam('id');

        $user_model = new User_Model_UserMapper();
        if (is_numeric($id)) {
            $user = $user_model->find($id);
        } else {
            $user = $user_model->findUserOn(array('user_login = ?' => $id));
        }

        if ($user->user_id == Zend_Auth::getInstance()->getIdentity()->user_id || Zend_Auth::getInstance()->getIdentity()->role == 'admin') {
            // Allowed to edit
        } else {
            new Zend_Exception('Not allowed');
        }
        
        $this->view->user = $user;
        
        if($this->_getParam('deactivate_confirmation') && $session->code && $this->_getParam('deactivate_confirmation') == $session->code) {
            $user_model->deactivateUser($user->user_id);
            Zend_Auth::getInstance()->clearIdentity();
            $this->_helper->FlashMessenger(array('info' => "Sorry to see you go!."));
            $this->_redirect('/');
        } else {
            $deactivate_code = md5(rand(5, 15) . strtotime(time()));
            $session->code = $deactivate_code;
            
            $this->view->deactivate_code = $session->code;
        }
        
    }

    /**
     * 
     * Function to validate a user
     * @param arr $data
     * @param int $user_id
     */
    public function validUserAccount($data, $user_id) {

        $errors = false;

        // Check email doesnt already exist
        if ($data['user_email']) {
            $data['user_email'] = strip_tags(trim($data['user_email']));
            $validemail = new Zend_Validate_EmailAddress();
            $validator = new Zend_Validate_Db_NoRecordExists(array('table' => 'users', 'field' => 'user_email', 'exclude' => array('field' => 'user_id', 'value' => $user_id)));
            if ($validemail->isValid($data['user_email'])) {
                if ($validator->isValid($data['user_email']) && $validemail->isValid($data['user_email'])) {
                    // Email is fine
                } else {
                    $this->_helper->FlashMessenger(array('error' => 'That email is already in the list!'));
                    $errors = true;
                }
            } else {
                $this->_helper->FlashMessenger(array('error' => 'Please enter a valid email address.'));
                $errors = true;
            }
        }

        // Check username doesnt already exist
        if ($data['user_login']) {
            $data['user_login'] = strip_tags(trim($data['user_login']));
            if (!preg_match('/^[a-zA-Z0-9_]{1,20}$/', $data['user_login'])) {
                $this->_helper->FlashMessenger(array('error' => 'Only alphanumerics or an underscore for usernames please.'));
                $errors = true;
            } else {
                $validator = new Zend_Validate_Db_NoRecordExists(array('table' => 'users', 'field' => 'user_login', 'exclude' => array('field' => 'user_id', 'value' => $user_id)));
                if ($validator->isValid($data['user_login'])) {
                    // Email is fine
                } else {
                    $this->_helper->FlashMessenger(array('error' => 'That username is already taken sorry!'));
                    $errors = true;
                }
            }
        }
        
        return $errors;
    }

    /**
     * Hash the plain text pass
     * @param str $pass
     */
    public function hashPassword($user_pass) {
        // New hashed password
        $pass = new Model_Passwordhash ();
        $pass->PasswordHash(8, true);
        return $pass->HashPassword($user_pass);
    }

}