<?php
use Carbon\Carbon;
global $wpdb;

class Contentkoenig_Project {
    private $api;
    private $projects;
    private $posts;
    private $wpdb;
    private $wpdbPrefix ;
    private $postsTableName;
    private $projectsTableName;
    private $debug = false;
    public $project;

    public function __construct($id) {
        global $wpdb;
        $uid = get_option(PLUGIN_SLUG_uhbyqy . '_uid');
        $class = PLUGIN_CLASS_uhbyqy . '_Api';
        $this->api = new $class($uid);

        $class = PLUGIN_CLASS_uhbyqy . '_Projects';
        $this->projects = new $class();
        $class = PLUGIN_CLASS_uhbyqy . '_Posts';
        $this->posts = new $class();
        $this->wpdb = $wpdb;
        $this->wpdbPrefix = $this->wpdb->prefix;
        $this->postsTableName = $this->wpdbPrefix . PLUGIN_SLUG_uhbyqy . '_posts';
        $this->projectsTableName = $this->wpdbPrefix . PLUGIN_SLUG_uhbyqy . '_projects';
        $this->project = $this->projects->get($id);

        if(is_null($this->project)){
            throw new Exception('Projects with ID ' . $id . ' not found');
        }
    }

    public function __get($property) {
        if (property_exists($this->project, $property)) {
            if($property === 'post_days'){
                return !is_null($this->project->post_days) ? json_decode($this->project->post_days, true) : ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
            }else if($property === 'target_linking_targets'){
                return !is_null($this->project->target_linking_targets) ? json_decode($this->project->target_linking_targets, true) : [];
            }else if($property === 'log'){
                return !is_null($this->project->log) ? json_decode($this->project->log, true) : [];
            }else{
                return $this->project->$property;
            }
        }
    }

    public function update($data){
        $allowed  = ['name', 'language', 'next_post', 'posts_made', 'max_posts_per_day', 'max_posts_total', 'post_days', 'status', 'post_type', 'active', 'authors', 'categories', 'prompt_type', 'subject', 'topics', 'post_time_start', 'post_time_end', 'interlinking', 'interlinking_all_projects', 'interlinking_count', 'target_linking', 'target_linking_targets', 'target_linking_percentage'];

        $filtered = array_filter(
            $data,
            fn ($key) => in_array($key, $allowed),
            ARRAY_FILTER_USE_KEY
        );

        if(isset($filtered['prompt_type']) && $filtered['prompt_type'] === 'standard:subject'){
            $filtered['topics'] = null;
        }

        $getNewPostDate = false;

        if($this->project->active == 0 && isset($filtered['active']) && $filtered['active'] == 1){
            if( $this->project->posts_made < $this->project->max_posts_total){
                //project is being made active so need to update next_post
                $getNewPostDate = true;
                $filtered['status'] = 'idle';
            }else{
                $filtered['active'] = 0;
            }
        }

        if(
            (isset($filtered['active']) && $filtered['active'] == 1) && (
                (isset($filtered['max_posts_per_day']) && $filtered['max_posts_per_day'] !== $this->project->max_posts_per_day) ||
                (isset($filtered['post_days']) && $filtered['post_days'] !== $this->project->post_days) ||
                (isset($filtered['post_time_start']) && $filtered['post_time_start'] !== $this->project->post_time_start) ||
                (isset($filtered['post_time_end']) && $filtered['post_time_end'] !== $this->project->post_time_end)
            )
        ){
            $getNewPostDate = true;
        }

        if($this->debug){
            $log = $this->log;
            $timestamp = Carbon::now()->timestamp;
            $log[$timestamp] = $filtered;
            $filtered['log'] = json_encode($log);
        }

        $this->wpdb->update( $this->projectsTableName, $filtered, ['id' => $this->project->id] );

        return $getNewPostDate;
    }

    public function posts(){
        return $this->wpdb->get_results(
            $this->wpdb->prepare("SELECT * FROM {$this->postsTableName} WHERE project_id =%d ORDER BY date DESC", $this->project->id)
        );
    }

    public function postsRange($start, $end){
        return $this->wpdb->get_results(
            $this->wpdb->prepare("SELECT * FROM {$this->postsTableName} WHERE project_id =%d AND posted=%d AND time >=%s AND time <=%s", $this->project->id, 1, $start, $end)
        );
    }

    public function postsToday(){
        $startOfDay = Carbon::now('UTC')->setTimezone(wp_timezone())->startOfDay()->toDateTimeString();
        $endOfDay = Carbon::now('UTC')->setTimezone(wp_timezone())->endOfDay()->toDateTimeString();

        return $this->postsRange($startOfDay, $endOfDay);
    }

    public function isPostingDay(){
        $now = Carbon::now('UTC')->setTimezone(wp_timezone());

        foreach ($this->post_days as $postDay) {
            if($now->is($postDay)){
                return true;
            }
        }

        return false;
    }

    public function isPostingTime(){
        if(is_null($this->project->post_time_start) || is_null($this->project->post_time_end)){
            //start and/or end not set so is posting time
            return true;
        }

        $now = Carbon::now('UTC')->setTimezone(wp_timezone());
        $postTimeStart = Carbon::now('UTC')->setTimezone(wp_timezone())->startOfDay()->addMinutes($this->project->post_time_start);
        $postTimeEnd = Carbon::now('UTC')->setTimezone(wp_timezone())->startOfDay()->addMinutes($this->project->post_time_end);

        return $now->greaterThanOrEqualTo($postTimeStart) && $now->lessThanOrEqualTo($postTimeEnd);
    }

    public function nextPostDay($now = null){
        if(is_null($now)){
            $now = Carbon::now('UTC')->setTimezone(wp_timezone());
        }

        $postDays = $this->post_days;

        for ($i = 0; $i <= 10; $i++) {
            if(in_array(strtolower($now->format('l')), $postDays)){
                return $now;
            }

            $now->addDay();
        }

        //shouldn't get here
        return false;
    }

    public function updateNextPostDate(){
        $nextPostDate = $this->nextPostDate();

        $this->wpdb->update( $this->projectsTableName, [
            'next_post' => $nextPostDate
        ], ['id' => $this->project->id] );

        return $nextPostDate;
    }

    public function nextPostDate(){
        //see if we are on a posting day
        if($this->isPostingDay()){
            $postingDay = Carbon::now('UTC')->setTimezone(wp_timezone());

            //find number of posts made in that day
            $postsInDay = count($this->postsRange($postingDay->copy()->startOfDay()->toDateTimeString(), $postingDay->copy()->endOfDay()->toDateTimeString()));

            if($postsInDay >= intval($this->project->max_posts_per_day)){
                $getNextPostingDay = true;
            }else{
                //we are good to post today
                $getNextPostingDay = false;

                //start of available range is now
                if($this->isPostingTime()){
                    $startRange = Carbon::now('UTC')->setTimezone(wp_timezone());
                }else{
                    $now = Carbon::now('UTC')->setTimezone(wp_timezone());
                    $postTimeStart = Carbon::now('UTC')->setTimezone(wp_timezone())->startOfDay()->addMinutes($this->project->post_time_start);

                    if($now->lessThan($post_time_start)){
                        $startRange = Carbon::now('UTC')->setTimezone(wp_timezone())->startOfDay()->addMinutes($this->project->post_time_start);
                    }else{
                        $getNextPostingDay = true;
                    }
                }
            }
        }else{
            $getNextPostingDay = true;
        }

        if($getNextPostingDay){
            $postingDay = $this->nextPostDay(Carbon::now('UTC')->setTimezone(wp_timezone())->addDay());

            if(!is_null($this->project->post_time_start) && !is_null($this->project->post_time_end)){
                $startRange =  $postingDay->copy()->startOfDay()->addMinutes($this->project->post_time_start);
            }else{
                $startRange = $postingDay->copy()->startOfDay();
            }

            $postsInDay = 0;
        }

        if(!is_null($this->project->post_time_start) && !is_null($this->project->post_time_end)){
            $endRange =  $startRange->copy()->startOfDay()->addMinutes($this->project->post_time_end);
        }else{
            $endRange =  $startRange->copy()->endOfDay();
        }

        $timeMinutes = $startRange->diffInMinutes($endRange);
        $postsToMake = intval($this->project->max_posts_per_day) - $postsInDay;

        $nextPostMinutes = -log(rand() / getrandmax()) * $timeMinutes / $postsToMake;
        $nextPost = $startRange->copy()->addMinutes($nextPostMinutes);

        return $nextPost->setTimezone('UTC')->toDateTimeString();
    }

    public function initPost(){
        $active = get_option(PLUGIN_SLUG_uhbyqy . '_active');
        $licenceKey = get_option(PLUGIN_SLUG_uhbyqy . '_licence_key');
        $endpoint = get_rest_url(null, '/wordpress_autoblog/post');

        if(!$active || is_null($licenceKey) || $licenceKey === ''){
            return;
        }

        $secret = $this->generateRandomString(12);

        $postId = $this->posts->add([
            'project_id' => $this->project->id,
            'secret_key' => $secret,
            'title' => null,
            'body' => null,
            'tags' => null,
            'got_data' => false,
            'posted' => false
        ]);

        $callbackUrl = $endpoint . (parse_url($endpoint, PHP_URL_QUERY) ? '&' : '?') . "project_id=" . $this->project->id . "&post_id=" . $postId . "&secret_key=" . $secret;

        switch($this->project->prompt_type){
            case 'standard:subject':
                $prompt = $this->project->subject;
                break;
            case 'standard:subject_topics':
                $prompt = [$this->project->subject, $this->project->topics];
                break;
            default:
                $this->posts->delete($postId);
                return;
                break;
        }

        $language = !is_null($this->project->language) ? $this->project->language : 'en';

        $openai_key = get_option(PLUGIN_SLUG_uhbyqy . '_openai_api_key');
        $openai_key_added = $openai_key !== false && $openai_key !== '';

        $openai_temp = $openai_key_added === true ? trim($openai_key) : null;

        $response = $this->api->post($this->project->prompt_type, $prompt, $callbackUrl, $language, $openai_temp);

        if($response['error'] === true){
            if($response['response']['desc'] == 'post_limit_met'){
               //posts in last day has met or exceeded the limit, try again on next posting day
               $postingDay = $this->nextPostDay(Carbon::now('UTC')->setTimezone(wp_timezone())->addDay());

               if($postingDay === false){
                    //shouldn't happen but if it does, pause project
                    $this->posts->delete($postId);
                    $this->update([
                        'active' => 0,
                        'status' => 'idle'
                        //'status' => json_encode($response)
                    ]);
               }else{
                    $this->posts->delete($postId);
                    $this->update([
                        'next_post' => $postingDay->toDateTimeString(),
                        'status' => 'idle'
                        //'status' => json_encode($response)
                    ]);
               }
            }else{
                //some other server error creating post, pause project to be safe
                $this->posts->delete($postId);
                $this->update([
                    'active' => 0,
                    'status' => 'idle'
                    //'status' => json_encode($response)
                ]);
            }
        }else if($response['error'] === false && $response['response']['status'] !== 'article_create_waiting'){
            //some kind of error when adding post, status will be 'article_create_error'. Pause to be safe
            $this->posts->delete($postId);
            $this->update([
                'active' => 0,
                'status' => 'idle'
                //'status' => json_encode($response)
            ]);
        }else{
            //post made, waiting for callback
            $this->update([
                'status' => 'waiting'
            ]);
        }
    }

    private function generateRandomString($length = 12) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function doInterlinking($post) {
        $interlinkingEnabled = $this->interlinking === 1 || $this->interlinking === '1';
        $interlinkingAllProjects = $this->interlinking_all_projects === 1 || $this->interlinking_all_projects === '1';
        $interlinkingCount = intval($this->interlinking_count);

        if(!$interlinkingEnabled || $interlinkingCount <= 0){
            //no interlinking so end
            return;
        }

        $interlinkedBody = $post->addRelatedLinks($interlinkingAllProjects, $interlinkingCount);
        $post->update(['body' => $interlinkedBody]);
        wp_update_post([
            'ID' => $post->wp_post_id,
            'post_content' => $interlinkedBody
        ]);
    }

    public function doTargetLinking($post) {
        $targetLinkingEnabled = $this->target_linking === 1 || $this->target_linking === '1';
        $targetLinkingPercentage = intval($this->target_linking_percentage);

        if(!$targetLinkingEnabled){
            return;
        }

        if(rand(0, 100) <= $targetLinkingPercentage){
            $targets = $this->target_linking_targets;

            if(count($targets) <= 0){
                return;
            }

            $interlinkedBody = $post->addTargetLinks($targets, 1);
            $post->update(['body' => $interlinkedBody]);
            wp_update_post([
                'ID' => $post->wp_post_id,
                'post_content' => $interlinkedBody
            ]);
        }
    }
}