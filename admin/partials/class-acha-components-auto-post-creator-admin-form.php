<?php


class Acha_Components_Auto_Post_Creator_Admin_form
{
    public $option;
    public function __construct()
    {
        // $admin_auto_post_options = json_decode(stripslashes(get_option('admin_auto_post_settings')));
        // if (isset($admin_auto_post_options->options) && $admin_auto_post_options !== false) {
        //     $this->option = $admin_auto_post_options->options;
        // } else {
        //     $this->option = json_decode(stripslashes('{\"enable_game_summary\":\"\",\"user_password\":\"\"}')); //todo: make this handle no data better
        // }
        //delete_option('admin_auto_post_settings');
    }

    public function form_HTML()
    {
        // $checked = '';
        // $disabled = 'disabled';
        // $this->console_log($this->option);
        // if ($this->option->enable_game_summary == 'enable_game_summary') {
        //     $checked = 'checked="checked"';
        //     $disabled = '';
        // }
        // $pw_val = '';
        // if ($this->option->user_password) {
        //     $pw_val = $this->option->user_password;
        // }
        $content = '';
        $html =
            <<<HTML
                <div class="row">
                    <div class="col-md-4">
                        <input type="submit" class="btn btn-success btn-lg" name="submit" id="gs_admin_submit" value="Submit">
                        <div><br></div>
                        <div class="mb-3 row">
                            <p>This will create a cron that will run twice daily. On each cron trigger, it will call an external api with the password provided. 
                                The password is used to create a User, named "ac-api-user", with the Author role and must be the same as the api. 
                                The API will attempt to create posts with the user credentials so if the password is not the same as what the API is using then 
                                login will fail and no posts will be created.</p>
                        </div>
                        <div class="mb-3 row">
                            <label for="inputPassword" class="col-sm-2 col-form-label">Password</label>
                            <div class="col-sm-10">
                                <input type="password" class="form-control" id="inputPassword" disabled>
                            </div>
                        </div>
                            <input type="test" class="btn btn-outline-info" name="submit" id="gs_admin_test" value="Test Automated Summary">
                            <input type="insta_test" class="btn btn-outline-info" name="submit" id="insta_post_admin_test" value="Test Instagram to WP Post Maker">
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3 row">
                            <div class="form-check">
                                <input type="checkbox" class="custom-control-input game-summary-checkbox" id="customCheck1" value="enable_game_summary">
                                <label class="custom-control-label" for="customCheck1">Allow Automated Game Summaries</label>
                            </div>
                        </div>
                        <div class="mb-3 row"> 
                        </div>
                        <div class="mb-3 row">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3 row">
                            <div class="form-check">
                                <input type="checkbox" class="custom-control-input insta-post-checkbox" id="customCheck2" value="enable_insta_post">
                                <label class="custom-control-label" for="customCheck2">Allow Automated Posts From Instagram</label>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="insta_group_id">Instagram ID</label>
                            <input type="text" name="insta_group_id" placeholder="Enter Instagram ID" class="form-control name_list" id="insta_id_input"/>
                        </div>
                    </div>
                </div>
            HTML;

        $nonce = wp_create_nonce("auto_post_nonce");
        $test_nonce = wp_create_nonce("auto_post_test_nonce");
        $js =
            <<<JS
            <script>
                jQuery('#spinner-div').show();
                jQuery(document).ready(function () {
                    // Example usage: Fetch specific options
                    var optionNames = ['admin_auto_post_settings']; // Add more option names as needed
                    fetchOptions(optionNames);

                    // Function to fetch options via AJAX and update fields accordingly
                    // This is a different way of updating the settings with the current state of the db.
                    // Other classes add values to the inputs via PHP, this class uses jQuery
                    function fetchOptions(optionNames) {
                        jQuery.ajax({
                            url: ajaxurl,
                            type: 'post',
                            data: {
                                action: 'get_wp_options_via_ajax',
                                nonce: '{$nonce}',
                                option_names: optionNames
                            },
                            success: function(response) {
                                if (response.success) {
                                    console.log(response)
                                    // Update the form fields with the options received
                                    if (response.data.admin_auto_post_settings !== null && response.data.admin_auto_post_settings !== undefined) {
                                        // Now it's safe to access properties
                                        const options = response.data.admin_auto_post_settings.options
                                        jQuery('#inputPassword').val(options.user_password);
                                        if(options.user_password !== '' && options.user_password.length > 2) {
                                            jQuery('#inputPassword').prop('disabled', false);
                                        }
                                        if (options.enable_game_summary === 'enable_game_summary') { 
                                            jQuery('#customCheck1').prop('checked', true);
                                        } else {
                                            jQuery('#customCheck1').prop('checked', false);
                                        }
                                        if (options.enable_insta_posts === 'enable_insta_post') { 
                                            jQuery('#customCheck2').prop('checked', true);
                                        } else {
                                            jQuery('#customCheck2').prop('checked', false);
                                        }
                                        jQuery('#insta_id_input').val(options.insta_id);
                                    } 
                                } else {
                                    console.log('Failed to fetch options.');
                                }
                                jQuery('#spinner-div').hide();
                            },
                            error: function(xhr, status, error) {
                                console.log('AJAX Error: ' + status + ' - ' + error);
                                jQuery('#spinner-div').hide();
                            }
                        });
                    }

                    function togglePasswordField() {
                        const checkboxes = jQuery('.custom-control-input');
                        const passwordField = jQuery('#inputPassword');
                        const isAnyCheckboxChecked = checkboxes.is(':checked');

                        if (isAnyCheckboxChecked) {
                            passwordField.prop('disabled', false);
                        } else {
                            passwordField.prop('disabled', true);
                        }
                    }
                    togglePasswordField(); // Initialize the state of the password field on page load
                    jQuery('.custom-control-input').change(function() { // Attach the change event handler to checkboxes
                        togglePasswordField();
                    });

                    jQuery("#gs_admin_submit").click(function() {
                        let pw = jQuery('#inputPassword').val();
                        let enable_game_summary = ''
                        let enable_insta_posts = ''
                        let insta_id = ''
                        if(jQuery('#customCheck1').is(':checked')) {
                            enable_game_summary = jQuery('#customCheck1').val();
                        }
                        if(jQuery('#customCheck2').is(':checked')) {
                            enable_insta_posts = jQuery('#customCheck2').val();
                        }
                        if(jQuery('#insta_id_input').val()) {
                            insta_id = jQuery('#insta_id_input').val();
                        }
                        var data = {
                            'action'   : 'updateAutoPostOption', // the name of your PHP function!
                            'data' : JSON.stringify({
                                'style' : null,
                                'options' : {
                                    'enable_game_summary' : enable_game_summary,
                                    'user_password' : pw,
                                    'enable_insta_posts' : enable_insta_posts,
                                    'insta_id' : insta_id,
                                }
                            }),
                            'nonce' : '{$nonce}'
                        };
                            jQuery('#spinner-div').show();
                            jQuery.post(ajaxurl, data, function(response) {
                                if (response) {
                                    alert(response);
                                }
                                jQuery('#spinner-div').hide();
                            });
                    });

                    jQuery("#gs_admin_test").click(function() {
                        let pw = jQuery('#inputPassword').val();
                        let gameId = prompt('Add the game ID');
                        let targetTeamId = prompt('Add the target team ID');
                        confirm('Are you sure you want to create game summary post with these values?');
                        var data = {
                            'action'   : 'gameSummaryPostTest', // the name of your PHP function!
                            'data' : JSON.stringify({
                                'style' : null,
                                'options' : {
                                    'gameId' : gameId,
                                    'targetTeamId' : targetTeamId,
                                }
                            }),
                            'nonce' : '{$test_nonce}'
                            };
                            jQuery('#spinner-div').show();
                            jQuery.post(ajaxurl, data, function(response) {
                                if (response) {
                                    alert(response);
                                }
                                jQuery('#spinner-div').hide();
                            });
                    });

                    jQuery("#insta_post_admin_test").click(function() {
                        let pw = jQuery('#inputPassword').val();
                        let insta_id = jQuery('#insta_id_input').val();

                        var data = {
                            'action'   : 'instagramPostTest', // the name of your PHP function!
                            'data' : JSON.stringify({
                                'style' : null,
                                'options' : {
                                    'insta_id' : insta_id,
                                }
                            }),
                            'nonce' : '{$test_nonce}'
                            };
                            jQuery('#spinner-div').show();
                            jQuery.post(ajaxurl, data, function(response) {
                                if (response) {
                                    alert("Test Complete!");
                                }
                                jQuery('#spinner-div').hide();
                            });
                    });
                });
            </script>
            JS;
        $content .= $html;
        $content .= $js;
        return $content;
    }

    function console_log($output, $with_script_tags = true)
    {
        $js_code = 'console.log(' . json_encode($output, JSON_HEX_TAG) .
            ');';
        if ($with_script_tags) {
            $js_code = '<script>' . $js_code . '</script>';
        }
        echo $js_code;
    }
}
