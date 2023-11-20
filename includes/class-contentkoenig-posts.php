<?php
use Carbon\Carbon;
global $wpdb;

class Contentkoenig_Posts {
    private $wpdb;
    private $wpdbPrefix ;
    private $postsTableName;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->wpdbPrefix = $this->wpdb->prefix;
        $this->postsTableName = $this->wpdbPrefix . PLUGIN_SLUG_uhbyqy . '_posts';
    }

    public function all(){
        return $this->wpdb->get_results(
           "SELECT * FROM {$this->postsTableName} ORDER BY date DESC"
        );
    }

    public function get($id){
        return $this->wpdb->get_row(
           $this->wpdb->prepare("SELECT * FROM {$this->postsTableName} WHERE id =%d", $id)
        );
    }

    public function delete($id){
        return $this->wpdb->delete($this->postsTableName, ['id' => $id]);
    }

    public function getAlt($projectId, $postId, $secretKey, $gotData, $posted){
        return $this->wpdb->get_row(
           $this->wpdb->prepare("SELECT * FROM {$this->postsTableName} WHERE project_id =%d AND id =%d AND secret_key=%s AND got_data=%d AND posted=%d", $projectId, $postId, $secretKey, $gotData, $posted)
       );
    }

    public function toClear(){
        //posts that were started 30 minutes ago or more are broken
        $from = Carbon::now('UTC')->setTimezone(wp_timezone())->subMinutes(30)->toDateTimeString();

        return $this->wpdb->get_results(
           $this->wpdb->prepare("SELECT * FROM {$this->postsTableName} WHERE got_data = %d AND posted = %d AND time <= %s", 0, 0, $from)
        );
    }

    public function getByIds($ids){
        return $this->wpdb->get_results(
           "SELECT * FROM {$this->postsTableName} WHERE id IN(" . implode(',', $ids) . ")"
        );
    }

    public function add($data){
        $allowed  = ['project_id', 'secret_key', 'title', 'body', 'tags', 'time', 'got_data', 'posted', 'url', 'wp_post_id'];

        $filtered = array_filter(
            $data,
            fn ($key) => in_array($key, $allowed),
            ARRAY_FILTER_USE_KEY
        );

        $this->wpdb->insert( $this->postsTableName, $filtered);

        $id = $this->wpdb->insert_id;

        return $id;
    }
}