<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://https://github.com/connormesec/
 * @since      1.0.0
 *
 * @package    Acha_Roster
 * @subpackage Acha_Roster/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Acha_Roster
 * @subpackage Acha_Roster/admin
 * @author     Connor Mesec <connormesec@gmail.com>
 */
class Acha_Roster_Admin_Form
{
    public $admin_form_html;
    public function __construct()
    {
        if (get_option('admin_roster_form_data')) {
            $roster_arr = json_decode(stripslashes(get_option('admin_roster_form_data')));
            $this->admin_form_html = $this->buildCustomAdminForm($roster_arr);
        } else {
            $this->admin_form_html = $this->buildCustomAdminForm();
        }
    }

    private function buildCustomAdminForm($roster_form_data = null)
    {
        //if null set form data to an empty object so form will render properly
        if ($roster_form_data === null) {
            $roster_form_data = json_decode(stripslashes('[{\"rosterName\":\"teste\",\"url\":\"t\",\"last_team\":\"yes_last_team\",\"year_in_school\":\"yes_year_in_school\",\"style\":\"1\"}]')); //todo: make this handle no data better
        }
        $roster_arr = $roster_form_data->form_data;

        $pill = '';
        $dropdown = '';
        if ($roster_form_data->style->type === '1') {
            $pill = 'selected';
        } elseif ($roster_form_data->style->type === '2') {
            $dropdown = 'selected';
        }
        $html = <<<HTML
                    <div class="row">
                        <div class="col-md-10">
                            <div class="form-group">
                                <form name="add_name" id="add_name">
                                    <table class="table table-bordered" id="dynamic_field">
                                        <tr class="title">
                                            <td><h4>Roster Name</h4></td>
                                            <td><h4>Roster URL</h4></td>
                                            <td><h4>Roster Shortcode</h4></td>
                                            <td><h4>Actions</h4></td>
                                        </tr>
            HTML;
        $i = 1;
        $j = 1;
        foreach ($roster_arr as $row) {
            $show_last_team = '';
            if ($row->last_team === "yes_last_team") {
                $show_last_team = 'checked="checked"';
            }
            $show_year_in_school = '';
            if ($row->year_in_school === "yes_year_in_school") {
                $show_year_in_school = 'checked="checked"';
            }
            if ($j === 1) {
                $roster_name = ($row->rosterName) ? $row->rosterName : '';
                $first_url = ($row->url) ? $row->url : '';
                $html .= <<<HTML
                        <tr class="row_item">
                            <td>
                                <input type="text" name="roster_name_input_1" placeholder="Enter roster name" class="form-control name_list" value="{$roster_name}"/>
                            </td>
                            <td>
                                <div id="attributes">
                                    <table class="attr table table-borderless" id="dynamic_url_input_field_row_1">
                                        <tr>
                                            <td>
                                                <input name="" id="row_url_input_{$j}_{$i}" style="width:100%" type="text" placeholder="Enter roster URL" class="required-entry" value="{$first_url}">
                                                <tr>
                                                    <td>
                                                        <div class="custom-control custom-checkbox">
                                                            <input type="checkbox" class="custom-control-input last-team" id="customCheck{$j}" value="yes_last_team" $show_last_team>
                                                            <label class="custom-control-label" for="customCheck{$j}">Add "Last Team" column</label>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <div class="custom-control custom-checkbox">
                                                            <input type="checkbox" class="custom-control-input YOS" id="customCheckYOS{$j}" value="yes_year_in_school" {$show_year_in_school}>
                                                            <label class="custom-control-label" for="customCheckYOS{$j}">Add "Year In school" column</label>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </td>
                                        </tr>                              
                                    </table>
                                </div>
                            </td>
                            <td>
                                <input	type="text" id="ac-shortcode" onfocus="this.select();" readonly="readonly" class="large-text code" value='[ac-roster title="{$roster_name}"]'>
                            </td>
                            <td>
                                <button type="button" name="add" id="add" class="btn btn-primary">Add Roster</button>
                            </td>
                        </tr>
                    HTML;

                $j++;
            } else {
                $roster_name = ($row->rosterName) ? $row->rosterName : '';
                $first_url = ($row->url) ? $row->url : '';
                $html .= <<<HTML
                        <tr class="row_item" id="row{$j}">
                            <td>
                                <input type="text" name="roster_name_input_{$j}" placeholder="Enter roster name" class="form-control name_list" value="{$roster_name}"/>
                            </td>
                            <td>
                                <div id="attributes">
                                    <table class="attr table table-borderless" id="dynamic_url_input_field_row_{$j}">
                                        <tr>
                                            <td>
                                                <input name="" id="row_url_input_{$j}_{$i}" style="width:100%" type="text" placeholder="Enter roster URL" class="required-entry" value="{$first_url}">
                                                <tr>
                                                    <td>
                                                        <div class="custom-control custom-checkbox">
                                                            <input type="checkbox" class="custom-control-input last-team" id="customCheck{$j}" value="yes_last_team" $show_last_team>
                                                            <label class="custom-control-label" for="customCheck{$j}">Add "Last Team" column</label>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <div class="custom-control custom-checkbox">
                                                            <input type="checkbox" class="custom-control-input YOS" id="customCheckYOS{$j}" value="yes_year_in_school" {$show_year_in_school}>
                                                            <label class="custom-control-label" for="customCheckYOS{$j}">Add "Year In school" column</label>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </td>
                            <td>
                                <input	type="text" id="ac-shortcode" onfocus="this.select();" readonly="readonly" class="large-text code" value='[ac-roster title="{$roster_name}"]'>
                            </td>
                            <td>
                                <button type="button" name="remove" id="{$j}" class="btn btn-danger btn_remove">X</button>
                            </td>
                        </tr>
                    HTML;
                $j++;
            }
        }
        $primary_color = '';
        if ($primary_color = $roster_form_data->style->primaryColor) {
            $primary_color = $roster_form_data->style->primaryColor;
        }
        $secondary_color = '';
        if ($secondary_color = $roster_form_data->style->secondaryColor) {
            $secondary_color = $roster_form_data->style->secondaryColor;
        }
        $header_text_color = '';
        if ($header_text_color = $roster_form_data->style->headerTextColor) {
            $header_text_color = $roster_form_data->style->headerTextColor;
        }
        $text_color = '';
        if ($roster_form_data->style->textColor) {
            $text_color = $roster_form_data->style->textColor;
        }
        $container_bg_color = '';
        if ($roster_form_data->style->containerBgColor) {
            $container_bg_color = $roster_form_data->style->containerBgColor;
        }
        $html .= <<<HTML
                                    </table>
                                    <input type="submit" class="btn btn-success" name="submit" id="roster_admin_submit" value="Submit">
                                </form>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <table class="table table-bordered" id="style_field">
                                <tr class="title">
                                    <th>
                                        <h4>Style</h4>
                                    </th>
                                </tr>
                                <tr>
                                    <td>
                                        <select class="custom-select" id="rosterStyleSelect">
                                            <option {$pill} value="1">Pill</option>
                                            <option {$dropdown} value="2">Dropdown</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <label for="primary_color">Primary Color:</label>
                                        <input type="color" id="primary_color" name="primary_color" value="{$primary_color}">
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <label for="secondary_color">Secondary Color:</label>
                                        <input type="color" id="secondary_color" name="secondary_color" value="{$secondary_color}">
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <label for="header_text_color">Header Text Color:</label>
                                        <input type="color" id="header_text_color" name="header_text_color" value="{$header_text_color}">
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <label for="text_color">Text Color:</label>
                                        <input type="color" id="text_color" name="text_color" value="{$text_color}">
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <label for="container_bg_color">Container Color:</label>
                                        <input type="color" id="container_bg_color" name="container_bg_color" value="{$container_bg_color}">
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            HTML;

        $html .= $this->formJs($j);

        return $html;
    }

    private function formJs($j)
    {
        $nonce = wp_create_nonce("update_admin_roster_db_nonce");
        $js = <<<JS
                <script>
                jQuery('#spinner-div').show();
                jQuery(document).ready(function () {
                    jQuery('#spinner-div').hide();
                    var j = {$j}; //row iterator
                    
                    jQuery('.custom-control-input').change(function() {
                        if (jQuery(this).is(':checked')){
                            console.log('ischecked');
                            jQuery(this).attr('checked', true);
                            //jQuery(this).removeAttr('checked');
                        }else{
                            console.log('isNOTchecked');
                            jQuery(this).removeAttr('checked')
                            //jQuery(this).attr('checked');
                        }
                    });

                jQuery("#add").click(function () {
                    j++;
                    //problems in this string are due to the linter being confused, it thinks the vars are php but are actually js and legit
                    const code = `
                        <tr class="row_item" id="row${j}">
                            <td>
                                <input type="text" name="roster_name_input_${j}" placeholder="Enter roster name" class="form-control name_list"/>
                            </td>
                            <td>
                                <div id="attributes">
                                    <table class="attr table table-borderless" id="dynamic_url_input_field_row_${j}">
                                        <tr>
                                            <td>
                                                <input name="" id="row_url_input_${j}" style="width:100%" type="text" placeholder="Enter roster URL" class="required-entry">
                                                <tr>
                                                    <td>
                                                        <div class="custom-control custom-checkbox">
                                                            <input type="checkbox" class="custom-control-input last-team" id="customCheck${j}" value="yes_last_team">
                                                            <label class="custom-control-label" for="customCheck${j}">Add "Last Team" column</label>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <div class="custom-control custom-checkbox">
                                                            <input type="checkbox" class="custom-control-input YOS" id="customCheckYOS${j}" value="yes_year_in_school">
                                                            <label class="custom-control-label" for="customCheckYOS${j}">Add "Year In school" column</label>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </td>
                            <td>
                                <input	type="text" id="ac-shortcode" onfocus="this.select();" readonly="readonly" class="large-text code" value='[ac-roster title=""]'>
                            </td>
                            <td>
                                <button type="button" name="remove" id="${j}" class="btn btn-danger btn_remove">X</button>
                            </td>
                        </tr>
                    `;
                
                    jQuery("#dynamic_field").append(code); 
                });
                
    
                jQuery(document).on("click", ".btn_remove", function () {
                    j++;
                    var button_id = jQuery(this).attr("id");
                    jQuery("#row" + button_id + "").remove();
                });
                
                jQuery("#roster_admin_submit").click(function() {
                    let data_arr = [];
                    jQuery('.row_item').map(function () {
                        const rosterName = jQuery(this).find('input[placeholder="Enter roster name"]').val();
                        const url = jQuery(this).find('input[placeholder="Enter roster URL"]').val();
                        let last_team = ''
                        if(jQuery(this).find('.last-team').is(':checked')) {
                            last_team = jQuery(this).find('.last-team').val();
                        }
                        let year_in_school = ''
                        if(jQuery(this).find('.YOS').is(':checked')) {
                            year_in_school = jQuery(this).find('.YOS').val();
                        }
                        data_arr.push(
                            { 
                            rosterName, 
                            url,
                            last_team,
                            year_in_school
                        });
                    });
                    const style = jQuery('#rosterStyleSelect').val();
                    const primaryColor = jQuery('#primary_color').val();
                    const secondaryColor = jQuery('#secondary_color').val();
                    const textColor	= jQuery('#text_color').val();
                    const containerBgColor = jQuery('#container_bg_color').val();
                    const headerTextColor = jQuery('#header_text_color').val();
                    event.preventDefault();
                    
                    var data = {
                    'action'   : 'updateAdminRosterDB', // the name of your PHP function!
                    'roster_data' : JSON.stringify({
                        'style' : {
                            'type' : style,
                            'primaryColor' : primaryColor,
                            'secondaryColor' : secondaryColor,
                            'textColor' : textColor,
                            'containerBgColor' : containerBgColor,
                            'headerTextColor' : headerTextColor
                        },
                        'form_data' : data_arr
                    }),
                    'nonce' : '{$nonce}'
                    };
                    console.log(data)
                    jQuery('#spinner-div').show();
                    jQuery.post(ajaxurl, data, function(response) {
                        if (response) {
                            console.log(response);
                            jQuery("#error_table").remove();
                            jQuery("#roster_edit_table").replaceWith(response);
                        }
                        jQuery('#spinner-div').hide();
                    });
                });
    
    
                jQuery('.row_item').map(function () {
                    const val = jQuery(this).find('input[placeholder="Enter roster name"]');
                    val.after('<p> Shortcode: <b>[ac-roster title="' + val.val() +'"]</b> <p>');
                });
                jQuery('.form-control').on('input propertychange paste ', function () {
                    const p = jQuery(this).next();
                    p.replaceWith('<p> Shortcode: <b>[ac-roster title="' + jQuery(this).val() +'"]</b> <p>')
                })
                
                jQuery('#dynamic_field').on('input propertychange paste ', '.form-control', function () {
                    jQuery(this).parent().next('td').next('td').find('#ac-shortcode').val('[ac-roster title="' + jQuery(this).val() +'"]');
                });

                });	
            </script>	
            JS;
        return $js;
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
