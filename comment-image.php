<?php
/* ====================================================================================================
 *	Plugin Name: Comment Image Reloaded 
 *	Description: It allows visitors to attach images to comments. This plugin is better version of <a href="https://wordpress.org/plugins/comment-images/">Comment Image by Tom McFarlin</a> that allow you control imase size. And it does <strong>only to sql query to get all comments</strong> (not one query for one comment).
 *	Plugin URI: http://wp-puzzle.com/comment-images-reloaded/
 *	Version: 2.0.2
 *  Author: WP Puzzle 
 *  Author URI: http://wp-puzzle.com/
 * ==================================================================================================== */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
} // end if

require_once( plugin_dir_path( __FILE__ ) . 'class-comment-image.php' );
Comment_Image_Reloaded::get_instance();