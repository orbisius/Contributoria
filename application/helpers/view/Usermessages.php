<?php

/**
 * 
 * Renders a list of private messages
 * 
 * @author daniellevitt
 *
 */
class Helper_View_Usermessages extends Zend_View_Helper_Abstract {

    /**
     * 
     * Output a list of messages
     * @param arr $messages
     * @param str $term
     */
    function usermessages($messages = '', $term = 'inbox') {

        $user = Zend_Auth::getInstance()->getStorage()->read();

        $output = array();

        if (!$messages) {
            $output[] = "<p class=\"info\">No items in your {$term}</p>";
            return implode("\n", $output);
        }

        foreach ($messages as $message) {
            
            $profile_pics = str_replace('To: ', '', strip_tags($message['participants']));
            $profile_pics = explode(' ', $profile_pics.' ');
            $profile_thumb = $profile_pics[0];
            
            $class = "";
            if ($message['status'] == 'N') {
                $class = " highlight_message";
            }
            $output[] = "	<div class=\"row {$class}\">";
            $output[] = "       <div class=\"span7\">";
            $output[] = "		{$this->view->profilepicture($profile_thumb, 'small', 1, 'inbox_pic')}";
            $message_count_output = "";
            if ($message['count_thread'] > 1) {
                $message_count_output = " ({$message['count_thread']})";
                if ($message['status'] == 'A') {
                    $message_count_output = "<small>{$message_count_output}</small>";
                }
            }
            $output[] = "		<h4 class=\"participants\">{$message['participants']}{$message_count_output}</h4>";

            $css_sprite = "";
            if ($message['created_by'] == $user->user_id) {
                $css_sprite = "<span class=\"mailreplied\"></span>";
            }
            $output[] = "		<p><a href=\"{$this->view->url(array('id' => $user->user_login, 'message_id' => $message['message_id']), 'user_read')}\">" . substr(strip_tags($message['body']), 0, 180) . " ... more</a></p>";

            $output[] = "       </div>";
            $output[] = "       <div class=\"span2 timestamp\">{$this->view->timesince($message['created_on'])}</div>";
            $output[] = "	</div>";
            $output[] = "	<hr>";
        }

        return implode("\n", $output);
    }

}

