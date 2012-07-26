<?php

/**
 * 
 * Uses the Wunderground API to find weather for a particular latlng
 * 
 * @author daniellevitt
 *
 */
class Helper_View_Usersettingsmenu extends Zend_View_Helper_Abstract {

    /**
     * 
     * Returns data from the Wunderground API
     * 
     * @param arr $options
     */
    public function usersettingsmenu($user_login, $tab = 'account') {
        
        $links = array();
        
        $links[] = array('code' => 'account', 'url' => $this->view->url(array('id' => $user_login, 'edit' => 'account'), 'user_edit'), 'title' => 'Account Settings');
        $links[] = array('code' => 'profile', 'url' => $this->view->url(array('id' => $user_login, 'edit' => 'profile'), 'user_edit'), 'title' => 'Profile Information');
        $links[] = array('code' => 'notifications', 'url' => $this->view->url(array('id' => $user_login), 'user_edit_notifications'), 'title' => 'Notifications');
        $links[] = array('code' => 'picture', 'url' => $this->view->url(array('id' => $user_login), 'user_edit_picture'), 'title' => 'Profile Picture');
        $links[] = array('code' => 'social', 'url' => $this->view->url(array('id' => $user_login), 'user_edit_social'), 'title' => 'Social Networks');
        $links[] = array('code' => 'invite', 'url' => $this->view->url(array('id' => $user_login, 'service' => 'email'), 'user_edit_invite'), 'title' => 'Invite Friends');
        $links[] = array('code' => 'deactivate', 'url' => $this->view->url(array('id' => $user_login), 'user_edit_deactivate'), 'title' => 'Deactivate Account');

        $output = array();
        $output[] = "<div class=\"tabs-left user-tabs span2\">";
        $output[] = "   <ul class=\"nav nav-tabs\" style=\"width:100%;margin:0\">";
        foreach ($links as $link) {
            if($tab == $link['code']) {
                $output[] = "<li class=\"active\"><a href=\"#\">{$link['title']}</a></li>";
            } else {
                $output[] = "<li><a href=\"{$link['url']}\">{$link['title']}</a></li>";
            }
        }
        $output[] = "   </ul>";
        $output[] = "</div>";

        return implode("\n", $output);
    }

}
