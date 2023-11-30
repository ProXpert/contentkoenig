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
          wp_schedule_event( time(), 'every_five_minutes', PLUGIN_SLUG_uhbyqy . '_cron_make_posts' );
        }
        if ( ! wp_next_scheduled( PLUGIN_SLUG_uhbyqy . '_cron_licence_check' ) ) {
          wp_schedule_event( time(), 'daily', PLUGIN_SLUG_uhbyqy . '_cron_licence_check' );
        }
        if ( ! wp_next_scheduled( PLUGIN_SLUG_uhbyqy . '_cron_clear_posts' ) ) {
          wp_schedule_event( time(), 'every_fifteen_minutes', PLUGIN_SLUG_uhbyqy . '_cron_licence_check' );
        } else {
          //already scheduled so clear and recreate to make sure it is daily, used to be every 15 minutes
          $timestamp = wp_next_scheduled( PLUGIN_SLUG_uhbyqy . '_cron_clear_posts');
          wp_unschedule_event( $timestamp, PLUGIN_SLUG_uhbyqy . '_cron_clear_posts' );
          wp_schedule_event( time(), 'daily', PLUGIN_SLUG_uhbyqy. '_cron_clear_posts' );
        }
	}

}
