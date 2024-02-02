<?php
use Carbon\Carbon;
global $wpdb;

class Contentkoenig_Projects {
    private $wpdb;
    private $wpdbPrefix;
    private $projectsTableName;
    private $postsTableName;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->wpdbPrefix = $this->wpdb->prefix;
        $this->projectsTableName = $this->wpdbPrefix . PLUGIN_SLUG_uhbyqy . '_projects';
        $this->postsTableName = $this->wpdbPrefix . PLUGIN_SLUG_uhbyqy . '_posts';
    }

    public function all(){
        return $this->wpdb->get_results(
           "SELECT * FROM {$this->projectsTableName} ORDER BY name ASC"
        );
    }

    public function toPost(){
        $now = Carbon::now('UTC')->toDateTimeString();

        return $this->wpdb->get_results(
           $this->wpdb->prepare("SELECT * FROM {$this->projectsTableName} WHERE next_post <= %s AND status=%s AND active=%d", $now, 'idle', 1)
        );
    }

    public function get($id){
        return $this->wpdb->get_row(
           $this->wpdb->prepare("SELECT * FROM {$this->projectsTableName} WHERE id =%d", $id)
        );
    }

    public function delete($id){
        return $this->wpdb->delete($this->projectsTableName, ['id' => $id]);
    }

    public function getByIds($ids){
        return $this->wpdb->get_results(
           "SELECT * FROM {$this->projectsTableName} WHERE id IN(" . implode(',', $ids) . ")"
        );
    }

    public function add($data){
        $allowed  = ['name', 'language', 'next_post', 'posts_made', 'max_posts_per_day', 'max_posts_total', 'post_days', 'status', 'post_type', 'active', 'authors', 'categories', 'prompt_type', 'subject', 'topics', 'post_time_start', 'post_time_end', 'interlinking', 'interlinking_all_projects', 'interlinking_count', 'target_linking', 'target_linking_targets', 'target_linking_percentage'];

        $filtered = array_filter(
            $data,
            fn ($key) => in_array($key, $allowed),
            ARRAY_FILTER_USE_KEY
        );

        if($filtered['prompt_type'] === 'standard:subject'){
            $filtered['topics'] = null;
        }

        $this->wpdb->insert( $this->projectsTableName, $filtered);

        $id = $this->wpdb->insert_id;

        return $id;
    }
}