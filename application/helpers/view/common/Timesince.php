<?php

/**
 * 
 * Work out the time since an event in english language
 * (eg 2 minutes ago)
 * 
 * @author daniellevitt
 *
 */
class Helper_View_Common_Timesince {
    /*
     * Currently disabled because JS plugin works only when
     * a timestamp is given and not coded in server side
     * 
     */

    function timesince($timestamp) {

        if (!is_int($timestamp)) {
            $timestamp = strtotime($timestamp);
        }

        $title = date('c', $timestamp);

        /*
          $difference = time() - $timestamp;
          $periods = array("sec", "min", "hour", "day", "week", "month", "years", "decade");
          $lengths = array("60","60","24","7","4.35","12","10");

          if ($difference > 0) { // this was in the past
          $ending = "ago";
          } else { // this was in the future
          $difference = -$difference;
          $ending = "to go";
          }
          for($j = 0; $difference >= $lengths[$j]; $j++) $difference /= $lengths[$j];
          $difference = round($difference);
          if($difference != 1) $periods[$j].= "s";
          $text = "$difference $periods[$j] $ending";

          if($text == '0 secs to go') {
          $text = 'just now';
          }
         */

        $text = "<abbr class=\"timeago\" title=\"{$title}\">" . date('j F Y - G:i \B\S\T', $timestamp) . "</abbr>";

        return $text;
    }

}

