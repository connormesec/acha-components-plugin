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
class Acha_Components_Game_Slider extends Acha_Components_Schedule
{
    // public $schedule_arr;

    // public function __construct($arr_of_schedule_urls)
    // {
    //     $temp_arr = $this->createGameScheduleArr($arr_of_schedule_urls);
    //     $this->schedule_arr = $temp_arr;
    //     $this->console_log($this->schedule_arr);
    // }

    function buildGameSlider()
    {
        $game_schedule_arr = $this->schedule_arr;
        $styles = $this->style;
        wp_enqueue_style('gameSlider', plugin_dir_url(__FILE__) . '../public/css/acha-game-slider-carousel.css');
        $css = "
            .time {
                color:  " . $styles->header_text_color . ";
                background: " . $styles->header_bg_color . ";
            }
            .entry {
                background-color: " . $styles->body_bg_color . ";
            }
            .glider-prev:after{
                border-right: 8px solid " . $styles->body_bg_color . ";
            }
            .glider-next:after {
                border-left: 8px solid " . $styles->body_bg_color . ";
            }
            .game_vs_message {
                color:  " . $styles->body_text_color . ";
            }
            .glider-dot,
            .glider-next,
            .glider-prev {
                background: " . $styles->nav_arrow_color . ";
            }";
        wp_add_inline_style('gameSlider', $css);
        //determine right now in connortime
        $now = new DateTime();
        $now_connortime = $this->_ghettoOrder($now->format("M"), $now->format("j"), $now->format("g:i A"))->order_value;
        $content = '
		<div class="glider-contain" style="max-height: 130px; overflow:hidden;">
            <div class="glider">
        ';
        $counter = 0;
        foreach ($game_schedule_arr as $game) {
            //check to make sure the game is in the future before showing
            if ($now_connortime <= $game->order) {
                $content .= '<div class="content">
                                <div class="entry" style="height: 120px">
                                    <div class="game_vs_message">
                                        <div class="home_or_away">' . $game->home_or_away . '</div>
                                        <span class="vs"> VS </span>
                                    </div>
                                    <div class="hometeam">
                                        <div class="thumb">
                                            <img src="' . $game->opponent_team_logo . '" alt="'. $game->opponent_team_name .'" loading="lazy">
                                        </div>
                                    </div>
                                    <div class="awayteam_active">
                                        <div class="thumb ">
                                            <img src="' . $game->target_team_logo . '" alt="" loading="lazy">
                                        </div>
                                    </div>
                                    <div class="details">
                                        <span class="time">' . $game->game_date_time_message . '</span>
                                    </div>
                                </div>
                            </div>';
            } elseif ($game->target_score == '-') { //handle case of past games not having updated scores
                $counter++;
                $content .= '
                <div class="content">
                    <div class="entry" style="height: 120px">
                        <div class="game_vs_message">
                            <div class="home_or_away">' . $game->home_or_away . '</div>
                            <span class="vs"> VS </span>
                        </div>
                        <div class="hometeam">
                            <div class="thumb">
                                <img src="' . $game->opponent_team_logo . '" alt="away team" loading="lazy">
                            </div>
                        </div>
                        <div class="awayteam_active">
                            <div class="thumb ">
                                <img src="' . $game->target_team_logo . '" alt="MSU Bobcats" loading="lazy">
                            </div>
                        </div>
                        <div class="details">
                            <span class="time">' . $game->game_date_time_message . '</span>
                        </div>
                    </div>
                </div>';
            } else {
                $counter++;
                $content .= '<div class="content">
                                <div class="entry" style="height: 120px">
                                    <div class="game_vs_message">
                                        <div class="home_or_away">' . $game->home_or_away . '</div>
                                        <span class="vs"> ' . $game->target_score . ' - ' . $game->opponent_score . '</span>
                                    </div>
                                    <div class="hometeam">
                                        <div class="thumb">
                                            <img src="' . $game->opponent_team_logo . '" alt="away team" loading="lazy">
                                        </div>
                                    </div>
                                    <div class="awayteam_active">
                                        <div class="thumb ">
                                            <img src="' . $game->target_team_logo . '" alt="MSU Bobcats" loading="lazy">
                                        </div>
                                    </div>
                                    <div class="details">
                                        <span class="time">' . $game->game_date_time_message . '</span>
                                    </div>
                                </div>
                            </div>';
            }
        }

        $content .= '
            </div>
            <button aria-label="Previous" class="glider-prev">‹</button>
            <button aria-label="Next" class="glider-next">›</button>
        </div>
  
  <script src="https://cdn.jsdelivr.net/npm/glider-js@1/glider.min.js"></script>
        <script>
            window.addEventListener(\'load\', function() {
		  		var glider = new Glider(document.querySelector(\'.glider\'), {
					slidesToShow: 1,
					draggable: false,
					scrollLock: true,
					arrows: {
						prev: \'.glider-prev\',
						next: \'.glider-next\'
					},
					easing: function (x, t, b, c, d) {
                  return c*(t/=d)*t + b;
                },
					responsive: [
						{
						  // screens greater than >= 775px
						  breakpoint: 800,
						  settings: {
							// Set to `auto` and provide item width to adjust to viewport
							slidesToShow: 3,
							slidesToScroll: 1,
							itemWidth: 150,
							scrollLock: true,
							duration: 0.25
						  }
						},{
						  // screens greater than >= 1024px
						  breakpoint: 650,
						  settings: {
							slidesToShow: 2,
							slidesToScroll: 1,
							itemWidth: 150,
							scrollLock: true,
							duration: 0.25
						  }
						}
					  ]
		  		})
		  		glider.setOption({duration: 0});
                glider.scrollItem(' . $counter . ');
                glider.setOption({duration: .25});
			});
			
        </script>
  
  ';
        return $content;
    }
}
