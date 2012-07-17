<?php

class User_Model_UserMapper {

    // Pagination rules
    public $_pagination = null;
    public $_count = 10;
    public $_pagerange = 7;
    protected $_dbTable;

    public function returnPagination() {
        return $this->_pagination;
    }

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
        $reg_board = Zend_Registry::get('reg_board');
        if ($reg_board['domain_url']) {
            $model = new Noticeboard_Model_NoticeboardMapper();
            $canedit = $model->editNoticeboard($reg_board['domain_url'], Zend_Auth::getInstance()->getIdentity()->user_id);
            if ($canedit) {
                return true; // Domain admins can edit
            }
        }
        return false;
    }

    public function findUserlike($page = 1, $q = "", $not = '') {

        $db = Zend_Registry::get('db');

        $select = $db->select();
        $select->from(array('u' => 'users'), array('user_id', 'user_login'));
        $where = $db->quoteInto("MATCH(u.display_name) AGAINST(?)", $q);
        $select->where($where);
        if ($not) {
            $where = $db->quoteInto("user_login != ?", $not);
            $select->where($where);
        }
        $select->limit(10, ($page - 1) * 10);
        
        $results = $select->query()->fetchAll();

        return $results;
    }
    
    public function findUserFuzzy($page = 1, $q = "", $not = '') {

        $db = Zend_Registry::get('db');

        $select = $db->select();
        $select->from(array('u' => 'users'), array('user_id', 'user_login'));
        $where = $db->quoteInto("user_login LIKE ?", '%'.$q.'%');
        $select->where($where);
        if ($not) {
            $where = $db->quoteInto("user_login != ?", $not);
            $select->where($where);
        }
        $select->limit(10, ($page - 1) * 10);
        
        $results = $select->query()->fetchAll();

        return $results;
    }
    
    public function findUserSuggestions($page = 1, $count = 16, $filter_users = array()) {

        $db = Zend_Registry::get('db');

        $select = $db->select();
        $select->from(array('u' => 'users'), array('user_id', 'user_login'));
        $select->join(array('fu' => 'follow_users'), 'fu.object_id = u.user_id', array('followers' => 'COUNT(*)'));
        $select->group('u.user_id');
        
        if ($filter_users) {
            foreach ($filter_users as $user_id) {
                $where = $db->quoteInto("u.user_id != ?", $user_id);
                $select->where($where);
            }
        }
        $select->limit($count, ($page - 1) * $count);
        $results = $select->query()->fetchAll();
        
        $output = array();
        $i = 0;
        if($results) {
            foreach ($results as $result) {
                $output[$i] = $result;
                $output[$i]['small_bio'] = $this->findMeta($result['user_id'], 'small_bio');
                $i++;
            }
        }
        
        return $output;
    }
    
    
    /**
     * Create a new user or find existing user
     * 
     * @param unknown_type $user
     */
    public function fake_user_assign($username) {
        
        $user = $this->findUserOn(array('user_login = ?' => $username));
        
        if($user) {
            return $user->getUser_id();
        }
        
        $data = array(
            'user_login' => $username,
            'user_pass' => $this->hashPassword(md5(rand(5, 15) . strtotime(time()))),
            'user_realname' => $username,
            'user_email' => 'no-user@n0tice.com',
            'user_url' => '',
            'user_dob' => '',
            'user_location' => '',
            'user_registered' => date("Y-m-d H:i:s", time()),
            'user_lastonline' => date("Y-m-d H:i:s", time()),
            'user_ip' => long2ip(ip2long($_SERVER['REMOTE_ADDR'])),
            'user_iplast' => long2ip(ip2long($_SERVER['REMOTE_ADDR'])),
            'display_name' => $username,
            'role' => 'member',
            'user_status' => '',
            'twitter_oauth' => '',
            'facebook_oauth' => ''
        );
        
        return $this->getDbTable()->insert($data);
    }
    
    /**
     * Hash the plain text pass
     * @param str $pass
     */
    public function hashPassword($user_pass) {
        // New hashed password
        $pass = new Model_Passwordhash ();
        $pass->PasswordHash(8, true);
        return $pass->HashPassword($user_pass);
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
     * Find user by ID
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

    public function getDashboardcontent($username, $op = array()) {

        $apiModel = new Model_Noticeapi();
        $data = $apiModel->dashboard($username, $op);

        $output = array();
        foreach ($data['results'] as $report) {
            $output[] = new Api_Model_ReportMapper($report);
        }

        $readModel = new Api_Model_Read();
        $this->_pagination = $readModel->executeApiPagination($data['numberFound'], $op['page']);

        return $output;
    }

    /**
     * Find generic stream
     */
    public function getStream($page = 1, $count = 0, $order = 'aplha', $since = null, $format = null) {

        $db = Zend_Registry::get('db');

        if ($order == 'time') {
            $order = 'user_registered ASC';
        }
        if ($order == 'aplha') {
            $order = 'user_login ASC';
        }

        $select = $db->select()->from(array('u' => 'users'), array('user_id', 'user_email', 'user_registered', 'user_lastonline', 'user_login', 'display_name'))->order($order);

        if ($since) {
            $select->where('u.user_lastonline < ?', $since);
        }

        if ($format == 'csv') {
            return $select->query()->fetchAll();
        }

        $pagination = Zend_Paginator::factory($select);
        $pagination->setCurrentPageNumber($page);

        if ($count) {
            $pagination->setItemCountPerPage($count);
        } else {
            $pagination->setItemCountPerPage($this->_count);
        }
        $pagination->setPageRange($this->_pagerange);
        $this->_pagination = $pagination;
        $results = array();
        foreach ($pagination as $pa) {
            $results [] = $pa;
        }

        $i = 0;
        foreach ($results as $user) {
            $results[$i]['small_bio'] = $this->findMeta($user['user_id'], 'small_bio');
            $i++;
        }

        return $results;
    }

    /**
     * Returns the users who are either following a user or their followers
     * @param str $user_id
     * @return type 
     */
    public function getUserFollow($user_id, $count = false, $type = 'following', $page = 1) {

        $db = Zend_Registry::get('db');

        $select = $db->select();

        if ($count) {
            $select->from(array('fu' => 'follow_users'), array('COUNT(*) as num'));
        } else {
            $select->from(array('fu' => 'follow_users'), array());
            $select->limit(16, ($page - 1) * 16);
        }

        if ($type == 'following') {
            $select->joinLeft(array('u' => 'users'), 'fu.object_id = u.user_id', array('user_id', 'user_login'));
            $select->where('fu.user_id = ?', $user_id);
        }

        if ($type == 'followers') {
            $select->joinLeft(array('u' => 'users'), 'fu.user_id = u.user_id', array('user_id', 'user_login'));
            $select->where('fu.object_id = ?', $user_id);
        }
        
        $select->order('fu.date_created DESC');
        
        $results = $select->query()->fetchAll();
        
        if ($count) {
            return $results[0]['num'];
        } else {
            $output = array();
            $i = 0;
            foreach ($results as $result) {
                $output[$i] = $result;
                $output[$i]['small_bio'] = $this->findMeta($result['user_id'], 'small_bio');
                $i++;
            }
            return $output;
        }
    }

    /**
     * Check if a user is following a specific user
     * @param int $current_user_id
     * @param str $user_login
     * @return boolean 
     */
    public function isUserFollowingUser($current_user_id, $user_id) {

        $db = Zend_Registry::get('db');

        $select = $db->select();
        $select->from(array('fu' => 'follow_users'));
        $select->where('fu.user_id = ?', $current_user_id);
        $select->where('fu.object_id = ?', $user_id);

        $result = $select->query()->fetchAll();

        return $result;
    }

    public function deactivateUser($user_id) {

        if ($user_id) {
            /*
              $table = new Zend_Db_Table($table);
              $this->getDbTable('reports')->update(array('status' => 'deactive'), array('status = ?' => 'live', 'user_id = ?' => $user_id));
              $this->getDbTable('events')->update(array('status' => 'deactive'), array('status = ?' => 'live', 'user_id = ?' => $user_id));
              $this->getDbTable('offers')->update(array('status' => 'deactive'), array('status = ?' => 'live', 'user_id = ?' => $user_id));
              $this->getDbTable('users')->update(array('user_status' => 'deactivated'), array('user_id = ?' => $user_id));
             * 
             */
        }
    }

    public function activateUser($user_id) {

        if ($user_id) {
            /*
              $table = new Zend_Db_Table($table);
              $this->getDbTable('reports')->update(array('status' => 'live'), array('status = ?' => 'deactive', 'user_id = ?' => $user_id));
              $this->getDbTable('events')->update(array('status' => 'live'), array('status = ?' => 'deactive', 'user_id = ?' => $user_id));
              $this->getDbTable('offers')->update(array('status' => 'live'), array('status = ?' => 'deactive', 'user_id = ?' => $user_id));
              $this->getDbTable('users')->update(array('user_status' => '0'), array('user_id = ?' => $user_id));
             * 
             */
        }
    }

    /**
     * Follow a user
     * @param int $user_id
     * @param int $follower_id
     * @return boolean 
     */
    public function userFollowUser($user_id, $follower_id) {
        $data = array(
            'user_id' => $user_id,
            'object_id' => $follower_id,
            'date_created' => date("Y-m-d H:i:s", time())
        );
        $this->getDbTable('follow_users')->insert($data);
    }

    /**
     * Unfollow a user
     * @param int $user_id
     * @param int $follower_id
     * @return boolean 
     */
    public function userUnfollowUser($user_id, $follower_id) {
        $data = array(
            'user_id = ?' => $user_id,
            'object_id = ?' => $follower_id
        );
        $this->getDbTable('follow_users')->delete($data);
    }

    /**
     * Generic fetchAll query
     * @param unknown_type $where
     * @param unknown_type $order
     * @param unknown_type $count
     * @param unknown_type $offset
     */
    public function fetchAll($where = null, $order = null, $count = null, $offset = null) {

        $resultSet = $this->getDbTable()->fetchAll($where, $order, $count, $offset);
        $entries = array();
        foreach ($resultSet as $row) {
            $row = $this->object_to_array($row); // Set object to an array
            $entry = new User_Model_User($row);
            $entries[] = $entry;
        }
        return $entries;
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

