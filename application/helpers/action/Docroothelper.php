<?php

/*
 * 	Helper class to abstract away the docroot.
 * 	Anything which wishes to alter the docroot should do so through here.
 * 	Doing so will ensure that your code is safe it the docroot ever moves away from 
 * 	a signal machine, local filesystem based arrangement.
 *
 * 	Pushes to s3 if configured and mirrors to the local file system as before for local dev.
 *
 * 	ps. it's still only to work with temporary files on the local machine, 
 * 	so long as you don't want then to be persisted.
 */

class Helper_Action_Docroothelper extends Zend_Controller_Action_Helper_Abstract {

    private $s3;
    private $bucket;

    public function init() {
        $logger = new Zend_Log();
        $writer = new Zend_Log_Writer_Stream('/tmp/s3.log');    // TODO configure a proper n0tice log in /var/log
        $logger->addWriter($writer);

        $config = Zend_Registry::get('s3bucket');
        $logger->log('Initing docroot helper', Zend_Log::INFO);
        if ($config->bucket) {
            $accessKey = $config->accessKey;
            $secretKey = $config->secretKey;
            $this->s3 = new Zend_Service_Amazon_S3($accessKey, $secretKey);
            $this->bucket = $config->bucket;
            $logger->log('Docroot helper inited using bucket: ' . $this->bucket, Zend_Log::INFO);
        } else {
            $logger->log('No s3 bucket config seem; falling back to local file system', Zend_Log::WARN);
        }
    }

    public function saveFile($localFilePath, $docRootPath) {
        $this->init(); // TODO This is likely to be somewhat expensive if an oauth handshake is involved - hack to get around zend limitations

        $logger = new Zend_Log();
        $writer = new Zend_Log_Writer_Stream('/var/log/notice/frontend.log');
        $logger->addWriter($writer);

        $logger->log("Saving docroot file: " . $localFilePath . " -> " . $docRootPath, Zend_Log::INFO);

        if ($this->s3) {
            $logger->log('s3 is active', Zend_Log::INFO);
            try {
                $headers = array(Zend_Service_Amazon_S3::S3_ACL_HEADER => Zend_Service_Amazon_S3::S3_ACL_PUBLIC_READ, "Cache-Control" => "max-age=864000");
                $s3Path = $this->bucket . "/" . $docRootPath;
                $logger->log('s3 path is: ' . $s3Path, Zend_Log::INFO);
                $result = $this->s3->putObject($s3Path, file_get_contents($localFilePath), $headers);
                $logger->log('Result is: ' . $result, Zend_Log::INFO);
                return $result;
            } catch (Zend_Service_Amazon_S3_Exception $s3e) {
                $logger->log('S3 exception: ' . $s3e, Zend_Log::ERROR);
                return false;
            } catch (Zend_Http_Client_Exception $hce) {
                $logger->log('S3 exception: ' . $hce, Zend_Log::ERROR);
                return false;
            }
        } else {
            $logger->log('s3 is not active; copying to local file system', Zend_Log::WARN);
            return copy($localFilePath, $docRootPath);
        }
    }

    public function removeFile($docRootPath) {
        if ($this->s3) {
            return $this->s3->removeObject($this->bucket . "/" . $docRootPath);
        } else {
            return @unlink($docRootPath);
        }
    }

}
