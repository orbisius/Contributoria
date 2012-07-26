<?php

/**
 * 
 * Renders a list of friends to invite
 * 
 * @author daniellevitt
 *
 */
class Helper_View_Userinvitetw extends Zend_View_Helper_Abstract {

    public function userinvitetw($friends, $registered_users) {
        
        if(!$friends) {
            return '';
        }
        
        $output = array();
        
        $friends = array_chunk($friends, (count($friends) / 2));
                
        $output[] = "  <div style=\"float: left;width: 48%;\">";
        $output[] = "      <ul class=\"sc_inv\">";
        foreach ($friends[0] as $f) {
            $link = "";
            if (!in_array($f['id'], $registered_users)) {
                $link = "<a target=\"_blank\" class=\"btn\" style=\"float:right;margin:9px 30px 0 0;\" href=\"http://twitter.com/?status=".urlencode("@{$f['screen_name']} join me on http://www.n0tice.com")."\">Invite</a>";
            }
            $link .= "<img src=\"{$f['image']}\"><span>{$f['name']}</span>";
            $output[] = "      <li>{$link}</li>";
        }
        $output[] = "      </ul>";
        $output[] = "  </div>";

        $output[] = "  <div style=\"float: left;width: 48%;\">";
        $output[] = "      <ul class=\"sc_inv\">";
        foreach ($friends[1] as $f) {
            $link = "";
            if (!in_array($f['id'], $registered_users)) {
                $link = "<a target=\"_blank\" class=\"btn\" style=\"float:right;margin:9px 30px 0 0;\" href=\"http://twitter.com/?status=".urlencode("@{$f['screen_name']} join me on http://www.n0tice.com")."\">Invite</a>";
            }
            $link .= "<img src=\"{$f['image']}\"><span>{$f['name']}</span>";
            $output[] = "      <li>{$link}</li>";
        }
        $output[] = "      </ul>";
        $output[] = "  </div>";
        
        return implode("\n", $output);
    }

}
