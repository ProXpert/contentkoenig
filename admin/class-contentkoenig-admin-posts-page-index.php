<?php
$class = PLUGIN_CLASS_uhbyqy . '_Admin_Posts_List_Table';
$table = new $class();
$table->prepare_items();
$table->prepare_items();
?>
<div class="wrap">
    <h1><?php printf( esc_html_x( '%s Posts', 'heading', PLUGIN_SLUG_uhbyqy ), PLUGIN_NAME_uhbyqy ); ?></h1>
    <form method="get">
        <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
        <?php $table->display() ?>
    </form>
</div>