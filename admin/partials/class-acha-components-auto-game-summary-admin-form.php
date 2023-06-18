<?php


class Acha_Components_Auto_Game_Summary_Admin_form
{
    public $option;
    public function __construct()
    {
        $this->option = json_decode(stripslashes(get_option('admin_game_summary_form')))->options;
    }

    public function form_HTML()
    {
        if ($this->option === null) {
            $this->option = json_decode(stripslashes('{\"style\":null,\"options\":{\"enable_game_summary\":\"\",\"user_password\":\"\"}}')); //todo: make this handle no data better
        }
        $checked = '';
        $disabled = 'disabled';
        if ($this->option->enable_game_summary == 'enable_game_summary') {
            $checked = 'checked="checked"';
            $disabled = '';
        }
        $pw_val = '';
        if ($this->option->user_password) {
            $pw_val = $this->option->user_password;
        }
        $content = '';
        $html =
            <<<HTML
                <div class="row">
                    <div class="col-md-4">
                        <p>This will create a cron that will run twice daily. On each cron trigger, it will call an external api with the password provided. 
                            The password is used to create a User, named "ac-api-user", with the Author role and must be the same as the api. 
                            The API will attempt to create posts with the user credentials so if the password is not the same as what the API is using then 
                            login will fail and no posts will be created.</p>
                        <input type="test" class="btn btn-outline-info" name="submit" id="gs_admin_test" value="Test">
                    </div>
                    <div class="col-md-4">
                    <div class="mb-3 row">
                        <div class="form-check">
                            <input type="checkbox" class="custom-control-input game-summary-checkbox" id="customCheck1" value="enable_game_summary" {$checked}>
                            <label class="custom-control-label" for="customCheck1">Allow Automated Game Summaries</label>
                        </div>
                    </div>
                    <div class="mb-3 row">
                            <label for="inputPassword" class="col-sm-2 col-form-label">Password</label>
                            <div class="col-sm-10">
                                <input type="password" class="form-control" id="inputPassword" value="{$pw_val}" {$disabled}>
                            </div>
                    </div>
                    <div class="mb-3 row">
                        <input type="submit" class="btn btn-success" name="submit" id="gs_admin_submit" value="Submit">
                    </div>
                    <div class="col-md-4">
                    </div>
                </div>
            HTML;

        $nonce = wp_create_nonce("game_summary_nonce");
        $test_nonce = wp_create_nonce("game_summary_test_nonce");
        $js =
            <<<JS
            <script>
                jQuery('#spinner-div').show();
                jQuery(document).ready(function () {
                    jQuery('#spinner-div').hide();
                    jQuery('.custom-control-input').change(function() {
                        if (jQuery(this).is(':checked')){
                            console.log('ischecked');
                            jQuery(this).attr('checked', true);
                            jQuery('#inputPassword').prop('disabled', false);
                            //jQuery(this).removeAttr('checked');
                        }else{
                            console.log('isNOTchecked');
                            jQuery(this).removeAttr('checked')
                            jQuery('#inputPassword').prop('disabled', true)
                            //jQuery(this).attr('checked');
                        }
                    });

                    jQuery("#gs_admin_submit").click(function() {
                        let pw = jQuery('#inputPassword').val();
                        let enable_game_summary = ''
                        if(jQuery('#customCheck1').is(':checked')) {
                            enable_game_summary = jQuery('#customCheck1').val();
                        }
                        var data = {
                            'action'   : 'updateGameSummaryOption', // the name of your PHP function!
                            'data' : JSON.stringify({
                                'style' : null,
                                'options' : {
                                    'enable_game_summary' : enable_game_summary,
                                    'user_password' : pw
                                }
                            }),
                            'nonce' : '{$nonce}'
                            };
                            console.log(data)
                            jQuery('#spinner-div').show();
                            jQuery.post(ajaxurl, data, function(response) {
                                if (response) {
                                    alert(response);
                                    console.log(response);
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
                            console.log(data)
                            jQuery('#spinner-div').show();
                            jQuery.post(ajaxurl, data, function(response) {
                                if (response) {
                                    alert(response);
                                    console.log(response);
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
}
