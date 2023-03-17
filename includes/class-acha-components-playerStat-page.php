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
class Acha_Components_Player_Stat_Page
{
    public $player_info;
    public $player_stats;
    public $style;
    function __construct($player_id, $team_id, $style)
    {
        $this->style = $style;
        $temp_data = $this->getAndCleanPlayerData($player_id, $team_id);
        $this->player_info = $temp_data->player_info;
        $this->player_stats = $temp_data->player_stats;
    }

    private function getAndCleanPlayerData($player_id, $team_id)
    {
        $raw_data = $this->makeAsyncCurlRequests($player_id, $team_id);
        $raw_player = json_decode(substr($raw_data->raw_player_data, 1, -1));
        $raw_team = json_decode(substr($raw_data->raw_team_data, 1, -1))->roster[0]->sections;
        //search for player hometown
        $hometown = '';
        foreach ($raw_team as $position) {
            if ($hometown !== '') {
                break;
            }
            if ($position->title === "Coaches") {
                continue;
            }
            foreach ($position->data as $player) {
                if ($player->prop->name->playerLink == $player_id) {
                    $hometown = $player->row->hometown;
                    break;
                }
            }
        }
        $cleaned_up_stats = $raw_player->careerStats[0]->sections;
        $cleaned_up_player_info = $raw_player->info;
        $cleaned_up_player_info->hometown = $hometown;

        global $wpdb;
        $table_name = $wpdb->prefix . "roster";
        $roster_edits = $wpdb->get_results("SELECT * FROM $table_name", OBJECT_K);
        if (isset($roster_edits[$player->row->player_id])) {
            if (isset($roster_edits[$player->row->player_id]->last_team)) {
                $cleaned_up_player_info->last_team = $roster_edits[$player->row->player_id]->last_team;
            }
            if (isset($roster_edits[$player->row->player_id]->year_in_school)) {
                $cleaned_up_player_info->year_in_school = $roster_edits[$player->row->player_id]->year_in_school;
            }
        }

        $regular_season = [];
        foreach ($cleaned_up_stats[0]->data as $season) {
            array_push($regular_season, $season->row);
        }
        $playoffs = [];
        foreach ($cleaned_up_stats[1]->data as $season) {
            array_push($playoffs, $season->row);
        }
        $player_stats = (object)[
            'regularSeason' => $regular_season,
            'playoffs' => $playoffs
        ];
        $player_data = (object)[
            'player_stats' => $player_stats,
            'player_info' => $cleaned_up_player_info
        ];
        return $player_data;
    }

    public function buildPlayerStatCard()
    {
        wp_enqueue_style('playerStatCard_style', plugin_dir_url(__FILE__) . '../public/css/acha-components-playerStatCard.css');
        $styles = $this->style;
        $css = "
        .player_name, .player_position_title > h2 {
            color: " . $styles->headerTextColor . ";
        }
        .player_data {
            border-color: " . $styles->textColor . ";
        }
        .widget_title_wrapper, .table-wrapper table tr th.table_header {
            color: " . $styles->secondaryColor . ";
            background: " . $styles->primaryColor . ";
        }
        .player_info, .player_top, h1.player-name, h3.player-position, .player_bio_inner h2.player-number, .player_stats_holder .h2, .table-wrapper table tr td {
            color:  " . $styles->textColor . "; 
        }
        #content {
            background-color:  " . $styles->containerBgColor . ";
        }";
        wp_add_inline_style('playerStatCard_style', $css);
        $stats = $this->player_stats;
        $info = $this->player_info;
        $last_team_content = '';
        if (isset($info->last_team) && $info->last_team !== '') {
            $last_team_content = '<li>Last Team: ' . $info->last_team . '</li>';
        }
        $year_in_school_content = '';
        if (isset($info->year_in_school) && $info->year_in_school !== '') {
            $year_in_school_content = '<li>Year: ' . $info->year_in_school . '</li>';
        }
        $regular_season_content = '';
        if (isset($stats->regularSeason) && $stats->regularSeason) {
            $regular_season_content = '
            <div class="h2">Regular Season</div>
            <div class="table-wrapper" style="overflow:auto;">
                ' . $this->buildStatTable($stats->regularSeason, $info->position) . '
            </div>
            ';
        }
        $playoff_content = '';
        if (isset($stats->playoffs) && $stats->playoffs) {
            $playoff_content = '
            <div class="h2">Playoffs</div>
            <div class="table-wrapper" style="overflow:auto;">
                ' . $this->buildStatTable($stats->playoffs, $info->position) . '
            </div>
            ';
        }
        $shoots = '<li>Shoots: ' . $info->shoots . '</li>';
        if($info->position == 'G' && isset($info->catches)){
            $shoots = '<li>Catches: ' . $info->catches . '</li>';
        }
        $content = '
            <div id="content">
                <div class="player_bio_holder">
                    <div class="player_bio_inner">
                        <div class="player_img">
                            <img src="' . $info->profileImage . '" alt="">
                        </div>
                        <div class="player_info">
                            <div class="player_top">
                                <h1 class="player-name">' . $info->firstName . ' ' . $info->lastName . '</h1>
						        <h3 class="player-position">' . $info->position . '</h3>
						        <h2 class="player-number">#' . $info->jerseyNumber . '</h2>
                            </div>
                            <ul class="panel-top heading-font">
						        <li>DOB: ' . $info->birthDate . '</li>
						        <li>Ht: ' . $info->height . '</li>
						        <li>Wt: ' . $info->weight . '</li>
							    '. $shoots .'
						    </ul>
					        <ul class="panel-bottom heading-font">
								    <li>Hometown: ' . $info->hometown . '</li>
									' . $year_in_school_content . '
                                    ' . $last_team_content . '
					        </ul>
                        </div>
                    </div>
                </div>
                <div class="player_stats_holder">
                    <div class="widget_title_wrapper">
                        <h2 class="widget_title">Player Stats</h2>
                    </div>
                        ' . $regular_season_content . ' 
                        ' . $playoff_content . '    
                </div>
            </div>
        ';
        return $content;
    }

    //the reason this function exists is because the hockey tech api returns a home town on the team roster page but not on the player stats page
    //two calls need to made and this does them async using curl
    private function makeAsyncCurlRequests($player_id, $team_id)
    {
        // build the individual requests, but do not execute them
        $ch_1 = curl_init("https://lscluster.hockeytech.com/feed/index.php?feed=statviewfeed&view=player&player_id=" . $player_id . "&site_id=2&key=e6867b36742a0c9d&client_code=acha&league_id=&lang=en");
        $ch_2 = curl_init("https://lscluster.hockeytech.com/feed/index.php?feed=statviewfeed&view=roster&team_id=" . $team_id . "&key=e6867b36742a0c9d&client_code=acha&site_id=2&league_id=1&lang=en");
        curl_setopt($ch_1, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch_2, CURLOPT_RETURNTRANSFER, true);

        // build the multi-curl handle, adding both $ch
        $mh = curl_multi_init();
        curl_multi_add_handle($mh, $ch_1);
        curl_multi_add_handle($mh, $ch_2);

        // execute all queries simultaneously, and continue when all are complete
        $running = null;
        do {
            curl_multi_exec($mh, $running);
        } while ($running);

        //close the handles
        curl_multi_remove_handle($mh, $ch_1);
        curl_multi_remove_handle($mh, $ch_2);
        curl_multi_close($mh);

        // all of our requests are done, we can now access the results
        $response_1 = curl_multi_getcontent($ch_1);
        $response_2 = curl_multi_getcontent($ch_2);
        $data = (object)[
            'raw_player_data' => $response_1,
            'raw_team_data' => $response_2
        ];
        return $data;
    }

    private function buildStatTable($data, $position)
    {
        $content = '';
        if ($position == 'G') {
            $content .= '
            <table>
            <tbody>
                <tr>
                    <th class="table_header" title="Season" style="text-align:left">Season</th>
                    <th class="table_header" title="Games Played">GP</th>
                    <th class="table_header" title="Wins">W</th>
                    <th class="table_header" title="Losses">L</th>
                    <th class="table_header" title="Minutes Played">MIN</th>
                    <th class="table_header" title="Goals Against">GA</th>
                    <th class="table_header" title="Shutouts">SO</th>
                    <th class="table_header" title="Goals Against Average">GAA</th>
                    <th class="table_header" title="Saves">SVS</th>
                    <th class="table_header" title="Save Percentage">SV%</th>
                </tr>';
            foreach ($data as $season) {
                $content .= '
                <tr>
                    <td style="text-align:left">' . $season->season_name . '</td>
                    <td>' . $season->games_played . '</td>
                    <td>' . $season->wins . '</td>
                    <td>' . $season->losses . '</td>
                    <td>' . $season->minutes_played . '</td>
                    <td>' . $season->goals_against . '</td>
                    <td>' . $season->shutouts . '</td>
                    <td>' . $season->goals_against_average . '</td>
                    <td>' . $season->saves . '</td>
                    <td>' . $season->savepct . '</td>
                </tr>
            ';
            }
        } else {
            $content .= '
            <table>
            <tbody>
                <tr>
                    <th class="table_header" title="Season" style="text-align:left">Season</th>
                    <th class="table_header" title="Games Played">GP</th>
                    <th class="table_header" title="Goals">G</th>
                    <th class="table_header" title="Assists">A</th>
                    <th class="table_header" title="Points">PTS</th>
                    <th class="table_header" title="Penalty Minutes">PIM</th>
                    <th class="table_header" title="Power Play Goals">PPG</th>
                    <th class="table_header" title="Short Handed Goals">SHG</th>
                    <th class="table_header" title="Game Winning Goals">GWG</th>
                </tr>';
            foreach ($data as $season) {
                $content .= '
                <tr>
                    <td style="text-align:left">' . $season->season_name . '</td>
                    <td>' . $season->games_played . '</td>
                    <td>' . $season->goals . '</td>
                    <td>' . $season->assists . '</td>
                    <td>' . $season->points . '</td>
                    <td>' . $season->penalty_minutes . '</td>
                    <td>' . $season->power_play_goals . '</td>
                    <td>' . $season->short_handed_goals . '</td>
                    <td>' . $season->game_winning_goals . '</td>
                </tr>
            ';
            }
        }
        $content .= '</tbody></table>';
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
