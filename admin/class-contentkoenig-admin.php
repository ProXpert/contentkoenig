<?php
require dirname( __FILE__ ) . '/class-' . PLUGIN_SLUG_uhbyqy . '-admin-posts-list-table.php';
require dirname( __FILE__ ) . '/class-' . PLUGIN_SLUG_uhbyqy . '-admin-projects-list-table.php';

class Contentkoenig_Admin {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;

		$class = PLUGIN_CLASS_uhbyqy . '_Shared';
        $this->shared = new $class();

        $class = PLUGIN_CLASS_uhbyqy . '_Projects';
        $this->projects = new $class();

        $uid = get_option(PLUGIN_SLUG_uhbyqy . '_uid');
        $class = PLUGIN_CLASS_uhbyqy . '_Api';
        $this->api = new $class($uid);

        /*$class = PLUGIN_CLASS_uhbyqy . '_Project';
        $project = new $class(1);
        $class = PLUGIN_CLASS_uhbyqy . '_Post';
        $post = new $class(1);
        $project->doTargetLinking($post);
        die();*/
	}

	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/' . PLUGIN_SLUG_uhbyqy . '-admin.css', array(), $this->version, 'all' );
        wp_enqueue_style( $this->plugin_name . '-fv', plugin_dir_url( __FILE__ ) . 'css/formValidation.min.css', array(), $this->version, 'all' );
		wp_enqueue_style( $this->plugin_name . '-select2', plugin_dir_url( __FILE__ ) . 'css/select2.min.css', array(), $this->version, 'all' );
		wp_enqueue_style( $this->plugin_name . '-modal', plugin_dir_url( __FILE__ ) . 'css/jquery.modal.css', array(), $this->version, 'all' );
        wp_enqueue_style( $this->plugin_name . '-jquery-ui', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.2/themes/smoothness/jquery-ui.css', array(), $this->version, 'all' );
	}

	public function enqueue_scripts() {
		wp_register_script( $this->plugin_name . 'admin_js', plugin_dir_url( __FILE__ ) . 'js/' . PLUGIN_SLUG_uhbyqy . '-admin.js', array( 'jquery' ), $this->version, false );
        wp_enqueue_script($this->plugin_name . 'admin_js');
        wp_localize_script($this->plugin_name . 'admin_js', 'bloggerCustomVars', [
            'pluginSlug' => PLUGIN_SLUG_uhbyqy
        ]);

        wp_enqueue_script( $this->plugin_name . '-fa', 'https://kit.fontawesome.com/9a9670796a.js', $this->version, false );
        wp_register_script( $this->plugin_name . '-video-hosting', 'https://player.stoodaio.host/embed.js', array(), $this->version, false );
        wp_enqueue_script( $this->plugin_name . '-fv', plugin_dir_url( __FILE__ ) . 'js/fv/FormValidation.full.min.js', array(), $this->version, false );
        wp_enqueue_script( $this->plugin_name . '-select2', plugin_dir_url( __FILE__ ) . 'js/select2.min.js', array('jquery'), $this->version, false );
		wp_enqueue_script( $this->plugin_name . '-modal', plugin_dir_url( __FILE__ ) . 'js/jquery.modal.js', array( 'jquery' ), $this->version, true );
        wp_register_script( $this->plugin_name . '-embedly', 'https://cdn.embedly.com/widgets/platform.js', $this->version, false );
        wp_enqueue_script( 'jquery-ui-slider' );
	}

    public function upgrade_check(){
        $class = PLUGIN_CLASS_uhbyqy . '_Updater';
        $plugin_version = $this->version;
        $existing_version = get_option(PLUGIN_SLUG_uhbyqy . '_version');
        $updater = new $class($plugin_version, $existing_version, PLUGIN_SLUG_uhbyqy);

        if($updater->shouldUpdate()){
            $updater->update();
        }
    }

    public function add_notices(){
        global $pagenow;
        $admin_pages = [ 'admin.php', 'plugins.php' ];

        if ( in_array( $pagenow, $admin_pages ) ) {
            $licence_key = get_option(PLUGIN_SLUG_uhbyqy . '_licence_key');
            $active = get_option(PLUGIN_SLUG_uhbyqy . '_active');

            if($licence_key === ''){
                ?>
                <div class="notice notice-warning">
                    <p><a href="<?php echo menu_page_url(PLUGIN_SLUG_uhbyqy . '-admin-licence-key', false) ?>">Enter your licence key</a> to start using <?php echo PLUGIN_NAME_uhbyqy ?></p>
                </div>
                <?php
            }else if(!$active){
                ?>
                <div class="notice notice-warning">
                    <p>Your <?php echo PLUGIN_NAME_uhbyqy ?> account is not active, do you need to <a href="<?php echo menu_page_url(PLUGIN_SLUG_uhbyqy. '-admin-licence-key', false) ?>">update your licence key</a>?</p>
                </div>
                <?php
            }
        }

        if($pagenow === 'admin.php' && isset($_GET[PLUGIN_SLUG_uhbyqy . '_notice'])){
            if($_GET[PLUGIN_SLUG_uhbyqy . '_notice'] === 'licence_key_updated'){
            ?>
            <div class="notice notice-success">
                <p>Your licence key was successfully linked to this site!</p>
            </div>
            <?php
            }
        }
    }

    public function admin_redirects(){
        global $pagenow;

        if(
            $pagenow == 'admin.php' &&
            isset($_GET['page']) &&
            $_GET['page'] !== PLUGIN_SLUG_uhbyqy. '-admin-licence-key' &&
            $_GET['page'] !== PLUGIN_SLUG_uhbyqy. '-admin' &&
            str_contains($_GET['page'], PLUGIN_SLUG_uhbyqy. '-admin')
        ){
            $licence_key = get_option(PLUGIN_SLUG_uhbyqy . '_licence_key');
            $active = get_option(PLUGIN_SLUG_uhbyqy . '_active');
            if(is_null($licence_key) || $licence_key === '' || !$active){
                wp_redirect(menu_page_url(PLUGIN_SLUG_uhbyqy . '-admin-licence-key', false));
                exit;
            }
        }
    }

    public function admin_menu(){
        add_menu_page(
            PLUGIN_NAME_uhbyqy,
            PLUGIN_NAME_uhbyqy,
            'manage_options',
            PLUGIN_SLUG_uhbyqy . '-admin',
            [$this, 'render_admin_main_page']
        );

        add_submenu_page(
            PLUGIN_SLUG_uhbyqy . '-admin',
            PLUGIN_NAME_uhbyqy . ' Projects',
            'Projects',
            'manage_options',
            PLUGIN_SLUG_uhbyqy . '-admin-projects',
            [$this, 'render_admin_projects_page']
        );

        add_submenu_page(
            PLUGIN_SLUG_uhbyqy . '-admin',
            PLUGIN_NAME_uhbyqy . ' Posts',
            'Posts',
            'manage_options',
            PLUGIN_SLUG_uhbyqy . '-admin-posts',
            [$this, 'render_admin_posts_page']
        );

        add_submenu_page(
            PLUGIN_SLUG_uhbyqy . '-admin',
            PLUGIN_NAME_uhbyqy . ' Licence Key',
            'Licence Key',
            'manage_options',
            PLUGIN_SLUG_uhbyqy . '-admin-licence-key',
            [$this, 'render_admin_licence_key_page']
        );

        add_submenu_page(
            PLUGIN_SLUG_uhbyqy . '-admin',
            PLUGIN_NAME_uhbyqy . ' Settings',
            'Settings',
            'manage_options',
            PLUGIN_SLUG_uhbyqy . '-admin-settings',
            [$this, 'render_admin_settings_page']
        );
    }

    public function render_admin_main_page(){
        wp_enqueue_script($this->plugin_name . '-embedly');
        wp_enqueue_script( $this->plugin_name . '-video-hosting');

        if(file_exists(dirname( __FILE__ ) . '/class-' . PLUGIN_SLUG_uhbyqy . '-admin-main-page-default.php')){
            require dirname( __FILE__ ) . '/class-' . PLUGIN_SLUG_uhbyqy . '-admin-main-page-default.php';
        }else{
            require dirname( __FILE__ ) . '/class-' . PLUGIN_SLUG_uhbyqy . '-admin-main-page.php';
        }
    }

    public function render_admin_projects_page(){
        $action = isset($_GET['action']) ? $_GET['action'] : 'index';

        switch(strtolower($action)){
            case 'index':
            case 'delete':
            case 'resume':
            case 'pause':
            case 'post':
                require dirname( __FILE__ ) . '/class-' . PLUGIN_SLUG_uhbyqy . '-admin-projects-page-index.php';
                break;
            case 'add':
            case 'edit':
                require dirname( __FILE__ ) . '/class-' . PLUGIN_SLUG_uhbyqy . '-admin-projects-page-add-edit.php';
                break;
        }
    }

    public function render_admin_posts_page(){
        require dirname( __FILE__ ) . '/class-' . PLUGIN_SLUG_uhbyqy . '-admin-posts-page-index.php';
    }

    public function render_admin_licence_key_page(){
        require dirname( __FILE__ ) . '/class-' . PLUGIN_SLUG_uhbyqy . '-admin-licence-key-page.php';
    }

    public function render_admin_settings_page(){
        if(file_exists(dirname( __FILE__ ) . '/class-' . PLUGIN_SLUG_uhbyqy . '-admin-settings-page-default.php')){
            require dirname( __FILE__ ) . '/class-' . PLUGIN_SLUG_uhbyqy . '-admin-settings-page-default.php';
        }else{
            require dirname( __FILE__ ) . '/class-' . PLUGIN_SLUG_uhbyqy . '-admin-settings-page.php';
        }
    }

    public function ajax_save_licence_key(){
        $licence_key = $_POST['licence_key'];

        $uid = get_option(PLUGIN_SLUG_uhbyqy . '_uid');
        $class = PLUGIN_CLASS_uhbyqy . '_Api';
        $api = new $class($uid);
        $response = $api->licenceCheck($licence_key, true);

        update_option(PLUGIN_SLUG_uhbyqy . '_licence_key', sanitize_text_field($licence_key));

        if($response['error'] === false){
            update_option(PLUGIN_SLUG_uhbyqy . '_active', true);
            update_option(PLUGIN_SLUG_uhbyqy . '_uid', $response['response']['uid']);
            update_option(PLUGIN_SLUG_uhbyqy . '_authority_link', isset($response['response']['limits']['AuthorityLink']) && $response['response']['limits']['AuthorityLink'] === true);
        }else{
            update_option(PLUGIN_SLUG_uhbyqy . '_active', false);
            update_option(PLUGIN_SLUG_uhbyqy . '_uid', '');
            update_option(PLUGIN_SLUG_uhbyqy . '_authority_link', '');
        }

        echo json_encode($response);
        wp_die();
    }

    public function ajax_check_rewriter_key(){
        $key = $_REQUEST['rewriter_api_key'];
        $data = wp_remote_get("https://detectorbypass.com/v1/user/auth",[
            'headers' => [
                'Content-Type' => 'application/json; charset=utf-8',
                'API_KEY' => $key
            ],
            'timeout' => 15,
        ]);
        if ( is_wp_error($data) ) {
            echo json_encode([
                'valid' => false,
            ]);
        }

        echo json_encode([
            'valid' => $data['response']['code'] === 200,
        ]);
        wp_die();
    }

    public function ajax_update_settings(){
        add_option(PLUGIN_SLUG_uhbyqy . '_rewriter_api_key', '');
        $rewriterApiKeyNew = trim($_POST['rewriterApiKey']);
        $rewriterApiKeyOption = get_option(PLUGIN_SLUG_uhbyqy . '_rewriter_api_key');

        if($rewriterApiKeyOption === false){
            add_option(PLUGIN_SLUG_uhbyqy . '_rewriter_api_key', $rewriterApiKeyNew);
        }else{
            update_option(PLUGIN_SLUG_uhbyqy . '_rewriter_api_key', $rewriterApiKeyNew);
        }

        wp_die();
    }

    public function ajax_update_project(){
        $id = $_POST['id'];
        $name = $_POST['name'];
        $language = $_POST['language'];
        $max_posts_per_day = $_POST['max_posts_per_day'];
        $max_posts_total = $_POST['max_posts_total'];
        $post_days = $_POST['post_days'];
        $post_type = $_POST['post_type'];
        $active = intval($_POST['active']);
        $authors = $_POST['authors'];
	    $categories = isset($_POST['categories']) ? $_POST['categories'] : [];
        $prompt_type = $_POST['prompt_type'];
        $subject = $_POST['subject'];
        $topics = $_POST['topics'];
        $post_time_start = $_POST['post_time_start'];
        $post_time_end = $_POST['post_time_end'];
        $interlinking = $_POST['interlinking'];
        $interlinking_all_projects = $_POST['interlinking_all_projects'];
        $interlinking_count = $_POST['interlinking_count'];
        $target_linking = $_POST['target_linking'];
        $target_linking_targets = isset($_POST['target_linking_targets']) ? json_encode($_POST['target_linking_targets']) : '[]';
        $target_linking_percentage = $_POST['target_linking_percentage'];
        $rewrite = $_POST['rewrite'];

        $class = PLUGIN_CLASS_uhbyqy . '_Project';
        $project = new $class(intval($id));

        $data = [
            'name' => $name,
            'language' => $language,
            'max_posts_per_day' => $max_posts_per_day,
            'max_posts_total' => $max_posts_total,
            'post_days' => json_encode($post_days),
            'post_type' => $post_type,
            'active' => $active,
            'authors' => json_encode($authors),
            'categories' => json_encode($categories),
            'prompt_type' => $prompt_type,
            'subject' => $subject,
            'topics' => $topics,
            'post_time_start' => $post_time_start,
            'post_time_end' => $post_time_end,
            'interlinking' => $interlinking,
            'interlinking_all_projects' => $interlinking_all_projects,
            'interlinking_count' => $interlinking_count,
            'target_linking' => $target_linking,
            'target_linking_targets' => $target_linking_targets,
            'target_linking_percentage' => $target_linking_percentage,
            'rewrite' => $rewrite,
        ];

        $getNextPostDate = $project->update($data);

        if($getNewPostDate){
            //instantiate this instance again in case data has changed
            $class = PLUGIN_CLASS_uhbyqy . '_Project';
            $project = new $class(intval($id));
            $project->updateNextPostDate();
        }

        wp_die();
    }

    public function ajax_add_project(){
	    $name = $_POST['name'];
	    $language = $_POST['language'];
	    $max_posts_per_day = $_POST['max_posts_per_day'];
	    $max_posts_total = $_POST['max_posts_total'];
	    $post_days = $_POST['post_days'];
	    $post_type = $_POST['post_type'];
	    $active = intval($_POST['active']);
	    $authors = $_POST['authors'];
	    $categories = isset($_POST['categories']) ? $_POST['categories'] : [];
	    $prompt_type = $_POST['prompt_type'];
	    $subject = $_POST['subject'];
	    $topics = $_POST['topics'];
	    $post_time_start = $_POST['post_time_start'];
	    $post_time_end = $_POST['post_time_end'];
        $interlinking = $_POST['interlinking'];
        $interlinking_all_projects = $_POST['interlinking_all_projects'];
        $interlinking_count = $_POST['interlinking_count'];
        $target_linking = $_POST['target_linking'];
        $target_linking_targets = isset($_POST['target_linking_targets']) ? json_encode($_POST['target_linking_targets']) : '[]';
        $target_linking_percentage = $_POST['target_linking_percentage'];
        $rewrite = $_POST['rewrite'];

	    $id = $this->projects->add([
            'name' => $name,
            'language' => $language,
            'posts_made' => 0,
            'max_posts_per_day' => $max_posts_per_day,
            'max_posts_total' => $max_posts_total,
            'post_days' => json_encode($post_days),
            'status' => 'starting',
            'post_type' => $post_type,
            'active' => $active,
            'authors' => json_encode($authors),
            'categories' => json_encode($categories),
            'prompt_type' => $prompt_type,
            'subject' => $subject,
            'topics' => $topics,
            'post_time_start' => $post_time_start,
            'post_time_end' => $post_time_end,
            'interlinking' => $interlinking,
            'interlinking_all_projects' => $interlinking_all_projects,
            'interlinking_count' => $interlinking_count,
            'target_linking' => $target_linking,
            'target_linking_targets' => $target_linking_targets,
            'target_linking_percentage' => $target_linking_percentage,
            'rewrite' => $rewrite,
        ]);

        $class = PLUGIN_CLASS_uhbyqy . '_Project';
        $project = new $class(intval($id));
        $nextPostDate = $project->nextPostDate();

        $project->update([
            'status' => 'idle',
            'next_post' => $nextPostDate
        ]);

        wp_die();
    }

    public function ajax_authority_links(){
        $query = $_POST['query'];

        $uid = get_option(PLUGIN_SLUG_uhbyqy . '_uid');
        $class = PLUGIN_CLASS_uhbyqy . '_Api';
        $api = new $class($uid);

        $response = $api->authorityLinks($query);

        echo json_encode($response);
        wp_die();
    }
}
