<?php

/**
 *
 * @link              https://www.thedotstore.com/
 * @since             1.0.0
 * @package           Convert Classic Editor to Gutenberg Blocks
 *
 * @wordpress-plugin
 * Plugin Name:       Convert Classic Editor to Gutenberg Blocks
 * Plugin URI:        https://www.thedotstore.com/
 * Description:       This plugin will useful for convert existing classic editor post to Gutenberg blocks post.
 * Version:           1.0.0
 * Author:            Thedotstore
 * Author URI:        https://www.thedotstore.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       convert-classic-editor-to-gutenberg-blocks
 */

/**
 * Exit if accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit();
}

register_activation_hook( __FILE__, array( 'CCETGB_Admin_Settings', 'ccetgb_plugin_activation' ) );

require_once( plugin_dir_path( __FILE__ ) . 'class.convert-classic-to-gb-blocks-admin.php' );

add_action( 'init', array( 'CCETGB_Admin_Settings', 'ccetgb_init' ) );

if ( is_admin() ) {
    require_once( plugin_dir_path( __FILE__ ) . 'class.convert-classic-to-gb-blocks.php' );
    $convert_to_block =  new CCETGB_Convert_Classic_to_GB_Blocks();
}