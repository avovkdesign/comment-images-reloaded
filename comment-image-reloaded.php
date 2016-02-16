<?php
/* ====================================================================================================
 *	Plugin Name: Comment Image Reloaded 
 *	Description: Plugin allows visitors attach images to comments (reloaded version <a href="https://wordpress.org/plugins/comment-images/">Comment Image by Tom McFarlin</a>).
 *	Plugin URI: http://wp-puzzle.com/comment-images-reloaded/
 *  Author: WP Puzzle 
 *  Author URI: http://wp-puzzle.com/
 *	Version: 2.1.1
 * ==================================================================================================== */


// If this file is called directly, abort
if ( ! defined( 'WPINC' ) ) {
	die;
} 

require_once( plugin_dir_path( __FILE__ ) . 'class-comment-image-reloaded.php' );
Comment_Image_Reloaded::get_instance();