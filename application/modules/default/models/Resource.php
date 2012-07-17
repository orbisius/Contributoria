<?php

class Model_Resource implements Zend_Acl_Resource_Interface {

    public $owner_id = null;
    public $resource_id = 'checkpoint';

    public function getResourceId() {
        return $this->resource_id;
    }

}