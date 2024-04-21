<div class="wrap">
    <h1><?php printf( esc_html_x( '%s Settings', 'heading', PLUGIN_SLUG_uhbyqy ), PLUGIN_NAME_uhbyqy ); ?></h1>

    <form id="settingsForm" method="post">
        <table class="form-table" role="presentation">
        <?php wp_nonce_field( PLUGIN_SLUG_uhbyqy . '-settings-submit', PLUGIN_SLUG_uhbyqy . '-settings-submit' ); ?>
            <tbody>
                <tr>
                    <th scope="row"><label for="name"><?php esc_html_e( 'OpenAI API Key', PLUGIN_SLUG_uhbyqy ); ?></label></th>
                    <td>
                        <input name="openai_api_key" type="text" id="openai_api_key" value="<?php echo get_option(PLUGIN_SLUG_uhbyqy . '_openai_api_key', ''); ?>" class="regular-text">
                        <p class="description" id="openai_api_key-description">
                            <?php esc_html_e( 'Enter your OpenAI API key.', PLUGIN_SLUG_uhbyqy ); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
        <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_html_e( 'Save Changes', PLUGIN_SLUG_uhbyqy ); ?>"></p>
    </form>
</div>

<script type="text/javascript" >
jQuery(document).ready(function($) {
const fv = <?php echo PLUGIN_SLUG_uhbyqy; ?>.setupFormValidation(
        'settingsForm',
        {
            openai_api_key: {
                validators: {
                    stringLength: {
                        min: 51,
                        max: 56,
                        trim: true,
                        message: 'OpenAI API keys should be between 51 and 56 characters in length',
                    },
                    remote: {
                        message: 'Error checking API key',
                        method: 'POST',
                        url: ajaxurl,
                        data: {
                            action: 'check_openai_key_uhbyqy'
                        }
                    },
                }
            }
        },
        () => {
            $('#settingsForm input, #settingsForm select').prop('disabled', true);
            const openaiApiKey = $('#openai_api_key').val().trim();

            jQuery.post(ajaxurl, {
                action: 'update_settings_uhbyqy',
                openaiApiKey,
            }, function(response) {
                $('#settingsForm input, #settingsForm select').prop('disabled', false)

            });
        }
    )
});
</script>