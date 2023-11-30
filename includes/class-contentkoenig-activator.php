<?php

require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

class Contentkoenig_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
        if ( ! wp_next_scheduled( PLUGIN_SLUG_uhbyqy . '_cron_make_posts' ) ) {
          wp_schedule_event( time(), 'every_minute', PLUGIN_SLUG_uhbyqy . '_cron_make_posts' );
        }
        if ( ! wp_next_scheduled( PLUGIN_SLUG_uhbyqy . '_cron_licence_check' ) ) {
          wp_schedule_event( time(), 'daily', PLUGIN_SLUG_uhbyqy . '_cron_licence_check' );
        }
        if ( ! wp_next_scheduled( PLUGIN_SLUG_uhbyqy . '_cron_clear_posts' ) ) {
          wp_schedule_event( time(), 'daily', PLUGIN_SLUG_uhbyqy . '_cron_clear_posts' );
        }
	}

}
