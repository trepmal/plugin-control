<?php
/*
 * Plugin Name: Plugin Control
 * Plugin URI: trepmal.com
 * Description:
 * Version: 2013.03.29
 * Author: Kailey Lampert
 * Author URI: kaileylampert.com
 * License: GPLv2 or later
 * TextDomain: plugin-control
 * DomainPath:
 * Network: false
 */

$plugin_control = new Plugin_Control();

class Plugin_Control {

	var $blocklist;
	var $textdomain = 'plugin-control';

	var $pagename;

	function __construct() {

		$this->blocklist = get_option( 'block-plugins', array(
			'akismet/akismet.php',
		) );

		add_action( 'admin_init', array( &$this, 'admin_init' ) );

		// filter plugins list
		// add_filter( 'all_plugins', array( &$this, 'all_plugins' ) );
		add_filter( 'plugin_action_links', array( &$this, 'plugin_action_links' ), 10, 4 );
		add_action( 'after_plugin_row', array( &$this, 'after_plugin_row' ) );

		// prevent back-door activation
		add_filter( 'load-plugins.php', array( &$this, 'intercept_post' ), 10, 2 );
		add_filter( 'pre_update_option_active_plugins', array( &$this, 'pre_update_option_active_plugins' ), 10, 2 );

		add_action( 'admin_menu', array( &$this, 'admin_menu' ) );

	}

	function admin_init() {
		register_setting( 'pc-opt-group', 'block-plugins', array( &$this, 'sanitize' ) );
		add_settings_section( 'pc-section', __('', $this->textdomain ), '__return_false', $this->pagename );
		add_settings_field( 'pc-field', __( 'Block Plugins', $this->textdomain ), array( &$this, 'field' ), $this->pagename, 'pc-section', get_option( 'block-plugins', array() ) );
	}

	function sanitize( $input ) {
		printer( $input );
		return $input;
	}

	function field( $args ) {

		$plugins = wp_list_pluck( get_plugins(), 'Name' );
		echo '<ul>';
		foreach( $plugins as $path => $plug ) {
			$c = in_array( $path, $args ) ? ' checked="checked"' : '';
			echo "<li><label><input type='checkbox' name='block-plugins[]' value='$path'$c /> $plug</label></li>";
		}
		echo '</ul>';

	}

	function all_plugins( $get_plugins ) {
		foreach( $this->blocklist as $b )
			if ( isset( $get_plugins[ $b ] ) ) unset( $get_plugins[ $b ] );

		return $get_plugins;
	}

	var $status;
	var $status_change;

	function plugin_action_links( $actions, $plugin_file, $plugin_data, $context ) {

		$flip = array_flip( $this->blocklist );
		global $status;
		if ( $status == $context && ! $this->status_change ) {
			$this->status = $status; //preserve
			$this->status_change = true; //flag
		}
		if ( isset( $flip[ $plugin_file ] ) ) {

			$actions = array();

			// use  'mustuse' to disable the checkbox
			$status = 'mustuse';
		}
		return $actions;
	}
	// use this later hook to restore real $status
	function after_plugin_row() {
		if ( $this->status_change ) {
			global $status;
			$status = $this->status;
		}
	}

	function intercept_post() {
		if ( ! isset( $_REQUEST['checked'] ) ) return;
		$_REQUEST['checked'] = array_filter( $_REQUEST['checked'], array( &$this, 'is_plugin_unblocked' ) );
	}

	function pre_update_option_active_plugins( $newvalue, $oldvalue ) {
		// removed all blocked plugins
		$diff = array_diff( $newvalue, $oldvalue );
		$newvalue = array_filter( $diff, array( &$this, 'is_plugin_unblocked' ) );
		return $newvalue;
	}

	function admin_menu() {
		$this->pagename = add_plugins_page( __( 'Plugin Control', $this->textdomain ), __( 'Plugin Control', $this->textdomain ), 'edit_posts', __CLASS__, array( &$this, 'page' ) );
	}

	function page() {
		?><div class="wrap">
		<h2><?php _e( 'Plugin Control', $this->textdomain ); ?></h2>
		<p><?php _e( 'Coming soon. Choose which plugins are hidden for this site.', $this->textdomain ); ?></p>
		<form action="options.php" method="post">
		<?php
			settings_fields( 'pc-opt-group' );
			do_settings_sections( $this->pagename );
			submit_button();
		?>
		</form>
		</div><?php
	}

	function is_plugin_blocked( $plugin ) {
		return is_int( array_search( $plugin, $this->blocklist ) );
	}
	function is_plugin_unblocked( $plugin ) {
		return !$this->is_plugin_blocked( $plugin );
	}

}
if ( ! function_exists( 'printer') ) {
	function printer( $input ) {
		echo '<pre>' . print_r( $input, true ) . '</pre>';
	}
}
// eof