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
 * creates the database for the vehicles on install
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
      memo longtext  NULL,
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
                         'wpa_page_vehicle_lookup_dashboard',
                       ), plugins_url('images/icon-20.png', __FILE__));


        add_submenu_page( 'vehicle-lookup-dashboard', 'Vehicle Lookup' . 'Add Vehicle', 'Add Vehicle', 'manage_options', 'add-vehicle', array(
            __CLASS__,
            'wpa_page_add_vehicle'
        ));

        add_plugins_page('My Plugin Page', 'My Plugin', 'read', 'vehicle-added', array(
                __CLASS__,
                'wpa_page_vehicle_added'
            )
        );



//        add_shortcode( "vehicle-added", "wpa_page_vehicle_added" );



    }

    /*
     * verify that color is a hex color
     */
    function is_hex_color($hexColor)
    {
        global $wpdb;

        //check len is right
        $length = strlen($hexColor);
        if( 7 <= $length && $length <= 9 && $length != 8)
        {
            //check if hexdiecimal
            if (ctype_xdigit( substr($hexColor, 1) ))
            {
                if ($hexColor[0] === "#")
                {
                    return 1;
                }
            }
        }
        return 0;
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
          $color = ( self::is_hex_color($vehicle->color)) ? "<div style='background-color: ".$vehicle->color."; width:100%; height:25px'></div>" : "Unknown" ;

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


    /**
     * add a vehicle form
     */
    function wpa_page_add_vehicle() {
        global $wpdb; //access wordpress instance


        // if the submit button is clicked
        if ( isset( $_POST['submitted'] ) ) {

            // sanitize data from POST method and strip tags
            $vin             = strip_tags(sanitize_text_field( $_POST["vin"] ));
            $year            = strip_tags(sanitize_text_field( $_POST["year"] ));
            $make            = strip_tags(sanitize_text_field( $_POST["make"] ));
            $model           = strip_tags(sanitize_text_field( $_POST["model"] ));
            $color           = strip_tags(sanitize_text_field( $_POST["color"] ));
            $odometer        = strip_tags(sanitize_text_field( $_POST["odometer_at_last_serviced"] ));
            $lastServiced    = strip_tags(sanitize_text_field( $_POST["last_serviced"] ));
            $memo            = strip_tags(sanitize_text_field( $_POST["memo"] ));

            //check if required fields are there
            if ( $vin && $year && $make && $model) {
                //insert into db

                //the table to be inserted into
                $table_name = $wpdb->prefix . 'vehicle';

                echo $table_name;
                //construct the data
                $data = array(
                    'vin'                       => $vin,
                    'year'                      => $year,
                    'make'                      => $make,
                    'model'                     => $model,
                    'color'                     => $color,
                    'odometer_at_last_serviced' => $odometer,
                    'last_serviced'             => $lastServiced,
                    'memo'                      => $memo,
                );

                //format for the data
                $format = array('%s','%d');

                //do the insert
                $wpdb->insert($table_name, $data, $format);

                //add id
                $wpdb->insert_id;




                echo '<div>';
                echo '<p><b style="color:#36D696">Nifty! You Just added a '. $make .' ' . $model . ', would you like to add another?</b></p>';
                echo '</div>';

            } else {


                echo '<div>';
                echo '<p><b style="color:darkred">Oh On! Something went wrong?</b></p>';
                echo '</div>';

            }




        }


        //if editing



        //the form

        echo '<h2>Add Vehicle</h2>';
        echo '<table class="form-table"><tbody><form action="' . esc_url( admin_url('admin.php?page=add-vehicle') ) . '" method="post">';


        echo '<tr><th scope="row">VIN</th>';
        echo '<td> <fieldset><legend class="screen-reader-text"><span>VIN </span></legend><label for="vin">';
        echo '<input name="vin" type="text" id="vin" value=""> <b style="color:darkred">*</b></label>';
        echo '</fieldset></td></tr>';

        echo '<tr><th scope="row">Year</th>';
        echo '<td> <fieldset><legend class="screen-reader-text"><span>Year </span></legend><label for="year">';
        echo '<input name="year" type="number" id="year" value="2020" min="1953" max="2039" required=""> <b style="color:darkred">*</b></label>';
        echo '</fieldset></td></tr>';


        echo '<tr><th scope="row">Make</th>';
        echo '<td> <fieldset><legend class="screen-reader-text"><span>Make </span></legend><label for="make">';
        echo '<input name="make" type="text" id="make" value=""  required=""> <b style="color:darkred">*</b></label>';
        echo '</fieldset></td></tr>';


        echo '<tr><th scope="row">Model</th>';
        echo '<td> <fieldset><legend class="screen-reader-text"><span>Model </span></legend><label for="model">';
        echo '<input name="model" type="text" id="model" value=""  required=""> <b style="color:darkred">*</b></label>';
        echo '</fieldset></td></tr>';


        echo '<tr><th scope="row">Color</th>';
        echo '<td><fieldset><legend class="screen-reader-text"><span>Color </span></legend><label for="color">';
        echo '<input name="color" type="color" id="color" value="">';
        echo '</fieldset></td></tr>';


        echo '<tr><th scope="row">Odometer At Last Serviced</th>';
        echo '<td> <fieldset><legend class="screen-reader-text"><span>Odometer At Last Serviced </span></legend><label for="odometer_at_last_serviced">';
        echo '<input name="odometer_at_last_serviced" type="number"  id="odometer_at_last_serviced" value=""></label>';
        echo '</fieldset></td></tr>';


        echo '<tr><th scope="row">Last Serviced</th>';
        echo '<td> <fieldset><legend class="screen-reader-text"><span>Last Serviced </span></legend><label for="last_serviced">';
        echo '<input name="last_serviced" type="date" id="last_serviced" value=""></label>';
        echo '</fieldset></td></tr>';


        echo '<tr><th scope="row">Memo</th>';
        echo '<td> <fieldset><legend class="screen-reader-text"><span>Memo </span></legend><label for="memo">';
        echo '<textarea name="memo" style="width: 350px; height: 250px;" id="memo" value=""></textarea></label>';
        echo '</fieldset></td></tr>';

        echo '<tr><th scope="row">Add Vehicle</th>';
        echo '<td> <fieldset><legend class="screen-reader-text"><span>Add Vehicle </span></legend><label for="submitted">';
        echo '<input type="submit" style="background-color:#36D696; color:#ffffff;" id="submitted" name="submitted" value="Add Vehicle"/>';
        echo '</fieldset></td></tr>';

        echo '<b style="color:darkred">*</b> required';
        echo '</form></tbody></table>';










    }




    function wpa_page_vehicle_added() {

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
