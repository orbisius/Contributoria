<?php

class AuthController extends Zend_Controller_Action {

    private $_userModel = null;
    private $_requireEmailActivation = null;

    public function init() {
        $this->_requireEmailActivation = Zend_Registry::get('email_activation');
        $this->_userModel = new User_Model_UserMapper();
    }

    /**
     * Deny Access
     */
    public function noaccessAction() {

        $redirect = "";
        if ($this->_getParam('redirect')) {
            $redirect = "?redirect=" . $this->_getParam('redirect');
        }
        if (Zend_Auth::getInstance()->hasIdentity()) {
            $this->_helper->FlashMessenger(array('error' => "Sorry but you don't have access to that page."));
            $this->_redirect($this->_helper->url->url(array(), 'home'));
        } else {
            $this->_helper->FlashMessenger(array('error' => "Please log in or sign up to access that page."));
            $this->_redirect($this->_helper->url->url(array(), 'auth_login') . $redirect);
        }
    }

    /**
     * Deny Access
     */
    public function ajaxformvalidationAction() {

        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $name = $this->_getParam('form_name');
        $value = $this->_getParam('form_value');

        $output = array();

        if ($name && $value) {
            if ($name == 'user_login') {
                $output = array('code' => 2, 'msg' => 'Username available.');
                $value = strip_tags(trim($value));
                if (!preg_match('/^'.Zend_Registry::get('regex_user_login').'{1,20}$/', $value)) {
                    $output = array('code' => 1, 'msg' => 'Only alphanumerics for username please.');
                }
                $validator = new Zend_Validate_Db_RecordExists(array('table' => 'users', 'field' => 'user_login'));
                if ($validator->isValid($value)) {
                    $output = array('code' => 1, 'msg' => 'That username is already in use.');
                }
            }
            if ($name == 'user_email') {
                $output = array('code' => 2, 'msg' => 'Email available.');
                $value = strip_tags(trim($value));
                $validemail = new Zend_Validate_EmailAddress();
                $validator = new Zend_Validate_Db_RecordExists(array('table' => 'users', 'field' => 'user_email'));
                if ($validemail->isValid($value)) {
                    if ($validator->isValid($value)) {
                        $output = array('code' => 1, 'msg' => 'That email is already in use.');
                    }
                } else {
                    $output = array('code' => 1, 'msg' => 'Please enter a valid email address.');
                }
            }
        }
        echo json_encode($output);
    }

    /**
     * Deny access to logged in pages
     */
    public function loggedinredirect() {
        if (Zend_Auth::getInstance()->hasIdentity()) {
            $this->_helper->FlashMessenger(array('error' => "You are already logged in."));
            $this->_redirect($this->_helper->url->url(array(), 'home'));
        }
    }

    /**
     * Login Function
     */
    public function loginAction() {
        
        $this->view->display_intro = true;
        
        $this->loggedinredirect();

        // Catch all params
        $request = $this->getRequest();

        $login_form = new Form_LoginForm(array('action' => $this->_helper->url->url(array(), 'auth_login'), 'data-ajax' => 'false', 'class' => 'form-stacked'));
        $this->view->login_form = $login_form;

        if ($request->isPost()) {
            if ($login_form->isValid($this->_request->getPost())) {
                $form_data = $this->_request->getPost();
                $user = $this->findDetails($form_data['user_login']); // Can provide either username or email address.
                if ($user) {
                    $authAdapter = $this->getAuthAdapter();
                    $pass = new Model_Passwordhash ();
                    $pass->PasswordHash(8, true);
                    $hased_password = $pass->returnHashedPassword($form_data['user_pass'], $user->user_pass);
                    $authAdapter->setIdentity($user->user_login)->setCredential($hased_password);
                    $auths = Zend_Auth::getInstance();
                    $result = $auths->authenticate($authAdapter);
                    if ($result->isValid()) {
                        
                        if ($user->user_status == 'deactivated') {
                            $user->user_status = 0;
                            $this->_userModel->activateUser($user->user_id);
                            $this->_helper->FlashMessenger(array('info' => "Your account has been re-activated."));
                        }
                        
                        if ($user->user_status == 0) {
                            
                            if (isset($form_data['remember_me'])) { // If the user wants the session to last longer
                                //Zend_Session::rememberMe();
                            }
                            
                            $identity = $authAdapter->getResultRowObject(null, 'user_pass');
                            $auths->getStorage()->write($identity);
                            $this->_userModel->updateUserlogin($identity->user_id);
                            
                            if ($form_data['login_redirect']) {
                                $this->_redirect($form_data['login_redirect']); // Page they tried to access
                            }
                            $this->_redirect($this->_helper->url->url(array(), 'home'));
                        } else {
                            $this->view->notice = "<p>We sent a confirmation email to <strong>{$user->user_email}</strong>. Please click on the link in that message to activate your account.</p><a href=\"{$this->_helper->url->url(array('email' => $user->user_email), 'auth_resendemail')}\" class=\"button\">Resend confirmation email</a>";
                            Zend_Auth::getInstance()->clearIdentity();
                        }
                    } else {
                        $this->_helper->FlashMessenger(array('error' => "Wrong Username/Email and password combination."));
                    }
                } else {
                    $this->_helper->FlashMessenger(array('error' => "Wrong Username/Email and password combination."));
                }
                $this->_redirect($this->_helper->url->url(array(), 'auth_login'));
            }
        }
        $data = array(
            'login_redirect' => $this->_getParam('redirect')
        );
        if (isset($form_data['user_login'])) {
            $data['user_login'] = $form_data['user_login'];
        }
        $login_form->populate($data);
    }

    /**
     * Signup Function
     */
    public function signupAction() {
        
        $this->view->display_intro = true;
        
        $this->loggedinredirect();

        $form_data = array();

        $request = $this->getRequest();

        $signup_form = new Form_SignupForm(array('data-ajax' => 'false', 'class' => 'form-stacked'));
        $this->view->signup_form = $signup_form;
        
        if ($request->isPost()) {
            if ($signup_form->isValid($this->_request->getPost())) {

                $form_data = $this->_request->getPost();

                $terms = false;
                if (isset($form_data['terms'])) {
                    $terms = true;
                } else {
                    $this->_helper->FlashMessenger(array('error' => 'Please check the Terms and Conditions box before submitting your details.'));
                    return;
                }

                if ($this->validNewUser($form_data)) {

                    $time = date("Y-m-d H:i:s", time());

                    $require_activation = Zend_Registry::get('email_activation');
                    $random_email_code = 0;
                    if ($require_activation) {
                        $random_email_code = md5(rand(5, 15) . strtotime(time()));
                    }

                    // Build New User
                    $data = array(
                        'user_login' => $form_data['user_login'],
                        'user_pass' => $this->hashPassword($form_data['user_pass']),
                        'user_realname' => $form_data['user_login'],
                        'user_email' => $form_data['user_email'],
                        'user_url' => '',
                        'user_dob' => '',
                        'user_location' => '',
                        'user_registered' => $time,
                        'user_lastonline' => $time,
                        'user_ip' => long2ip(ip2long($_SERVER['REMOTE_ADDR'])),
                        'user_iplast' => long2ip(ip2long($_SERVER['REMOTE_ADDR'])),
                        'display_name' => $form_data['user_login'],
                        'role' => 'member',
                        'user_status' => $random_email_code,
                        'twitter_oauth' => '',
                        'facebook_oauth' => ''
                    );

                    // Save the new user
                    $new_user = new User_Model_User($data);
                    $user_id = $this->_userModel->save($new_user);
                    
                    $this->_userModel->saveMeta($user_id, 'notification_message', 1);
                    
                    // See if the site wants to confirm the users email

                    if ($this->_requireEmailActivation) {
                        
                        $opt_model = Zend_Registry::get('general_information');
                        
                        // Send the user activation email
                        $email_code_url = $opt_model->url . $this->_helper->url->url(array('email' => $form_data['user_email'], 'code' => $random_email_code), 'auth_confirmemail');

                        $subject = 'Just one more step to get started on ' . $opt_model->name;
                        $message = '<p>Hi ' . $form_data['user_login'] . ',</p><p>To complete the sign-up process, please follow this link:</p><p><a href="' . $email_code_url . '">' . $email_code_url . '</a></p>';
                        
                        $email_model = new Model_Email();
                        $email_model->email($form_data['user_email'], $form_data['user_login'], $subject, $message);

                        // Tell the userto check their email
                        $this->_helper->viewRenderer->setNoRender(true);
                        echo "<h2>Thank you for signing up!</h2>";
                        echo "<h4>As a final step, please go to {$form_data['user_email']} to complete the sign-up process.</h4>";
                        return;
                    } else {
                        // Get the new details
                        $user = $this->findDetails($form_data['user_login']);

                        // Proceed to log in the new guy
                        $authAdapter = $this->getAuthAdapter();

                        $pass = new Model_Passwordhash ();
                        $pass->PasswordHash(8, true);
                        $hased_password = $pass->returnHashedPassword($form_data['user_pass'], $user->user_pass);

                        $authAdapter->setIdentity($user->user_login)->setCredential($hased_password);
                        $auths = Zend_Auth::getInstance();
                        $auths->authenticate($authAdapter);

                        // Retrieve user details for storage
                        $identity = $authAdapter->getResultRowObject(null, 'user_pass');

                        // Write user details to storage
                        $auths->getStorage()->write($identity);

                        setcookie("newuser", 1, time() + 86400);

                        $this->_redirect($this->_helper->url->url(array(), 'home'));
                    }
                } else {
                    $this->_helper->FlashMessenger(array('error' => "Please check the form for errors."));
                }
            } else {
                $this->_helper->FlashMessenger(array('error' => "Please check the form for errors."));
            }
        }
        $signup_form->populate($form_data);
    }

    /**
     * Facebook integration
     */
    public function facebookAction() {

        $this->loggedinredirect();

        $options = Zend_Registry::get('facebook_config');

        if (!isset($_SESSION ['FACEBOOK_TOKEN'])) {
            if (isset($_GET['error'])) {
                $this->_helper->FlashMessenger(array('error' => "There was an error adding Facebook to your account, please try again later."));
            } else {
                if (isset($_GET['code']) && $_GET['state'] == $_SESSION['state']) {
                    unset($_SESSION['state']);
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, "https://graph.facebook.com/oauth/access_token?client_id={$options->clientid}&redirect_uri=http://{$_SERVER['HTTP_HOST']}{$this->_helper->url->url()}&client_secret={$options->secretkey}&code={$_GET['code']}");
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    $response = curl_exec($ch);
                    curl_close($ch);
                    $params = null;
                    parse_str($response, $params);
                    $_SESSION ['FACEBOOK_TOKEN'] = $params['access_token'];
                } else {
                    $_SESSION['state'] = md5(uniqid(rand(), TRUE)); //CSRF protection
                    $url = "https://www.facebook.com/dialog/oauth?client_id={$options->clientid}&redirect_uri=http://{$_SERVER['HTTP_HOST']}{$this->_helper->url->url()}&scope={$options->permissions}&state={$_SESSION['state']}";
                    header('Location: ' . $url);
                    return;
                }
            }
        }

        if ($_SESSION ['FACEBOOK_TOKEN']) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://graph.facebook.com/me?access_token=' . $_SESSION ['FACEBOOK_TOKEN']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $data = curl_exec($ch);
            curl_close($ch);
            $facebook_details = json_decode($data, true);
            if ($facebook_details['verified']) {
                if (!$this->getRequest()->isPost()) { // Already signed up?
                    $user = $this->_userModel->findUserSocial('facebook', $facebook_details['id']);
                    if ($user) {
                        unset($_SESSION ['FACEBOOK_TOKEN']);
                        $this->simulateLoginProcess($user);
                    }
                }
                $fb_form = new Form_FacebookForm(array('data-ajax' => 'false', 'class' => 'form-stacked'));
                $this->view->facebook_form = $fb_form;
                if ($this->getRequest()->isPost()) {
                    if ($fb_form->isValid($this->_request->getPost())) {
                        $form_data = $this->_request->getPost();
                        if ($this->validNewUser($form_data)) { // Get extra info
                            
                            $terms = false;
                            if (isset($form_data['terms'])) {
                                $terms = true;
                            } else {
                                $this->_helper->FlashMessenger(array('error' => 'Please check the Terms and Conditions box before submitting your details.'));
                                return;
                            }
                            
                            $data = array(
                                'user_login' => $form_data['user_login'],
                                'user_pass' => $this->hashPassword($this->createPassword()),
                                'user_realname' => $facebook_details ['name'],
                                'user_email' => $form_data['user_email'],
                                'user_url' => $facebook_details ['link'],
                                'user_dob' => '',
                                'user_location' => '',
                                'user_registered' => date("Y-m-d H:i:s", time()),
                                'user_lastonline' => date("Y-m-d H:i:s", time()),
                                'user_ip' => long2ip(ip2long($_SERVER['REMOTE_ADDR'])),
                                'user_iplast' => long2ip(ip2long($_SERVER['REMOTE_ADDR'])),
                                'display_name' => $facebook_details ['name'],
                                'role' => 'member',
                                'user_status' => 0,
                                'facebook_oauth' => $_SESSION ['FACEBOOK_TOKEN'],
                                'twitter_oauth' => '',
                            );

                            // Save the new user
                            $new_user = new User_Model_User($data);
                            $user_id = $this->_userModel->save($new_user);
                            $this->_userModel->saveMeta($user_id, 'facebook_username', $facebook_details['username']);
                            $this->_userModel->saveMeta($user_id, 'facebook_id', $facebook_details['id']);
                            
                            $this->_userModel->saveMeta($user_id, 'notification_message', 1);
                            $this->_userModel->saveMeta($user_id, 'notification_update', 1);
                            $this->_userModel->saveMeta($user_id, 'notification_interesting', 1);
                            $this->_userModel->saveMeta($user_id, 'notification_repost', 1);
                            $this->_userModel->saveMeta($user_id, 'notification_follow', 1);
                            $this->_userModel->saveMeta($user_id, 'notification_noticeboard', 1);
                            
                            setcookie("newuser", 1, time() + 86400);

                            // Add profile picture
                            $this->addProfilePicture($user_id, "https://graph.facebook.com/me/picture?type=large&access_token=" . $_SESSION ['FACEBOOK_TOKEN']);

                            unset($_SESSION ['FACEBOOK_TOKEN']);
                            $this->simulateLoginProcess($new_user);
                        }
                    }
                } else {
                    // Prepare the form
                    $data = array(
                        'user_login' => $facebook_details['username'],
                        'user_email' => $facebook_details['email']
                    );
                    $fb_form->populate($data);
                }
            } else {
                $this->_helper->FlashMessenger(array('error' => "Your Facebook account is not yet Verified."));
            }
        } else {
            $this->_helper->FlashMessenger(array('error' => "Facebook login failed."));
        }
    }

    /**
     * 
     * Twitter integration
     */
    public function twitterAction() {

        $this->loggedinredirect();

        $config = Zend_Registry::get('twitter_config');
        $options = $config->toArray();

        $options['callbackUrl'] = 'http://' . $_SERVER['HTTP_HOST'] . $this->_helper->url->url();
        $consumer = new Zend_Oauth_Consumer($options);

        if (isset($_GET['denied'])) {
            $this->_helper->FlashMessenger(array('error' => "There was an error connecting your Twitter account, please try again later."));
            $this->_redirect($this->_helper->url->url(array(), 'home'));
        }
        if (!isset($_SESSION ['TWITTER_ACCESS_TOKEN'])) {
            if (!empty($_GET) && isset($_SESSION ['REQUEST_TOKEN'])) {
                $token = $consumer->getAccessToken($_GET, unserialize($_SESSION ['REQUEST_TOKEN']));
                $_SESSION ['TWITTER_ACCESS_TOKEN'] = serialize($token);
            } else {
                $token = $consumer->getRequestToken();
                $_SESSION ['REQUEST_TOKEN'] = serialize($token);
                $consumer->redirect();
            }
        }

        if ($_SESSION ['TWITTER_ACCESS_TOKEN']) {
            $token = unserialize($_SESSION ['TWITTER_ACCESS_TOKEN']);
            if (!$this->getRequest()->isPost()) { // Already signed up?
                $user = $this->_userModel->findUserSocial('twitter', $token->user_id);
                if ($user) {
                    unset($_SESSION ['TWITTER_ACCESS_TOKEN']);
                    $this->simulateLoginProcess($user);
                }
            }
            $twitter_form = new Form_TwitterForm(array('data-ajax' => 'false', 'class' => 'form-stacked'));
            $this->view->twitter_form = $twitter_form;
            if ($this->getRequest()->isPost()) {
                if ($twitter_form->isValid($this->_request->getPost())) {
                    $form_data = $this->_request->getPost();
                    if ($this->validNewUser($form_data)) { // Get extra info
                        
                        $terms = false;
                        if (isset($form_data['terms'])) {
                            $terms = true;
                        } else {
                            $this->_helper->FlashMessenger(array('error' => 'Please check the Terms and Conditions box before submitting your details.'));
                            return;
                        }
                        
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, 'http://api.twitter.com/1/users/show.json?screen_name=' . $token->screen_name . '&include_entities=true');
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                        $data = curl_exec($ch);
                        curl_close($ch);

                        $twitter_details = json_decode($data, true);
                        $data = array(
                            'user_login' => $form_data['user_login'],
                            'user_pass' => $this->hashPassword($this->createPassword()),
                            'user_realname' => $twitter_details ['name'],
                            'user_email' => $form_data['user_email'],
                            'user_url' => $twitter_details ['url'],
                            'user_dob' => '',
                            'user_location' => '',
                            'user_registered' => date("Y-m-d H:i:s", time()),
                            'user_lastonline' => date("Y-m-d H:i:s", time()),
                            'user_ip' => long2ip(ip2long($_SERVER['REMOTE_ADDR'])),
                            'user_iplast' => long2ip(ip2long($_SERVER['REMOTE_ADDR'])),
                            'display_name' => $twitter_details ['name'],
                            'role' => 'member',
                            'user_status' => 0,
                            'facebook_oauth' => '',
                            'twitter_oauth' => $_SESSION ['TWITTER_ACCESS_TOKEN'],
                        );

                        // Save the new user
                        $new_user = new User_Model_User($data);
                        $user_id = $this->_userModel->save($new_user);
                        $this->_userModel->saveMeta($user_id, 'small_bio', $twitter_details ['description']);
                        $this->_userModel->saveMeta($user_id, 'twitter_username', $twitter_details ['screen_name']);
                        $this->_userModel->saveMeta($user_id, 'twitter_id', $token->user_id);
                        
                        $this->_userModel->saveMeta($user_id, 'notification_message', 1);
                        $this->_userModel->saveMeta($user_id, 'notification_update', 1);
                        $this->_userModel->saveMeta($user_id, 'notification_interesting', 1);
                        $this->_userModel->saveMeta($user_id, 'notification_repost', 1);
                        $this->_userModel->saveMeta($user_id, 'notification_follow', 1);
                        $this->_userModel->saveMeta($user_id, 'notification_noticeboard', 1);
                        
                        setcookie("newuser", 1, time() + 86400);

                        // Add profile picture
                        $this->addProfilePicture($user_id, "https://api.twitter.com/1/users/profile_image?screen_name={$twitter_details ['screen_name']}&size=original");

                        unset($_SESSION ['TWITTER_ACCESS_TOKEN']);
                        $this->simulateLoginProcess($new_user);
                    }
                }
            } else {
                // Prepare the form
                $data = array(
                    'user_login' => $token->screen_name
                );
                $twitter_form->populate($data);
            }
        } else {
            $this->_helper->FlashMessenger(array('error' => "Twitter login failed."));
        }
    }

    /**
     * Logout action
     */
    public function logoutAction() {
        $user_id = Zend_Auth::getInstance()->getIdentity()->user_id;
        if ($user_id) {
            $forget_location = $this->_userModel->findMeta($user_id, 'forget_location');
            Zend_Auth::getInstance()->clearIdentity();
            if ($forget_location) {
                $this->destroySessions();
            } else {
                $this->_helper->FlashMessenger(array('info' => "You have now been logged out."));
            }
        } else {
            $this->_helper->FlashMessenger(array('info' => "You were not signed in."));
        }
        $this->_redirect($this->_helper->url->url(array(), 'home'));
    }

    /**
     * forgetdata action
     */
    public function forgetdataAction() {
        $this->destroySessions();
        $this->_helper->FlashMessenger(array('info' => "Location focus cleared."));
        $this->_redirect($this->_getParam('redirect', $this->_helper->url->url(array(), 'home')));
    }

    /**
     * Confirm email Function
     */
    public function confirmemailAction() {

        $this->_helper->viewRenderer->setNoRender(true);

        $email = $this->_getParam('email');
        $code = $this->_getParam('code');

        $user = $this->findDetails($email); // Can provide either username or email address.

        if ($user->user_status) {
            if ($code == $user->user_status) {
                // Update user status to 0
                $user->user_status = 0;
                $this->_userModel->save($user);

                echo "<h2>Email Confirmed!</h2>";
                echo "<h4>Your account has now been activated, please log in using the button below.</h4>";
                echo "<a href=\"{$this->_helper->url->url(array(), 'auth_login')}\" class=\"btn btn-large\">Log In &raquo;</a>";
                return;
            }
        }
        echo "<div class=\"error\"><h2>Error</h2>There was an error I'm afraid.</div>";

        return;
    }

    /**
     * Reset function for lost passwords
     */
    public function resetAction() {

        if ($this->_getParam('success')) {
            $this->view->title = "New password saved!";
            $this->view->text = "<h4>Your new password has been saved, please log in using your new details.</h4> <a href=\"{$this->_helper->url->url(array(), 'auth_login')}\" class=\"button green\">Log In</a>";
            $this->view->reset_form = "";
            return;
        }

        $reset_form = new Form_ResetForm();

        $email = $this->_getParam('email');
        $code = $this->_getParam('code');
        $user = 0;

        $user = $this->findDetails($email);

        if ($user) {
            $db_code = $this->_userModel->findMeta($user->user_id, 'reset_password_code');

            if ($code == $db_code) {
                $form_data = array(
                    'email' => $email,
                    'code' => $code
                );
                $reset_form->populate($form_data);
                $this->view->title = "Set a new password";
                $this->view->reset_form = $reset_form;
            } else {
                $this->view->title = "Error!";
                $this->view->text = "<h4>Please check the link in the email we sent to your inbox.</h4>";
                return;
            }
        } else {
            $this->view->title = "Error!";
            $this->view->text = "<h4>Please check the link in the email we sent to your inbox.</h4>";
            return;
        }

        $request = $this->getRequest();
        if ($request->isPost()) {
            if ($reset_form->isValid($this->_request->getPost())) {

                $email = $reset_form->getValue('email');
                $code = $reset_form->getValue('code');
                $user = 0;
                if (preg_match('/@/', $email)) {
                    $user = $this->findDetails($email);
                }
                if ($user) {
                    $db_code = $this->_userModel->findMeta($user->user_id, 'reset_password_code');

                    if ($code == $db_code) {

                        if ($reset_form->getValue('user_password') == $reset_form->getValue('user_password_confirm')) {

                            $user->user_pass = $this->hashPassword($reset_form->getValue('user_password'));

                            // Save new password
                            $this->_userModel->save($user);
                            // Remove the meta data
                            $this->_userModel->saveMeta($user->user_id, 'reset_password_time', 0);
                            $this->_userModel->saveMeta($user->user_id, 'reset_password_code', 0);

                            $this->view->title = "New password saved!";
                            $this->view->text = "<h4>Your new password has been saved, please log in using your new details.</h4> <a href=\"{$this->_helper->url->url(array(), 'auth_login')}\" class=\"btn\">Log In &raquo;</a>";
                            $this->view->reset_form = "";
                        } else {
                            $this->view->text = "<div class=\"error\">Your new passwords do not match!</div>";
                            $form_data = array(
                                'email' => $email,
                                'code' => $code
                            );
                            $reset_form->populate($form_data);
                            $this->view->reset_form = $reset_form;
                        }
                    }
                }
            }
        }
    }

    /**
     * Lost function for lost passwords
     */
    public function lostAction() {

        $this->loggedinredirect();

        $request = $this->getRequest();

        $lost_form = new Form_LostForm();
        $this->view->title = "Reset your password?";
        $this->view->lost_form = $lost_form;
        $this->view->text = "<h4>If your cannot remember your password, simply enter in the email address you signed up with.</h4><hr>";

        if ($request->isPost()) {
            if ($lost_form->isValid($this->_request->getPost())) {
                $email = $lost_form->getValue('user_email');
                if (preg_match('/@/', $email)) {
                    $user = $this->findDetails($email);
                }
                if ($user) {
                    $reset_code = md5(rand(5, 15) . strtotime(time()));

                    // Create two meta data entries, one time and one hash code
                    $this->_userModel->saveMeta($user->user_id, 'reset_password_time', date("Y-m-d H:i:s", time()));
                    $this->_userModel->saveMeta($user->user_id, 'reset_password_code', $reset_code);

                    $opt_model = new Model_Options();
                    $email_code_url = $opt_model->url . $this->_helper->url->url(array('email' => $email, 'code' => $reset_code), 'auth_reset');

                    // Send the code to email
                    $subject = 'You requested a new ' . $opt_model->name . ' password';
                    $message = '<p>Hi ' . $user->user_realname . ',</p><p>You recently asked to reset your ' . $opt_model->name . ' password. To complete your request, please follow this link:</p><p><a href="' . $email_code_url . '">' . $email_code_url . '</a></p><p>If you did not request a new password, please ignore this email.</p>';
                    
                    $email_model = new Model_Email();
                    $email_model->email($user->user_email, $user->user_realname, $subject, $message);

                    $message = array();
                    $message[] = "<h4>Check your email for a message from us, it will contain a link that you should follow that will reset your password for you.</h4>";
                    $message[] = "<h4>If you have a different problem accessing your account, please email our support at <a href=\"mailto:{$opt_model->email_support}\">{$opt_model->email_support}</a>.</h4>";

                    $this->view->title = "Please respond to an email we have sent!";
                    $this->view->text = implode("\n", $message);
                    $this->view->lost_form = "";
                    return;
                }
                $this->_helper->FlashMessenger(array('error' => "There was an error I'm afraid. Please check your email."));
                return;
            }
        }
    }

    /**
     * Resend function for confirmation email
     */
    public function resendemailAction() {

        $this->loggedinredirect();

        $this->_helper->viewRenderer->setNoRender(true);

        $email = $this->_getParam('email');

        $user = $this->findDetails($email); // Can provide either username or email address.

        if ($user->user_status) {

            $opt_model = Zend_Registry::get('general_information');

            // Send the user activation email
            $email_code_url = $opt_model->url . $this->_helper->url->url(array('email' => $user->user_email, 'code' => $user->user_status), 'auth_confirmemail');

            $subject = 'Just one more step to get started on ' . $opt_model->name;
            $message = '<p>Hi ' . $user->user_realname . ',</p><p>To complete the sign-up process, please follow this link:</p><p><a href="' . $email_code_url . '">' . $email_code_url . '</a></p><p>Welcome to ' . $opt_model->name . '!</p>';
            
            $email_model = new Model_Email();
            $email_model->email($user->user_email, $user->user_realname, $subject, $message);

            // Tell the userto check their email
            $this->_helper->viewRenderer->setNoRender(true);
            echo "<h2>Confirm your email!</h2>";
            echo "<h4>Your confirmation email has been sent, please go to {$user->user_email} to complete the sign-up process.</h4>";
            return;

            echo "<h2>Email Confirmed!</h2>";
            echo "<h4>Your email has now been confirmed, please log in using the button below.</h4>";
            echo "<a href=\"{$this->_helper->url->url(array(), 'auth_login')}\" class=\"button green\">Log In</a>";
            return;
        }
        echo "<div class=\"error\"><h2>Error</h2>There was an error I'm afraid.</div>";

        return;
    }

    private function getAuthAdapter() {
        $authAdapter = new Zend_Auth_Adapter_DbTable(Zend_Registry::get("db"));
        $authAdapter->setTableName('users')->setIdentityColumn('user_login')->setCredentialColumn('user_pass');
        return $authAdapter;
    }

    /**
     * Find user details for a user using either username or email.
     * @param unknown_type $user_login
     */
    public function findDetails($user_login) {
        $param = array('user_login = ?' => $user_login);
        // If they are using email then find using email instead.
        if (preg_match('/@/', $user_login)) {
            $param = array('user_email = ?' => $user_login);
        }
        $user_details = $this->_userModel->findUserOn($param);
        return $user_details;
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

    /**
     * 
     * Login process
     * @param str $pass_presented
     * @param str $pass_original
     */
    public function simulateLoginProcess($user, $redirect = null) {

        if ($user->user_status) {
            $this->view->notice = "<p>We sent a confirmation email to <strong>{$user->user_email}</strong>. Please click on the link in that message to activate your account.</p><a href=\"{$this->_helper->url->url(array('email' => $user->user_email), 'auth_resendemail')}\" class=\"button\">Resend confirmation email</a>";
            Zend_Auth::getInstance()->clearIdentity();
            $this->_redirect($this->_helper->url->url(array(), 'home'));
        }

        $authAdapter = $this->getAuthAdapter();

        $authAdapter->setIdentity($user->user_login)->setCredential($user->user_pass);
        $auths = Zend_Auth::getInstance();
        $auths->authenticate($authAdapter);

        $identity = $authAdapter->getResultRowObject(null, 'user_pass');
        $auths->getStorage()->write($identity);
        
        //Zend_Session::rememberMe(864000);
        
        $this->_userModel->updateUserlogin($user->user_id);
        $this->_helper->FlashMessenger(array('success' => "You are now logged in"));
        if ($redirect) {
            $this->_redirect($redirect);
        }
        $this->_redirect($this->_helper->url->url(array(), 'home'));
    }

    /**
     * Validate a new user
     * @param array $data
     */
    public function validNewUser($data) {

        $valid = 1;

        // Check email doesnt already exist
        $data['user_email'] = strip_tags(trim($data['user_email']));

        $validemail = new Zend_Validate_EmailAddress();
        $validator = new Zend_Validate_Db_RecordExists(array('table' => 'users', 'field' => 'user_email'));
        if ($validemail->isValid($data['user_email'])) {
            if ($validator->isValid($data['user_email'])) {
                $this->_helper->FlashMessenger(array('error' => 'That email is already in use.'));
                $valid = 0;
            }
        } else {
            $this->_helper->FlashMessenger(array('error' => 'Please enter a valid email address.'));
            $valid = 0;
        }

        // Check username doesnt already exist
        $data['user_login'] = strip_tags(trim($data['user_login']));
        
        if (!preg_match('/^'.Zend_Registry::get('regex_user_login').'{1,20}$/', $data['user_login'])) {
            $this->_helper->FlashMessenger(array('error' => 'Only alphanumerics for username please.'));
            $valid = 0;
        }

        $validator = new Zend_Validate_Db_RecordExists(array('table' => 'users', 'field' => 'user_login'));
        if ($validator->isValid($data['user_login'])) {
            $this->_helper->FlashMessenger(array('error' => 'That username is already in use.'));
            $valid = 0;
        }

        return $valid;
    }

    /**
     * 
     * Generate a random password
     */
    public function createPassword() {
        $chars = "234567890abcdefghijkmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $i = 0;
        $password = "";
        while ($i <= 8) {
            $password .= $chars{mt_rand(0, strlen($chars))};
            $i++;
        }
        return $password;
    }

    /**
     * Downloads an image and sets it as the users profile picture
     * 
     * @param type $user_id
     * @param type $image_url 
     */
    public function addProfilePicture($user_id, $image_url) {
        if ($image_url) {
            $imagename = substr(md5(uniqid() . time()), 0, 16);
            $download_image = Zend_Registry::get('temp_uploads') . $imagename . '.jpg';

            $img = file_get_contents($image_url);

            file_put_contents($download_image, $img);
            $this->_helper->imagescalingservice->createSizedImages($download_image, 'images/profile/', $imagename);
            @unlink($download_image);
            $this->_userModel->saveMeta($user_id, 'profile_picture', $imagename);
        }
    }

    /**
     * 
     * Destroys all session data
     */
    public function destroySessions() {
        $session = new Zend_Session_Namespace('location');
        unset($session->location_data);
        unset($session->address);
        unset($session->recent_addresses);
        unset($session->lng);
        unset($session->lat);
        unset($session->user_country);
        unset($session->user_country_code);
        unset($session->locality);
    }

}