<?php

class User_Model_UserMapper {

    // Pagination rules
    public $_pagination = null;
    public $_count = 10;
    public $_pagerange = 7;
    protected $_dbTable;
    
    /**
     * Generic pagination object
     * @return type 
     */
    public function returnPagination() {
        return $this->_pagination;
    }
    
    /**
     * Returns database table object
     * @param type $table
     * @return \Zend_Db_Table 
     */
    public function getDbTable($table = 'users') {
        $user_table = new Zend_Db_Table($table);
        return $user_table;
    }

    /**
     * Can the user edit the content
     * @param type $content_owner_id
     * @return boolean 
     */
    public function canUserEditContent($content_owner_id) {
        if (!Zend_Auth::getInstance()->getIdentity() || !Zend_Auth::getInstance()->getIdentity()->user_id) {
            return false;
        }
        if (Zend_Auth::getInstance()->getIdentity()->role == 'admin') {
            return true; // Admin can edit
        }
        if ($content_owner_id && $content_owner_id == Zend_Auth::getInstance()->getIdentity()->user_id) {
            return true; // Content author can edit
        }
        return false;
    }
    

    /**
     * Save or Update user
     * 		If no user_id is passed then data is inserted in the DB accordingly
     * @param unknown_type $user
     */
    public function save(User_Model_User $user) {

        $data = array(
            'user_id' => $user->getUser_id(),
            'user_login' => $user->getUser_login(),
            'user_email' => $user->getUser_email(),
            'user_pass' => $user->getUser_pass(),
            'user_realname' => $user->getUser_realname(),
            'user_dob' => $user->getUser_dob(),
            'user_location' => $user->getUser_location(),
            'user_url' => $user->getUser_url(),
            'user_registered' => $user->getUser_registered(),
            'user_lastonline' => $user->getUser_lastonline(),
            'user_ip' => $user->getUser_ip(),
            'user_iplast' => $user->getUser_iplast(),
            'user_status' => $user->getUser_status(),
            'display_name' => $user->getDisplay_name(),
            'role' => $user->getRole(),
            'twitter_oauth' => $user->getTwitter_oauth(),
            'facebook_oauth' => $user->getFacebook_oauth()
        );

        if (null === ($user_id = $user->getUser_id())) {
            unset($data['user_id']);
            return $this->getDbTable()->insert($data);
        } else {
            Zend_Registry::get('15minCache')->remove('user' . $user_id);
            return $this->getDbTable()->update($data, array('user_id = ?' => $user_id));
        }
    }

    /**
     * Find user by User_id
     * @param unknown_type $id
     */
    public function find($id) {

        $cache_id = 'user' . $id;

        if (!$data = Zend_Registry::get('15minCache')->load($cache_id)) {
            $result = $this->getDbTable()->find($id);
            if (0 == count($result)) {
                return;
            }
            $row = $result->current();
            $row = $this->object_to_array($row); // Set object to an array

            $data = new User_Model_User($row);

            Zend_Registry::get('15minCache')->save($data, $cache_id);
        }

        return $data;
    }

    /**
     * Find the user on a particular field, 
     * 		i.e pass array('user_login = ?' => 'billgates')
     * @param unknown_type $array
     */
    public function findUserOn($array, $as_array = 0) {

        $result = $this->getDbTable()->fetchRow($array);
        if (0 == count($result)) {
            return;
        }
        $row = $this->object_to_array($result); // Set object to an array
        $entry = new User_Model_User($row);
        if ($as_array) {
            $entry = $this->object_to_array($entry);
        }
        return $entry;
    }

    /**
     * Find the user for a facebook or twitter ID
     * 
     * @param	int		$user 	The user_id
     * @param	string	$meta	The field wanted
     */
    public function findUserSocial($service, $id) {
        $params = array(
            'meta_key = ?' => $service . '_id',
            'meta_value = ?' => $id
        );
        $result = $this->getDbTable('users_meta')->fetchAll($params, null, 1);
        if (0 == count($result)) {
            return;
        }
        $row = $result->current();
        $row = $this->object_to_array($result); // Set object to an array
        // Return the user
        return $this->getDbTable()->fetchRow(array('user_id = ?' => $row[0]['user_id']));
    }

    /**
     * Find the metadata from a user
     * 
     * @param	int		$user 	The user_id
     * @param	string	$meta	The field wanted
     */
    public function findMeta($user, $meta) {

        $cache_id = str_replace(' ', '_', preg_replace("/[^a-zA-Z0-9\s]/", "", 'findMeta' . $user . $meta));

        if (!$data = Zend_Registry::get('15minCache')->load($cache_id)) {

            $params = array(
                'user_id = ?' => $user,
                'meta_key = ?' => $meta
            );
            $result = $this->getDbTable('users_meta')->fetchAll($params, null, 1); // TODO This results in many describe table / select from where round trips - why not simple return the whole meta map to the controller?
            if (0 == count($result)) {
                return;
            }
            $row = $result->current();
            $row = $this->object_to_array($result); // Set object to an array
            $data = $row[0]['meta_value'];

            Zend_Registry::get('15minCache')->save($data, $cache_id);
        }

        return $data;
    }
    
    /**
     * Returns all the metadata from a user
     * 
     * @param	int		$identifier             The identifier
     * @param	string          $identifier_type	The thing to identify on
     */
    public function getMeta($identifier, $identifier_type = 'user_id') {
        
        $db = Zend_Registry::get('db');
        
        $cache_id = str_replace(' ', '_', preg_replace("/[^a-zA-Z0-9\s]/", "", 'getMeta' . $identifier_type . $identifier));

        if (!$data = Zend_Registry::get('15minCache')->load($cache_id)) {

            $select = $db->select();
            $select->from(array('um' => 'users_meta'), array('meta_key', 'meta_value'));
            $select->join(array('u' => 'users'), 'u.user_id = um.user_id', array());
            $select->where('u.' . $identifier_type . ' = ?', $identifier);
            $rows = $select->query()->fetchAll();

            if (!$rows) {
                return;
            }

            $rows = $this->object_to_array($rows);
            $data = array();
            foreach ($rows as $row) {
                $data[$row['meta_key']] = $row['meta_value'];
            }

            Zend_Registry::get('15minCache')->save($data, $cache_id);
        }

        return $data;
    }

    /**
     * Save or update MetaData for user
     * @param int $user
     * @param key $meta
     * @param value $value
     */
    public function saveMeta($user, $meta, $value) {

        $data = array(
            'user_id' => $user,
            'meta_key' => $meta,
            'meta_value' => $value
        );
        $params = array(
            'user_id = ?' => $user,
            'meta_key = ?' => $meta
        );

        if (!$value) {
            // Just Remove
            $this->getDbTable('users_meta')->delete($params);
        } else {
            $result = $this->findMeta($user, $meta);
            if (isset($result)) {
                $this->getDbTable('users_meta')->update($data, $params);
            } else {
                $this->getDbTable('users_meta')->insert($data);
            }
        }
        
        Zend_Registry::get('15minCache')->remove(str_replace(' ', '_', preg_replace("/[^a-zA-Z0-9\s]/", "", 'findMeta' . $user . $meta)));
        Zend_Registry::get('15minCache')->remove('getMeta' . $user);
    }
    
    /**
     * Returns all the notifications from a user
     * 
     * @param	int		$user_id             The identifier
     */
    public function getNotifications($user_id) {
        
        $db = Zend_Registry::get('db');
        
        $cache = Zend_Registry::get('5minCache');
        $cache_id = 'getUserNotifications' . $user_id;

        if (!$data = $cache->load($cache_id)) {

            $select = $db->select();
            $select->from(array('un' => 'users_notifications'));
            $select->where('user_id = ?', $user_id);
            $select->order('date_created DESC');
            $select->limit(7);
            $data = $select->query()->fetchAll();

            if (!$data) {
                return;
            }
            
            $cache->save($data, $cache_id);
        }

        return $data;
    }

    /**
     * Insert a new notification
     * @param type $user_id
     * @param type $message
     * @param type $webUrl
     * @param type $date_created 
     */
    public function saveNotification($user_id, $message, $webUrl, $date_created) {
        
        $send = true;
        if(Zend_Auth::getInstance()->getIdentity()) {
            if($user_id == Zend_Auth::getInstance()->getIdentity()->user_id) {
                $send = false;
            }
        }
        
        if($send) {
            $data = array(
                'user_id' => $user_id,
                'message' => $message,
                'webUrl' => $webUrl,
                'status' => 'u',
                'date_created' => $date_created
            );
            $this->getDbTable('users_notifications')->insert($data);
            Zend_Registry::get('5minCache')->remove('getUserNotifications' . $user_id);
        }
    }
    
    /**
     * Set notifications to read
     * 
     * @param int $user
     */
    public function setUnreadNotifications($user_id, $notification_id = null) {

        $data = array(
            'status' => 'r'
        );
        $params = array(
            'user_id = ?' => (int) $user_id
        );
        if($notification_id) {
            $params['notification_id = ?'] = (int) $notification_id;
        }
        
        $this->getDbTable('users_notifications')->update($data, $params);

        Zend_Registry::get('5minCache')->remove('getUserNotifications' . $user_id);
    }

    /**
     * 
     * Function to update the users last login time
     * @param int $user_id
     */
    public function updateUserlogin($user_id) {
        $data = array(
            'user_lastonline' => date("Y-m-d H:i:s", time()),
            'user_iplast' => long2ip(ip2long($_SERVER['REMOTE_ADDR']))
        );
        $this->getDbTable()->update($data, array('user_id = ?' => $user_id));
    }
    
    /**
     * Find out if a user on a social network is registered on our site
     * @param type $service
     * @param type $ids
     * @return type 
     */
    public function findSocialRegistered($service, $ids = array()) {

        $output = array();
        if ($ids) {
            $db = Zend_Registry::get('db');
            $select = $db->select();
            $select->from(array('r' => 'users_meta'), 'meta_value');
            $select->where('meta_key = ?', $service . '_id');
            $query = array();
            foreach ($ids as $id) {
                $query[] = $db->quoteInto('meta_value = ?', $id);
            }
            $select->where(implode(' OR ', $query));
            $results = $select->query()->fetchAll();
            if ($results) {
                $output = array();
                foreach ($results as $r) {
                    $output[] = $r['meta_value'];
                }
            }
        }
        return $output;
    }
    
    /**
     * Deactivate a user
     * @param type $user_id 
     */
    public function deactivateUser($user_id) {

        if ($user_id) {
              $this->getDbTable('users')->update(array('user_status' => 'deactivated'), array('user_id = ?' => $user_id));
        }
    }
    
    /**
     * Activate a user
     * @param type $user_id 
     */
    public function activateUser($user_id) {

        if ($user_id) {
              $this->getDbTable('users')->update(array('user_status' => '0'), array('user_id = ?' => $user_id));
        }
    }

    /**
     * Convert an object to an array
     *
     * @param    object  $object The object to convert
     * @return      array
     */
    function object_to_array($object) {
        if (is_array($object) || is_object($object)) {
            $result = array();
            foreach ($object as $key => $value) {
                $result[$key] = $this->object_to_array($value);
            }
            return $result;
        }
        return $object;
    }

}

