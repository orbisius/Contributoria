<?php

class User_Model_User {

    protected $_user_id;
    protected $_user_login;
    protected $_user_pass;
    protected $_user_realname;
    protected $_user_email;
    protected $_user_dob;
    protected $_user_location;
    protected $_user_url;
    protected $_user_registered;
    protected $_user_lastonline;
    protected $_user_ip;
    protected $_user_iplast;
    protected $_user_status;
    protected $_display_name;
    protected $_role;
    protected $_twitter_oauth;
    protected $_facebook_oauth;
    protected $_small_bio; // TODO bio field isn't on the users table - probably should be so that the table layout matches the model. ie bio is a property of user.

    public function __construct($options = null) {
        if (is_object($options)) {
            $options = array($options);
        }
        if (is_array($options)) {
            $this->setOptions($options);
        }
    }

    public function __set($name, $value) {
        $method = 'set' . $name;
        if (('mapper' == $name) || !method_exists($this, $method)) {
            throw new Exception('Invalid user property');
        }
        $this->$method($value);
    }

    public function __get($name) {
        $method = 'get' . $name;
        if (('mapper' == $name) || !method_exists($this, $method)) {
            throw new Exception('Invalid user property');
        }
        return $this->$method();
    }

    public function setOptions(array $options) {
        $methods = get_class_methods($this);
        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (in_array($method, $methods)) {
                $this->$method($value);
            }
        }
        return $this;
    }

    /**
     * @return the $_user_id
     */
    public function getUser_id() {
        return $this->_user_id;
    }

    /**
     * @param field_type $_user_id
     */
    public function setUser_id($_user_id) {
        $this->_user_id = $_user_id;
    }

    /**
     * @return the $_user_login
     */
    public function getUser_login() {
        return $this->_user_login;
    }

    /**
     * @param field_type $_user_login
     */
    public function setUser_login($_user_login) {
        $this->_user_login = $_user_login;
    }

    /**
     * @return the $_user_email
     */
    public function getUser_email() {
        return $this->_user_email;
    }

    /**
     * @param field_type $_user_email
     */
    public function setUser_email($_user_email) {
        $this->_user_email = $_user_email;
    }

    /**
     * @return the $_user_pass
     */
    public function getUser_pass() {
        return $this->_user_pass;
    }

    /**
     * @param field_type $_user_pass
     */
    public function setUser_pass($_user_pass) {
        $this->_user_pass = $_user_pass;
    }

    /**
     * @return the $_user_realname
     */
    public function getUser_realname() {
        return $this->_user_realname;
    }

    /**
     * @param field_type $_user_realname
     */
    public function setUser_realname($_user_realname) {
        $this->_user_realname = $_user_realname;
    }

    /**
     * @return the $_user_dob
     */
    public function getUser_dob() {
        return $this->_user_dob;
    }

    /**
     * @param field_type $_user_dob
     */
    public function setUser_dob($_user_dob) {
        $this->_user_dob = $_user_dob;
    }

    /**
     * @return the $_user_location
     */
    public function getUser_location() {
        return $this->_user_location;
    }

    /**
     * @param field_type $_user_location
     */
    public function setUser_location($_user_location) {
        $this->_user_location = $_user_location;
    }

    /**
     * @return the $_user_url
     */
    public function getUser_url() {
        return $this->_user_url;
    }

    /**
     * @param field_type $_user_url
     */
    public function setUser_url($_user_url) {
        $this->_user_url = $_user_url;
    }

    /**
     * @return the $_user_registered
     */
    public function getUser_registered() {
        return $this->_user_registered;
    }

    /**
     * @param field_type $_user_registered
     */
    public function setUser_registered($_user_registered) {
        $this->_user_registered = $_user_registered;
    }

    /**
     * @return the $_user_lastonline
     */
    public function getUser_lastonline() {
        return $this->_user_lastonline;
    }

    /**
     * @param field_type $_user_lastonline
     */
    public function setUser_lastonline($_user_lastonline) {
        $this->_user_lastonline = $_user_lastonline;
    }

    /**
     * @return the $_user_status
     */
    public function getUser_status() {
        return $this->_user_status;
    }

    /**
     * @param field_type $_user_status
     */
    public function setUser_status($_user_status) {
        $this->_user_status = $_user_status;
    }

    /**
     * @return the $_display_name
     */
    public function getDisplay_name() {
        return $this->_display_name;
    }

    /**
     * @param field_type $_display_name
     */
    public function setDisplay_name($_display_name) {
        $this->_display_name = $_display_name;
    }

    /**
     * @return the $_role
     */
    public function getRole() {
        return $this->_role;
    }

    /**
     * @param field_type $_role
     */
    public function setRole($_role) {
        $this->_role = $_role;
    }

    /**
     * @return the $_twitter_oauth
     */
    public function getTwitter_oauth() {
        return $this->_twitter_oauth;
    }

    /**
     * @param field_type $_twitter_oauth
     */
    public function setTwitter_oauth($_twitter_oauth) {
        $this->_twitter_oauth = $_twitter_oauth;
    }

    /**
     * @return the $_facebook_oauth
     */
    public function getFacebook_oauth() {
        return $this->_facebook_oauth;
    }

    /**
     * @param field_type $_facebook_oauth
     */
    public function setFacebook_oauth($_facebook_oauth) {
        $this->_facebook_oauth = $_facebook_oauth;
    }

    /**
     * @return the $_user_ip
     */
    public function getUser_ip() {
        return $this->_user_ip;
    }

    /**
     * @param field_type $_user_ip
     */
    public function setUser_ip($_user_ip) {
        $this->_user_ip = $_user_ip;
    }

    /**
     * @return the $_user_iplast
     */
    public function getUser_iplast() {
        return $this->_user_iplast;
    }

    /**
     * @param field_type $_user_iplast
     */
    public function setUser_iplast($_user_iplast) {
        $this->_user_iplast = $_user_iplast;
    }

    public function getSmall_bio() {
        return $this->_small_bio;
    }

    public function setSmall_bio($_small_bio) {
        $this->_small_bio = $_small_bio;
    }

}