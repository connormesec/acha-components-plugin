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
class Acha_Components_Schedule
{
    public $input;
    public $schedule_arr;
    public $style;
    public $errors;

    public function __construct($arr_of_schedule_urls, $style = null)
    {
        $this->errors = [];
        $this->input = $arr_of_schedule_urls;
        $this->style = $style;
        $temp_arr = $this->createGameScheduleArr($arr_of_schedule_urls);
        $this->schedule_arr = $temp_arr;
    }

    public function createGameScheduleArr($arr_of_schedule_urls)
    {
        $schedule_edits = $this->getChangesFromDb();

        $game_schedule_arr = array();
        foreach ($arr_of_schedule_urls as $link) {
            if (!$link) {
                //if link is empty break out of current iteration
                array_push($this->errors, 'An input value is empty');
                continue;
            }
            $team_and_schedule_id = $this->_get_string_between($link, 'schedule/', '/all-months');
            if (!$team_and_schedule_id) {

                array_push($this->errors, "There was an error with '$link' please make sure it is a valid url");
                continue;
            }
            $team_and_schedule_exploded = explode("/", $team_and_schedule_id);
            $team_id = $team_and_schedule_exploded[0];
            $season_id = $team_and_schedule_exploded[1];
            $division_id = $this->_get_string_between($link, 'division_id=', '&');

            $schedule_request_url = "https://lscluster.hockeytech.com/feed/index.php?feed=statviewfeed&view=schedule&team=" . $team_id . "&season=" . $season_id . "&month=-1&location=homeaway&key=e6867b36742a0c9d&client_code=acha&site_id=2&league_id=1&division_id=" . $division_id  . "&lang=en";
            $get_schedule = @file_get_contents($schedule_request_url);
            if ($get_schedule === false) {
                $error = error_get_last();
                array_push($this->errors, "HTTP request failed. Error was: " . $error['message']);
                continue;
            }
            $raw_schedule = json_decode(substr($get_schedule, 1, -1))[0]->sections[0]->data; //this gets the schedule from hockey db, removes the parens, and parses the ugly response to JSON
            //todo make this so that one call is made instead of one call for each link
            //currently the Hockey Tech api does not allow for a call to get all teams
            //to get all teams one call would need to be made to get all mens teams D1, D2, & D3
            //then one call to get W1 and a third to get W2 which is sketch
            $team_logo_request_url = "https://lscluster.hockeytech.com/feed/index.php?feed=statviewfeed&view=teamsForSeason&season=" . $season_id . "&division=" . $division_id  . "&key=e6867b36742a0c9d&client_code=acha&site_id=2";
            $logo_arr = json_decode(substr(file_get_contents($team_logo_request_url), 1, -1))->teams;
            $a = array();
            foreach ($logo_arr as $key => $value) {
                $a[$value->id] = $value;
            }
            $b = array();
            foreach ($raw_schedule as $game) {
                $game_date_time_message = $game->row->date_with_day;
                $game_time = '';
                if ($game->row->home_goal_count == '-') {
                    $game_date_time_message = $game->row->date_with_day . ' @ ' . $game->row->game_status;
                    $game_time = $game->row->game_status;
                }

                $has_extra_game_details = false;
                $header_value = '';
                $text_value = '';
                $img_link = '';
                if (isset($schedule_edits[$game->row->game_id])) {
                    $has_extra_game_details = true;
                    $header_value = $this->convertTextToHtml(stripslashes($schedule_edits[$game->row->game_id]->header_text));
                    $text_value = $this->convertTextToHtml(stripslashes($schedule_edits[$game->row->game_id]->text));
                    $img_link = $schedule_edits[$game->row->game_id]->img_link;
                }
                $month = $this->_get_string_between($game->row->date_with_day, ", ", " ");
                preg_match_all('/[0-9]/', $game->row->date_with_day, $day_temp);
                $day = (int)implode("", $day_temp[0]);
                $time = $game->row->game_status;
                $target_team = (object)[];
                if ($team_id == $game->prop->home_team_city->teamLink) {
                    $target_team = (object)[
                        'target_team_name' => $game->row->home_team_city,
                        'opponent_team_name' => $game->row->visiting_team_city,
                        'target_team_nickname' => $a[$game->prop->home_team_city->teamLink]->nickname,
                        'opponent_team_nickname' => $a[$game->prop->visiting_team_city->teamLink]->nickname,
                        'target_team_logo' => $a[$game->prop->home_team_city->teamLink]->logo,
                        'opponent_team_logo' => $a[$game->prop->visiting_team_city->teamLink]->logo,
                        'target_team_id' => $game->prop->home_team_city->teamLink,
                        'opponent_team_id' => $game->prop->visiting_team_city->teamLink,
                        'target_score' => $game->row->home_goal_count,
                        'opponent_score' => $game->row->visiting_goal_count,
                        'game_date_time_message' => $game_date_time_message,
                        'game_status' => $game->row->game_status,
                        'game_date_day' => $game->row->date_with_day,
                        'game_id' => $game->row->game_id,
                        'order' => $this->_ghettoOrder($month, $day, $time)->order_value,
                        'month' => $this->_ghettoOrder($month, $day, $time)->month,
                        'home_or_away' => 'home',
                        'game_time' => $game_time,
                        'venue_name' => $game->row->venue_name,
                        'has_extra_game_details' => $has_extra_game_details,
                        'promotion_header_value' => $header_value,
                        'promotion_text_value' => $text_value,
                        'promotion_img_url' => $img_link
                    ];
                } else {
                    $target_team = (object)[
                        'opponent_team_name' => $game->row->home_team_city,
                        'target_team_name' => $game->row->visiting_team_city,
                        'opponent_team_nickname' => $a[$game->prop->home_team_city->teamLink]->nickname,
                        'targett_team_nickname' => $a[$game->prop->visiting_team_city->teamLink]->nickname,
                        'opponent_team_logo' => $a[$game->prop->home_team_city->teamLink]->logo,
                        'target_team_logo' => $a[$game->prop->visiting_team_city->teamLink]->logo,
                        'opponent_team_id' => $game->prop->home_team_city->teamLink,
                        'target_score' => $game->row->visiting_goal_count,
                        'opponent_score' => $game->row->home_goal_count,
                        'target_team_id' => $game->prop->visiting_team_city->teamLink,
                        'game_date_time_message' => $game_date_time_message,
                        'game_status' => $game->row->game_status,
                        'game_date_day' => $game->row->date_with_day,
                        'game_id' => $game->row->game_id,
                        'order' => $this->_ghettoOrder($month, $day, $time)->order_value,
                        'month' => $this->_ghettoOrder($month, $day, $time)->month,
                        'home_or_away' => 'away',
                        'game_time' => $game_time,
                        'venue_name' => $game->row->venue_name,
                        'has_extra_game_details' => $has_extra_game_details,
                        'promotion_header_value' => $header_value,
                        'promotion_text_value' => $text_value,
                        'promotion_img_url' => $img_link
                    ];
                }
                array_push($b, $target_team);
            }
            $game_schedule_arr = array_merge($game_schedule_arr, $b);
        }
        //sort the game schedule array by order
        usort($game_schedule_arr, function ($a, $b) {
            return $a->order <=> $b->order;
        });

        return $game_schedule_arr;
    }

    public function buildPillSchedule()
    {
        $game_schedule_arr = $this->schedule_arr;
        $styles = $this->style;
        wp_enqueue_style('pill_style', plugin_dir_url(__FILE__) . '../public/css/acha-pill-schedule.css');
        $css = ".home{
                color: " . $styles->primaryColor . ";
                background-color: " . $styles->secondaryColor . ";
            }
            .game_month_title, .btn_header_wrap, .header_btn {
                color: " . $styles->headerTextColor . ";
            }
            .game_date, .op_nickname, .op_name, .game_time, .arena, .Chevron, .promotion, .promo_dropdown_header, .promotion_text {
                color:  " . $styles->textColor . "; 
            }
            .game_container, .accordion-content {
                background-color:  " . $styles->gameContainerColor . ";
            }";
        wp_add_inline_style('pill_style', $css);

        //determine right now in connortime
        $now = new DateTime();
        $now_connortime = $this->_ghettoOrder($now->format("M"), $now->format("j"), $now->format("g:i A"))->order_value;
        $past_games = array();
        $future_games = array();
        foreach ($game_schedule_arr as $game) {
            if ($game->order < $now_connortime) {
                array_push($past_games, $game);
            } else {
                array_push($future_games, $game);
            }
        }
        $past_future_games = (object)[
            'past_games' => $this->array_group_by($past_games, 'month'),
            'future_games' => $this->array_group_by($future_games, 'month')
        ];

        $months = array('August', 'September', 'October', 'November', 'December', 'January', 'February', 'March', 'April');
        $content = ' <div class="btn_header_wrap">
                <button class="header_btn" id="upcoming_games_btn" onclick="show_hide(\'future_games\',\'past_games\', \'upcoming_games_btn\', \'past_games_btn\')">Upcoming</button>
                <button class="header_btn" id="past_games_btn" onclick="show_hide(\'past_games\', \'future_games\', \'past_games_btn\', \'upcoming_games_btn\')">Past</button>
                </div>';

        $content .= '<div class="schedule_container css-transitions-only-after-page-load">';

        $content .= '<div id="past_games" class="past_games" style="display: none;">';
        foreach (array_reverse($months) as $month) {
            $content .= $this->buildScheduleByMonth($past_future_games->past_games, $month, true);
        }
        $content .= '</div>';

        $content .= '<div id="future_games" class="future_games">';
        foreach ($months as $month) {
            $content .= $this->buildScheduleByMonth($past_future_games->future_games, $month, false);
        }
        $content .= '</div>';

        $content .= '
        <script>
            function show_hide(show, hide, active_btn_id, unactive_btn_id) {
                var show = document.getElementById(show);
                var hide = document.getElementById(hide);
                var active_btn = document.getElementById(active_btn_id);
                var unactive_btn = document.getElementById(unactive_btn_id);
                if (show.style.display === "none") {
                show.style.display = "block";
                hide.style.display = "none";
                active_btn.style.opacity = "1";
                unactive_btn.style.opacity = "0.5";
                }
            }

            const accordionBtns = document.querySelectorAll(".pill_accordion");

            accordionBtns.forEach((accordion) => {
                accordion.onclick = function () {
                this.classList.toggle("is-open");

            let content = this.nextElementSibling;
            console.log(content);

            if (content.style.maxHeight) {
            //this is if the accordion is open
            content.style.maxHeight = null;
            } else {
            //if the accordion is currently closed
            content.style.maxHeight = content.scrollHeight + "px";
            console.log(content.style.maxHeight);
    }
  };
});
        </script>
    ';
        return $content;
    }

    private function buildScheduleByMonth(array $month_games, string $month, bool $is_past)
    {
        $content = '';
        if (isset($month_games[$month])) {

            $content .= '<div class="month_container">
                            <h2 class="game_month_title">' . $month . '</h2>';
            if ($is_past) {
                foreach (array_reverse($month_games[$month]) as $game) {
                    $content .= $this->createEachGame($game, false);
                }
            } else {
                foreach ($month_games[$month] as $game) {
                    $content .= $this->createEachGame($game, true);
                }
            }
            $content .= '</div>';
        }
        return $content;
    }

    private function createEachGame($game, bool $should_hide_score)
    {
        $game_result_message = '';
        if ($game->game_status == 'Final') {
            if ($game->target_score < $game->opponent_score) {
                $game_result_message = 'L';
            } elseif ($game->target_score > $game->opponent_score) {
                $game_result_message = 'W';
            } else {
                $game_result_message = 'T';
            }
        } elseif ($game->game_status == 'Final OT') {
            if ($game->target_score < $game->opponent_score) {
                $game_result_message = 'OTL';
            } elseif ($game->target_score > $game->opponent_score) {
                $game_result_message = 'OTW';
            } else {
                $game_result_message = 'OT';
            }
        } elseif ($game->game_status == 'Final SO') {
            if ($game->target_score < $game->opponent_score) {
                $game_result_message = 'SOL';
            } elseif ($game->target_score > $game->opponent_score) {
                $game_result_message = 'SOW';
            } else {
                $game_result_message = 'SOT';
            }
        } else {
            if ($game->target_score < $game->opponent_score) {
                $game_result_message = 'L';
            } elseif ($game->target_score > $game->opponent_score) {
                $game_result_message = 'W';
            } else {
                $game_result_message = '';
            }
        }

        $hide = '';
        $accordion = '';
        $promo_header = '';
        $chevron = '';
        $promo_content = '';
        if ($should_hide_score === true) {
            $hide = 'style="display: none;"';

            if ($game->promotion_text_value || $game->promotion_img_url) {
                $accordion = 'pill_accordion';
                $chevron = '<div class="arrow_wrap">
                    <span class="Chevron"></span>
                </div>';
                $promo_content = '<div class="accordion-content">
                    <div class="item game_promotion_image">
                            <img src="' . $game->promotion_img_url . '" alt="">
                    </div>
                <div class="promo_group">
                    <div class="item promo_dropdown_header">
                        ' . $game->promotion_header_value . '
                    </div>
                    <div class="item promotion_text">
                    
                        ' . $game->promotion_text_value . '
                    
                    </div>
                </div>
                </div>';
            }
            if ($game->promotion_header_value) {
                $promo_header = '
                <div class="promotion">
                    ' . $game->promotion_header_value . '
                </div>
                ' . $chevron;
            }
        }
        $content = '
                    <div class="game_container ' . $accordion . '">
                        <div class="date_time_location">
                            <div class="home_away_container ' . $game->home_or_away . '">
                                <div class="home_away_text">
                                    ' . $game->home_or_away . '
                                </div>
                            </div>
                            <div class="date_time_container">
                                <div class="game_date">
                                    ' . $game->game_date_day . '
                                </div>
                                <div class="game_time">
                                    ' . $game->game_time . '
                                </div>
                            </div>
                        </div>
                        <div class="op_logo_name">
                            <img src="' . $game->opponent_team_logo . '" class="op_logo">
                            <div class="op_name_nickname">
                                <div class="op_name">
                                    ' . $game->opponent_team_name . '
                                </div>
                                <div class="op_nickname">
                                    ' . $game->opponent_team_nickname . '
                                </div>
                            </div>
                        </div>
                        <div class="arena">
                            ' . $game->venue_name . '
                        </div>
                        <div class="results"' . $hide . '>
                            <div class="results_wrap">
                                <span class="results_text_wrap">
                                    <span class="results_text_WL win_or_loss_' . $game_result_message . '">
                                        ' . $game_result_message . '
                                    </span>
                                    <span class="results_text_score">
                                        ' . $game->target_score . '-' . $game->opponent_score . ' 
                                    </span>
                                </span>
                            </div>
                        </div>
                        ' . $promo_header . '
                    </div>
                    ' . $promo_content;
        return $content;
    }

    public function buildDropdownSchedule()
    {
        $game_schedule_arr = $this->schedule_arr;
        $styles = $this->style;
        wp_enqueue_style('dropdown_style', plugin_dir_url(__FILE__) . '../public/css/acha-dropdown-schedule.css');
        $css = "
        .accordion.active .title_right_content:after {
            border-bottom-color: " . $styles->headerTextColor . ";
        }
        .accordion:before {
            background: " . $styles->secondaryColor . ";
        }
        .game_month_title, .title_right_content, .accordion {
            color: " . $styles->headerTextColor . ";
            background: " . $styles->primaryColor . ";
        }
        .date, .time, .vs, .team_title, .game_outcome {
            color:  " . $styles->textColor . "; 
        }
        .game_list {
            border-bottom-color: " . $styles->secondaryColor . "; 
        }
        .panel {
            background-color:  " . $styles->gameContainerColor . ";
        }";
        wp_add_inline_style('dropdown_style', $css);
        $month_games = $this->array_group_by($game_schedule_arr, 'month');
        $months = array('August', 'September', 'October', 'November', 'December', 'January', 'February', 'March', 'April');

        //determine right now in connortime
        $now = new DateTime();
        $now_connortime = $this->_ghettoOrder($now->format("M"), 1, 1)->order_value;

        $content = '<div class="schedule_container css-transitions-only-after-page-load">';
        foreach ($months as $month) {
            if (isset($month_games[$month])) {
                $showActive = '';
                if ($this->_ghettoOrder($month, 1, 1) >= $now_connortime) {
                    $showActive = ' active';
                }
                $month_title = $month;
                $content .= '<div class="month_container">
                                <div class="accordion' . $showActive . '">
                                    <h2 class="game_month_title">' . $month_title . '</h2>
                                    <span class="title_right_content">open</span>
                                </div>
                            <div class="panel' . $showActive . '">';

                foreach ($month_games[$month] as $game) {
                    $vs_at = 'VS';
                    if ($game->home_or_away === 'away') {
                        $vs_at = 'AT';
                    }

                    $promotion_section = '';
                    if ($game->has_extra_game_details) {
                        $promotion_section = $this->addGameDetails($game->promotion_header_value, $game->promotion_text_value, $game->promotion_img_url);
                    }
                    $game_result_message = $this->gameResultMessage($game);
                    $content .= '<div class="game_list">
                                <div class="date-time">
                                    <span class="date">' . $game->game_date_day . '</span>
                                    <span class="time">' . $game->game_status . '</span>
                                </div>
                                <div class="team_info">
                                    <img class="msu_thumb" src="' . $game->target_team_logo . '"/>
                                    <span class="vs">' . $vs_at . '</span>
                                    <img src="' . $game->opponent_team_logo . '"/>
                                    <span class="team_title">' . $game->opponent_team_name . '</span>
                                </div>
                                <div class="game_detail">
                                    <span class="game_outcome">' . $game_result_message . ': ' . $game->target_score . ' - ' . $game->opponent_score . '</span>
                                </div>
                                ' . $promotion_section . '
                            </div>';
                }
            }
            $content .= '</div></div>';
        }

        $content .= '</div>';
        $content .= '<script>
                        var acc = document.getElementsByClassName("accordion");
                        var i;
    
                        for (i = 0; i < acc.length; i++) {
                          acc[i].addEventListener("click", function() {
                            if(!this.classList.contains("active")){
                                this.classList.add("active");
                                this.nextElementSibling.classList.add("active");
                            }else{
                                this.classList.remove("active");
                                this.nextElementSibling.classList.remove("active");
                            }
                            var panel = this.nextElementSibling;
                            if (panel.style.maxHeight) {
                              panel.style.maxHeight = null;
                            } else {
                              panel.style.maxHeight = panel.scrollHeight + "px";
                            }
                          });
                        }
                        //toggle max height for inital panels if active
                        var x = document.getElementsByClassName("panel active");
                        var y;
                        for (y = 0; y < x.length; y++) {
                            x[y].style.maxHeight = x[y].scrollHeight + "px";
                        }
                        //makes sure to not show transition until after page load, css was changed too
                        $(document).ready(function () {
                            $(".css-transitions-only-after-page-load").each(function (index, element) {
                                setTimeout(function () { $(element).removeClass("css-transitions-only-after-page-load") }, 10);
                            });
                        });
                    </script>';
        return $content;
    }

    private function addGameDetails($header, $text, $img_url)
    {
        $content = '
            <div class="game_promotions_container" data-toggle-id="461">
				<div class="game_detail_reveal game_promotion_item">
					<div class="game_promotion_image">
						<img src="' . $img_url . '" alt="">
                    </div>
					<div class="game_promotion_body">
						<p class="game_promotion_item_header">
							<strong>' . $header . ' </strong>
						</p>
						<p>' . $text . '</p>
					</div>
				</div>
			</div>
        ';
        return $content;
    }

    public function build_admin_schedule_html_table()
    {
        $arr = $this->schedule_arr;
        $results = $this->getChangesFromDb();

        //security number used only once that we verify before updating db in ajax call
        $nonce = wp_create_nonce("update_schedule_db_nonce");
        $content = '<div class="container">
			<table id="schedule_edit_table" class="table table-bordered">';
        foreach ($arr as $result) {
            $text_value = '';
            $header_value = '';
            $img_link = '';
            if (isset($results[$result->game_id])) {
                $header_value = $results[$result->game_id]->header_text;
                $text_value = $results[$result->game_id]->text;
                $img_link = $results[$result->game_id]->img_link;
            }
            $content .= '<tr>';
            $content .= '<td>' . $result->game_date_time_message . '</td>';
            $content .= '<td>' . $result->target_team_name . '</td>';
            $content .= '<td>' . $result->target_score . '</td>';
            $content .= '<td>' . $result->opponent_team_name . '</td>';
            $content .= '<td>' . $result->opponent_score . '</td>';
            $content .= '<td id="' . $result->game_id . '">' . $result->game_id . '</td>';
            $content .= '<td><input id="header_input_' . $result->game_id . '" type="string" value="' . stripslashes($header_value) . '"></td>';
            $content .= '<td><textarea id="text_input_' . $result->game_id . '">' . stripslashes($text_value) . '</textarea></td>';
            $content .= '<td><input id="img_input_' . $result->game_id . '" type="string" value="' . $img_link . '"></td>';
            $content .= '<td><button id="_' . $result->game_id . '">Save</button>';
            $content .= '<span class="successful_save_' . $result->game_id . '" style="display: none; color: green">Saved</span><span class="error_' . $result->game_id . '" style="display: none; color: red">Error</span></td>';
            $content .= '</tr>';
            $content .= "<script>
			// This is the ajax script that will call a php function to update the db
			jQuery('#_" . $result->game_id . "').click(function() {

				var text_input = jQuery('#text_input_" . $result->game_id . "').val();
				var header_input = jQuery('#header_input_" . $result->game_id . "').val();
				var img_input = jQuery('#img_input_" . $result->game_id . "').val();

				var data = {
				   'action'   : 'updateScheduleDB', // the name of your PHP function!
				   'text_input' : text_input,
				   'header_input' : header_input,
					'img_input' : img_input,
				   'game_id'   : " . $result->game_id . ",
				   'nonce' : '" . $nonce . "'
				   };
                console.log(data);
				   jQuery.post(ajaxurl, data, function(response) {
					if(response){
						jQuery('.successful_save_" . $result->game_id . "').fadeIn().delay(1500).fadeOut();
					}else{
						jQuery('.error_" . $result->game_id . "').fadeIn().delay(1500).fadeOut();
					};
				  });
            });
            </script>
            ";
        }
        $content .= '</table></div>';
        return $content;
    }

    private function gameResultMessage($game)
    {
        $game_result_message = '';
        if ($game->game_status == 'Final') {
            if ($game->target_score < $game->opponent_score) {
                $game_result_message = 'L';
            } elseif ($game->target_score > $game->opponent_score) {
                $game_result_message = 'W';
            } else {
                $game_result_message = 'T';
            }
        } elseif ($game->game_status == 'Final OT') {
            if ($game->target_score < $game->opponent_score) {
                $game_result_message = 'OTL';
            } elseif ($game->target_score > $game->opponent_score) {
                $game_result_message = 'OTW';
            } else {
                $game_result_message = 'OT';
            }
        } elseif ($game->game_status == 'Final SO') {
            if ($game->target_score < $game->opponent_score) {
                $game_result_message = 'SOL';
            } elseif ($game->target_score > $game->opponent_score) {
                $game_result_message = 'SOW';
            } else {
                $game_result_message = 'SOT';
            }
        } else {
            if ($game->target_score < $game->opponent_score) {
                $game_result_message = 'L';
            } elseif ($game->target_score > $game->opponent_score) {
                $game_result_message = 'W';
            } else {
                $game_result_message = '';
            }
        }
        return $game_result_message;
    }

    private function array_group_by(array $array, $key)
    {
        if (!is_string($key) && !is_int($key) && !is_float($key) && !is_callable($key)) {
            trigger_error('array_group_by(): The key should be a string, an integer, or a callback', E_USER_ERROR);
            return null;
        }

        $func = (!is_string($key) && is_callable($key) ? $key : null);
        $_key = $key;

        // Load the new array, splitting by the target key
        $grouped = [];
        foreach ($array as $value) {
            $key = null;

            if (is_callable($func)) {
                $key = call_user_func($func, $value);
            } elseif (is_object($value) && property_exists($value, $_key)) {
                $key = $value->{$_key};
            } elseif (isset($value[$_key])) {
                $key = $value[$_key];
            }

            if ($key === null) {
                continue;
            }

            $grouped[$key][] = $value;
        }

        // Recursively build a nested grouping if more parameters are supplied
        // Each grouped array value is grouped according to the next sequential key
        if (func_num_args() > 2) {
            $args = func_get_args();

            foreach ($grouped as $key => $value) {
                $params = array_merge([$value], array_slice($args, 2, func_num_args()));
                $grouped[$key] = call_user_func_array('array_group_by', $params);
            }
        }

        return $grouped;
    }

    //this function aims to solve the problem that not having a year provided from the hcokey tech response causes
    //This turns the season into an int that can be compared to others
    //ie Feb, 25, 8:15 pm MST becomes 6252015
    //6000000(FEB) + 25th(250000) + 8:15 pm (800 + 1200(pm) + 15)
    //it's like a crappy unix time... connor time...
    protected function _ghettoOrder($month, $day, $time)
    {
        (int)$order_value = 0;
        $long_month = '';

        if ($month == "Aug" || $month == "August") {
            $order_value = 1000000;
            $long_month = 'August';
        } elseif ($month == "Sep" || $month == "September") {
            $order_value = 2000000;
            $long_month = 'September';
        } elseif ($month == "Oct" || $month == "October") {
            $order_value = 3000000;
            $long_month = 'October';
        } elseif ($month == "Nov" || $month == "November") {
            $order_value = 4000000;
            $long_month = 'November';
        } elseif ($month == "Dec" || $month == "December") {
            $order_value = 5000000;
            $long_month = 'December';
        } elseif ($month == "Jan" || $month == "January") {
            $order_value = 6000000;
            $long_month = 'January';
        } elseif ($month == "Feb" || $month == "February") {
            $order_value = 7000000;
            $long_month = 'February';
        } elseif ($month == "Mar" || $month == "March") {
            $order_value = 8000000;
            $long_month = 'March';
        } elseif ($month == "Apr" || $month == "April") {
            $order_value = 9000000;
            $long_month = 'April';
        } else {
            $order_value = 0;
        }
        $order_value = $order_value + ($day * 10000);
        if (preg_match("/am|AM|pm|PM/i", $time) == 1) {
            $temp = explode(":", $time);
            $hour = $temp[0];
            $min = explode(" ", $temp[1])[0];
            if (preg_match("/pm|PM/i", $time) == 1) {
                $order_value = (int)$order_value + ($hour * 100) + 1200 + $min;
            } else {
                $order_value = (int)$order_value + ($hour * 100) + $min;
            }
        }
        $obj = (object)[
            'order_value' => $order_value,
            'month' => $long_month
        ];
        return $obj;
    }

    private function getChangesFromDb()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . "schedule";
        return $wpdb->get_results("SELECT * FROM $table_name", OBJECT_K);
    }

    private function _sortByOrder($a, $b)
    {
        return $a->id - $b->id;
    }

    private function _get_string_between($string, $start, $end)
    {
        $string = ' ' . $string;
        $ini = strpos($string, $start);
        if ($ini == 0) return '';
        $ini += strlen($start);
        $len = strpos($string, $end, $ini) - $ini;
        return substr($string, $ini, $len);
    }

    private function convertTextToHtml($text) {
        $noBreaks = $text;
        $linebs = "<br />";
        $noBreaks = str_replace(array("\r\n", "\n", "\r"), "XiLBXZ", $noBreaks);
        $re4 = '/XiLBXZXiLBXZ/i';
        $noBreaks = preg_replace($re4, "</p><p>", $noBreaks);
        $re5 = '/XiLBXZ/gi';
        $noBreaks = str_replace($re5, $linebs . "\r\n", $noBreaks);
        $noBreaks = "<p>" . $noBreaks . "</p>";
        $noBreaks = str_replace("<p></p>", "", $noBreaks);
        $noBreaks = str_replace("\r\n\r\n", "", $noBreaks);
        $noBreaks = preg_replace('/<\/p><p>/i', "</p>\r\n\r\n<p>", $noBreaks);
        $noBreaks = str_replace("<p><br />", "<p>", $noBreaks);
        $noBreaks = str_replace("<p><br>", "<p>", $noBreaks);
        return $noBreaks;
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
