<?php
if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

use Carbon\Carbon;

class Contentkoenig_Admin_Projects_List_Table extends WP_List_Table
{
    private $shared;

    public function __construct() {
        // Set parent defaults.
        parent::__construct( array(
            'singular' => 'project',     // Singular name of the listed records.
            'plural'   => 'projects',    // Plural name of the listed records.
            'ajax'     => false,       // Does this table support ajax?
        ) );
    }

    public function get_columns() {
        $columns = array(
            'cb'       => '<input type="checkbox" />', // Render a checkbox instead of text.
            'name'    => _x( 'Name', 'Column label', PLUGIN_SLUG_uhbyqy ),
            'active'    => _x( 'Active', 'Column label', PLUGIN_SLUG_uhbyqy ),
            'next_post'   => _x( 'Next Post', 'Column label', PLUGIN_SLUG_uhbyqy ),
            'posts_made' => _x( 'Posts Made', 'Column label', PLUGIN_SLUG_uhbyqy ),
            'schedule' => _x( 'Schedule', 'Column label', PLUGIN_SLUG_uhbyqy ),
        );

        return $columns;
    }

    protected function get_sortable_columns() {
        $sortable_columns = array(
            'active'    => array( 'active', false ),
            'name'    => array( 'name', false ),
            'next_post'   => array( 'next_post', false ),
            'posts_made' => array( 'posts_made', false ),
        );

        return $sortable_columns;
    }

    protected function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'active':
                return $item[$column_name] == 0 ? '<span class="dashicons dashicons-no"></span>' : '<span class="dashicons dashicons-yes"></span>';
                break;
            case 'next_post':
                $format = get_option('date_format') . ' ' . get_option('time_format');
                return $item['active'] == 0 ? '-' : Carbon::parse($item[$column_name], wp_timezone())->format($format);
                break;
            case 'schedule':
                $item['post_days'] = !is_null($item['post_days']) ? json_decode($item['post_days']) : ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                $post_days = array_map(function ($p) { return ucfirst($p); }, $item['post_days']);

                $seconds_to_time = function($seconds){
                    $hours = floor($seconds / 60);
                    $minutes = $seconds - ($hours * 60);

                    if(strlen((string)$hours) == 1){
                        $hours = '0' . $hours;
                    }

                    if (strlen((string)$minutes) == 1){
                        $minutes = '0' . $minutes;
                    }

                    if ($minutes == 0){
                        $minutes = '00';
                    }

                    if ($hours >= 12) {
                    if ($hours == 12) {
                        $hours = $hours;
                        $minutes = $minutes . " PM";
                    } else if ($hours == 24) {
                        $hours = $hours - 12;
                        $minutes = $minutes . " AM";
                    } else {
                        $hours = $hours - 12;
                        $minutes = $minutes . " PM";
                    }
                    } else {
                        $hours = $hours;
                        $minutes = $minutes . " AM";
                    }
                    if ($hours == 0) {
                        $hours = 12;
                        $minutes = $minutes;
                    }

                    return $hours . ':' . $minutes;
                };

                return '<strong>' . __( 'Max Posts Total', PLUGIN_SLUG_uhbyqy ) . '</strong>: ' . $item['max_posts_total'] . '<br />' .
                '<strong>' . __( 'Max Posts Per Day', PLUGIN_SLUG_uhbyqy ) . '</strong>: ' . $item['max_posts_per_day'] . '<br />' .
                '<strong>' . __( 'Posting Days', PLUGIN_SLUG_uhbyqy ) . '</strong>: ' . implode(', ', $post_days) . '<br />' .
                '<strong>' . __( 'Posting Time', PLUGIN_SLUG_uhbyqy ) . '</strong>: ' . $seconds_to_time($item['post_time_start']) . '-' . $seconds_to_time($item['post_time_end']);
                break;
            default:
                //return print_r( $item, true ); // Show the whole array for troubleshooting purposes.
                return $item[$column_name]; // Show the whole array for troubleshooting purposes.
        }
    }

    protected function column_cb( $item ) {
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            $this->_args['singular'],  // Let's simply repurpose the table's singular label ("project").
            $item['id']                // The value of the checkbox should be the record's id.
        );
    }

    protected function column_name( $item ) {
        $page = wp_unslash( $_REQUEST['page'] ); // WPCS: Input var ok.

        // Build edit row action.
        $edit_query_args = array(
            'page'   => $page,
            'action' => 'edit',
            'project'  => $item['id'],
        );

        $actions['edit'] = sprintf(
            '<a href="%1$s">%2$s</a>',
            esc_url(  add_query_arg( $edit_query_args, 'admin.php' ) ),
            _x( 'Edit', 'List table row action', PLUGIN_SLUG_uhbyqy )
        );

        // Build edit row action.
        if($item['active'] == 0){
            $status_query_args = array(
                'page'   => $page,
                'action' => 'resume',
                'project'  => $item['id'],
                PLUGIN_SLUG_uhbyqy . '_redirect' => 'projects'
            );

            $actions['status'] = sprintf(
                '<a href="%1$s">%2$s</a>',
                esc_url(   add_query_arg( $status_query_args, 'admin.php' ) ),
                _x( 'Resume', 'List table row action', PLUGIN_SLUG_uhbyqy )
            );
        }else{
            $status_query_args = array(
                'page'   => $page,
                'action' => 'pause',
                'project'  => $item['id'],
                 PLUGIN_SLUG_uhbyqy . '_redirect' => 'projects'
            );

            $actions['status'] = sprintf(
                '<a href="%1$s">%2$s</a>',
                esc_url(  add_query_arg( $status_query_args, 'admin.php' ) ),
                _x( 'Pause', 'List table row action', PLUGIN_SLUG_uhbyqy )
            );
        }

        if($item['active'] == 1 && $item['posts_made'] < $item['max_posts_total'] && $item['status'] == 'idle'){
            $post_query_args = array(
                'page'   => $page,
                'action' => 'post',
                'project'  => $item['id'],
                PLUGIN_SLUG_uhbyqy . '_redirect' => 'projects'
            );

            $actions['post'] = sprintf(
                '<a href="%1$s">%2$s</a>',
                esc_url(  add_query_arg( $post_query_args, 'admin.php' ) ),
                _x( 'Post Now', 'List table row action', PLUGIN_SLUG_uhbyqy )
            );
        }

        // Build delete row action.
        $delete_query_args = array(
            'page'   => $page,
            'action' => 'delete',
            'project'  => $item['id'],
            PLUGIN_SLUG_uhbyqy . '_redirect' => 'projects'
        );

        $actions['delete'] = sprintf(
            '<a href="%1$s">%2$s</a>',
            esc_url(  add_query_arg( $delete_query_args, 'admin.php' ) ),
            _x( 'Delete', 'List table row action', PLUGIN_SLUG_uhbyqy )
        );

        $status = '<small class="subtext">';
        if($item['active'] == 0){
            $status .= _x( 'Not active', 'status', PLUGIN_SLUG_uhbyqy );
        }else{
            switch($item['status'] ){
                case 'starting':
                    $status .= _x( 'Starting', 'status', PLUGIN_SLUG_uhbyqy );
                    break;
                case 'idle':
                    $status .= _x( 'Waiting for next post', 'status', PLUGIN_SLUG_uhbyqy );
                    break;
                case 'waiting':
                    $status .= _x( 'Generating post', 'status', PLUGIN_SLUG_uhbyqy );
                    break;
                case 'finished':
                    $status .= _x( 'Finished', 'status', PLUGIN_SLUG_uhbyqy );
                    break;
            }
        }
        $status .= '</small>';

        // Return the title contents.
        return sprintf( '%1$s %2$s %3$s',
            $item['name'],
            $status,
            $this->row_actions( $actions )
        );
    }

    protected function get_bulk_actions() {
        $actions = array(
            'delete' => _x( 'Delete', 'List table bulk action', PLUGIN_SLUG_uhbyqy ),
            'resume' => _x( 'Resume', 'List table bulk action', PLUGIN_SLUG_uhbyqy ),
            'pause' => _x( 'Pause', 'List table bulk action', PLUGIN_SLUG_uhbyqy ),
        );

        return $actions;
    }

    protected function process_bulk_action() {
        // Detect when a bulk action is being triggered.
        if ( 'delete' === $this->current_action() ) {
            global $wpdb;
            $wpdb_prefix = $wpdb->prefix;
            $projects_table_name = $wpdb_prefix. PLUGIN_SLUG_uhbyqy . '_projects';
            $posts_table_name = $wpdb_prefix. PLUGIN_SLUG_uhbyqy . '_posts';

            $project = $_GET['project'];
            if(is_string($project)){
                $project = [$project];
            }

            $ids = implode( ',', array_map( 'absint', $project ) );
            $wpdb->query("DELETE FROM $projects_table_name WHERE id IN($ids)");
            $wpdb->query("DELETE FROM $posts_table_name WHERE project_id IN($ids)");
        }else if ( 'pause' === $this->current_action() || 'resume' === $this->current_action() ) {
            global $wpdb;
            $wpdb_prefix = $wpdb->prefix;
            $projects_table_name = $wpdb_prefix. PLUGIN_SLUG_uhbyqy . '_projects';

            $project = $_GET['project'];
            if(is_string($project)){
                $project = [$project];
            }

            if($this->current_action() == 'resume'){
                //set next_post
                foreach ($project as $id) {
                    $class = PLUGIN_CLASS_uhbyqy . '_Project';
                    $project = new $class(intval($id));

                    if($project->active == 0 && $project->posts_made < $project->max_posts_total){
                        $project->update([
                            'active' => 1,
                            'status' => 'idle',
                            'next_post' => $project->nextPostDate()
                        ]);
                    }
                }
            }else{
                $ids = implode( ',', array_map( 'absint', $project ) );
                $wpdb->query("UPDATE $projects_table_name SET active = 0 WHERE id IN($ids)");
            }
        }else if ( 'post' === $this->current_action() ) {
            global $wpdb;
            $wpdb_prefix = $wpdb->prefix;
            $projects_table_name = $wpdb_prefix. PLUGIN_SLUG_uhbyqy . '_projects';

            $project = $_GET['project'];
            if(is_string($project)){
                $project = [$project];
            }

            foreach ($project as $id) {
                $class = PLUGIN_CLASS_uhbyqy . '_Project';
                $project = new $class(intval($id));

                $project->update([
                    'next_post' => Carbon::now(wp_timezone())->toDateTimeString()
                ]);
            }
        }
    }

    function prepare_items() {
        global $wpdb; //This is used only if making any database queries
        $wpdb_prefix = $wpdb->prefix;
        $projects_table_name = $wpdb_prefix. PLUGIN_SLUG_uhbyqy . '_projects';


        $current_page = $this->get_pagenum();
        $per_page = 20;
        $offset = ($current_page - 1) * $per_page;

        $columns  = $this->get_columns();
        $hidden   = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array( $columns, $hidden, $sortable );

        $this->process_bulk_action();

        $orderby = ! empty( $_REQUEST['orderby'] ) ? $_REQUEST['orderby'] : 'name';
        $order = ! empty( $_REQUEST['order'] ) ? $_REQUEST['order'] : 'asc';
        $orderQuery = sanitize_sql_orderby("$orderby $order");

        $data = $wpdb->get_results("SELECT * FROM $projects_table_name ORDER BY $orderQuery LIMIT $per_page OFFSET $offset", ARRAY_A);

        $total_items = $wpdb->get_var("SELECT COUNT(*) from $projects_table_name");

        $this->items = $data;

        $this->set_pagination_args( array(
            'total_items' => $total_items,                     // WE have to calculate the total number of items.
            'per_page'    => $per_page,                        // WE have to determine how many items to show on a page.
            'total_pages' => ceil( $total_items / $per_page ), // WE have to calculate the total number of pages.
        ) );
    }
}