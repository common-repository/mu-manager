<?php
defined( 'MU_PLUGIN_MANAGER_PLUGIN_DIR' ) || exit;

//Return array of disabled mu-plugins
function eos_mupm_get_disabled_mu_plugins() {
  $mu_plugins = array();
  if ( ! is_dir( WPMU_PLUGIN_DIR ) ) {
    return $mu_plugins;
  }
  $dh = opendir( WPMU_PLUGIN_DIR );
  if ( ! $dh ) {
    return $mu_plugins;
  }
  while ( ( $plugin = readdir( $dh ) ) !== false ) {
    if ( 'disabled' === substr( pathinfo( $plugin, PATHINFO_EXTENSION ), 0, 8 ) ) {
        $mu_plugins[] = $plugin;
    }
  }
  closedir( $dh );
  sort( $mu_plugins );
  return $mu_plugins;
}

//It retrieves the disabled mu-plugins data
function eos_mupm_get_disabled_mu_plugins_data() {
    $wp_plugins   = array();
    $plugin_files = array();
    if ( ! is_dir( WPMU_PLUGIN_DIR ) ) {
        return $wp_plugins;
    }
    // Files in wp-content/mu-plugins directory.
    $plugins_dir = @opendir( WPMU_PLUGIN_DIR );
    if( $plugins_dir ){
      while ( ( $file = readdir( $plugins_dir ) ) !== false ) {
        if ( 'disabled' === substr( pathinfo( $file, PATHINFO_EXTENSION ), 0, 8 ) ) {
            $plugin_files[] = $file;
        }
      }
    }
    else {
      return $wp_plugins;
    }
    closedir( $plugins_dir );
    if ( empty( $plugin_files ) ) {
        return $wp_plugins;
    }
    foreach ( $plugin_files as $plugin_file ) {
      if ( ! is_readable( WPMU_PLUGIN_DIR . "/$plugin_file" ) ) {
          continue;
      }
      $plugin_data = get_plugin_data( WPMU_PLUGIN_DIR . "/$plugin_file",false,false );
      if ( empty( $plugin_data['Name'] ) ) {
          $plugin_data['Name'] = $plugin_file;
      }
      $wp_plugins[ $plugin_file ] = $plugin_data;
    }
    if ( isset( $wp_plugins['index.php'] ) && filesize( WPMU_PLUGIN_DIR . '/index.php' ) <= 30 ) {
        // Silence is golden.
        unset( $wp_plugins['index.php'] );
    }
    uasort( $wp_plugins, '_sort_uname_callback' );
    return $wp_plugins;
}

//Activate mu-plugin
function eos_mupm_activate_mu_plugin( $mu_plugin ){
  if( current_user_can( 'activate_plugins' ) ){
    eos_mupm_rename_mu_plugin( $mu_plugin, str_replace( pathinfo( $mu_plugin, PATHINFO_EXTENSION ), 'php', $mu_plugin ) );
  }
}
//Deactivate mu-plugin
function eos_mupm_deactivate_mu_plugin( $mu_plugin ){
  if( current_user_can( 'deactivate_plugin' ) ){
    eos_mupm_rename_mu_plugin( $mu_plugin, str_replace( '.php','.disabled-' . uniqid(), $mu_plugin ) );
  }
}
//Delete mu-plugin
function eos_mupm_delete_mu_plugin( $mu_plugin){
  if( current_user_can( 'delete_plugins' ) ){
    eos_mupm_rename_mu_plugin( $mu_plugin, str_replace( pathinfo( $mu_plugin, PATHINFO_EXTENSION ), 'deleteted-' . uniqid(), $mu_plugin ) );
  }
}

//Rename file
function eos_mupm_rename_mu_plugin( $old,$new ){
	$writeAccess = false;
	$access_type = get_filesystem_method();
	if( $access_type === 'direct' ){
		/* you can safely run request_filesystem_credentials() without any issues and don't need to worry about passing in a URL */
		$creds = request_filesystem_credentials( admin_url(), '', false, false, array() );
		/* initialize the API */
		if ( ! WP_Filesystem( $creds ) ) {
			/* any problems and we exit */
			return false;
		}
    if( file_exists( WPMU_PLUGIN_DIR. '/' . $old ) ){
      if( ! file_exists( WPMU_PLUGIN_DIR . '/index.php' ) ) {
        file_put_contents( WPMU_PLUGIN_DIR . '/index.php', '<?php' . PHP_EOL . '// Silent is golden.' );
      }
      if( ! file_exists( WPMU_PLUGIN_DIR . '/mu-manager.php' ) ) {
        file_put_contents( WPMU_PLUGIN_DIR . '/mu-manager.php', '<?php' . PHP_EOL . '// Empty MU Plugin installed by MU Manager to prevent WP from hidding the MU Plugins page.' );
      }
		  rename( WPMU_PLUGIN_DIR . '/' . $old, WPMU_PLUGIN_DIR . '/' . $new );
    }
	}
}

add_filter( 'plugins_list', function( $plugins ) {
  if( isset( $plugins['mustuse'] ) && 1 === count( $plugins['mustuse'] ) && in_array( 'mu-manager.php', array_keys( $plugins['mustuse'] ) ) ) {
    $plugins['mustuse'] = array(
      'mu-manager.php' => array(
          'Name' => 'MU Manager',
          'PluginURI' => '',
          'Version' => '',
          'Description' => esc_html__( 'Empty MU Plugin installed by MU Manager to prevent WP from hidding this page. Do not deactivate this mu-plugin.', 'mu-manager' ),
          'Author' => '',
          'AuthorURI' => '',
          'TextDomain' => '',
          'DomainPath' => '',
          'Network' => '',
          'RequiresWP' => '',
          'RequiresPHP' => '',
          'UpdateURI' => '',
          'RequiresPlugins' => '',
          'Title' => '',
          'AuthorName' => ''
      )
    );
  }
  return $plugins;
} );