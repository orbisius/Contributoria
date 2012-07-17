<?php

/**
 * 
 * Class to replicate Facebook messages
 * @author daniellevitt
 *
 */
class User_Model_Messages {

    /**
     * 
     * Get an list of messages inbox
     * @param int $message_id
     * @param int $user_id
     */
    public function getInbox($user_id) {

        $db = Zend_Registry::get('db');

        $select = $db->select()
                ->from(array('r' => 'users_messages_recips'), array('r.status'))
                ->join(array('m' => 'users_messages'), 'm.message_id = r.message_id AND m.seq = r.seq', array('m.message_id', 'm.seq', 'm.created_on', 'm.created_by', 'm.body'))
                ->where('r.uid = ? ', $user_id)
                ->where('r.status in (\'A\', \'N\')')
                ->where('r.seq=(select MAX(rr.seq) FROM users_messages_recips rr WHERE rr.message_id=m.message_id AND rr.status IN (\'A\', \'N\'))')
                ->where('if (m.seq=1 AND m.created_by=?, 1=0, 1=1)', $user_id);

        $messages = $select->query()->fetchAll();

        if ($messages) {
            $i = 0;
            foreach ($messages as $message) {

                $select = $db->select()
                        ->from(array('m' => 'users_messages'), array('COUNT(*) as total'))
                        ->where('m.message_id = ? ', $message['message_id']);
                $count = $select->query()->fetchAll();

                $messages[$i]['count_thread'] = $count[0]['total'];
                $i++;
            }
            foreach ($messages as $key => $row) {
                $column1[$key] = $row['status'];
                $column2[$key] = $row['created_on'];
            }
            array_multisort($column1, SORT_DESC, $column2, SORT_DESC, $messages);
        }

        return $messages;
    }

    /**
     * 
     * Get count of unread messages
     * @param int $message_id
     * @param int $user_id
     */
    public function getUnreadInbox($user_id) {
        $db = Zend_Registry::get('db');

        $select = $db->select()
                ->from(array('r' => 'users_messages_recips'), array('r.status'))
                ->where('r.uid = ? ', $user_id)
                ->where('r.status = \'N\' ');

        $unread = $select->query()->fetchAll();

        return count($unread);
    }

    /**
     * 
     * Get an list of messages outbox
     * @param int $message_id
     * @param int $user_id
     */
    public function getOutbox($user_id) {
        $db = Zend_Registry::get('db');

        $select = $db->select()
                ->from(array('r' => 'users_messages_recips'), array('r.status'))
                ->join(array('m' => 'users_messages'), 'm.message_id = r.message_id AND m.seq = r.seq', array('m.message_id', 'm.seq', 'm.created_on', 'm.created_by', 'm.body'))
                ->where('m.created_by = ? ', $user_id)
                ->where('r.uid = ? ', $user_id)
                ->where('r.status != \'D\' ')
                ->where('m.seq=(select MAX(rr.seq) FROM users_messages_recips rr WHERE rr.message_id=m.message_id AND rr.status != \'D\' AND rr.uid= ?)', $user_id)
                ->order('m.created_on DESC');

        return $select->query()->fetchAll();
    }

    /**
     * 
     * Get an individual thread
     * @param int $message_id
     * @param int $user_id
     */
    public function getThread($message_id, $user_id) {
        $db = Zend_Registry::get('db');

        $select = $db->select()
                ->from(array('r' => 'users_messages_recips'), array('r.status'))
                ->join(array('m' => 'users_messages'), 'm.message_id = r.message_id AND m.seq = r.seq', array('m.message_id', 'm.seq', 'm.created_on', 'm.created_by', 'm.body'))
                ->join(array('u' => 'users'), 'u.user_id = m.created_by', array('u.display_name', 'u.user_login'))
                ->where('r.uid= ? ', $user_id)
                ->where('m.message_id= ? ', $message_id)
                ->where('r.status IN (\'A\', \'N\')');

        return $select->query()->fetchAll();
    }

    /**
     * 
     * Set status to read
     * @param int $message_id
     * @param int $user_id
     */
    public function updateStatus($message_id, $user_id) {
        $db = Zend_Registry::get('db');

        $data = array(
            'status' => 'A'
        );

        $where[] = "status = 'N'";
        $where[] = "message_id = " . $db->quote($message_id, 'INTEGER');
        $where[] = "uid = " . $db->quote($user_id, 'INTEGER');

        $n = $db->update('users_messages_recips', $data, $where);
    }

    /**
     * 
     * Get the people in the converstaion
     * @param int $message_id
     */
    public function messageParticipants($message_id, $user_login = '') {

        $db = Zend_Registry::get('db');
        $userModel = new User_Model_UserMapper();

        $select = $db->select()
                ->from(array('r' => 'users_messages_recips'), array('DISTINCT(r.uid) AS uid'))
                ->join(array('u' => 'users'), 'u.user_id = r.uid', array('u.display_name', 'u.user_login'))
                ->where('r.message_id= ? ', $message_id);

        $results = $select->query()->fetchAll();
                
        $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('ViewRenderer');

        $uids = array();
        foreach ($results as $result) {
            if ($user_login) {
                if ($result['user_login'] !== $user_login) {
                    $uids[] = "<a href=\"{$viewRenderer->view->url(array('id' => $result['user_login']), 'user_view')}\">{$result['user_login']}</a>";
                }
            } else {
                $uids[] = "<a href=\"{$viewRenderer->view->url(array('id' => $result['user_login']), 'user_view')}\">{$result['user_login']}</a>";
            }
        }
        
        if(!$uids) {
            return $user_login;
        }

        if (count($uids) == 1) {
            return $uids[0];
        }

        $last = array_pop($uids);
        return implode(', ', $uids) . ' and ' . $last;
    }

    public function getRecips($message_id) {
        $db = Zend_Registry::get('db');

        $select = $db->select()
                ->from(array('r' => 'users_messages_recips'), array('DISTINCT(uid) AS uid'))
                ->where('r.message_id = ? ', $message_id);

        return $select->query()->fetchAll();
    }

    public function getSeq($message_id) {
        $db = Zend_Registry::get('db');

        $select = $db->select()
                ->from(array('m' => 'users_messages'), array('MAX(seq)+1 AS seq'))
                ->where('m.message_id = ? ', $message_id);

        return $select->query()->fetchAll();
    }

}

