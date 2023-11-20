<?php
$class = PLUGIN_CLASS_uhbyqy . '_Admin_Projects_List_Table';
$table = new $class();
$table->prepare_items();
$add_url = esc_url(  add_query_arg( ['page' => wp_unslash( $_REQUEST['page'] ), 'action' => 'add'], 'admin.php' ) )
?>
<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e( PLUGIN_NAME_uhbyqy . ' Projects', PLUGIN_SLUG_uhbyqy ); ?></h1>
    <a href="<?php echo $add_url ?>" class="page-title-action">Add New</a>
    <form method="get">
        <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
        <?php $table->display() ?>
    </form>
</div>
