<?php

/**
 * Class to prefix images with a static url.
 * If an s3 bucket is configured, the return value will be a s3 url.
 * Optionally a filepath can be past in for conversion into a full url.
 */
class Helper_View_Common_StaticUrl {

    private $_path = null;
    private $_bucket = null;

    public function staticUrl($filepath = null) {
        if (!isset($this->_path)) {

            $s3_config = Zend_Registry::get('s3bucket');
            $this->_path = Zend_Registry::get('static_path');
            $this->_bucket = $s3_config->bucket;
        }

        $prefix = $this->_path;
        if ($this->isS3Enabled()) {
            $prefix = 'http://' . $this->_bucket . '.s3.amazonaws.com';
        }

        if (isset($filepath)) {
            return $prefix . $filepath;
        }
        return $prefix;
    }

    private function isS3Enabled() {
        return isset($this->_bucket) && $this->_bucket != "";
    }

}