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
class Acha_Components_Upcoming_Games_Table extends Acha_Components_Schedule
{

    function buildUpcomingGameTable()
    {
        $game_schedule_arr = $this->schedule_arr;
        $styles = $this->style;
        $headerRgb = $this->hex_to_rgb($styles->promo_header_bg_color);
        wp_enqueue_style('upcomingGamesTable', plugin_dir_url(__FILE__) . '../public/css/acha-components-upcoming-games-table.css');
        $css = "
            #upcomingGamesTable #upcoming-games .ugames-col1 {
                background-color: " . $styles->logo_bg_color . ";
            }
            #upcomingGamesTable #upcoming-games .ugames-col1::after {
                background-image: linear-gradient(to bottom right, " . $styles->logo_bg_color . " 50%, transparent 50%);
            }
            #upcomingGamesTable #upcoming-games tr {
                background-color: " . $styles->body_bg_color . ";
            }
            #upcomingGamesTable .detail-txt, .accent-txt, .ugames-teams {
                color: " . $styles->primary_text_color . ";
            }
            .preseason-banner {
                color: " . $styles->promo_header_text_color . ";
                background: linear-gradient(to right, " . $styles->promo_header_bg_color . " 0%, rgba(" . implode(", ", $headerRgb) . ", 0.75) 50%, rgba(" . implode(", ", $headerRgb) . ", 0) 100%);
            }
            #upcomingGamesTable .home-blue-btn {
                background-color: " . $styles->promo_ticket_button_bg_color . ";
            }
            
            
            
            ";
        wp_add_inline_style('upcomingGamesTable', $css);
        //determine right now in connortime
        $now = new DateTime();
        $now_connortime = $this->_ghettoOrder($now->format("M"), $now->format("j"), $now->format("g:i A"))->order_value;
        $content = '
		<div id="upcomingGamesTable">
        <div id="upcoming-games">
		<table><caption class="screen-reader-only">Upcoming Games and Promos</caption><tbody>
        ';
        $count = 0;
        foreach ($game_schedule_arr as $game) {
            if($count >= 5) {
                break;
            }
            //check to make sure the game is in the future before showing
            if ($now_connortime <= $game->order) {
                $formattedDate = $this->convertDate($game->game_date_day);

                $header = '';
                if ($game->promotion_header_value !== "") {
                    $header = '<div class="preseason-banner">' . $game->promotion_header_value . '</div>';
                }
                $vsorat = 'vs.';
                if (strtolower($game->home_or_away) === 'away'){
                    $vsorat = '@';
                }
                $ticket_button = '';
                if ($game->promotion_tickets_url !== '') {
                    $ticket_button = '<a href="ticket link" class="btn home-blue-btn" target="_blank" title="Opens ticketing site in a new tab" rel="noopener">
                                            Buy Tickets
                                        </a>';
                }

                $content .= '<tr class="a-game">
                                <td class="ugames-col1" scope="row">
                                    <img src="' . $game->opponent_team_logo . '" alt="opponent" class="team-logo">
                                </td>
                                <td class="ugames-col2">
                                    <span class="accent-txt">
                                        ' . $formattedDate['date'] . '
                                    </span>
                                    <span class="detail-txt">
                                        ' . $formattedDate['day'] . ' | ' . $game->game_time . '
                                    </span>
                                </td>
                                <td class="ugames-col3">
                                    ' . $header . '
                                    <h3 class="ugames-teams"> 
                                        ' . $game->target_team_nickname . '
                                        <span class="atvs">
                                            <sub>' . $vsorat . '</sub> 
                                        </span>
                                        ' . $game->opponent_team_name . '
                                    </h3>
                                    ' . $ticket_button . '
                                </td>
                            </tr>';
            $count++;
            }
        }
        $content .= '</tbody></table></div></div>';
        return $content;
    }

    private function convertDate($dateString) {
        // Create a DateTime object from the input string
        $dateTime = DateTime::createFromFormat('l, M jS', $dateString);
    
         // If that fails, try the second format: "Sat, Oct 5"
        if (!$dateTime) {
            $dateTime = DateTime::createFromFormat('D, M j', $dateString);
        }

        // Check if the date was parsed successfully
        if (!$dateTime) {
            return false; // Return false if the date string is invalid
        }
    
        // Format the date and day as required
        $formattedDate = $dateTime->format('n/j'); // "1-27"
        $formattedDay = $dateTime->format('D');    // "Sat"
    
        return [
            'date' => $formattedDate,
            'day' => $formattedDay,
        ];
    }

    private function hex_to_rgb($hex) {
        // Remove the hash if it's there
        $hex = str_replace("#", "", $hex);
    
        // Handle shorthand hex codes (e.g., #FFF)
        if(strlen($hex) == 3) {
            $r = hexdec(str_repeat(substr($hex, 0, 1), 2));
            $g = hexdec(str_repeat(substr($hex, 1, 1), 2));
            $b = hexdec(str_repeat(substr($hex, 2, 1), 2));
        } else {
            // Standard 6-character hex codes
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }
    
        return array($r, $g, $b); // Return RGB as an array
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
