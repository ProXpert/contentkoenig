<?php
use Carbon\Carbon;

class Contentkoenig_Public {
	private $plugin_name;
	private $version;
    private $shared;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;

        $class = PLUGIN_CLASS_uhbyqy . '_Shared';
        $this->shared = new $class();

        $class = PLUGIN_CLASS_uhbyqy . '_Projects';
        $this->projects = new $class();

        $class = PLUGIN_CLASS_uhbyqy . '_Posts';
        $this->posts = new $class();

        $class = PLUGIN_CLASS_uhbyqy . '_Api';
        $this->api = new $class();
	}

	public function enqueue_styles() {

	}

	public function enqueue_scripts() {
	}

    public function add_cron_intervals($schedules) {
        $schedules['every_minute'] = array(
            'interval'  => 60,
            'display'   => __( 'Every Minute', PLUGIN_SLUG_uhbyqy )
        );

        $schedules['daily'] = array(
            'interval'  => 86400,
            'display'   => __( 'Every Day', PLUGIN_SLUG_uhbyqy )
        );

        return $schedules;
    }

    public function cron_licence_check(){
        $licence_key = get_option(PLUGIN_SLUG_uhbyqy . '_licence_key');
        $uid = get_option(PLUGIN_SLUG_uhbyqy . '_uid');

        if($licence_key === ''){
            //if no licence key then deactivate
            update_option(PLUGIN_SLUG_uhbyqy . '_active', false);
            return;
        }

        $class = PLUGIN_CLASS_uhbyqy . '_Api';
        $api = new $class($uid);
        $response = $api->licenceCheck($licence_key, false);

        if($response['error'] === false){
            update_option(PLUGIN_SLUG_uhbyqy . '_active', true);
            update_option(PLUGIN_SLUG_uhbyqy . '_uid', $response['response']['uid']);
            update_option(PLUGIN_SLUG_uhbyqy . '_authority_link', $response['response']['limits']['AuthorityLink'] ?? false);
        }else{
            update_option(PLUGIN_SLUG_uhbyqy . '_active', false);
            update_option(PLUGIN_SLUG_uhbyqy . '_uid', '');
            update_option(PLUGIN_SLUG_uhbyqy . '_authority_link', '');
        }
    }

    public function cron_make_posts(){
        $projects_due = $this->projects->toPost();

        foreach($projects_due as $project_due){
            $class = PLUGIN_CLASS_uhbyqy . '_Project';
            $project = new $class($project_due->id);
            $project->initPost();
        }
    }

    public function cron_clear_posts(){
        $posts = $this->posts->toClear();

        foreach($posts as $post){
            $class = PLUGIN_CLASS_uhbyqy . '_Project';
            $project = new $class($post->project_id);

            $postingDay = $project->nextPostDay(Carbon::now('UTC')->setTimezone(wp_timezone())->addDay());

            $this->posts->delete($post->id);

            $project->update([
                'status' => 'idle',
                'next_post' => $postingDay->toDateTimeString()
            ]);
        }
    }

    public function receive_post($request){
        $project_id = $request->get_param( 'project_id' );
        $post_id = $request->get_param( 'post_id' );
        $secret_key = $request->get_param( 'secret_key' );

        if(is_null($project_id) || is_null($post_id) || is_null($secret_key)){
            return new WP_Error(
                '[' . PLUGIN_SLUG_uhbyqy . '] ' . 400,
                'Param error',
                array(
                     'status' => 400,
                )
            );
        }

        $project = $this->projects->get($project_id);
        $post = $this->posts->getAlt($project_id, $post_id, $secret_key, 0, 0);

        if(is_null($post) || is_null($project)){
            return new WP_Error(
                '[' . PLUGIN_SLUG_uhbyqy . '] ' . 404,
                'No post found error',
                array(
                     'status' => 404,
                )
            );
        }

        $class = PLUGIN_CLASS_uhbyqy . '_Post';
        $post = new $class($post->id);
        $post_data = $request->get_json_params();
        $post_received = $post->received($post_data);

        $class = PLUGIN_CLASS_uhbyqy . '_Project';
        $project = new $class($project_id);

        //get a fresh instance of post so we know data is up to date
        $class = PLUGIN_CLASS_uhbyqy . '_Post';
        $post = null;
        $post = new $class($post_id);
        $project->doTargetLinking($post);

        //and again
        $class = PLUGIN_CLASS_uhbyqy . '_Post';
        $post = null;
        $post = new $class($post_id);
        $project->doInterlinking($post);

        echo json_encode($post_received);
    }
}
