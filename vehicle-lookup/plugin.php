<?php
/**
 * Plugin Name: Vehicle Lookup
 * Plugin URI: https://suwdesign.com
 * Description: Looks vehicles up by there vin number.
 * Version: 1.0
 * Author: Brandon Stewart
 * Author URI: http://brandonsreusme.ml
 */


//hook for created the db
register_activation_hook( __FILE__, 'create_vehicle_db' );


/**
 * creates the database for the vehicles
 */
function create_vehicle_db() {

    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'vehicle';

    $sql = "CREATE TABLE $table_name (
      id mediumint(9) NOT NULL AUTO_INCREMENT,
      vin text NULL,
      year text NULL,
   		make text NULL,
      model text  NULL,
      color text  NULL,
      odometer_at_last_serviced int(20)  NULL,
      last_serviced datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
      time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
  		UNIQUE KEY id (id)
 	) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}







class WP_Analytify_Simple{

  // Constructor
    function __construct() {

        add_action( 'admin_menu', array( $this, 'wpa_add_menu' ));
        register_activation_hook( __FILE__, array( $this, 'wpa_install' ) );
        register_deactivation_hook( __FILE__, array( $this, 'wpa_uninstall' ) );
    }

    /*
      * Actions perform at loading of admin menu
      */
    function wpa_add_menu() {

        add_menu_page( 'Vehicle Lookup', 'Vehicle Lookup', 'manage_options', 'vehicle-lookup-dashboard', array(
                          __CLASS__,
                         'wpa_page_vehicle_lookup_dashboard'
                       ), plugins_url('images/icon-20.png', __FILE__));

    }

    /*
     * Actions perform on loading of menu pages
     */
    function wpa_page_vehicle_lookup_dashboard() {

      global $wpdb;

      $table_name = $wpdb->prefix . 'vehicle';

      //get vehicles
      $vehicles = $wpdb->get_results('SELECT * FROM '.$table_name.' LIMIT 10');

      echo '<h2>Vehicle Lookup</h2>';

  		echo
      '<table class="widefat fixed" cellspacing="0">
      <thead><tr>
      <th>Vin</th>
      <th>Year</th>
      <th>Make</th>
      <th>Model</th>
      <th>Color</th>
      <th>Odometer Last Serviced</th>
      <th>Last Serviced</th>
      </tr></thead><tbody>';

      foreach ($vehicles as $vehicle) {

          $color = ($vehicle->color == "unknown" or $vehicle->color == "Unknowable" ) ? "Unknowable" :
          "<div style='background-color: blue; width:100%; height:25px'></div>";

        echo "<tr>";

        echo "<td>".$vehicle->vin."</td>";
        echo "<td>".$vehicle->year."</td>";
        echo "<td>".$vehicle->make."</td>";
        echo "<td>".$vehicle->model."</td>";
        echo "<td>".$color."</td>";
        echo "<td>".$vehicle->last_serviced."</td>";
        echo "<td>".$vehicle->odometer_at_last_serviced."</td>";

        echo "</tr>";

      }

  		echo "</tbody></table>";



    }

    /*
     * Actions perform on activation of plugin
     */
    function wpa_install() {



    }

    /*
     * Actions perform on de-activation of plugin
     */
    function wpa_uninstall() {



    }

}

new WP_Analytify_Simple();
