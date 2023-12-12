<?php
define( 'PLUGIN_VERSION_uhbyqy', '1.0.35' );
define( 'PLUGIN_SLUG_uhbyqy', 'contentkoenig' );
define( 'PLUGIN_CLASS_uhbyqy', 'Contentkoenig' );
define( 'PLUGIN_NAME_uhbyqy', 'Content König' );

/*
 * Plugin Name:       Content König
 * Plugin URI:        https://contentkoenig.com
 * Description:       Content König erstellt und postet automatisch fertige Artikel mit künstlicher Intelligenz  inkl. Bildern zu jedem Thema deiner Wahl
 * Version:           1.0.35
 * Author:            Torsten Jaeger
 * Author URI:        https://contentkoenig.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       contentkoenig
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
    exit( sprintf( 'Plugin requires PHP 7.4 or higher. Your currently installed version is %s.', PHP_VERSION ) );
}

function activate_uhbyqy() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-' . PLUGIN_SLUG_uhbyqy . '-activator.php';
    $class = PLUGIN_CLASS_uhbyqy . '_Activator';
    $class::activate();
}

function deactivate_uhbyqy() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-' . PLUGIN_SLUG_uhbyqy . '-deactivator.php';
    $class = PLUGIN_CLASS_uhbyqy . '_Deactivator';
    $class::deactivate();
}

register_activation_hook( __FILE__, 'activate_uhbyqy' );
register_deactivation_hook( __FILE__, 'deactivate_uhbyqy' );

require plugin_dir_path( __FILE__ ) . 'includes/class-' . PLUGIN_SLUG_uhbyqy . '-shared.php';
require plugin_dir_path( __FILE__ ) . 'includes/class-' . PLUGIN_SLUG_uhbyqy . '-api.php';
require plugin_dir_path( __FILE__ ) . 'includes/class-' . PLUGIN_SLUG_uhbyqy . '-post.php';
require plugin_dir_path( __FILE__ ) . 'includes/class-' . PLUGIN_SLUG_uhbyqy . '-posts.php';
require plugin_dir_path( __FILE__ ) . 'includes/class-' . PLUGIN_SLUG_uhbyqy . '-project.php';
require plugin_dir_path( __FILE__ ) . 'includes/class-' . PLUGIN_SLUG_uhbyqy . '-projects.php';
require plugin_dir_path( __FILE__ ) . 'includes/class-' . PLUGIN_SLUG_uhbyqy . '-updater.php';
require plugin_dir_path( __FILE__ ) . 'includes/class-' . PLUGIN_SLUG_uhbyqy . '.php';
require plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
require plugin_dir_path( __FILE__ ) . 'plugin-update-checker/plugin-update-checker.php';

Puc_v4_Factory::buildUpdateChecker(
    'https://wordpressautoblog.com/plugin/contentkoenig/contentkoenig.json',
    __FILE__,
    PLUGIN_SLUG_uhbyqy
);

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_uhbyqy() {
    $class = PLUGIN_CLASS_uhbyqy;
    $plugin = new $class();
    $plugin->run();
}
run_uhbyqy();
