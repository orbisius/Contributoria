<?php

/**
 * Bit.ly API implementation
 *
 * @category   App
 * @package    App_Service_ShortUrl
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Helper_Action_Bitly extends Zend_Controller_Action_Helper_Abstract {

    /**
     * Base URI of the service
     *
     * @var string
     */
    protected $_baseUri = 'http://bit.ly';

    /**
     * Username used to authenticate the api call
     *
     * @var string
     */
    protected $_username;

    /**
     * The api key of the service
     *
     * @var string
     */
    protected $_apiKey;

    /**
     * Store the username and the apiKey
     * 
     */
    public function __construct() {
        $this->_username = "o_5rmd4fo9b3";
        $this->_apiKey = "R_7a562cf7de9f13968fb6e260c2fadf0f";
    }

    /**
     * This function shortens long url
     *
     * @param string $url URL to Shorten
     * @throws Zend_Service_ShortUrl_Exception When URL is not valid
     * @return string New URL
     */
    public function shorten($url) {

        $serviceUri = 'http://api.bit.ly/v3/shorten';

        $url = $serviceUri . "?longUrl={$url}&apiKey={$this->_apiKey}&login={$this->_username}";

        $opts = array(
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_URL => $url
        );
        $ch = curl_init();
        curl_setopt_array($ch, $opts);

        $result = curl_exec($ch);
        curl_close($ch);

        $results = Zend_Json::decode($result);
        if ($results['status_txt'] == 'OK' && isset($results['data']['url'])) {
            return $results['data']['url'];
        }

        throw new Zend_Exception(sprintf('Error while shortening %s: %s', $url, $results['status_txt']));
    }

    /**
     * Reveals target for short URL
     *
     * @param string $shortenedUrl URL to reveal target of
     * @throws Zend_Service_ShortUrl_Exception When URL is not valid or is not shortened by this service
     * @return string
     */
    public function unshorten($shortenedUrl) {
        
        $serviceUri = 'http://api.bit.ly/v3/expand';

        $url = $serviceUri . "?shortUrl={$shortenedUrl}&apiKey={$this->_apiKey}&login={$this->_username}";

        $opts = array(
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_URL => $url
        );
        $ch = curl_init();
        curl_setopt_array($ch, $opts);

        $result = curl_exec($ch);
        curl_close($ch);

        $results = Zend_Json::decode($result);

        if ($results['status_txt'] == 'OK' && isset($results['data']['expand'][0]['long_url'])) {
            return $results['data']['expand'][0]['long_url'];
        }

        throw new Zend_Service_ShortUrl_Exception(sprintf('Error while shortening %s: %s', $url, $results['status_txt']));
    }

}
