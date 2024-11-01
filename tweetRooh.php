<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/*
Plugin Name: AutoTweet any part of page: (includes tinyURL back to page with those parts highlighted)
Plugin URI: http://roohit.com/site/wpPlugins.php
Description: This plugin is being replaced: Please deactive it and install the **Exciting, new [Rooh.It Simplifier](http://wordpress.org/extend/plugins/roohit-plugin)**
Version: 5.1
Author: Rooh.It
Author URI: http://roohit.com
*/

/* 
* +--------------------------------------------------------------------------+
* | Copyright (c) 2006 RoohIt                   (email : support@roohit.com) |
* +--------------------------------------------------------------------------+
* | This program is free software; you can redistribute it and/or modify     |
* | it under the terms of the GNU General Public License as published by     |
* | the Free Software Foundation; either version 2 of the License, or        |
* | (at your option) any later version.                                      |
* |                                                                          |
* | This program is distributed in the hope that it will be useful,          |
* | but WITHOUT ANY WARRANTY; without even the implied warranty of           |
* | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            |
* | GNU General Public License for more details.                             |
* |                                                                          |
* | You should have received a copy of the GNU General Public License        |
* | along with this program; if not, write to the Free Software              |
* | Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA |
* +--------------------------------------------------------------------------+
*/

require_once('rooh_vBtns.php') ;
include_once ('roohUtils.php') ;
include_once ('rooh_hBtns.php') ;

$pathToBtns = "http://roohit.com/images/btns/v3/" ;

$tweetRooh_settings = array( array('offsetFromTop', ''), array('onRight', ''), array('buttonLook', '') );

//$plugin = plugin_basename(__FILE__) ;

add_action("plugins_loaded", "tweetRooh_init");
add_action("admin_menu", "tweetRooh_admin_menu");
add_action("wp_footer", "showPoweredBy" );     // I have placed this function in an external file

$ROOH_BTN = 1 ;		// IMPORTANT: This should be AFTER the include of roohUtil.php

function tweetRooh_init()
{
	global $tweetRooh_hideHorz ;

	add_action( 'get_footer', 'getTRSnippet' );		// I have placed this function in an external file

	//register_sidebar_widget(__('Tweet Highlights'), 'widget_tweetRooh');
  
	// Add the user defined settings
//    add_option('tweetRooh_buttonLook');
//    add_option('tweetRooh_offsetFromTop');

	getTRUserSettings() ;

    if (!isset($tweetRooh_hideHorz)) $tweetRooh_hideHorz = get_option('tweetRooh_hideHorz');
    //$tweetRooh_settings['hideHorz'] = $tweetRooh_hideHorz;
	if ($tweetRooh_hideHorz == '')
		add_filter('the_content', 'getHorzBtnSnippetTH');

	if (!isset($tweetRooh_inited)) $tweetRooh_inited = get_option('tweetRooh_trk');
	if (strlen($tweetRooh_inited) == 0) 
		roohSetup() ;
	else
		$tweetRooh_inited = '1'; 
}


function tweetRooh_admin_menu() {
	$plugin = plugin_basename(__FILE__) ;
	add_options_page("Tweet Highlights Options", "AutoTweet Hilights", 8, "$plugin", "tweetRooh_settings");
}

////////////////////// Begin /////////////
// Add settings link on plugin page
function tweetRooh_settings_link($links) {
	$plugin = plugin_basename(__FILE__) ;
	$settings_link = "<A href='options-general.php?page=$plugin'>Settings</A>";
	array_unshift($links, $settings_link);
	return $links;
}
$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 'tweetRooh_settings_link' );
///////////// End //////////////

function tweetRooh_settings() {
	global $pathToBtns ;
	global $tweetRooh_buttonStyles ;
    global $tweetRooh_offsetFromTop;
    global $tweetRooh_onRight;
    global $tweetRooh_buttonLook;
	global $tweetRooh_hideHorz ;
?>
    <div class="wrap">
    <h2>RoohIt: directly send Highlights to Twitter</h2>

    <div align="center" id="message" class="error"><p><a href="mailto:support@roohit.com?subject=WordPress: Tweet Highlights Plugin Feedback">Tell us</a> what you think of this plugin <b>please</b></p></div>

    <form method="post" action="options.php">
    <?php wp_nonce_field('update-options'); ?>

    <center><input class="button-primary" type="submit" name="Submit" value="<?php _e('Save Changes') ?>" /></center>
    <h3>Button Options</h3>
    <table class="form-table">
        <tr valign="top">
            <th scope="row"><?php _e("Show on which Side:", 'tweetRooh_trans_domain' ); ?></th>
            <td>            
            	<input type="radio" name="tweetRooh_onRight" value='0' id="tweetRooh_onRight_0" <?php if ($tweetRooh_onRight == '0') echo 'checked=\"checked\"' ?> /> <?php _e("Left", 'tweetRooh_trans_domain' ); ?>
            	<input type="radio" name="tweetRooh_onRight" value='1' id="tweetRooh_onRight_1" <?php if ($tweetRooh_onRight == '1') echo 'checked=\"checked\"' ?> /> <?php _e("Right (recommended)", 'tweetRooh_trans_domain' ); ?>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row"><?php _e("Offset from top of Page:", 'tweetRooh_trans_domain' ); ?></th>
            <td>
            	<input type="text" name="tweetRooh_offsetFromTop" size="3" maxlength="3" value="<?=$tweetRooh_offsetFromTop?>" />px
            </td>
		</tr>
        <tr>
            <!-- We should also display the gallery of horizontal buttons so that user can pick one to his/her taste -->
            <th scope="row"><?php _e("Hide horizontal button:", 'tweetRooh_trans_domain' ); ?></th>
        	<td>
            	<input type="checkbox" <?php if ($tweetRooh_hideHorz == 'on') { echo "checked='checked'"; }?> name="tweetRooh_hideHorz" />(Recommended it is NOT CHECKED)
            </td>
        </tr>
        <tr valign="top">
            <th scope="row"><?php _e("Pick Button Style:<br>(2nd image is displayed on mouse over. <a href=\"http://roohit.com/forum/viewforum.php?f=7\">Share your thoughts</a>)", 'roohit_trans_domain' ); ?></th>
            <td>
                <table><tbody>
                    <?php
						$numOfBtnsInARow = 4 ;
						$fileSuffix = '' ; 
						$numStyles = sizeof($tweetRooh_buttonStyles) ;
                        for ($i=0; $i < $numStyles; )
                        {
                            echo "<tr>";
                            for ($j=0; $j < $numOfBtnsInARow; $j++)
                            {
                                if ($i > $numStyles)
                                    break ;
								if ( $j < $numOfBtnsInARow/2 ) 
									$fileSuffix = 'L' ;
								$filename = $pathToBtns . $tweetRooh_buttonStyles[$i]['img'] . $fileSuffix . ".png" ;
								$fileExists = url_validate($filename) ;
								if ( !$fileExists)
								{
									$filename = $pathToBtns . $tweetRooh_buttonStyles[$i]['img'] . ".png" ;
									$fileExists = url_validate($filename) ;
								}
								
								if ( $fileExists ) 
								{
									echo "<td align=\"top\" onClick=\"document.getElementById('tweetRooh_buttonLook_$i').checked=true;\" > <input type=\"radio\" name=\"tweetRooh_buttonLook\" value=\"" . $tweetRooh_buttonStyles[$i]['img'] . "\" id=\"tweetRooh_buttonLook_$i\"". ($tweetRooh_buttonStyles[$i]['img'] == $tweetRooh_buttonLook ? "checked=\"checked\"" : "") . "/> " . "<img src=\"$filename\" border=\"0\" alt=\"Use this button on your page\" style=\"border:none;\"  align=\"top\" onMouseOver=\"javascript:document.getElementById('tweetRooh_buttonLook_$i').src='" . $filename . "_hover.png';\" /> </td>" ;
								} 
								else 
								{ 
									$j-- ;  
									//echo "i=$i, j=$j " ;
									//echo $filename, '<br>' ; 
								}
                                $i++ ;
                            }
							//echo "i=$i, j=$j" ;
                            echo "</tr>" ;
                        }
					?>
                </tbody></table>
            </td>
        </tr>
    </table>

    <input type="hidden" name="action" value="update" />
    <input type="hidden" name="page_options" value="tweetRooh_offsetFromTop, tweetRooh_onRight, tweetRooh_buttonLook, tweetRooh_hideHorz"/>

    <input class="button-primary" type="submit" name="Submit" value="<?php _e('Save Changes') ?>" />

    </form>

    <h2>Like this plugin?</h2>
    <!-- <p>Why not do any of the following:</p> -->
    <ol>
        <li>&nbsp;&nbsp;&nbsp;&nbsp;&bull; <strong>Write a blog</strong> posting <a href="http://roohit.com/site/blogThis.php">about it</a>, and link to it so other folks can find out about it.</li>
        <li>&nbsp;&nbsp;&nbsp;&nbsp;&bull; Please <a href="http://wordpress.org/extend/plugins/tweet-highlights/"><strong>give it a good rating</strong></a> on WordPress.org, so others will find it easily!</li>
        <li>&nbsp;&nbsp;&nbsp;&nbsp;&bull; <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=7137089">Donate</a> a token of your appreciation.</li>
    </ol>

    <h2>Need support?</h2>
    <p>If you have any problems or good ideas, please talk about them in the <a href="http://wordpress.org/tags/tweet-highlights">Support forums</a>.</p>
    
    <h2>About</h2>
    <p><em>Rooh</em> means <em>soul</em>. When you Rooh It you get to the soul of the page, as you individually determine it. </p>
    <p>The no-signup, no-download, highlighter was conceived and created by Rohit Chandra and has been maintained by <a href="http://roohit.com/">RoohIt</a> since the very beginning.</p>

    </div>

<?php
}

// Description: Enables visitors to <strong>highlight</strong>, share, save, and <strong>promote your website on Twitter, Facebook, LinkedIn, and all their social networks, blogs and friends</strong>. All highlights made on your page are automatically posted to all of our <a href='http://roohit.com/site/urWidget.php'>MicroBloggers'</a> blogs and Social Networks pages - instantly generating LOTS and LOTS of links back to your posting. It's all Free, and no-one ever needs to "join" any site; nor download anything!

/*  To Do
	1. Clean up how I am doing the Settings Link (tweetRooh.php) value and standardize it
	
*/

?>
