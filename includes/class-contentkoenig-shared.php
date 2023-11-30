<?php

use Carbon\Carbon;

class Contentkoenig_Shared {
    private $wpdb;
    private $wpdb_prefix;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->wpdb_prefix = $this->wpdb->prefix;
    }

    public function plugin_active(){
        $licence_key = get_option(PLUGIN_SLUG_uhbyqy . '_licence_key');
        $active = get_option(PLUGIN_SLUG_uhbyqy . '_active');

        if(!$active){
            return false;
        }

        if($licence_key === ''){
            return false;
        }

        return true;
    }
}