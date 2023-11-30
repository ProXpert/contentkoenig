<?php
$current_licence_key = get_option(PLUGIN_SLUG_uhbyqy . '_licence_key');
$success_redirect = menu_page_url(PLUGIN_SLUG_uhbyqy . '-admin', false) . '&' . PLUGIN_SLUG_uhbyqy . '_notice=licence_key_updated';
?>
<div class="wrap">
    <h1><?php _e( PLUGIN_NAME_uhbyqy . ' Licence Key', PLUGIN_SLUG_uhbyqy ); ?></h1>

    <div class="metabox-holder">
        <div class="postbox">
            <div class="inside" style="margin-bottom: 0 !important; padding-bottom: 20px;">
                <form id="licenceKeyForm">
                    <table style="width: 100%;">
                        <tr>
                            <td colspan="3" style="text-align: center;">
                                <p>Enter your Licence Key below to start using <?php echo PLUGIN_NAME_uhbyqy ?></p>
                            </td>
                        </tr>
                        <tr>
                            <td style="width:25%;"></td>
                            <td style="text-align: center; width:50%;">
                                <input type="text" name="licence_key" id="licence_key" value="<?php echo $current_licence_key; ?>" class="large-text" style="font-size: 2em; text-align:center;" placeholder="Enter Your Licence Key">
                            </td>
                            <td style="width:25%;"></td>
                        </tr>
                        <tr>
                            <td colspan="3" style="text-align: center; padding: 10px;">
                                <input type="submit" name="save_licence_key" id="save_licence_key" class="button button-primary button-large" value="Save" style="font-size: 1.25em;">
                            </td>
                        </tr>
                    </table>
                </form>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript" >
jQuery(document).ready(function($) {
    const fv = <?php echo PLUGIN_SLUG_uhbyqy; ?>.setupFormValidation(
        'licenceKeyForm',
        {
            licence_key: {
              validators: {
                  notEmpty: {
                      message: 'Enter your licence key',
                  }
              },
            }
        },
        () => {
            const licence_key = $('#licence_key').val().trim();

            $('#licenceKeyForm input, #projectForm select').prop('disabled', true)

            jQuery.post(ajaxurl, {action: 'save_licence_key_uhbyqy', licence_key}, function(response) {
                 $('#licenceKeyForm input, #projectForm select').prop('disabled', false)

                response = JSON.parse(response);

                if(!response.error){
                    window.location.href = '<?php echo $success_redirect; ?>';
                }else{
                    const error = response.response.desc;
                    //user_not_found
                    //user_not_active
                    //site_limit_met
                    //error_adding_site
                    //server_error

                    let message;
                    switch(error){
                        case 'user_not_found':
                            message = 'Licence key not found';
                            break;
                        case 'user_not_active':
                            message = 'User associated with this licence key does not have an active account';
                            break;
                        case 'site_limit_met':
                            message = 'Your <?php echo PLUGIN_NAME_uhbyqy ?> account site limit has been reached';
                            break;
                        case 'error_adding_site':
                        case 'server_error':
                        default:
                            message = 'There was a problem activating this site. Try again and if the problem persists, please contact support';
                            break;

                    }

                    alert(message)
                }
            });
        }
    )
});
</script>