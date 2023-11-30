<?php
use Carbon\Carbon;
global $wpdb;
require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

class Contentkoenig_Updater {
    private $plugin_version;
    private $existing_version;
    private $plugin_slug;
    private $wpdb;
    private $wpdbPrefix ;
    private $charsetCollate ;

    public function __construct($plugin_version, $existing_version, $plugin_slug) {
        $this->plugin_version = $plugin_version;
        $this->existing_version = $existing_version;
        $this->plugin_slug = $plugin_slug;
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->wpdbPrefix = $this->wpdb->prefix;
        $this->charsetCollate = $this->wpdb->get_charset_collate();
        $this->projectsTableName = $this->wpdbPrefix . $this->plugin_slug . '_projects';
        $this->postsTableName = $this->wpdbPrefix . $this->plugin_slug . '_posts';
    }

    public function shouldUpdate(){
        //By default, version_compare() returns -1 if the first version is lower than the second, 0 if they are equal, and 1 if the second is lower.
        return is_null($this->existing_version) || $this->existing_version === false || $this->existing_version === '' || version_compare($this->existing_version, $this->plugin_version) < 0;
    }

    public function update(){
        if(!$this->shouldUpdate()){
            return;
        }

        $this->cron();
        $this->version_1_0_0();
        $this->version_1_0_6();
        $this->version_1_0_8();
        $this->version_1_0_10();
        $this->version_1_0_12();
        $this->version_1_0_17();
        $this->version_1_0_25();
        $this->version_1_0_32();

        //wp_cache_delete ( 'alloptions', 'options' );
        update_option($this->plugin_slug . '_version', $this->plugin_version);
    }

    public function cron(){
        if ( ! wp_next_scheduled( $this->plugin_slug . '_cron_make_posts' ) ) {
          wp_schedule_event( time(), 'every_five_minutes', $this->plugin_slug . '_cron_make_posts' );
        }
        if ( ! wp_next_scheduled( $this->plugin_slug . '_cron_licence_check' ) ) {
          wp_schedule_event( time(), 'daily', $this->plugin_slug . '_cron_licence_check' );
        }
        if ( ! wp_next_scheduled( $this->plugin_slug . '_cron_clear_posts' ) ) {
          wp_schedule_event( time(), 'daily', $this->plugin_slug . '_cron_clear_posts' );
        } else {
          //already scheduled so clear and recreate to make sure it is daily, used to be every 15 minutes
          $timestamp = wp_next_scheduled( $this->plugin_slug . '_cron_clear_posts');
          wp_unschedule_event( $timestamp, $this->plugin_slug . '_cron_clear_posts' );
          wp_schedule_event( time(), 'daily', $this->plugin_slug. '_cron_clear_posts' );
        }
    }

    private function version_1_0_0_check(){
        $query = $this->wpdb->prepare( 'SHOW TABLES LIKE %s', $this->wpdb->esc_like( $this->projectsTableName ) );
        return $this->wpdb->get_var( $query ) !== $this->projectsTableName;
    }

    private function version_1_0_6_check(){
       $query = $this->wpdb->prepare( 'SHOW COLUMNS FROM ' . $this->projectsTableName . ' LIKE %s', 'language' );
       return is_null($this->wpdb->get_row( $query ));
    }

    private function version_1_0_8_check(){
       return version_compare($this->existing_version, '1.0.8') < 0;
    }

    private function version_1_0_10_check(){
       $query = $this->wpdb->prepare( 'SHOW COLUMNS FROM ' . $this->projectsTableName . ' LIKE %s', 'interlinking' );
       return is_null($this->wpdb->get_row( $query ));
    }

    private function version_1_0_12_check(){
       $query = $this->wpdb->prepare( 'SHOW COLUMNS FROM ' . $this->projectsTableName . ' LIKE %s', 'target_linking' );
       return is_null($this->wpdb->get_row( $query ));
    }

    private function version_1_0_17_check(){
        $authorityLink = get_option($this->plugin_slug . '_authority_link');
        return $authorityLink === false || $authorityLink === '' || is_null($authorityLink);
    }

    private function version_1_0_25_check(){
       $query = $this->wpdb->prepare( 'SHOW COLUMNS FROM ' . $this->projectsTableName . ' LIKE %s', 'log' );
       return is_null($this->wpdb->get_row( $query ));
    }

    private function version_1_0_32_check(){
       $query = $this->wpdb->prepare( 'SHOW COLUMNS FROM ' . $this->projectsTableName . ' LIKE %s', 'rewrite' );
       return is_null($this->wpdb->get_row( $query ));
    }

    private function version_1_0_0(){
        if(!$this->version_1_0_0_check()){
            return;
        }

        $query = $this->wpdb->prepare( 'SHOW TABLES LIKE %s', $this->wpdb->esc_like( $this->projectsTableName ) );
        if ( $this->wpdb->get_var( $query ) !== $this->projectsTableName ) {
            $sql = "CREATE TABLE `$this->projectsTableName` (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name tinytext NOT NULL,
            next_post datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            posts_made int DEFAULT 0 NOT NULL,
            max_posts_per_day int NOT NULL,
            max_posts_total int NOT NULL,
            status varchar(25) NOT NULL,
            post_type varchar(25) DEFAULT 'draft' NOT NULL,
            authors text NOT NULL,
            categories text NOT NULL,
            active boolean DEFAULT false NOT NULL,
            prompt_type varchar(35) NOT NULL,
            subject text NOT NULL,
            topics text DEFAULT NULL,
            post_days TEXT DEFAULT NULL,
            post_time_start INT DEFAULT NULL,
            post_time_end INT DEFAULT NULL,
            PRIMARY KEY  (id)
            ) $this->charsetCollate;";
            dbDelta($sql);

            $sql = "CREATE INDEX idx_next_post ON `$this->projectsTableName` (next_post)";
            $this->wpdb->query($sql);

            $sql = "CREATE INDEX idx_status ON `$this->projectsTableName` (status)";
            $this->wpdb->query($sql);

            $sql = "CREATE INDEX idx_active ON `$this->projectsTableName` (active)";
            $this->wpdb->query($sql);

            $sql = "CREATE INDEX idx_next_post_status_active ON `$this->projectsTableName` (next_post, status, active)";
            $this->wpdb->query($sql);
        }

        $query = $this->wpdb->prepare( 'SHOW TABLES LIKE %s', $this->wpdb->esc_like( $this->postsTableName ) );
        if ( $this->wpdb->get_var( $query ) !== $this->postsTableName ) {
            $sql = "CREATE TABLE `$this->postsTableName` (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            project_id mediumint(9) NOT NULL,
            secret_key varchar(12) NOT NULL,
            title text DEFAULT NULL,
            body text DEFAULT NULL,
            tags text DEFAULT NULL,
            time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            got_data boolean DEFAULT false NOT NULL,
            posted boolean DEFAULT false NOT NULL,
            url TEXT DEFAULT NULL,
            PRIMARY KEY  (id)
            ) $this->charsetCollate;";
            dbDelta($sql);

            $sql = "CREATE INDEX idx_project_id ON `$this->postsTableName` (project_id)";
            $this->wpdb->query($sql);

            $sql = "CREATE INDEX idx_time ON `$this->postsTableName` (time)";
            $this->wpdb->query($sql);

            $sql = "CREATE INDEX idx_got_data ON `$this->postsTableName` (got_data)";
            $this->wpdb->query($sql);

            $sql = "CREATE INDEX idx_posted ON `$this->postsTableName` (posted)";
            $this->wpdb->query($sql);
        }
    }

    private function version_1_0_6(){
        if(!$this->version_1_0_6_check()){
            return;
        }

        $query = $this->wpdb->prepare( 'SHOW COLUMNS FROM ' . $this->projectsTableName . ' LIKE %s', 'language' );
        if ( is_null($this->wpdb->get_row( $query ) ) ) {
            $sql = "ALTER TABLE $this->projectsTableName ADD COLUMN language VARCHAR(28) DEFAULT NULL";
            $this->wpdb->query($sql);
        }

        $languages = get_option($this->plugin_slug . '_languages');
        if($languages === false || $languages === '' || is_null($languages)){
            add_option($this->plugin_slug . '_languages', json_encode(json_decode('[{"language":"bg","name":"Bulgarian"},{"language":"cs","name":"Czech"},{"language":"da","name":"Danish"},{"language":"de","name":"German"},{"language":"el","name":"Greek"},{"language":"en","name":"English"},{"language":"es","name":"Spanish"},{"language":"et","name":"Estonian"},{"language":"fi","name":"Finnish"},{"language":"fr","name":"French"},{"language":"hu","name":"Hungarian"},{"language":"id","name":"Indonesian"},{"language":"it","name":"Italian"},{"language":"ja","name":"Japanese"},{"language":"lt","name":"Lithuanian"},{"language":"lv","name":"Latvian"},{"language":"nl","name":"Dutch"},{"language":"pl","name":"Polish"},{"language":"pt","name":"Portuguese"},{"language":"ro","name":"Romanian"},{"language":"ru","name":"Russian"},{"language":"sk","name":"Slovak"},{"language":"sl","name":"Slovenian"},{"language":"sv","name":"Swedish"},{"language":"tr","name":"Turkish"},{"language":"zh","name":"Chinese(simplified)"}]')));
        }
    }

    private function version_1_0_8(){
        if(!$this->version_1_0_8_check()){
            return;
        }

        $sql = "UPDATE $this->postsTableName SET tags = '[]' WHERE tags = 'null' AND posted = 1";
        $this->wpdb->query($sql);
    }

    private function version_1_0_10(){
        if(!$this->version_1_0_10_check()){
            return;
        }

        $query = $this->wpdb->prepare( 'SHOW COLUMNS FROM ' . $this->projectsTableName . ' LIKE %s', 'interlinking' );
        if ( is_null($this->wpdb->get_row( $query ) ) ) {
            $sql = "ALTER TABLE $this->projectsTableName ADD COLUMN interlinking boolean DEFAULT false NOT NULL";
            $this->wpdb->query($sql);
        }

        $query = $this->wpdb->prepare( 'SHOW COLUMNS FROM ' . $this->projectsTableName . ' LIKE %s', 'interlinking_all_projects' );
        if ( is_null($this->wpdb->get_row( $query ) ) ) {
            $sql = "ALTER TABLE $this->projectsTableName ADD COLUMN interlinking_all_projects boolean DEFAULT false NOT NULL";
            $this->wpdb->query($sql);
        }

        $query = $this->wpdb->prepare( 'SHOW COLUMNS FROM ' . $this->projectsTableName . ' LIKE %s', 'interlinking_count' );
        if ( is_null($this->wpdb->get_row( $query ) ) ) {
            $sql = "ALTER TABLE $this->projectsTableName ADD COLUMN interlinking_count mediumint(9) DEFAULT 2 NOT NULL";
            $this->wpdb->query($sql);
        }

        $query = $this->wpdb->prepare( 'SHOW COLUMNS FROM ' . $this->postsTableName . ' LIKE %s', 'wp_post_id' );
        if ( is_null($this->wpdb->get_row( $query ) ) ) {
            $sql = "ALTER TABLE $this->postsTableName ADD COLUMN wp_post_id mediumint(9) DEFAULT NULL";
            $this->wpdb->query($sql);
        }
    }

    private function version_1_0_12(){
        if(!$this->version_1_0_12_check()){
            return;
        }

        $query = $this->wpdb->prepare( 'SHOW COLUMNS FROM ' . $this->projectsTableName . ' LIKE %s', 'target_linking' );
        if ( is_null($this->wpdb->get_row( $query ) ) ) {
            $sql = "ALTER TABLE $this->projectsTableName ADD COLUMN target_linking boolean DEFAULT false NOT NULL";
            $this->wpdb->query($sql);
        }

        $query = $this->wpdb->prepare( 'SHOW COLUMNS FROM ' . $this->projectsTableName . ' LIKE %s', 'target_linking_targets' );
        if ( is_null($this->wpdb->get_row( $query ) ) ) {
            $sql = "ALTER TABLE $this->projectsTableName ADD COLUMN target_linking_targets text DEFAULT NULL";
            $this->wpdb->query($sql);
        }

        $query = $this->wpdb->prepare( 'SHOW COLUMNS FROM ' . $this->projectsTableName . ' LIKE %s', 'target_linking_percentage' );
        if ( is_null($this->wpdb->get_row( $query ) ) ) {
            $sql = "ALTER TABLE $this->projectsTableName ADD COLUMN target_linking_percentage mediumint(9) DEFAULT 100";
            $this->wpdb->query($sql);
        }
    }

    private function version_1_0_17(){
        if(!$this->version_1_0_17_check()){
            return;
        }

        add_option($this->plugin_slug . '_authority_link', '');
    }

    private function version_1_0_25(){
        if(!$this->version_1_0_25_check()){
            return;
        }

        $query = $this->wpdb->prepare( 'SHOW COLUMNS FROM ' . $this->projectsTableName . ' LIKE %s', 'log' );
        if ( is_null($this->wpdb->get_row( $query ) ) ) {
            $sql = "ALTER TABLE $this->projectsTableName ADD COLUMN log LONGTEXT DEFAULT NULL";
            $this->wpdb->query($sql);
        }
    }

    private function version_1_0_32(){
        if(!$this->version_1_0_32_check()){
            return;
        }

        $query = $this->wpdb->prepare( 'SHOW COLUMNS FROM ' . $this->projectsTableName . ' LIKE %s', 'rewrite' );
        if ( is_null($this->wpdb->get_row( $query ) ) ) {
            $sql = "ALTER TABLE $this->projectsTableName ADD COLUMN rewrite boolean DEFAULT false NOT NULL";
            $this->wpdb->query($sql);
        }

        add_option($this->plugin_slug . '_rewriter_api_key', '');
    }
}