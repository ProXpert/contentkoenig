<?php
if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

use Carbon\Carbon;

//https://github.com/Veraxus/wp-list-table-example
class Contentkoenig_Admin_Posts_List_Table extends WP_List_Table
{
    private $shared;

	public function __construct() {
		// Set parent defaults.
		parent::__construct( array(
			'singular' => 'post',     // Singular name of the listed records.
			'plural'   => 'posts',    // Plural name of the listed records.
			'ajax'     => false,       // Does this table support ajax?
		) );

        $class = PLUGIN_CLASS_uhbyqy . '_Shared';
		$this->shared = new $class;
	}

	public function get_columns() {
		$columns = array(
			'time'    => __( 'Time', 'Column label', PLUGIN_SLUG_uhbyqy ),
			'project'    => __( 'Project', 'Column label', PLUGIN_SLUG_uhbyqy ),
			'title'    => __( 'Title', 'Column label', PLUGIN_SLUG_uhbyqy ),
			'status'   => __( 'Status', 'Column label', PLUGIN_SLUG_uhbyqy ),
			'url'   => __( 'URL', 'Column label', PLUGIN_SLUG_uhbyqy ),
		);

		return $columns;
	}

	protected function get_sortable_columns() {
		$sortable_columns = array(
			'time'    => array( 'time', false ),
		);

		return $sortable_columns;
	}

	protected function column_default( $item, $column_name ) {
		switch ( $column_name ) {
		    case 'time':
                $format = get_option('date_format') . ' ' . get_option('time_format');
                return Carbon::parse($item[$column_name])->setTimezone(wp_timezone())->format($format);
		        break;
		    case 'project':
		        return $item['project_name'];
		        break;
		    case 'status':
                if($item['got_data'] == 0 && $item['posted'] == 0){
                    return 'Generating Post';
                }
                if($item['got_data'] == 1 && $item['posted'] == 0){
                    return 'Posting';
                }
                if($item['got_data'] == 1 && $item['posted'] == 1){
                    return 'Posted';
                }
                break;
            case 'url':
                return !is_null($item['url']) ? '<a href="' . $item['url'] . '" target="_blank">' . $item['url'] . '</a>' : '-';
                break;
			default:
				//return print_r( $item, true ); // Show the whole array for troubleshooting purposes.
				return $item[$column_name]; // Show the whole array for troubleshooting purposes.
		}
	}

	protected function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			$this->_args['singular'],  // Let's simply repurpose the table's singular label ("post").
			$item['id']                // The value of the checkbox should be the record's id.
		);
	}

	protected function column_name( $item ) {
		$page = wp_unslash( $_REQUEST['page'] ); // WPCS: Input var ok.

		// Build edit row action.
		$edit_query_args = array(
			'page'   => $page,
			'action' => 'edit',
			'post'  => $item['id'],
		);

		$actions['edit'] = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url(  add_query_arg( $edit_query_args, 'admin.php' ) ),
			__( 'Edit', 'List table row action', PLUGIN_SLUG_uhbyqy )
		);

		// Build edit row action.
		if($item['active'] == 0){
		    $status_query_args = array(
                'page'   => $page,
                'action' => 'resume',
                'post'  => $item['id'],
                PLUGIN_SLUG_uhbyqy . '_redirect' => 'posts'
            );

            $actions['status'] = sprintf(
                '<a href="%1$s">%2$s</a>',
                esc_url(   add_query_arg( $status_query_args, 'admin.php' ) ),
                __( 'Resume', 'List table row action', PLUGIN_SLUG_uhbyqy )
            );
		}else{
		    $status_query_args = array(
                'page'   => $page,
                'action' => 'pause',
                'post'  => $item['id'],
                PLUGIN_SLUG_uhbyqy . '_redirect' => 'posts'
            );

            $actions['status'] = sprintf(
                '<a href="%1$s">%2$s</a>',
                esc_url(  add_query_arg( $status_query_args, 'admin.php' ) ),
                __( 'Pause', 'List table row action', PLUGIN_SLUG_uhbyqy )
            );
		}

        if($item['active'] == 1 && $item['posts_made'] < $item['max_posts_total'] && $item['status'] == 'idle'){
            $post_query_args = array(
                'page'   => $page,
                'action' => 'post',
                'post'  => $item['id'],
                PLUGIN_SLUG_uhbyqy . '_redirect' => 'posts'
            );

            $actions['post'] = sprintf(
                '<a href="%1$s">%2$s</a>',
                esc_url(  add_query_arg( $post_query_args, 'admin.php' ) ),
                __( 'Post Now', 'List table row action', PLUGIN_SLUG_uhbyqy )
            );
        }

		// Build delete row action.
		$delete_query_args = array(
			'page'   => $page,
			'action' => 'delete',
			'post'  => $item['id'],
            PLUGIN_SLUG_uhbyqy . '_redirect' => 'posts'
		);

		$actions['delete'] = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url(  add_query_arg( $delete_query_args, 'admin.php' ) ),
			__( 'Delete', 'List table row action', PLUGIN_SLUG_uhbyqy )
		);

        $status = '<small class="subtext">';
        if($item['active'] == 0){
            $status .= 'Not active';
        }else{
            switch($item['status'] ){
                case 'starting':
                    $status .= 'Starting';
                    break;
                case 'idle':
                    $status .= 'Waiting for next post';
                    break;
                case 'waiting':
                    $status .= 'Generating post';
                    break;
                case 'finished':
                    $status .= 'Finished';
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
		$actions = array();

		return $actions;
	}

	protected function process_bulk_action() {

	}

	function prepare_items() {
		global $wpdb; //This is used only if making any database queries
		$wpdb_prefix = $wpdb->prefix;
        $posts_table_name = $wpdb_prefix. PLUGIN_SLUG_uhbyqy . '_posts';
        $projects_table_name = $wpdb_prefix. PLUGIN_SLUG_uhbyqy . '_projects';

		$current_page = $this->get_pagenum();
		$per_page = 20;
		$offset = ($current_page - 1) * $per_page;

		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->process_bulk_action();

        $orderby = ! empty( $_REQUEST['orderby'] ) ? $_REQUEST['orderby'] : 'time';
        $order = ! empty( $_REQUEST['order'] ) ? $_REQUEST['time'] : 'desc';
        $orderQuery = sanitize_sql_orderby("$orderby $order");

        $data = $wpdb->get_results("SELECT $posts_table_name.time, $posts_table_name.project_id, $posts_table_name.title, $posts_table_name.got_data, $posts_table_name.posted, $posts_table_name.url, $projects_table_name.name as project_name FROM $posts_table_name INNER JOIN $projects_table_name ON $posts_table_name.project_id = $projects_table_name.id ORDER BY $orderQuery LIMIT $per_page OFFSET $offset", ARRAY_A);

		$total_items = $wpdb->get_var("SELECT COUNT(*) from $posts_table_name");

		$this->items = $data;

		$this->set_pagination_args( array(
			'total_items' => $total_items,                     // WE have to calculate the total number of items.
			'per_page'    => $per_page,                        // WE have to determine how many items to show on a page.
			'total_pages' => ceil( $total_items / $per_page ), // WE have to calculate the total number of pages.
		) );
	}
}