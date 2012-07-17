<?php

/**
 * Is the user allowed to perform certain actions?
 */
class Model_Allowed {

    /**
     * Basic function to determined if the user is allowed to perform an action or not.
     * @param int $owner_id
     */
    public function isUserAllowed($owner_id) {

        $owner_id = (int) $owner_id;

        $userRole = new Model_UserRole ();
        $resource = new Model_Resource ();
        $resource->owner_id = $owner_id;

        if (Zend_Registry::get('acl')->isAllowed($userRole, $resource, 'edit')) {
            return true;
        }
        return false;
    }

}
