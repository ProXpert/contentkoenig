<?php

class Contentkoenig {
    protected $loader;
    protected $plugin_name;
    protected $version;
    private $public;
    private $admin;

    public function __construct() {
        if ( defined( 'PLUGIN_VERSION_uhbyqy' ) ) {
            $this->version = PLUGIN_VERSION_uhbyqy;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = PLUGIN_SLUG_uhbyqy;

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();

        add_action( 'rest_api_init', [$this, 'add_api_routes']);
        add_action( 'rest_post_dispatch', [$this, 'handle_preflight']);
    }

    private function load_dependencies() {
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-' . PLUGIN_SLUG_uhbyqy . '-loader.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-' . PLUGIN_SLUG_uhbyqy . '-i18n.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-' . PLUGIN_SLUG_uhbyqy . '-admin.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-' . PLUGIN_SLUG_uhbyqy . '-public.php';

        $class = PLUGIN_CLASS_uhbyqy . '_Loader';
        $this->loader = new $class();
    }

    private function set_locale() {
        $class = PLUGIN_CLASS_uhbyqy . '_i18n';
        $plugin_i18n = new $class();

        $this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
    }

    private function define_admin_hooks() {
        $class = PLUGIN_CLASS_uhbyqy . '_Admin';
        $this->admin = new $class( $this->get_plugin_name(), $this->get_version() );

        $this->loader->add_action( 'admin_enqueue_scripts', $this->admin, 'enqueue_styles' );
        $this->loader->add_action( 'admin_enqueue_scripts', $this->admin, 'enqueue_scripts' );

        $this->loader->add_action('admin_init', $this->admin, 'upgrade_check');

        $this->loader->add_action( 'admin_notices', $this->admin, 'add_notices' );
        $this->loader->add_action( 'admin_menu', $this->admin, 'admin_menu' );
        $this->loader->add_action( 'admin_init', $this->admin, 'admin_redirects' );

        $this->loader->add_action( 'wp_ajax_save_licence_key_uhbyqy', $this->admin, 'ajax_save_licence_key' );
        $this->loader->add_action( 'wp_ajax_update_settings_uhbyqy', $this->admin, 'ajax_update_settings' );
        $this->loader->add_action( 'wp_ajax_check_openai_key_uhbyqy', $this->admin, 'ajax_check_openai_key' );
        $this->loader->add_action( 'wp_ajax_update_project_uhbyqy', $this->admin, 'ajax_update_project' );
        $this->loader->add_action( 'wp_ajax_add_project_uhbyqy', $this->admin, 'ajax_add_project' );
        $this->loader->add_action( 'wp_ajax_authority_links_uhbyqy', $this->admin, 'ajax_authority_links' );
    }

    private function define_public_hooks() {
        $class = PLUGIN_CLASS_uhbyqy . '_Public';
        $this->public = new $class( $this->get_plugin_name(), $this->get_version() );

        $this->loader->add_action( 'wp_enqueue_scripts', $this->public, 'enqueue_styles' );
        $this->loader->add_action( 'wp_enqueue_scripts', $this->public, 'enqueue_scripts' );

        $this->loader->add_filter('cron_schedules', $this->public, 'add_cron_intervals');
        $this->loader->add_action(PLUGIN_SLUG_uhbyqy . '_cron_licence_check', $this->public, 'cron_licence_check' );
        $this->loader->add_action(PLUGIN_SLUG_uhbyqy . '_cron_make_posts', $this->public, 'cron_make_posts' );
        $this->loader->add_action(PLUGIN_SLUG_uhbyqy . '_cron_clear_posts', $this->public, 'cron_clear_posts' );
    }

    public function run() {
        $this->loader->run();
    }

    public function get_plugin_name() {
        return $this->plugin_name;
    }

    public function get_loader() {
        return $this->loader;
    }

    public function get_version() {
        return $this->version;
    }

    public function handle_preflight(\WP_REST_Response $result){
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            $result->header('Access-Control-Allow-Headers', 'Authorization, Content-Type', true);
        }
        return $result;
    }

    public function add_api_routes(){
        register_rest_route(
            'wordpress_autoblog',
            'post',
            array(
                'methods' => 'POST',
                'callback' => array($this->public, 'receive_post'),
                'permission_callback' => '__return_true',
            )
        );
    }
}
