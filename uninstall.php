<?php
/**
 * Connected Products Uninstall
 *
 * Uninstalling Connected Products deletes meta_data from database.
 *
 * @author      Chasil
 * @category    Core
 * @package     ProductsCategory/Uninstaller
 * @version     1.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

include_once( 'includes/class-wc-install.php' );
WC_Install::remove_roles();

$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_conn_prod_ids'");

wp_cache_flush();