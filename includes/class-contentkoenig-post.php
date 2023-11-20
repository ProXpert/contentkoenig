<?php
use Carbon\Carbon;
global $wpdb;

class Contentkoenig_Post {
    private $posts;
    private $wpdb;
    private $wpdbPrefix ;
    private $postsTableName;
    public $post;

    public function __construct($id) {
        global $wpdb;
        $class = PLUGIN_CLASS_uhbyqy . '_Posts';
        $this->posts = new $class();
        $this->wpdb = $wpdb;
        $this->wpdbPrefix = $this->wpdb->prefix;
        $this->postsTableName = $this->wpdbPrefix . PLUGIN_SLUG_uhbyqy . '_posts';
        $this->post = $this->posts->get($id);

        if(is_null($this->post)){
            throw new Exception('Post with ID ' . $id . ' not found');
        }
    }

    public function __get($property) {
        if (property_exists($this->post, $property)) {
            if($property === 'tags'){
                return !is_null($this->post->tags) ? json_decode($this->post->tags, true) : [];
            }else{
                return $this->post->$property;
            }
        }
    }

    public function update($data){
        $allowed  = ['secret_key', 'title', 'body', 'tags', 'time', 'got_data', 'posted', 'url', 'wp_post_id'];

        $filtered = array_filter(
            $data,
            fn ($key) => in_array($key, $allowed),
            ARRAY_FILTER_USE_KEY
        );

        $this->wpdb->update( $this->postsTableName, $filtered, ['id' => $this->post->id] );
    }

    public function received($receivedData){
        @set_time_limit(180);

        $class = PLUGIN_CLASS_uhbyqy . '_Project';
        $project = new $class($this->project_id);

        if($receivedData['status'] === 'article_create_error' || $receivedData['article']['status'] === 'error'){
            //there was an error creating this article. Delete this post and set project status so it is tried again next post day
            $postingDay = $project->nextPostDay(Carbon::now('UTC')->setTimezone(wp_timezone())->addDay());

            $this->posts->delete($this->id);

            $project->update([
                'status' => 'idle',
                'next_post' => $postingDay->toDateTimeString()
            ]);

            return ['posted' => false];
        }

        if(is_null($project->authors)){
            $author = null;
        }else{
            $authors = json_decode($project->authors);
            $author = $authors[array_rand($authors)];
        }

        if(is_null($project->categories)){
            $category = null;
        }else{
            $categories = json_decode($project->categories);
            $category = count($categories) > 0 ? $categories[array_rand($categories)] : null;
        }

        $images_uploaded = [];

        //need to check for images and upload locally
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->encoding = 'utf-8';
        $temp_body = htmlspecialchars_decode( htmlentities( html_entity_decode( $receivedData['article']['body'] ) ) );
        $dom->loadHTML($temp_body);

        $images = $dom->getElementsByTagName('img');

        foreach($images as $image) {
            $imgSrc = $image->getAttribute('src');
            //some WP installations escape the quote so remove them
            $imgSrc = str_replace('%5c%22', '', $imgSrc);

            $upload_image_id = $this->uploadImageFromUrl($imgSrc);
            if ( !is_wp_error( $upload_image_id ) ) {
                array_push($images_uploaded, $upload_image_id );

                $image_url = wp_get_attachment_image_url($upload_image_id, 'large ');
                $image_url = str_replace('%5c%22', '', $image_url);

                if($image_url){
                    $image->setAttribute('src', $image_url);
                }else{
                    //remove the image from source
                    $figure = $image->parentNode;
                    $figureParent = $image->parentNode->parentNode;
                    $figureParent->removeChild($figure);
                }
            }else{
                //remove the image from source
                $figure = $image->parentNode;
                $figureParent = $image->parentNode->parentNode;
                $figureParent->removeChild($figure);
            }
        }

        //build a mock document and extract the body. Previously used flags to omit body and doctype but caused formatting issues (not ending H2s)
        $mock = new DOMDocument;
        $body = $dom->getElementsByTagName('body')->item(0);
        foreach ($body->childNodes as $child){
            $mock->appendChild($mock->importNode($child, true));
        }

        $receivedData['article']['body'] = $mock->saveHTML();

        //$receivedData['article']['body'] = wp_filter_post_kses($receivedData['article']['body']);
        $receivedData['article']['title'] = sanitize_text_field($receivedData['article']['title']);

        $insert_post = wp_insert_post([
            'post_content' => $receivedData['article']['body'],
            'post_title' => $receivedData['article']['title'],
            'tags_input' => isset($receivedData['article']['tags']) ? $receivedData['article']['tags'] : [],
            'post_status' => $project->post_type,
            'post_author' => $author,
            'post_category' => [$category]
        ]);

        if ( is_wp_error( $insert_post ) ) {
            //some kind of error making this post so try again next post day
            $postingDay = $project->nextPostDay(Carbon::now('UTC')->setTimezone(wp_timezone())->addDay());

            $this->posts->delete($this->id);

            $project->update([
                'status' => 'idle',
                'next_post' => $postingDay->toDateTimeString()
            ]);

            return ['posted' => false];
        }

        if(count($images_uploaded) > 0){
            set_post_thumbnail($insert_post, $images_uploaded[0]);
        }

        $post_url = get_permalink($insert_post);

        $receivedData['article']['got_data'] = true;
        $receivedData['article']['posted'] = true;
        $receivedData['article']['tags'] = isset($receivedData['article']['tags']) ? json_encode($receivedData['article']['tags']) : json_encode([]);
        $receivedData['article']['url'] = $post_url;
        $receivedData['article']['wp_post_id'] = $insert_post;
        $this->update($receivedData['article']);

        $previous_total_posts_made = $project->posts_made;
        $new_total_posts_made = $previous_total_posts_made + 1;

        $project->update(['posts_made' => $new_total_posts_made]);

        if($new_total_posts_made >= $project->max_posts_total){
            //project complete
            $project->update(['status' => 'finished', 'active' => 0]);

            return ['posted' => true, 'url' => $post_url];
        }

        $next_post = $project->nextPostDate();

        $project->update(['status' => 'idle', 'next_post' => $next_post]);

        return ['posted' => true, 'url' => $post_url];
    }

    public function uploadImageFromUrl($image_url){
        if ( ! function_exists( 'download_url' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        if ( ! function_exists( 'media_handle_sideload' ) ) {
            require_once ABSPATH . 'wp-admin/includes/admin.php';
        }

        //download image to temp path
        $temp_path = download_url( $image_url );

        if ( is_wp_error( $temp_path ) ) {
            return $temp_path;
        }

        $file_array  = [
            'name' => wp_basename( $image_url ),
            'tmp_name' => $temp_path
        ];

        if(str_contains($file_array['name'], '?')){
            $file_array['name'] = substr($file_array['name'], 0, strpos($file_array['name'], '?'));
        }

        $image_id = media_handle_sideload( $file_array, 0 );

        if ( is_wp_error( $image_id ) ) {
            @unlink($temp_path);
        }

        return $image_id;
    }

    public function addRelatedLinks($allProjects = false, $relatedToAdd = 1){
        $relatedAdded = 0;

        //get the tags belonging to this post which also exist and are linkable in the post body
        $tags = $this->getTagsInBody($this->body, $this->tags);
        shuffle($tags);

        $postBody = $this->body;

        if(count($tags) <= 0){
            return $postBody;
        }

        //keep track of posts to exclude from query. If adding multiple links then we want to link to different posts
        $excludeIds = [intval($this->id)];

        //keep looping while we have tags left to use or we have met the quota for related posts to find
        foreach($tags as $tag){
            if($relatedAdded >= $relatedToAdd){
                //added all the links required
                return $postBody;
            }

            $query = 'SELECT * FROM ' . $this->postsTableName . ' WHERE id NOT IN (' . implode(',', $excludeIds) . ') AND tags LIKE %s AND got_data = %d AND posted = %d';
            if(!$allProjects){
                $query .= ' AND project_id = ' . $this->project_id;
            }
            $query .= ' ORDER BY RAND()';

            $postContainingTag = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    $query, '%' . $this->wpdb->esc_like( '"' . $tag . '"' ) . '%', 1, 1
                )
            );

            if(!is_null($postContainingTag)){
                //insert the link, might fail if the body has changed so check response
                $addLink = $this->addLink($postBody, $tag, $postContainingTag->url);

                if($addLink['added']){
                    array_push($excludeIds, $postContainingTag->id);

                    $postBody = $addLink['body'];
                    $relatedAdded++;
                }
            }
        }

        return $postBody;
    }

    public function addTargetLinks($targets = [], $targetsToAdd = 1){
        $targetsAdded = 0;

        $postBody = $this->body;

        shuffle($targets);

        if(count($targets) <= 0){
            return $postBody;
        }

        if($targetsToAdd >= count($targets)){
            //do nothing, use all targets
        }else{
            $targets = array_splice($targets, 0, $targetsToAdd);
        }

        foreach($targets as $target){
            if($targetsAdded >= $targetsToAdd){
                //added all the links required
                return $postBody;
            }

            $targetUrl = $target['url'];
            $targetKeywords = $target['keywords'];
            shuffle($targetKeywords);

            foreach($targetKeywords as $targetKeyword){
                $addLink = $this->addLink($postBody, $targetKeyword, $targetUrl);

                if($addLink['added']){
                    //increment added counter and break out of keyword loop because link was added successfully
                    $targetsAdded++;
                    $postBody = $addLink['body'];
                    break;
                }
            }
        }

        return $postBody;
    }

    private function addLink($body, $anchor, $url){
        $anchor = strtolower($anchor);

        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML($body, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $x = new DOMXPath($dom);
        $t = $x->evaluate("//text()");

        $pregFind = '/\b(' . $anchor . ')\b/i';

        $sectionMatchesCount = 0;

        foreach($t as $textNode) {
            //ignore a tags and headings because we don't want to link inside them
            if( $textNode->parentNode->tagName == "a" || strtolower(substr($textNode->parentNode->tagName, 0, 1)) == "h") {
                continue;
            }

            $sections = preg_split( $pregFind, $textNode->nodeValue, null, PREG_SPLIT_DELIM_CAPTURE);

            $sectionMatches = array_filter($sections, function($section) use ($anchor) {
                return strtolower($section) === $anchor;
            });

            $sectionMatchesCount += count($sectionMatches);
        }

        if($sectionMatchesCount <= 0){
            return [
                'added' => false,
                'body' => $body
            ];
        }

        $sectionMatchToUse = rand(0, $sectionMatchesCount - 1);
        $sectionMatchIndex = 0;
        $linkAdded = false;

        foreach($t as $textNode) {
            //ignore a tags and headings because we don't want to link inside them
            if( $textNode->parentNode->tagName == "a" || strtolower(substr($textNode->parentNode->tagName, 0, 1)) == "h") {
                continue;
            }

            $sections = preg_split( $pregFind, $textNode->nodeValue, null, PREG_SPLIT_DELIM_CAPTURE);

            $parentNode = $textNode->parentNode;

            foreach($sections as $section) {
                if(strtolower($section) !== $anchor) {
                    $parentNode->insertBefore( $dom->createTextNode($section), $textNode );
                    continue;
                }

                if($sectionMatchIndex < $sectionMatchToUse){
                    $sectionMatchIndex++;
                    $parentNode->insertBefore( $dom->createTextNode($section), $textNode );
                    continue;
                }else if($sectionMatchIndex > $sectionMatchToUse){
                    $parentNode->insertBefore( $dom->createTextNode($section), $textNode );
                    continue;
                }else{
                    $sectionMatchIndex++;

                    $a = $dom->createElement('a', $section);
                    $a->setAttribute('href', $url);
                    $a->setAttribute('target', '_blank');
                    $parentNode->insertBefore( $a, $textNode );

                    $linkAdded = true;
                }
            }
            $parentNode->removeChild( $textNode );
        }

        return [
            'added' => $linkAdded,
            'body' => $dom->saveHTML()
        ];
    }

    private function getTagsInBody($body, $tags){
        $tagsInBody = [];

        //lowercase the tags
        array_walk($tags, function(&$value){
          $value = strtolower($value);
        });

        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML($body, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $x = new DOMXPath($dom);
        $t = $x->evaluate("//text()");

        foreach($tags as $tag){
            foreach($t as $textNode) {
                //ignore a tags and headings because we don't want to link inside them
                if( $textNode->parentNode->tagName == "a" || strtolower(substr($textNode->parentNode->tagName, 0, 1)) == "h") {
                    continue;
                }

                $pregFind = '/\b(' . $tag . ')\b/i';

                $sections = preg_split( $pregFind, $textNode->nodeValue, null, PREG_SPLIT_DELIM_CAPTURE);

                $parentNode = $textNode->parentNode;

                foreach($sections as $section) {
                    if(!in_array(strtolower($section), $tags)) {
                        continue;
                    }

                    if(!in_array(strtolower($section), $tagsInBody)){
                        array_push($tagsInBody, strtolower($section));
                    }
                }
            }
        }

        return $tagsInBody;
    }
}