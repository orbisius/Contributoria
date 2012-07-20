<?php

/**
 * 
 * Class to send emails
 * @author daniellevitt
 *
 */
class Model_Email {
    
    /**
     * 
     * Email function
     * 
     * @param type $user_id
     * @param type $subject
     * @param type $message 
     */
    public function email($user_email, $display_name, $subject, $message) {
        
        if(APPLICATION_ENV == 'production') {
            
            //$mailgun_account = 'n0ticemail';
            //$auth = 'api:key-0lqhopmitub0ixq1h-tgrh7howrbofh6';

            $options = Zend_Registry::get('general');

            $attachment = array(
                'from' => $options->name . ' <' . $options->email_noreply . '>',
                'to' => $user_email,
                'subject' => $subject,
                'text' => $message,
                'html' => $this->htmlEmail($subject, $message)
            );
            
            /*
             * 
            $opts = array(
                CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
                CURLOPT_USERPWD => $auth,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $attachment,
                CURLOPT_URL => 'https://api.mailgun.net/v2/' . $mailgun_account . '.mailgun.org/messages',
            );

            $ch = curl_init();
            curl_setopt_array($ch, $opts);

            $result = curl_exec($ch);
            curl_close($ch);
             * 
             */
            
        }
        return;
    }
    
    /**
     * 
     * HTML email
     * 
     * @param type $subject
     * @param type $message
     * @return string 
     */
    public function htmlEmail($subject, $message) {
        
        $html = new Zend_View();
        $html->setScriptPath(APPLICATION_PATH . '/modules/default/views/scripts/emails/');

        // assign valeues
        $html->assign('subject', $subject);
        $html->assign('message', $message);

        // render view
        $bodyText = $html->render('templatesimple.phtml');

        return $bodyText;
    }

}
