<?php

/**
 * The file that handled building the schedule from hockey tech
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://https://github.com/connormesec/
 * @since      1.0.0
 *
 * @package    Acha_Components
 * @subpackage Acha_Components/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Acha_Components
 * @subpackage Acha_Components/includes
 * @author     Connor Mesec <connormesec@gmail.com>
 */
class Acha_Components_Roster
{
    public $errors;
    public $player_arr;
    private $roster_admin_data;

    //null is used to return all rostered players for admin table
    //title is used to specify which roster should be included based on shortcode atts
    public function __construct($title = null)
    {
        $this->errors = [];
        $this->roster_admin_data = $this->getRosterArrFromOptions();
        $this->player_arr = $this->buildRosterDataObject($title);
    }

    private function getRosterArrFromOptions()
    {
        $opt_name = 'admin_roster_form_data';
        $existing_val = get_option($opt_name);
        $roster_admin_data = json_decode(stripslashes($existing_val));

        return $roster_admin_data;
    }

    private function buildRosterDataObject($title)
    {
        //if $title is null put everything into object and return it
        $arr_of_rosters = $this->roster_admin_data->form_data;
        $style = $this->roster_admin_data->style;
        global $wpdb;
        $table_name = $wpdb->prefix . "roster";
        $roster_edits = $wpdb->get_results("SELECT * FROM $table_name", OBJECT_K);
        $player_arr = [];
        foreach ($arr_of_rosters as $roster) {
            if ($title !== $roster->rosterName && $title !== null) {
                continue;
            }
            if (!$roster->url) {
                //if link is empty break out of current iteration
                array_push($this->errors, 'An input value is empty');
                continue;
            }
            $team_and_schedule_id = $this->get_string_between($roster->url, 'roster/', '?');
            if (!$team_and_schedule_id) {
                //check to see if csv data exists
                $opt_name = 'admin_roster_form_data';
		        $opt_val = json_decode(stripslashes(get_option($opt_name)));
                if($opt_val){
                    foreach($opt_val->form_data as $roster_row){
                        if($roster_row->csvData && $roster_row->url === $roster->url){
                            $csvGameArr = $this->formatCsvDataToSomethingThatWillWorkInThePlayerArr(JSON_decode($roster_row->csvData));
                            $clean_roster_data = (object)[
                                'roster_title' => $roster->rosterName,
                                'roster' => $csvGameArr,
                                'show_last_team' => $roster->last_team,
                                'show_year_in_school' => $roster->year_in_school,
                                'style' => $style,
                                'team_id' => null,
                                'season_id' => null
                            ];
                            array_push($player_arr, $clean_roster_data);
                        }
                    }
                }
                array_push($this->errors, "There was an error with '$roster->url' please make sure it is a valid url");
                continue;
            }
            
            if (!$team_and_schedule_id) {

                array_push($this->errors, "There was an error with '$roster->url' please make sure it is a valid url");
                continue;
            }
            $team_and_schedule_exploded = explode("/", $team_and_schedule_id);
            $team_id = $team_and_schedule_exploded[0];
            $season_id = $team_and_schedule_exploded[1];

            $roster_request_url = "https://lscluster.hockeytech.com/feed/index.php?feed=statviewfeed&view=roster&team_id=" . $team_id . "&season_id=" . $season_id . "&key=e6867b36742a0c9d&client_code=acha&site_id=2&league_id=1&lang=en";
            $raw_roster = json_decode(substr(file_get_contents($roster_request_url), 1, -1))->roster[0]->sections; //this gets the roster from hockey tech, removes the parens, and parses the ugly response to JSON
            $pos_arr = [];
            foreach ($raw_roster as $position) {
                if ($position->title === "Coaches") {
                    continue;
                }
                $position_player_arr = [];
                foreach ($position->data as $player) {
                    if (isset($roster_edits[$player->row->player_id]) && $roster->last_team !== '') {
                        $player->row->last_team = $roster_edits[$player->row->player_id]->last_team;
                    }
                    if (isset($roster_edits[$player->row->player_id]) && $roster->year_in_school !== '') {
                        $player->row->year_in_school = $roster_edits[$player->row->player_id]->year_in_school;
                    }
                    array_push($position_player_arr, $player->row);
                }
                $roster_table_data = (object)[
                    'title' => $position->title,
                    'players' => $position_player_arr
                ];
                array_push($pos_arr, $roster_table_data);
            }
            $clean_roster_data = (object)[
                'roster_title' => $roster->rosterName,
                'roster' => $pos_arr,
                'show_last_team' => $roster->last_team,
                'show_year_in_school' => $roster->year_in_school,
                'style' => $style,
                'team_id' => $team_id,
                'season_id' => $season_id
            ];
            array_push($player_arr, $clean_roster_data);
        }
        return $player_arr;
    }

    public function buildPlayerCardRoster()
    {
        $styles =  $this->roster_admin_data->style;
        if (isset($_GET['player']) && isset($_GET['team']) && isset($_GET['season'])) {
            $player_id = $_GET['player'];
            $team_id = $_GET['team'];
            $season_id = $_GET['season'];
            $playerPage = new Acha_Components_Player_Stat_Page($player_id, $team_id, $season_id, $styles);
            return $playerPage->buildPlayerStatCard();
        } else {
            wp_enqueue_style('player_card', plugin_dir_url(__FILE__) . '../public/css/acha-playerCard-roster.css');
            $css = "
                .player_name, .player_position_title > h2 {
                    color: " . $styles->headerTextColor . ";
                }
                .player_data {
                    border-color: " . $styles->textColor . ";
                }
                .label {
                    color: " . $styles->secondaryColor . ";
                    background: " . $styles->primaryColor . ";
                }
                .info {
                    color:  " . $styles->textColor . "; 
                }
                .player_item {
                    background-color:  " . $styles->containerBgColor . ";
                }";
            wp_add_inline_style('player_card', $css);
            
            $roster_data = $this->player_arr[0];
            $content = '<div class="roster_container">';

            function createPlayerCard($player_id, $team_id, $season_id, $headshot_image, $number, $name, $position, $hometown, $ht, $wt, $shoots, $year_in_school = null, $last_team = null)
            {
                $player_card = '
            <div class="player_item clearfix" id="' . $player_id . '" data-team="'. $team_id .'" data-season="'. $season_id .'">
            <div class="thumb">
                <img src="' . $headshot_image . '" onerror="this.onerror=null;this.src=\'https://www.pathwaysvermont.org/wp-content/uploads/2017/03/avatar-placeholder-e1490629554738.png\';" alt="player headshot" loading="lazy"/>
            </div>
            <div class="info">
                <div class="label">' . $number . '</div>
                <h3 class="player_name">' . $name . '</h3>
                <div class="position">' . $position . '</div>
                <div class="player_data">
                    <div>
                        <span class="shoots">
                        Shoots: ' . $shoots . ' </span>
                        <span class="hometown">
                        Hometown: ' . $hometown . '</span>
                    </div>
                    <div>
                        <span class="height">
                        HT/WT: ' . $ht . ' / ' . $wt . ' </span>';
                    if($year_in_school){
                        $player_card .= '
                            <span class="year">
                            Year: ' . $year_in_school . '</span>';
                    }
                    $player_card .= '
                    </div>';
                    if($last_team){
                        $player_card .= '
                            <div>
                            <span class="prev_team">
                            Last Team: ' . $last_team . '</span>
                            </div>';
                    }
                $player_card .= '
                </div>
            </div>
            </div>
        ';
                return $player_card;
            }
            //forwards
            $content .= '<div class="player_position_title"><h2>Forwards</h2></div>';
            foreach ($roster_data->roster[0]->players as $player) {
                //Maybe use something like this to replace with wordpress logo for image links that don't work
                //$logo = get_theme_mod( 'custom_logo' );
                // $image = wp_get_attachment_image_src( $logo , 'full' );
                // $image_url = $image[0];
                // $image_width = $image[1];
                // $image_height = $image[2];
                $last_team = null;
                if (isset($player->last_team)) {
                    $last_team = $player->last_team;
                }
                $year_in_school = null;
                if (isset($player->year_in_school)) {
                    $year_in_school = $player->year_in_school;
                }
                $headshot_image_link = "https://www.pathwaysvermont.org/wp-content/uploads/2017/03/avatar-placeholder-e1490629554738.png";
                if (isset($player->player_id) && $player->player_id !== null){
                    $headshot_image_link = "https://assets.leaguestat.com/acha/240x240/" . $player->player_id . ".jpg";
                } else if (isset($player->headshot_link) && $player->player_id == null) { //else check csv headshot
                    $headshot_image_link = $player->headshot_link;
                }
                $content .= createPlayerCard($player->player_id, $roster_data->team_id, $roster_data->season_id, $headshot_image_link, $player->tp_jersey_number, $player->name, $player->position, $player->hometown, $player->height_hyphenated, $player->w, $player->shoots, $year_in_school, $last_team);
            }
            //defense
            $content .= '<div class="player_position_title"><h2 class="lower">Defense</h2></div>';
            foreach ($roster_data->roster[1]->players as $player) {
                $last_team = null;
                if (isset($player->last_team)) {
                    $last_team = $player->last_team;
                }
                $headshot_image_link = "https://www.pathwaysvermont.org/wp-content/uploads/2017/03/avatar-placeholder-e1490629554738.png";
                if (isset($player->player_id) && $player->player_id !== null){
                    $headshot_image_link = "https://assets.leaguestat.com/acha/240x240/" . $player->player_id . ".jpg";
                } else if (isset($player->headshot_link) && $player->player_id == null) { //else check csv headshot
                    $headshot_image_link = $player->headshot_link;
                }
                $content .= createPlayerCard($player->player_id, $roster_data->team_id, $roster_data->season_id, $headshot_image_link, $player->tp_jersey_number, $player->name, $player->position, $player->hometown, $player->height_hyphenated, $player->w, $player->shoots, $year_in_school, $last_team);
            }

            //goalies
            $content .= '<div class="player_position_title"><h2 class="lower">Goalies</h2></div>';
            foreach ($roster_data->roster[2]->players as $player) {
                $last_team = null;
                if (isset($player->last_team)) {
                    $last_team = $player->last_team;
                }
                $headshot_image_link = "https://www.pathwaysvermont.org/wp-content/uploads/2017/03/avatar-placeholder-e1490629554738.png";
                if (isset($player->player_id) && $player->player_id !== null){
                    $headshot_image_link = "https://assets.leaguestat.com/acha/240x240/" . $player->player_id . ".jpg";
                } else if (isset($player->headshot_link) && $player->player_id == null) { //else check csv headshot
                    $headshot_image_link = $player->headshot_link;
                }
                $content .= createPlayerCard($player->player_id, $roster_data->team_id, $roster_data->season_id, $headshot_image_link, $player->tp_jersey_number, $player->name, $player->position, $player->hometown, $player->height_hyphenated, $player->w, $player->shoots, $year_in_school, $last_team);
            }
            $content .= '</div>';
            $content .= "
            <script>
            // This is the ajax script that will call a php function to update the db
            jQuery('.player_item').click(function() {
                let playerId = jQuery(this).attr('id');
                if (!playerId) { return; };
                let teamId = jQuery(this).data('team');
                let seasonId = jQuery(this).data('season');
                var searchParams = new URLSearchParams(window.location.search)
                searchParams.set('player', playerId);
                searchParams.set('team', teamId);
                searchParams.set('season', seasonId);
                window.location.search = searchParams.toString();
            });
            </script>
        ";
            return $content;
        }
    }

    public function build_admin_roster_html_table()
    {
        $rosters = $this->player_arr;
        $results = $this->getChangesFromDb();

        //security number used only once that we verify before updating db in ajax call
        $nonce = wp_create_nonce("update_roster_db_nonce");
        $content = '<div class="container">
			<table id="roster_edit_table" class="table table-bordered">';
        $content .= '<tr><th><h5>Roster</h5></th><th><h5>#</h5></th><th><h5>Name</h5></th><th><h5>Pos</h5></th></th><th><h5>Ht</h5></th><th><h5>Wt</h5></th><th><h5>Shoots</h5></th><th><h5>Hometown</h5></th><th><h5>ID</h5></th><th class="last_team_table_header"><h5>Last Team</h5></th><th class="year_in_school_table_header"><h5>Year</h5></th><th><h5>Actions</h5></th></tr>';

        foreach ($rosters as $roster) {
            $roster_title = $roster->roster_title;
            $show_last_team = false;
            if ($roster->show_last_team === 'yes_last_team') {
                $show_last_team = true;
            }
            $show_year_in_school = false;
            if ($roster->show_year_in_school === 'yes_year_in_school') {
                $show_year_in_school = true;
            }
            foreach ($roster->roster as $position) {
                foreach ($position->players as $player) {
                    if ($player->player_id == null) {
                        continue;
                    }
                    $last_team_value = '';
                    if (isset($player->last_team)) {
                        $last_team_value = $player->last_team;
                    }
                    $year_in_school = '';
                    if (isset($player->year_in_school)) {
                        $year_in_school = $player->year_in_school;
                    }
                    $content .= '<tr>';
                    $content .= '<td>' . $roster_title . '</td>';
                    $content .= '<td>' . $player->tp_jersey_number . '</td>';
                    $content .= '<td>' . $player->name . '</td>';
                    $content .= '<td>' . $player->position . '</td>';
                    $content .= '<td>' . $player->height_hyphenated . '</td>';
                    $content .= '<td>' . $player->w . '</td>';
                    $content .= '<td>' . $player->shoots . '</td>';
                    $content .= '<td>' . $player->hometown . '</td>';
                    $content .= '<td id="' . $player->player_id . '">' . $player->player_id . '</td>';
                    $content .= '<td class="last_team_row">';
                    if ($show_last_team) {
                        $content .= '<input id="last_team_' . $player->player_id . '" type="string" value="' . $last_team_value . '">';
                    }
                    $content .= '</td><td class="year_in_school_row">';
                    if ($show_year_in_school) {
                        $content .= '<input id="year_in_school_' . $player->player_id . '" type="string" value="' . $year_in_school . '">';
                    }
                    $content .= '</td><td><button class="save_button" id="_' . $player->player_id . '">Save</button>';
                    $content .= '<span class="successful_save_' . $player->player_id . '" style="display: none; color: green">Saved</span><span class="error_' . $player->player_id . '" style="display: none; color: red">Error</span></td>';
                    $content .= '</tr>';
                    $content .= "<script>
                    // This is the ajax script that will call a php function to update the db
                    jQuery('#_" . $player->player_id . "').click(function() {

                        var last_team_input = jQuery('#last_team_" . $player->player_id . "').val();
                        var year_in_school_input = jQuery('#year_in_school_" . $player->player_id . "').val();

                        var data = {
                        'action'   : 'updateRosterDB', // the name of your PHP function!
                        'last_team_input' : last_team_input,
                        'year_in_school_input' : year_in_school_input,
                        'player_id'   : " . $player->player_id . ",
                        'nonce' : '" . $nonce . "'
                        };
                        jQuery.post(ajaxurl, data, function(response) {
                            if(response){
                                jQuery('.successful_save_" . $player->player_id . "').fadeIn().delay(1500).fadeOut();
                            }else{
                                jQuery('.error_" . $player->player_id . "').fadeIn().delay(1500).fadeOut();
                            };
                        });
                    });
                    </script>";
                }
            }
        }
        $content .= '</table></div>';
        return $content;
    }

    private function getChangesFromDb()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . "roster";
        return $wpdb->get_results("SELECT * FROM $table_name", OBJECT_K);
    }

    private function get_string_between($string, $start, $end)
    {
        $string = ' ' . $string;
        $ini = strpos($string, $start);
        if ($ini == 0) return '';
        $ini += strlen($start);
        $len = strpos($string, $end, $ini) - $ini;
        return substr($string, $ini, $len);
    }

    private function formatCsvDataToSomethingThatWillWorkInThePlayerArr($csvData){
        $roster[0] = (object) array(
            'title' => 'Forwards',
            'players' => array()
        );
        $roster[1] = (object) array(
            'title' => 'Defense',
            'players' => array()
        );
        $roster[2] = (object) array(
            'title' => 'Goalies',
            'players' => array()
        );
        foreach($csvData as $player){
           if( strtolower($player->pos) == 'forward' || strtolower($player->pos) == 'f'){
                $target_team = (object)[
                    'headshot_link' => $player->headshot_link,
                    'height_hyphenated' => $player->ht,
                    'hometown' => $player->hometown,
                    'last_team' => $player->last_team,
                    'name' => $player->name,
                    'player_id' => null,
                    'position' => $player->pos,
                    'rookie' => null,
                    'shoots' => $player->shoots,
                    'tp_jersey_number' => $player->number,
                    'w' => $player->wt,
                    'year_in_school' => $player->year_in_school,
                    'major' => $player->major,
                ];
                array_push($roster[0]->players, $target_team);
            } else if ( strtolower($player->pos) == 'defense' || strtolower($player->pos) == 'd'){
                $target_team = (object)[
                    'headshot_link' => $player->headshot_link,
                    'height_hyphenated' => $player->ht,
                    'hometown' => $player->hometown,
                    'last_team' => $player->last_team,
                    'name' => $player->name,
                    'player_id' => null,
                    'position' => $player->pos,
                    'rookie' => null,
                    'shoots' => $player->shoots,
                    'tp_jersey_number' => $player->number,
                    'w' => $player->wt,
                    'year_in_school' => $player->year_in_school,
                    'major' => $player->major,
                ];
                array_push($roster[1]->players, $target_team); 
            } else if ( strtolower($player->pos) == 'goalie' || strtolower($player->pos) == 'g'){
                $target_team = (object)[
                    'headshot_link' => $player->headshot_link,
                    'height_hyphenated' => $player->ht,
                    'hometown' => $player->hometown,
                    'last_team' => $player->last_team,
                    'name' => $player->name,
                    'player_id' => null,
                    'position' => $player->pos,
                    'rookie' => null,
                    'shoots' => $player->shoots,
                    'tp_jersey_number' => $player->number,
                    'w' => $player->wt,
                    'year_in_school' => $player->year_in_school,
                    'major' => $player->major,
                ];
                array_push($roster[2]->players, $target_team); 
            }
        }
        return $roster;
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
