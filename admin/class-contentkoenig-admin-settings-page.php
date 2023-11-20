<div class="wrap">
    <h1><?php _e( PLUGIN_NAME_uhbyqy . ' Settings', PLUGIN_SLUG_uhbyqy ); ?></h1>

    <form id="settingsForm" method="post">
        <table class="form-table" role="presentation">
        <?php wp_nonce_field( PLUGIN_SLUG_uhbyqy . '-settings-submit', PLUGIN_SLUG_uhbyqy . '-settings-submit' ); ?>
            <tbody>
                <tr>
                    <th scope="row"><label for="name">Rewriter API Key</label></th>
                    <td>
                        <input name="rewriter_api_key" type="text" id="rewriter_api_key" value="<?php echo get_option(PLUGIN_SLUG_uhbyqy . '_rewriter_api_key', ''); ?>" class="regular-text">
                        <p class="description" id="rewriter_api_key-description">
                            Enter your Rewriter API key to enable automatic AI detection rewriting for your projects.
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
        <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"></p>
    </form>
</div>

<script type="text/javascript" >
jQuery(document).ready(function($) {
const fv = <?php echo PLUGIN_SLUG_uhbyqy; ?>.setupFormValidation(
        'settingsForm',
        {
            rewriter_api_key: {
                validators: {
                    stringLength: {
                        min: 24,
                        max: 24,
                        trim: true,
                        message: 'Rewriter API keys should be 24 characters in length',
                    },
                    remote: {
                        message: 'Error checking API key',
                        method: 'POST',
                        url: ajaxurl,
                        data: {
                            action: 'check_rewriter_key_uhbyqy'
                        }
                    },
                }
            }
        },
        () => {
            $('#settingsForm input, #settingsForm select').prop('disabled', true);
            const rewriterApiKey = $('#rewriter_api_key').val().trim();

            jQuery.post(ajaxurl, {
                action: 'update_settings_uhbyqy',
                rewriterApiKey,
            }, function(response) {
                $('#settingsForm input, #settingsForm select').prop('disabled', false)

            });
        }
    )
});
</script>