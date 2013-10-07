<?php

/*
 Plugin Name: F1 CDN Files
 Description: Manage files hosted on Rackspace CloudFiles CDN
 Version: 1.1
 Author: Factor1
 Author URI: http://www.factor1studios.com/
*/
$f1CdnFiles = new f1CdnFiles();

define('RAXSDK_OBJSTORE_NAME','cloudFiles');
define('RAXSDK_OBJSTORE_REGION','ORD');

$libraryPath = $f1CdnFiles->plugin_dir."/lib";

// Include the autoloader
require_once $libraryPath . '/Autoload.php';

// Register the root OpenCloud namespace
$classLoader = new SplClassLoader('OpenCloud', $libraryPath);
$classLoader->register();

class f1CdnFiles
{
    public $capability = 'manage_options';
    public $plugin_dir = null;
    public $settings = null;
    public $cdn_connection= NULL;

    function __construct(){
        $this->plugin_dir = plugin_dir_path(__FILE__);

        #Admin Menu
        add_action( 'admin_menu', array( &$this, 'add_top_level_menu' ) );
        #Load CSS/Javascripts
        add_action( 'admin_init', array(&$this, 'admin_enqueue_scripts') );

    }

    function admin_enqueue_scripts($hook) {

        wp_register_style( 'f1CdnFilesStyle', plugins_url('style.css', __FILE__) );
        wp_enqueue_style( 'f1CdnFilesStyle' );


        wp_enqueue_script( 'f1CdnFilesGlobalJs', plugins_url( '/js/global.js', __FILE__ ), array('jquery'));

    }
    function add_top_level_menu()
    {

        // Settings for the function call below
        $page_title = 'CDN Files';
        $menu_title = 'CDN Files';
        $menu_slug = 'f1-cdn-files';
        $function = array( &$this, 'display_admin_index' );
        #$icon_url = NULL;
        #$position = '';

        // Creates a top level admin menu - this kicks off the 'display_page()' function to build the page
        #$page = add_menu_page($page_title, $menu_title, $this->capability, $menu_slug, $function, $icon_url, 10);

        add_menu_page($page_title, $menu_title, $this->capability, $menu_slug,$function);

        // Adds an additional sub menu page to the above menu - if we add this, we end up with 2 sub menu pages (the main pages is then in sub menu. But if we omit this, we have no sub menu
        // This has been left in incase we want to add an additional page here soon
        $function = array( &$this, 'display_admin_settings' );
        add_submenu_page( $menu_slug, 'Settings', 'Settings', $this->capability, $menu_slug . '_settings', $function );
    }
    function display_admin_instructions() {
        echo $this->render("admin_instructions.tpl.php");
    }
    function display_admin_settings() {
       $saved = false;

        if('POST' == $_SERVER['REQUEST_METHOD']) {
            $this->set_setting('container_name', $_POST['f1_settings']['container_name']);
            $this->set_setting('cdn_username', $_POST['f1_settings']['cdn_username']);
            $this->set_setting('cdn_apikey', $_POST['f1_settings']['cdn_apikey']);
            $saved = true;
        }
        $container_name = $this->get_setting('container_name');

        echo $this->render('admin_settings.tpl.php', array(
            'container_name' => $container_name,
            'saved' => $saved,
        ));
    }

    function display_admin_index()
    {

        if (!current_user_can($this->capability ))
            wp_die(__('You do not have sufficient permissions to access this page.'));

        $container_name = $this->get_setting('container_name');
        $cdn_username = $this->get_setting('cdn_username');
        $cdn_apikey = $this->get_setting('cdn_apikey');
        $file_added = false;
        $file_delete = false;
        $search_term = false;
        if(empty($container_name) || empty($cdn_username) || empty($cdn_apikey)) {
            echo $this->render('admin_index_container_name_missing.tpl.php');
        } else {
            if("POST" == $_SERVER['REQUEST_METHOD']) {
                if(isset($_POST['f1_action_delete'])) {
                    $container = $this->cdn_get_container($container_name);
                    try {
                        $object = $container->DataObject($_POST['f1_action_delete']);
                        $object->Delete();
                        $file_delete = true;
                    } catch(Exception $e) {

                    }
                }
                if(isset($_POST['f1_action_add'])) {
                    #adding a file
                    $source_path = false;
                    $content_type = Null;
                    if(!empty($_POST['file_url'])) {
                        #its a URL based file
                        $source_path = $_POST['file_url'];
                        $file_name = explode('/', $source_path);
                        $file_name = $file_name[count($file_name)-1];

                    } else {
                        if(is_uploaded_file($_FILES['file']['tmp_name'])) {
                            $source_path = $_FILES['file']['tmp_name'];
                            $file_name = $_FILES['file']['name'];

                        }
                    }

                    if($source_path) {
                        $content_type = get_mime_type($file_name);
                        try {
                            $container = $this->cdn_get_container($container_name);
                            $new_file = $container->DataObject();
                            $new_file->SetData(file_get_contents($source_path));
                            $new_file->name = $file_name;
                            $new_file->content_type = $content_type;
                            $new_file->Create();
                            $file_added = true;
                        } catch(Exception $e) {
                        }

                    }
                }
                if(!empty($_POST['f1_search_term'])) {
                    $search_term = $_POST['f1_search_term'];
                }
            }
            $data_list = $this->cdn_container_list_data($this->get_setting('container_name'), $search_term);

            echo $this->render('admin_index.tpl.php', array('data_list' => $data_list, 'file_added' => $file_added, 'file_delete' => $file_delete));
        }

    }
    function cdn_connection() {
        if(is_null($this->cdn_connection)) {
            $this->cdn_connection = new \OpenCloud\Rackspace(
            RACKSPACE_US,
            array(
                'username' => $this->get_setting('cdn_username'),
                'apiKey' => $this->get_setting('cdn_apikey')
            ));
        }
        return $this->cdn_connection;
    }
    function cdn_get_container($container_name) {
        $conn = $this->cdn_connection();
        $ostore = $conn->ObjectStore();

        try {
            $cont = $ostore->Container($container_name);
        } catch(Exception $e) {
            $cont = $ostore->Container();
            $cont->Create(array('name'=>$container_name));
            $cont->EnableCDN();
        }
        return $cont;
    }
    function cdn_container_list_data($container_name, $search_term=false) {
        $cont = $this->cdn_get_container($container_name);
        if($search_term) {
            $list = $cont->ObjectList(array('prefix' => $search_term));
        } else {
            $list = $cont->ObjectList();
        }

        $results = array();
        while($obj = $list->Next()) {
            $results[strtotime($obj->last_modified)][] = $obj;
        }
        krsort($results, SORT_ASC);
        return $results;
    }

    function render($__tpl__, $vars = array()) {
        if(is_array($vars)) {
            foreach($vars as $__ky__=>$__val__) {
                $$__ky__ = $__val__;
            }
        }

        ob_start();
        include($this->plugin_dir."templates/".$__tpl__);
        $_output = ob_get_contents();
        ob_end_clean();
        return $_output;
    }
    public function slugify($term) {
        $term = preg_replace('/[^a-z0-9\-_\.]/', '_', strtolower($term));
        $term = preg_replace('/_+/', '_', $term);
        return $term;
    }

    function get_setting($setting_name) {
        if(is_null($this->settings)) {
            $this->load_settings();
        }
        if(isset($this->settings[strtoupper($setting_name)])) {
            return $this->settings[strtoupper($setting_name)];
        }
    }
    function set_setting($setting_name, $value) {
        if(is_null($this->settings)) {
            $this->load_settings();
        }
        $this->settings[strtoupper($setting_name)] = $value;
        update_option('f1_cdnfiles_settings', serialize($this->settings));

    }
    function load_settings() {
        $this->settings = unserialize(get_option('f1_cdnfiles_settings'));
    }
}

/**
 * SplClassLoader implementation that implements the technical interoperability
 * standards for PHP 5.3 namespaces and class names.
 *
 * http://groups.google.com/group/php-standards/web/final-proposal
 *
 *     // Example which loads classes for the Doctrine Common package in the
 *     // Doctrine\Common namespace.
 *     $classLoader = new SplClassLoader('Doctrine\Common', '/path/to/doctrine');
 *     $classLoader->register();
 *
 * @author Jonathan H. Wage <jonwage@gmail.com>
 * @author Roman S. Borschel <roman@code-factory.org>
 * @author Matthew Weier O'Phinney <matthew@zend.com>
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 * @author Fabien Potencier <fabien.potencier@symfony-project.org>
 */
class SplClassLoader
{
    private $_fileExtension = '.php';
    private $_namespace;
    private $_includePath;
    private $_namespaceSeparator = '\\';

    /**
     * Creates a new <tt>SplClassLoader</tt> that loads classes of the
     * specified namespace.
     *
     * @param string $ns The namespace to use.
     */
    public function __construct($ns = null, $includePath = null)
    {
        $this->_namespace = $ns;
        $this->_includePath = $includePath;
    }

    /**
     * Sets the namespace separator used by classes in the namespace of this class loader.
     *
     * @param string $sep The separator to use.
     */
    public function setNamespaceSeparator($sep)
    {
        $this->_namespaceSeparator = $sep;
    }

    /**
     * Gets the namespace seperator used by classes in the namespace of this class loader.
     *
     * @return void
     */
    public function getNamespaceSeparator()
    {
        return $this->_namespaceSeparator;
    }

    /**
     * Sets the base include path for all class files in the namespace of this class loader.
     *
     * @param string $includePath
     */
    public function setIncludePath($includePath)
    {
        $this->_includePath = $includePath;
    }

    /**
     * Gets the base include path for all class files in the namespace of this class loader.
     *
     * @return string $includePath
     */
    public function getIncludePath()
    {
        return $this->_includePath;
    }

    /**
     * Sets the file extension of class files in the namespace of this class loader.
     *
     * @param string $fileExtension
     */
    public function setFileExtension($fileExtension)
    {
        $this->_fileExtension = $fileExtension;
    }

    /**
     * Gets the file extension of class files in the namespace of this class loader.
     *
     * @return string $fileExtension
     */
    public function getFileExtension()
    {
        return $this->_fileExtension;
    }

    /**
     * Installs this class loader on the SPL autoload stack.
     */
    public function register()
    {
        spl_autoload_register(array($this, 'loadClass'));
    }

    /**
     * Uninstalls this class loader from the SPL autoloader stack.
     */
    public function unregister()
    {
        spl_autoload_unregister(array($this, 'loadClass'));
    }

    /**
     * Loads the given class or interface.
     *
     * @param string $className The name of the class to load.
     * @return void
     */
    public function loadClass($className)
    {
        if (null === $this->_namespace || $this->_namespace.$this->_namespaceSeparator === substr($className, 0, strlen($this->_namespace.$this->_namespaceSeparator))) {
            $fileName = '';
            $namespace = '';
            if (false !== ($lastNsPos = strripos($className, $this->_namespaceSeparator))) {
                $namespace = substr($className, 0, $lastNsPos);
                $className = substr($className, $lastNsPos + 1);
                $fileName = str_replace($this->_namespaceSeparator, DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
            }
            $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . $this->_fileExtension;

            require ($this->_includePath !== null ? $this->_includePath . DIRECTORY_SEPARATOR : '') . $fileName;
        }
    }
}

function get_mime_type($file)
{

    // our list of mime types
    $mime_types = array(
        "pdf"=>"application/pdf"
    ,"exe"=>"application/octet-stream"
    ,"zip"=>"application/zip"
    ,"docx"=>"application/msword"
    ,"doc"=>"application/msword"
    ,"xls"=>"application/vnd.ms-excel"
    ,"ppt"=>"application/vnd.ms-powerpoint"
    ,"gif"=>"image/gif"
    ,"png"=>"image/png"
    ,"jpeg"=>"image/jpg"
    ,"jpg"=>"image/jpg"
    ,"mp3"=>"audio/mpeg"
    ,"wav"=>"audio/x-wav"
    ,"mpeg"=>"video/mpeg"
    ,"mpg"=>"video/mpeg"
    ,"mp4"=>"video/mp4"
    ,"mpe"=>"video/mpeg"
    ,"mov"=>"video/quicktime"
    ,"avi"=>"video/x-msvideo"
    ,"3gp"=>"video/3gpp"
    ,"css"=>"text/css"
    ,"jsc"=>"application/javascript"
    ,"js"=>"application/javascript"
    ,"php"=>"text/html"
    ,"htm"=>"text/html"
    ,"html"=>"text/html"
    );

    $extension = strtolower(end(explode('.',$file)));
    if(array_key_exists($extension, $mime_types)) {
        return $mime_types[$extension];
    }
    return "application/octet-stream";
}