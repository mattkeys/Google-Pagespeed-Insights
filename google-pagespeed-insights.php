<?php
/*
Plugin Name: Google Pagespeed Insights
Plugin URI: http://mattkeys.me
Description: Google Pagespeed Insights
Author: Matt Keys
Version: 2.0
Author URI: http://mattkeys.me
*/

/*  Copyright 2016  Matt Keys  (email : me@mattkeys.me)
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Path to this file
if ( ! defined( 'GPI_PLUGIN_FILE' ) ) {
	define( 'GPI_PLUGIN_FILE', __FILE__ );
}

// Path to the plugin's directory
if ( ! defined( 'GPI_DIRECTORY' ) ) {
	define( 'GPI_DIRECTORY', dirname( __FILE__ ) );
}

// Publicly accessible path
if ( ! defined( 'GPI_PUBLIC_PATH' ) ) {
	define( 'GPI_PUBLIC_PATH', plugin_dir_url( __FILE__ ) );
}

// Internal version number 
if ( ! defined( 'GPI_VERSION' ) ) {
	define( 'GPI_VERSION', '2.0' );
}

if ( is_admin() ) {
	require 'classes/class-GPI-Activation.php';
	require 'classes/class-GPI-Uninstall.php';
	require 'classes/class-GPI-Admin.php';

	$doaction = ( isset( $_REQUEST['action'] ) && -1 != $_REQUEST['action'] ) ? $_REQUEST['action'] : false;

	if ( $doaction && ( isset( $_GET['page'] ) && 'google-pagespeed-insights' == $_GET['page'] ) ) {
		require 'classes/class-GPI-Actions.php';
	}
}

require 'classes/class-GPI-Core.php';