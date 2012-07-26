<?php

/**
 * 
 * Renders private messages
 * 
 * @author daniellevitt
 *
 */
class Helper_View_Usermessagethread extends Zend_View_Helper_Abstract {

    /**
     * 
     * Inbox messages
     */
    function usermessagethread($messages = '') {

        $output = array();

        if (!$messages) {
            $output[] = "<p class=\"error\">Cannot find this message.</p>";
            return implode("\n", $output);
        }

        foreach ($messages as $message) {
            $output[] = "<div class=\"status_{$message['status']}\">";
            $output[] = "   {$this->view->profilepicture($message['user_login'], 'small', 1, 'inbox_pic')}";
            $output[] = "   <div class=\"user-content message_content\">";
            $output[] = "       <h4 class=\"participants\"><a href=\"{$this->view->url(array('id' => $message['user_login']), 'user_view')}\">{$message['user_login']}</a> <small>{$this->view->timesince($message['created_on'])}</small></h4>";
            $output[] = "       " . $this->view->htmlpurify($message['body']);
            $output[] = "   </div>";
            $output[] = "</div>";
            $output[] = "<hr>";
        }

        return implode("\n", $output);
    }

}

