<?php

/**
 * 
 * Bootstrap for application
 *
 */
class Bootstrap extends Zend_Application_Bootstrap_Bootstrap {

    private $_acl = null;
    private $_sitename = null;
    private $_db = null;
    private $_cachingType = null;

    /**
     * Initialise session namespaces
     *
     * @return void
     */
    protected function _initSessionNamespaces() {
        $this->bootstrap("session");
    }

    /**
     * Initialise application configuration settings
     *
     * @return void
     */
    protected function _initAppconfig() {

        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', APPLICATION_ENV);

        $general_options = $config->general;
        $this->_sitename = $general_options->name;

        Zend_Registry::set('general', $config->general);

        date_default_timezone_set('Europe/London');
        ini_set('date.timezone', 'Europe/London');
        ini_set('date.default_latitude', 51.500181);
        ini_set('date.default_longitude', -0.12619);

        Zend_Registry::set('s3bucket', $config->s3);
        Zend_Registry::set('static_path', $config->static->base->url);

        $this->_cachingType = $config->caching->type;
        $this->_db = $config->database;
    }

    /**
     * Initialise caching
     *
     * @return void
     */
    protected function _initCaching() {

        $backendOpts = array(
            'servers' => array(
                array('host' => 'localhost', 'port' => 11211) // TODO needs to be configurable at somepoint
            ),
            'compression' => false
        );
        $frontendOpts = array('caching' => true, 'automatic_serialization' => true);

        $frontendOpts['lifetime'] = 60; // Tiny - 1 min
        $cache = Zend_Cache::factory('Core', $this->_cachingType, $frontendOpts, $backendOpts);
        Zend_Registry::set('1minCache', $cache);

        $frontendOpts['lifetime'] = 300; // Small - 5 min
        $cache = Zend_Cache::factory('Core', $this->_cachingType, $frontendOpts, $backendOpts);
        Zend_Registry::set('5minCache', $cache);

        $frontendOpts['lifetime'] = 900; // Medium - 15 mins
        $cache = Zend_Cache::factory('Core', $this->_cachingType, $frontendOpts, $backendOpts);
        Zend_Registry::set('15minCache', $cache);

        $frontendOpts['lifetime'] = 86400; // Large - 1 Day
        $cache = Zend_Cache::factory('Core', $this->_cachingType, $frontendOpts, $backendOpts);
        Zend_Registry::set('1dayCache', $cache);
    }

    /**
     * Initialise database conenctions
     *
     * @return void
     */
    protected function _initDB() {

        $db = Zend_Db::factory($this->_db);
        Zend_Db_Table::setDefaultAdapter($db);
        Zend_Registry::set('db', $db);

        Zend_Db_Table_Abstract::setDefaultMetadataCache(Zend_Registry::get('15minCache'));

        // Profile DB calls in Firefox using Firebug
        //$profiler = new Zend_Db_Profiler_Firebug('All DB Queries');
        //$profiler->setEnabled(true);
        //$db->setProfiler($profiler);
        $logger = new Zend_Log();
        $writer = new Zend_Log_Writer_Firebug();
        $logger->addWriter($writer);
        Zend_Registry::set('logger', $logger);
    }

    /**
     * Initialise Autoloader functions
     *
     * @return void
     */
    protected function _initAutoload() {

        $autoloader = new Zend_Application_Module_Autoloader(array(
                    'namespace' => '',
                    'basePath' => APPLICATION_PATH . '/modules/default'
                ));

        Zend_Controller_Action_HelperBroker::addPath(APPLICATION_PATH . '/helpers/action/', 'Helper_Action_');

        return $autoloader;
    }

    /**
     * Initialise ZFDebug
     *
     * @return void
     */
    protected function _initZFDebug() {
        $autoloader = Zend_Loader_Autoloader::getInstance();
        $autoloader->registerNamespace('ZFDebug');

        $options = array(
            'plugins' => array('Variables',
                'File' => array('base_path' => '/path/to/project'),
                'Memory',
                'Time',
                'Registry',
                'Exception')
        );

        $options['plugins']['Database']['adapter'] = Zend_Registry::get('db');

        # Setup the cache plugin
        if ($this->hasPluginResource('cache')) {
            $this->bootstrap('cache');
            $cache = $this - getPluginResource('cache')->getDbAdapter();
            $options['plugins']['Cache']['backend'] = $cache->getBackend();
        }

        $debug = new ZFDebug_Controller_Plugin_Debug($options);

        $this->bootstrap('frontController');
        $frontController = $this->getResource('frontController');
        $frontController->registerPlugin($debug);
    }

    /**
     * Initialise plugin architecture and ACL permissions
     *
     * @return void
     */
    protected function _initPlugin() {

        if (Zend_Auth::getInstance()->hasIdentity()) {
            Zend_Registry::set('role', Zend_Auth::getInstance()->getStorage()->read()->role);
        } else {
            Zend_Registry::set('role', 'guestgroup');
        }

        $this->_acl = new Model_StaticAcl ();
        $this->_auth = Zend_Auth::getInstance();

        $fc = Zend_Controller_Front::getInstance();

        // Detect if user is allowed to access page
        $fc->registerPlugin(new Plugin_AccessCheck($this->_acl));

        Zend_Registry::set('acl', $this->_acl);
    }

    /**
     * Setup the locale based on the browser
     *
     * @return void
     */
    protected function _initLocale() {

        $locale = new Zend_Locale();

        if (!Zend_Locale::isLocale($locale, TRUE, FALSE)) {
            if (!Zend_Locale::isLocale($locale, FALSE, FALSE)) {
                throw new Zend_Exception("The locale '$locale' is no known locale");
            }

            $locale = new Zend_Locale($locale);
        }

        $locale = new Zend_Locale('en_US');

        if ($locale instanceof Zend_Locale) {
            Zend_Registry::set('Zend_Locale', $locale);
        }
    }

    /**
     * Initialise view render
     *
     * @return void
     */
    protected function _initView() {

        $options = $this->getOptions();
        $config = $options ['resources'] ['view'];
        if (isset($config)) {
            $view = new Zend_View($config);
        } else {
            $view = new Zend_View ();
        }
        if (isset($config ['doctype'])) {
            $view->doctype($config ['doctype']);
        }
        if (isset($config ['charset'])) {
            $view->headMeta()->setCharset($config ['charset'], 'charset');
        }

        $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('ViewRenderer');
        $viewRenderer->setView($view);

        $view->headTitle()->setSeparator(' - ');
        $view->headTitle($this->_sitename);
        
        $view->sitename = $this->_sitename;
        
        // Generic JS files needed for mobile or non-mobile
        $view->headScript()->appendFile('https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js');

        $view->addHelperPath(APPLICATION_PATH . '/helpers/view/common', 'Helper_View_Common_');
        $view->addHelperPath(APPLICATION_PATH . '/helpers/view/', 'Helper_View_');

        // CSS
        $view->headLink()->appendStylesheet('/media/css/bootstrap.min.css');
        $view->headLink()->appendStylesheet('http://fonts.googleapis.com/css?family=Pacifico');
        $view->headLink()->appendStylesheet('/media/css/master.css');

        // JS
        $view->headScript()->appendFile('https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js');
        $view->headScript()->appendFile('/media/js/bootstrap.min.js');
    }

    /**
     * Initialise application routes
     *
     * @return void
     */
    protected function _initRoutes() {

        $router = Zend_Controller_Front::getInstance()->getRouter();

        // Authorisation
        $router->addRoute('auth_noaccess', $this->urlRouter('no-access', 'default', 'auth', 'noaccess'));
        $router->addRoute('auth_facebook', $this->urlRouter('auth/facebook', 'default', 'auth', 'facebook'));
        $router->addRoute('auth_twitter', $this->urlRouter('auth/twitter', 'default', 'auth', 'twitter'));
        $router->addRoute('auth_login', $this->urlRouter('login', 'default', 'auth', 'login'));
        $router->addRoute('auth_logout', $this->urlRouter('logout', 'default', 'auth', 'logout'));
        $router->addRoute('auth_signup', $this->urlRouter('signup', 'default', 'auth', 'signup'));
        $router->addRoute('auth_lost', $this->urlRouter('lost-password', 'default', 'auth', 'lost'));
        $router->addRoute('auth_reset', $this->urlRouter('reset-password/:email/:code', 'default', 'auth', 'reset', array('code' => '', 'email' => '')));
        $router->addRoute('auth_confirmemail', $this->urlRouter('confirm-email/:email/:code', 'default', 'auth', 'confirmemail', array('code' => '', 'email' => '')));
        $router->addRoute('auth_resendemail', $this->urlRouter('resend-email/:email', 'default', 'auth', 'noaccess', array('email' => '')));
        $router->addRoute('auth_forgetdata', $this->urlRouter('forget-data', 'default', 'auth', 'forgetdata'));

        $router->addRoute('admin_home', new Zend_Controller_Router_Route('admin/', array('module' => 'admin', 'controller' => 'index', 'action' => 'index')));

        $router->addRoute('home', new Zend_Controller_Router_Route('/', array('module' => 'default', 'controller' => 'index', 'action' => 'index')));
    }

    private function urlRouter($path, $module, $controller, $action, $params = array()) {

        $route_data = array(
            'module' => $module,
            'controller' => $controller,
            'action' => $action
        );
        if ($params) {
            foreach ($params as $key => $value) {
                $route_data[$key] = $value;
            }
        }

        $path = $path . '/';
        
        $route = new Zend_Controller_Router_Route($path, $route_data);
        
        return $route;
    }

}