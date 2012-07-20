<?php

/**
 * 
 * Configures and builds an avatar for the user
 * 
 * @author daniellevitt
 *
 */
class Helper_View_Common_Profilepicture extends Zend_View_Helper_Abstract {

    public function profilepicture($user_login, $size = 0, $link = 1, $class = "", $avairy = "") {

        $imageFilename = $this->getImageFilename($user_login);

        if ($class) {
            $class = " class=\"{$class}\" ";
        }

        $sizes_array = array(
            'small' => 'small/',
            0 => 'medium/',
            'medium' => 'medium/',
            'large' => 'large/'
        );

        $path = $this->view->staticUrl("/images/profile/{$sizes_array[$size]}{$imageFilename}.jpg");

        if ($link === 'src') {
            return $path;
        }

        $image = "<img src=\"{$path}\"{$class} alt=\"Avatar for {$user_login}\" title=\"{$user_login}\">";

        if ($link) {
            $image = "<a href=\"{$this->view->url(array('id' => $user_login), 'user_view')}\" title=\"{$user_login}\">{$image_url}</a>";
        }

        return $image;
    }

    private function getImageFilename($user_login) {

        $cache = Zend_Registry::get('5minCache');

        $cacheId = 'profilepicture' . str_replace(' ', '_', preg_replace("/[^a-zA-Z0-9\s]/", "", $user_login));

        if (!$image = $cache->load($cacheId)) {
            $user_model = new User_Model_UserMapper();
            $data = $user_model->getMeta($user_login, 'user_login');
            $image = null;
            if(isset($data['profile_picture'])) {
                $image = $data['profile_picture'];
            }
            if (!$image) {
                $image = "default";
            }
            $cache->save($image, $cacheId);
        }
        return $image;
    }

}