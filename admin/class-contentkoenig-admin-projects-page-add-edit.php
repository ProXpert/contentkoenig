<?php
$actionNice = ucfirst($action);

if($action === 'edit'){
    $class = PLUGIN_CLASS_uhbyqy . '_Project';
    $project = new $class($_GET['project']);
}else{
    $project = null;
}

$users = get_users( ['role__in' => [ 'administrator', 'editor', 'author' ] ]);
$projectAuthors = $action === 'edit' ? json_decode($project->authors) : [];
$projectCategories = $action === 'edit' ? json_decode($project->categories) : [];

if($action === 'add'){
    $postDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    $language = 'en';
    $targetLinkingTargets = json_encode([]);
}else{
    $postDays = $project->post_days;
    $language = !is_null($project->language) ? $project->language : 'en';
    $targetLinkingTargets = json_encode($project->target_linking_targets);
}

$languageOptions = json_decode(get_option(PLUGIN_SLUG_uhbyqy . '_languages'), true);
usort($languageOptions, function ($item1, $item2) {
    return $item1['name'] <=> $item2['name'];
});

$settings_url = menu_page_url(PLUGIN_SLUG_uhbyqy . '-admin-settings', false);
$success_redirect = menu_page_url(PLUGIN_SLUG_uhbyqy . '-admin-projects', false);
$ajax_action = $action === 'edit' ? 'update_project' : 'add_project';
$ajax_action = $ajax_action . '_uhbyqy';

$authority_link = get_option(PLUGIN_SLUG_uhbyqy . '_authority_link');
if(is_null($authority_link) || $authority_link === '' || !$authority_link){
    $authority_link = false;
}else{
    $authority_link = true;
}
?>
<div id="authority_link_modal" class="modal" style="max-width: 50%; width: 50%; z-index: 999999;">
    <h2><?php esc_html_e( 'Find Authority Links', PLUGIN_SLUG_uhbyqy ); ?></h2>
    <table width="100%" style="width: 100%;">
        <tr>
            <td style="width: 80%;">
                <input name="name" type="text" id="authority_link_modal_search" value="" placeholder="<?php esc_html_e( 'Enter search query...', PLUGIN_SLUG_uhbyqy ); ?>" class="regular-text" style="width: 100%;">
            </td>
            <td>
                <p class="submit" style="padding-top: 5px;"><button type="button" name="authority_link_modal_submit" id="authority_link_modal_submit" class="button button-primary" style="width: 100%;"><?php esc_html_e( 'Search', PLUGIN_SLUG_uhbyqy ); ?></button></p>
            </td>
        </tr>
    </table>
    <table id="authority_link_modal_results" width="100%" style="display: none;">
        <thead>
            <tr>
                <th colspan="2" style="text-align: center; color: #616161;"><?php esc_html_e( 'Click the icon next to a link below to add it to your target links.', PLUGIN_SLUG_uhbyqy ); ?></th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
<div class="wrap">
    <h1><?php esc_html_e( $actionNice . ' Project', PLUGIN_SLUG_uhbyqy ); ?></h1>
    <form id="projectForm" method="post">
        <table class="form-table" role="presentation">
        <?php wp_nonce_field( PLUGIN_SLUG_uhbyqy . '-project-submit', PLUGIN_SLUG_uhbyqy . '-project-submit' ); ?>
            <tbody>
                <tr>
                    <th scope="row"><label for="name"><?php echo esc_html_x( 'Name', 'Row label', PLUGIN_SLUG_uhbyqy ); ?></label></th>
                    <td>
                        <input name="name" type="text" id="name" value="<?php echo $action === 'edit' ? $project->name : '' ?>" class="regular-text">
                        <p class="description" id="name-description">
                            <?php printf( esc_html__( 'The name used to refer to this project within Wordpress, use something easily identifiable to help you with managing %s', PLUGIN_SLUG_uhbyqy ), PLUGIN_NAME_uhbyqy ); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="active"><?php echo esc_html_x( 'Active', 'Row label', PLUGIN_SLUG_uhbyqy ); ?></label></th>
                    <td>
                        <select name="active" id="active">
                            <option value="0"<?php echo $action === 'edit' && $project->active == 0 ? ' selected="selected"' : '' ?>><?php echo esc_html_e( 'Inactive', PLUGIN_SLUG_uhbyqy ); ?></option>
                            <option value="1"<?php echo $action === 'edit' && $project->active == 1 ? ' selected="selected"' : '' ?>><?php echo esc_html_e( 'Active', PLUGIN_SLUG_uhbyqy ); ?></option>
                        </select>
                        <p class="description" id="active-description">
                            <?php esc_html_e( 'Set this project to active or inactive. An inactive project will not have any new posts created', PLUGIN_SLUG_uhbyqy ); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="language"><?php echo esc_html_x( 'Language', 'Row label', PLUGIN_SLUG_uhbyqy ); ?></label></th>
                    <td>
                        <select name="language" id="language">
                            <?php foreach($languageOptions as $languageOption){ ?>
                            <option value="<?php echo $languageOption['language']; ?>"<?php echo $language === $languageOption['language'] ? ' selected="selected"' : '';?>>
                                <?php echo $languageOption['name']; ?>
                            </option>
                            <?php } ?>
                        </select>
                        <p class="description" id="language-description">
                            <?php esc_html_e( 'The language that posts will be made in', PLUGIN_SLUG_uhbyqy ); ?>
                        </p>
                    </td>
                </tr>
                <tr><td colspan="2" style="padding: 0;"><hr /></td></tr>
                <tr>
                    <th scope="row"><label for="max_posts_total"><?php echo esc_html_x( 'Maximum Posts Total', 'Row label', PLUGIN_SLUG_uhbyqy ); ?></label></th>
                    <td>
                        <input name="max_posts_total" type="number" id="max_posts_total" value="<?php echo $action === 'edit' ? $project->max_posts_total : '25' ?>" class="regular-text">
                        <p class="description" id="max_posts_total-description">
                            <?php esc_html_e( 'The maximum number of posts to make total in this project', PLUGIN_SLUG_uhbyqy ); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="max_posts_per_day"><?php echo esc_html_x( 'Maximum Posts Per Day', 'Row label', PLUGIN_SLUG_uhbyqy ); ?></label></th>
                    <td>
                        <input name="max_posts_per_day" type="number" id="max_posts_per_day" value="<?php echo $action === 'edit' ? $project->max_posts_per_day : '1' ?>" class="regular-text">
                        <p class="description" id="max_posts_per_day-description">
                            <?php esc_html_e( 'The maximum number of posts to make per day in this project', PLUGIN_SLUG_uhbyqy ); ?>
                        </p>
                    </td>
                </tr>
                <tr><td colspan="2" style="padding: 0;"><hr /></td></tr>

                <tr>
                    <th scope="row"><label for="authors"><?php echo esc_html_x( 'Post Days', 'Row label', PLUGIN_SLUG_uhbyqy ); ?></label></th>
                    <td>
                        <label for="post_days[]">
                            <input type="checkbox" class="post_day" name="post_days[]" value="monday" name="checkbox"<?php echo in_array('monday', $postDays) ? ' checked="checked"' : '' ?> />
                            <?php echo esc_html_x( 'Monday', 'weekday', PLUGIN_SLUG_uhbyqy ); ?>
                        </label><br />
                        <label for="post_days[]">
                            <input type="checkbox" class="post_day" name="post_days[]" value="tuesday" name="checkbox"<?php echo in_array('tuesday', $postDays) ? ' checked="checked"' : '' ?> />
                            <?php echo esc_html_x( 'Tuesday', 'weekday', PLUGIN_SLUG_uhbyqy ); ?>
                        </label><br />
                        <label for="post_days[]">
                            <input type="checkbox" class="post_day" name="post_days[]" value="wednesday" name="checkbox"<?php echo in_array('wednesday', $postDays) ? ' checked="checked"' : '' ?> />
                            <?php echo esc_html_x( 'Wednesday', 'weekday', PLUGIN_SLUG_uhbyqy ); ?>
                        </label><br />
                        <label for="post_days[]">
                            <input type="checkbox" class="post_day" name="post_days[]" value="thursday" name="checkbox"<?php echo in_array('thursday', $postDays) ? ' checked="checked"' : '' ?> />
                            <?php echo esc_html_x( 'Thursday', 'weekday', PLUGIN_SLUG_uhbyqy ); ?>
                        </label><br />
                        <label for="post_days[]">
                            <input type="checkbox" class="post_day" name="post_days[]" value="friday" name="checkbox"<?php echo in_array('friday', $postDays) ? ' checked="checked"' : '' ?> />
                            <?php echo esc_html_x( 'Friday', 'weekday', PLUGIN_SLUG_uhbyqy ); ?>
                        </label><br />
                        <label for="post_days[]">
                            <input type="checkbox" class="post_day" name="post_days[]" value="saturday" name="checkbox"<?php echo in_array('saturday', $postDays) ? ' checked="checked"' : '' ?> />
                            <?php echo esc_html_x( 'Saturday', 'weekday', PLUGIN_SLUG_uhbyqy ); ?>
                        </label><br />
                        <label for="post_days[]">
                            <input type="checkbox" class="post_day" name="post_days[]" value="sunday" name="checkbox"<?php echo in_array('sunday', $postDays) ? ' checked="checked"' : '' ?> />
                            <?php echo esc_html_x( 'Sunday', 'weekday', PLUGIN_SLUG_uhbyqy ); ?>
                        </label><br />
                        <p class="description" id="post_days-description">
                            <?php echo esc_html_x( 'Select the days that this project should post on', 'weekday', PLUGIN_SLUG_uhbyqy ); ?>
                        </p>
                        <p class="description description-error"></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="post_time"><?php echo esc_html_x( 'Post Time', 'Row label', PLUGIN_SLUG_uhbyqy ); ?></label></th>
                    <td>
                        <p><span class="slider-time"></span> - <span class="slider-time2"></span>
                        <div id="post_time" style="width: 400px;"></div>
                        <p class="description" id="post_time-description">
                            <?php esc_html_e( 'Posts will only be made from in this time range within scheduled days', PLUGIN_SLUG_uhbyqy ); ?>
                        </p>
                    </td>
                </tr>
                <tr><td colspan="2" style="padding: 0;"><hr /></td></tr>
                <tr>
                    <th scope="row"><label for="prompt_type"><?php echo esc_html_x( 'Creation Type', 'Row label', PLUGIN_SLUG_uhbyqy ); ?></label></th>
                    <td>
                        <select name="prompt_type" id="prompt_type">
                            <option value="standard:subject"<?php echo $action === 'edit' && $project->prompt_type == 'standard:subject' ? ' selected="selected"' : '' ?>><?php esc_html_e( 'Subject', PLUGIN_SLUG_uhbyqy ); ?></option>
                            <option value="standard:subject_topics"<?php echo $action === 'edit' && $project->prompt_type == 'standard:subject_topics' ? ' selected="selected"' : '' ?>><?php esc_html_e( 'Subject & Topics', PLUGIN_SLUG_uhbyqy ); ?></option>
                        </select>
                        <p class="description" id="prompt_type-description">
                            <?php esc_html_e( "This setting decides how we prompt the AI engine to create articles, 'Subject' will generate articles for a broad niche whilst 'Subject & Topics' allows you to generate more specific content", PLUGIN_SLUG_uhbyqy ); ?>
                        </p>
                    </td>
                </tr>
                <tr id="prompt_type_subject">
                    <th scope="row"><label for="subject"><?php echo esc_html_x( 'Niche', 'Row label', PLUGIN_SLUG_uhbyqy ); ?></label></th>
                    <td>
                        <input name="subject" type="text" id="subject" value="<?php echo $action === 'edit' ? $project->subject : '' ?>" class="regular-text">
                        <p class="description" id="subject-description">
                            <?php esc_html_e( "The niche/subject of this article. This should be a fairly high level description which allows the AI engine to focus it's knowledge", PLUGIN_SLUG_uhbyqy ); ?>
                        </p>
                    </td>
                </tr>
                <tr id="prompt_type_topics">
                    <th scope="row"><label for="topics"><?php echo esc_html_x( 'Topics', 'Row label', PLUGIN_SLUG_uhbyqy ); ?></label></th>
                    <td>
                        <textarea name="topics" type="text" id="topics" class="regular-text" rows="10"><?php echo $action === 'edit' ? $project->topics : '' ?></textarea>
                        <p class="description" id="topics-description">
                            <?php esc_html_e( 'If you would like to focus on specific topic(s) of your niche then enter them here. 1 per line', PLUGIN_SLUG_uhbyqy ); ?>
                        </p>
                    </td>
                </tr>
                <tr><td colspan="2" style="padding: 0;"><hr /></td></tr>
                <tr>
                    <th scope="row"><label for="post_type"><?php echo esc_html_x( 'Post Type', 'Row label', PLUGIN_SLUG_uhbyqy ); ?></label></th>
                    <td>
                        <select name="post_type" id="post_type">
                            <option value="publish"<?php echo $action === 'edit' && $project->post_type == 'publish' ? ' selected="selected"' : '' ?>><?php esc_html_e( 'Publish', PLUGIN_SLUG_uhbyqy ); ?></option>
                            <option value="draft"<?php echo $action === 'edit' && $project->post_type == 'draft' ? ' selected="selected"' : '' ?>><?php esc_html_e( 'Draft', PLUGIN_SLUG_uhbyqy ); ?></option>
                        </select>
                        <p class="description" id="active-description">
                            <?php esc_html_e( "Setting to 'Publish' will make posts immediately public on your blog, select 'Draft' if you would like the option to review and manually approve articles", PLUGIN_SLUG_uhbyqy ); ?>
                        </p>
                    </td>
                </tr>
                <tr><td colspan="2" style="padding: 0;"><hr /></td></tr>
                <tr>
                    <th scope="row"><label for="interlinking"><?php echo esc_html_x( 'Interlinking', 'Row label', PLUGIN_SLUG_uhbyqy ); ?></label></th>
                    <td>
                        <select name="interlinking" id="interlinking">
                            <option value="0"<?php echo $action === 'edit' && $project->interlinking == 0 ? ' selected="selected"' : '' ?>><?php esc_html_e( 'Disabled', PLUGIN_SLUG_uhbyqy ); ?></option>
                            <option value="1"<?php echo $action === 'edit' && $project->interlinking == 1 ? ' selected="selected"' : '' ?>><?php esc_html_e( 'Enabled', PLUGIN_SLUG_uhbyqy ); ?></option>
                        </select>
                        <p class="description" id="interlinking-description">
                            <?php esc_html_e( 'Enabling interlinking will link from new posts to existing posts (made by the plugin) which use the same tag(s)', PLUGIN_SLUG_uhbyqy ); ?>
                        </p>
                    </td>
                </tr>
                <tr class="interlinking_row">
                    <th scope="row"><label for="interlinking_all_projects"><?php echo esc_html_x( 'Interlinking Targets', 'Row label', PLUGIN_SLUG_uhbyqy ); ?></label></th>
                    <td>
                        <select name="interlinking_all_projects" id="interlinking_all_projects">
                            <option value="0"<?php echo $action === 'edit' && $project->interlinking_all_projects == 0 ? ' selected="selected"' : '' ?>><?php esc_html_e( 'This Project Only', PLUGIN_SLUG_uhbyqy ); ?></option>
                            <option value="1"<?php echo $action === 'edit' && $project->interlinking_all_projects == 1 ? ' selected="selected"' : '' ?>><?php esc_html_e( 'All Projects', PLUGIN_SLUG_uhbyqy ); ?></option>
                        </select>
                        <p class="description" id="interlinking_all_projects-description">
                            <?php esc_html_e( 'Select if you would like to link between posts made just in this project or across all projects', PLUGIN_SLUG_uhbyqy ); ?>
                        </p>
                    </td>
                </tr>
                <tr class="interlinking_row">
                    <th scope="row"><label for="interlinking_count"><?php echo esc_html_x( 'Number of Links', 'Row label', PLUGIN_SLUG_uhbyqy ); ?></label></th>
                    <td>
                        <input name="interlinking_count" type="number" id="interlinking_count" value="<?php echo $action === 'edit' ? $project->interlinking_count : '2' ?>" class="regular-text">
                        <p class="description" id="interlinking_count-description">
                            <?php esc_html_e( 'The number of links that the plugin will attempt to create in each post', PLUGIN_SLUG_uhbyqy ); ?>
                        </p>
                    </td>
                </tr>
                <tr><td colspan="2" style="padding: 0;"><hr /></td></tr>
                <tr>
                    <th scope="row"><label for="target_linking"><?php echo esc_html_x( 'Target Linking', 'Row label', PLUGIN_SLUG_uhbyqy ); ?></label></th>
                    <td>
                        <select name="target_linking" id="target_linking">
                            <option value="0"<?php echo $action === 'edit' && $project->target_linking == 0 ? ' selected="selected"' : '' ?>><?php esc_html_e( 'Disabled', PLUGIN_SLUG_uhbyqy ); ?></option>
                            <option value="1"<?php echo $action === 'edit' && $project->target_linking == 1 ? ' selected="selected"' : '' ?>><?php esc_html_e( 'Enabled', PLUGIN_SLUG_uhbyqy ); ?></option>
                        </select>
                        <p class="description" id="target_linking-description">
                            <?php esc_html_e( 'Enable if you would like the plugin to insert outbound links to the URL(s) of your choice, enable this option', PLUGIN_SLUG_uhbyqy ); ?>
                        </p>
                    </td>
                </tr>
                <tr class="target_linking_row">
                    <th scope="row"><label for="target_linking_percentage"><?php echo esc_html_x( 'Target Linking Percentage', 'Row label', PLUGIN_SLUG_uhbyqy ); ?></label></th>
                    <td>
                        <p><span id="target_linking_percentage_display"></span>%</span>
                        <div id="target_linking_percentage" style="width: 400px;"></div>
                        <p class="description" id="target_linking_percentage-description">
                            <?php esc_html_e( 'The percentage of posts in which target links will be added', PLUGIN_SLUG_uhbyqy ); ?>
                        </p>
                    </td>
                </tr>
                <tr class="target_linking_row">
                    <th scope="row"><label><?php echo esc_html_x( 'Targets', 'Row label', PLUGIN_SLUG_uhbyqy ); ?></label></th>
                    <td style="padding-top: 0;">
                        <table id="target_linking_targets_table">
                            <div id="target_linking_targets_table_append"></div>
                            <tr>
                                <td colspan="4" style="padding-left: 0;">
                                    <a href="#" id="add_target_link" class="button button-primary" alt="Add new target link" style="padding: 5px; padding-right: 10px; padding-left: 10px; color: #737373; background: #d4d4d4; border-color: #d4d4d4">
                                        <i class="fa-solid fa-plus fa-xl"></i>&nbsp;&nbsp;<?php esc_html_e( 'Add Link', PLUGIN_SLUG_uhbyqy ); ?>
                                    </a>
                                    <?php if($authority_link){ ?>
                                    <a href="#authority_link_modal" rel="modal:open" id="authority_link_finder_btn" class="button button-primary" alt="Find authority links" style="padding: 5px; padding-right: 10px; padding-left: 10px; color: #737373; background: #d4d4d4; border-color: #d4d4d4">
                                        <i class="fa-solid fa-search fa-xl"></i>&nbsp;&nbsp;<?php esc_html_e( 'Find Authority Links', PLUGIN_SLUG_uhbyqy ); ?>
                                    </a>
                                    <?php } ?>
                                </td>
                                <td></td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr><td colspan="2" style="padding: 0;"><hr /></td></tr>
                <tr>
                    <th scope="row"><label for="authors"><?php echo esc_html_x( 'Authors', 'Row label', PLUGIN_SLUG_uhbyqy ); ?></label></th>
                    <td>
                        <?php foreach($users as $user){ ?>
                        <label for="authors[]">
                            <input type="checkbox" class="author" name="authors[]" value="<?php echo $user->ID ?>" name="checkbox"<?php echo in_array($user->ID, $projectAuthors) ? ' checked="checked"' : '' ?> />
                            <?php echo $user->user_email ?>
                        </label><br />
                        <?php } ?>
                        <p class="description" id="authors-description">
                            <?php esc_html_e( 'Select which user(s) posts should be made as. If more than 1 is selected than a random user will be used from the selection each time', PLUGIN_SLUG_uhbyqy ); ?>
                        </p>
                        <p class="description description-error"></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="categories"><?php echo esc_html_x( 'Categories', 'Row label', PLUGIN_SLUG_uhbyqy ); ?></label></th>
                    <td>
                       <ul style="margin-top: 7px;">
                           <?php wp_category_checklist(0, 0, $projectCategories, false, null, false); ?>
                       </ul>
                        <p class="description" id="categories-description">
                            <?php esc_html_e( 'Select which categories posts should be assigned to. If none are selected posts will be made uncategorized', PLUGIN_SLUG_uhbyqy ); ?>
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
    let TARGET_LINKING_PERCENTAGE = 100;

    const showHideSubjectType = () => {
        switch($('#prompt_type').val()){
            case 'standard:subject':
                $('#prompt_type_subject').show();
                $('#prompt_type_topics').hide();
                break;
            case 'standard:subject_topics':
                $('#prompt_type_subject').show();
                $('#prompt_type_topics').show();
                break;
        }
    }

    const showHideInterlinking = () => {
        if($('#interlinking').val() === '0'){
            $('.interlinking_row').hide();
        }else{
            $('.interlinking_row').show();
        }
    }

    const showHideTargetLinking = () => {
        if($('#target_linking').val() === '0'){
            $('.target_linking_row').hide();
        }else{
            $('.target_linking_row').show();
        }
    }

    const addTargetLink = (url = null, keywords = []) => {
        let $row = $(`<tr class="target_link_row">
        <th style="max-width: 100px;">URL</th>
        <td><input name="target_link_urls[]" type="text" value="` + (url ? url : '') + `" placeholder="https://yourlink.com/" class="regular-text target_link_url"></td>
        <th style="max-width: 100px;">Keywords</th>
        <td><select name="target_link_keywords[]" type="text" value="" placeholder="Link Keywords" class="regular-text target_link_keywords" multiple></select></td>
        <td style="padding-left: 0;">
            <a href="#" style="color: #b3b3b3; padding-right: 10px;" alt="Copy Keywords to other links" title="Copy Keywords to other links" class="copy_target_link_keywords"><i class="fa-solid fa-copy fa-xl"></i></a>
            <a href="#" style="color: #FF0000;" alt="Delete this target link" title="Delete this target link" class="delete_target_link"><i class="fa-solid fa-delete-left fa-xl"></i></a>
        </td>
        </tr>`);

        $('#target_linking_targets_table_append').append($row);

        const $keywordsEl = $row.find('.target_link_keywords');
        const select2 = $keywordsEl.select2({
            placeholder: 'Link Keywords',
            multiple: true,
            tags: true,
            minimumResultsForSearch: Infinity
        });

        if(keywords && keywords.length > 0){
            for(const keyword of keywords){
                const newOption = new Option(keyword, keyword, true, true);
                select2.append(newOption).trigger('change');
            }
        }
    }

    const getTargetLinks = () => {
        const targets = [];

        $('.target_link_row').each(function( index ) {
            const url = $(this).find('.target_link_url').val().trim();
            const keywords = $(this).find('.target_link_keywords').val();

            if(url !== '' && keywords.length > 0){
                targets.push({url, keywords})
            }
        });

        return targets;
    }

    const authorityLinkSearch = (query) => {
        query = query.toLowerCase().trim();

        if(query === ''){
            alert('Enter the keyword you would like to find authority links for')
            return;
        }

        $('#authority_link_modal_results').hide();
        $('#authority_link_modal_results tbody').html('');
        $('#authority_link_modal_search').prop( "disabled", true );
        $('#authority_link_modal_submit').prop( "disabled", true );

        jQuery.post(ajaxurl, {action: 'authority_links_uhbyqy', query}, function(response) {
            response = JSON.parse(response);

            let html = '';

            for(const result of response.response){
                html += `<tr><td>
                <a href="${result.link}" target="_blank">
                    ${result.title}<br />
                    <span>${result.link}</span>
                </a>
                </td>
                <td>
                    <a href="#" alt="Add to target links" title="Add to target links" class="add_to_target_links" data-link="${result.link}">
                    <i class="fa-solid fa-plus fa-xl"></i>
                    </a>
                </td></tr>`;
            }

            $('#authority_link_modal_results tbody').html(html)

            $('#authority_link_modal_results').show();
            $('#authority_link_modal_search').prop( "disabled", false );
            $('#authority_link_modal_submit').prop( "disabled", false );
        });
    }

    showHideSubjectType();
    showHideInterlinking();

    for(const targetLink of JSON.parse(String.raw`<?php echo $targetLinkingTargets; ?>`)){
        addTargetLink(targetLink.url, targetLink.keywords)
    }
    showHideTargetLinking();

    $('#prompt_type').change(showHideSubjectType);
    $('#interlinking').change(showHideInterlinking);
    $('#target_linking').change(showHideTargetLinking);

    $('#add_target_link').on( "click", function(e){
        e.preventDefault();

        addTargetLink();
    });

    $('#authority_link_modal_submit').on( "click", function(e){
        e.preventDefault();

        authorityLinkSearch($('#authority_link_modal_search').val());
    });

    $('#target_linking_targets_table_append').on('click', 'a.delete_target_link', function(e) {
        e.preventDefault();

        $(this).parents('.target_link_row').first().remove();
    });

    $('#target_linking_targets_table_append').on('click', 'a.copy_target_link_keywords', function(e) {
        e.preventDefault();

        if (!confirm("This will copy this links keywords to all other URLs, are you sure?")) {
            return;
        }

        const self = $(this).parents('.target_link_row').first().find('.target_link_keywords');
        const toCopy = self.select2().val();

        $('.target_link_keywords').not(self).each(function( index ) {
            const select2 = $(this).select2();
            select2.val(null).trigger('change');

            for(const keyword of toCopy){
                const newOption = new Option(keyword, keyword, true, true);
                select2.append(newOption).trigger('change');
            }

        });
    });

    $('#authority_link_modal_results tbody').on('click', 'a.add_to_target_links', function(e) {
        e.preventDefault();

        const link = $(this).data('link')

        addTargetLink(link);
    });

    let post_time_start = <?php echo $action === 'edit' && !is_null($project->post_time_start) ? $project->post_time_start : '0' ?>;
    let post_time_end = <?php echo $action === 'edit' && !is_null($project->post_time_end) ? $project->post_time_end : '1440' ?>;

    const secondsToTime = (seconds) => {
        var hours = Math.floor(seconds / 60);
        var minutes = seconds - (hours * 60);

        if (hours.length == 1) hours = '0' + hours;
        if (minutes.length == 1) minutes = '0' + minutes;
        if (minutes == 0) minutes = '00';
        if (hours >= 12) {
           if (hours == 12) {
               hours = hours;
               minutes = minutes + " PM";
           } else if (hours == 24) {
               hours = hours - 12;
               minutes = minutes + " AM";
           } else {
               hours = hours - 12;
               minutes = minutes + " PM";
           }
        } else {
           hours = hours;
           minutes = minutes + " AM";
        }
        if (hours == 0) {
           hours = 12;
           minutes = minutes;
        }

        return hours + ':' + minutes;
    }

    const update_time_range_display = (from, to) => {
        post_time_start = from;
        post_time_end = to;

        $('.slider-time').html(secondsToTime(from));
        $('.slider-time2').html(secondsToTime(to));
    }

    function updateTargetLinkingPercentage(value){
        TARGET_LINKING_PERCENTAGE = value;
        $('#target_linking_percentage_display').html(value);
    }

    update_time_range_display(post_time_start, post_time_end);
    updateTargetLinkingPercentage(<?php echo $action === 'edit' ? $project->target_linking_percentage : 100 ?>);

    //https://codepen.io/caseymhunt/pen/ARgpxO
    $("#post_time").slider({
        range: true,
        min: 0,
        max: 1440,
        step: 30,
        values: [post_time_start, post_time_end],
        slide: function (e, ui) {
            update_time_range_display(ui.values[0], ui.values[1]);
        }
    });

    $("#target_linking_percentage").slider({
        min: 0,
        max: 100,
        step: 1,
        value: <?php echo $action === 'edit' ? $project->target_linking_percentage : 100 ?>,
        slide: function (e, ui) {
            updateTargetLinkingPercentage(ui.value);
        }
    });

    const fv = <?php echo PLUGIN_SLUG_uhbyqy; ?>.setupFormValidation(
        'projectForm',
        {
            name: {
                validators: {
                    notEmpty: {
                      message: 'Enter a name for this project',
                    }
                }
            },
            max_posts_per_day: {
                validators: {
                    notEmpty: {
                      message: 'Enter the maximum number of posts to make per day',
                    },
                    digits: {
                    message: 'Numbers only'
                    },
                    greaterThan: {
                      message: 'Must make at least 1 post per day',
                      min: 0,
                      inclusive: false
                    }
                },
            },
            max_posts_total: {
                validators: {
                    notEmpty: {
                      message: 'Enter the maximum number of posts in this project',
                    },
                    digits: {
                    message: 'Numbers only'
                    },
                    greaterThan: {
                      message: 'Must make at least 1 post total',
                      min: 0,
                      inclusive: false
                    }
                }
            },
            subject: {
                validators: {
                    notEmpty: {
                      message: 'Enter the niche for article creation',
                    }
                }
            },
            topics: {
                validators: {
                    notEmpty: {
                      message: 'Enter the topics for article creation',
                    }
                }
            },
            interlinking_count: {
                validators: {
                    notEmpty: {
                      message: 'Enter the maximum number of links to insert',
                    },
                    digits: {
                        message: 'Numbers only'
                    },
                    greaterThan: {
                      message: 'Must add at least 1 link',
                      min: 0,
                      inclusive: false
                    }
                }
            },
            'authors[]': {
                validators: {
                    choice: {
                        min: 1,
                        max: 99999,
                        message: 'Select at least 1 author',
                    },
                }
            },
            'post_days[]': {
                validators: {
                    choice: {
                        min: 1,
                        max: 7,
                        message: 'Select at least 1 posting day',
                    },
                }
            }
        },
        () => {
            const name = $('#name').val().trim();
            const language = $('#language').val().trim();
            const max_posts_per_day = $('#max_posts_per_day').val().trim();
            const max_posts_total = $('#max_posts_total').val().trim();
            const post_days = $('.post_day:checked').map(function() {
                return this.value;
            }).get();
            const post_type = $('#post_type').val().trim();
            const active = $('#active').val().trim();
            const authors = $('.author:checked').map(function() {
                return this.value;
            }).get();
            const categories = $('input[name="post_category[]"]:checked').map(function() {
                return this.value;
            }).get();
            const prompt_type = $('#prompt_type').val().trim();
            const subject = $('#subject').val().trim();
            const topics = $('#topics').val().trim();
            const interlinking = $('#interlinking').val().trim();
            const interlinking_all_projects = $('#interlinking_all_projects').val().trim();
            const interlinking_count = $('#interlinking_count').val().trim();
            const target_linking = $('#target_linking').val().trim();
            const target_linking_targets = getTargetLinks();
            const target_linking_percentage = TARGET_LINKING_PERCENTAGE;

            $('#projectForm input, #projectForm select').prop('disabled', true);

            jQuery.post(ajaxurl, {
                action: '<?php echo $ajax_action; ?>',
                id: <?php echo $action == 'edit' ? $project->id : 'null' ?>,
                name,
                language,
                max_posts_per_day,
                max_posts_total,
                post_days, post_type,
                active,
                authors,
                categories,
                prompt_type,
                subject,
                topics,
                post_time_start,
                post_time_end,
                interlinking,
                interlinking_all_projects,
                interlinking_count,
                target_linking,
                target_linking_targets,
                target_linking_percentage,
            }, function(response) {
                $('#projectForm input, #projectForm select').prop('disabled', false)

                window.location.href = '<?php echo $success_redirect; ?>';
            });
        }
    )
});
</script>
