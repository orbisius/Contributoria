<?php

class User_SocialController extends Zend_Controller_Action {

    private $_user = null;
    private $_userModel = null;

    public function init() {

        $id = $this->_getParam('id');

        $this->_userModel = new User_Model_UserMapper();
        $this->_user = $this->_userModel->findUserOn(array('user_login = ?' => $id));

        $userRole = new Model_UserRole ();
        $commentResource = new Model_Resource ();
        $commentResource->owner_id = $this->_user->user_id;
        if (!Zend_Registry::get('acl')->isAllowed($userRole, $commentResource, 'edit')) {
            new Zend_Exception('Not allowed');
        }
    }

    public function addAction() {

        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        //unset( $_SESSION ['FACEBOOK_TOKEN']);die;
        $redirect = $this->view->url(array('id' => $this->_user->user_login), 'user_edit_social');

        if ($this->_getParam('service') == 'facebook') {
            $options = Zend_Registry::get('facebook_config');
            if (!isset($_SESSION ['FACEBOOK_TOKEN'])) {
                if ($_GET['error']) {
                    $this->_helper->FlashMessenger(array('error' => "There was an error adding Facebook to your account, please try again later."));
                } else {
                    if ($_GET['code'] && $_GET['state'] == $_SESSION['state']) {
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
                    $this->_userModel->saveMeta($this->_user->user_id, 'facebook_username', $facebook_details['username']);
                    $this->_userModel->saveMeta($this->_user->user_id, 'facebook_id', $facebook_details['id']);
                    $this->_user->facebook_oauth = $_SESSION ['FACEBOOK_TOKEN'];
                    $this->_userModel->save($this->_user);
                    unset($_SESSION ['FACEBOOK_TOKEN']);
                    $this->_helper->FlashMessenger(array('success' => "Your Facebook account is now connected."));
                } else {
                    $this->_helper->FlashMessenger(array('error' => "Your Facebook account is not yet Verified."));
                }
            } else {
                $this->_helper->FlashMessenger(array('error' => "There was a problem connecting your Facebook account."));
            }
        }

        if ($this->_getParam('service') == 'twitter') {

            $config = Zend_Registry::get('twitter_config');
            $options = $config->toArray();

            $options['callbackUrl'] = 'http://' . $_SERVER['HTTP_HOST'] . $this->_helper->url->url();
            $consumer = new Zend_Oauth_Consumer($options);

            if ($_GET['denied']) {
                $this->_helper->FlashMessenger(array('error' => "There was an error connecting your Twitter account, please try again later."));
                $this->_redirect($redirect);
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
                $this->_userModel->saveMeta($this->_user->user_id, 'twitter_username', $token->screen_name);
                $this->_userModel->saveMeta($this->_user->user_id, 'twitter_id', $token->user_id);
                $this->_user->twitter_oauth = $_SESSION ['TWITTER_ACCESS_TOKEN'];
                $this->_userModel->save($this->_user);
                unset($_SESSION ['TWITTER_ACCESS_TOKEN']);
                $this->_helper->FlashMessenger(array('success' => "Your Twitter account is now connected."));
            } else {
                $this->_helper->FlashMessenger(array('error' => "There was a problem connecting your Facebook account."));
            }
        }
        $this->_redirect($redirect);
    }

    public function removeAction() {

        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        if ($this->_getParam('service') == 'facebook') {
            $this->_userModel->saveMeta($this->_user->user_id, 'facebook_username', 0);
            $this->_userModel->saveMeta($this->_user->user_id, 'facebook_id', 0);
            $this->_user->facebook_oauth = '';
            $this->_userModel->save($this->_user);
            $this->_helper->FlashMessenger(array('success' => "Your Facebook account has been disconnected."));
        }

        if ($this->_getParam('service') == 'twitter') {
            $this->_userModel->saveMeta($this->_user->user_id, 'twitter_username', 0);
            $this->_userModel->saveMeta($this->_user->user_id, 'twitter_id', 0);
            $this->_user->twitter_oauth = '';
            $this->_userModel->save($this->_user);
            $this->_helper->FlashMessenger(array('success' => "Your Twitter account has been disconnected."));
        }
        $this->_redirect($this->view->url(array('id' => $this->_user->user_login), 'user_edit_social'));
    }

    public function posttofacebookAction() {

        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        if ($this->_userModel->findMeta(Zend_Auth::getInstance()->getIdentity()->user_id, 'facebook_sharing')) {
            $this->_userModel->saveMeta(Zend_Auth::getInstance()->getIdentity()->user_id, 'facebook_sharing', 0);
            $this->_helper->FlashMessenger(array('info' => "Facebook sharing turned off."));
        } else {
            $this->_userModel->saveMeta(Zend_Auth::getInstance()->getIdentity()->user_id, 'facebook_sharing', 1);
            $this->_helper->FlashMessenger(array('info' => "Facebook sharing enabled."));
        }
        $this->_redirect($this->view->url(array('id' => $this->_user->user_login), 'user_edit_social'));
    }

    public function addpictureAction() {

        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $cache = Zend_Registry::get('5minCache');
        $cacheId = 'profilepicture' . str_replace(' ', '_', preg_replace("/[^a-zA-Z0-9\s]/", "", $this->_user->user_login));

        $temp_uploads = Zend_Registry::get('temp_uploads');
        $imagename = substr(md5(uniqid() . time()), 0, 16);
        $download_image = null;

        if ($this->_getParam('service') == 'facebook') {
            if ($this->_user->facebook_oauth) {
                $img = file_get_contents("https://graph.facebook.com/me/picture?type=large&access_token=" . $this->_user->facebook_oauth);
                $download_image = $temp_uploads . $imagename . '.jpg';
                file_put_contents($download_image, $img);
            }
        }

        if ($this->_getParam('service') == 'twitter') {
            $twitter_username = $this->_userModel->findMeta($this->_user->user_id, 'twitter_username');
            if ($twitter_username) {
                $img = file_get_contents("https://api.twitter.com/1/users/profile_image?screen_name={$twitter_username}&size=original");
                $download_image = $temp_uploads . $imagename . '.jpg';
                file_put_contents($download_image, $img);
            }
        }

        if ($download_image) {
            chmod($download_image, 0777);
            $image_saved = $this->_helper->imagescalingservice->createSizedImages($download_image, 'images/profile/', $imagename);
            if ($image_saved) {
                @unlink($download_image);
                if (!$old_imagename = $cache->load($cacheId)) {
                    $db = Zend_Registry::get("db");
                    $select = $db->select()->from(array('users'), array())->join('users_meta', 'users.user_id = users_meta.user_id', array('meta_value'))->where('meta_key = ?', 'profile_picture')->where('user_login = ?', $this->_user->user_login);
                    $old_imagename = $db->fetchOne($select);
                    $cache->save($old_imagename, $cacheId);
                }
                if ($old_imagename && $old_imagename !== 'default') {
                    $this->_helper->docroothelper->removeFile('images/profile/small/' . $old_imagename . '.jpg');
                    $this->_helper->docroothelper->removeFile('images/profile/medium/' . $old_imagename . '.jpg');
                    $this->_helper->docroothelper->removeFile('images/profile/large/' . $old_imagename . '.jpg');
                }

                $this->_userModel->saveMeta($this->_user->user_id, 'profile_picture', $imagename);
                $cache->remove($cacheId);

                $this->_helper->FlashMessenger(array('success' => "Your new picture has been saved."));
                $this->_redirect($this->view->url(array('id' => $this->_user->user_login), 'user_edit_picture'));
            } else {
                $this->_helper->FlashMessenger(array('error' => "Your profile picture could not be saved, please try again."));
            }
        }

        $this->_redirect($this->view->url(array('id' => $this->_user->user_login), 'user_edit_social'));
    }

}