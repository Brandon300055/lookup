<?php
/**
 * Plugin Name: Vehicle Lookup
 * Plugin URI: https://suwdesign.com
 * Description: Looks vehicles up by there VIN.
 * Version: 1.0
 * Author: Brandon Stewart
 * Author URI: http://brandonsreusme.ml
 */


//hooks for created the db's on install

//vehicle db
register_activation_hook( __FILE__, 'create_vehicle_db' );

//services db
register_activation_hook( __FILE__, 'create_services_db' );

/**
 * creates the database for the vehicles on install
 */
function create_vehicle_db() {

    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'vehicle';

    $sql = "CREATE TABLE $table_name (
      id mediumint(9) NOT NULL AUTO_INCREMENT,
      vin text NOT NULL,
      year text NOT NULL,
   		make text NOT NULL,
      model text  NOT NULL,
      color text  NULL,
      time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
  		UNIQUE KEY id (id)
 	) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}


/**
 * creates the services table in the db
 */
function create_services_db() {

    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'services';

    $sql = "CREATE TABLE $table_name (
      id mediumint(9) NOT NULL AUTO_INCREMENT,
      vehicle_id mediumint(9) NOT NULL,
      status text  NULL,
      odometer_at_last_serviced int(20)  NULL,
      last_serviced datetime DEFAULT '0000-00-00' NULL,
      memo longtext  NULL,
  		UNIQUE KEY id (id)
 	) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}

/**
 * Class WP_Analytify_Simple
 *
 * this class manages the plugin
 */

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
                       ), plugins_url('images/Icon-20.png', __FILE__));


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

      //reference search.js
        wp_enqueue_script('search', plugin_dir_url(__FILE__).'js/'.'search.js');

      $table_name = $wpdb->prefix . 'vehicle';

      //get vehicles
      $vehicles = $wpdb->get_results('SELECT * FROM '.$table_name);

      echo '<h2>Vehicle Lookup</h2>';


      echo '<input type="text" id="vin" onkeyup="search(\'vin\', 1)" placeholder="Search for VIN">';
      echo '<input type="text" id="year" onkeyup="search(\'year\', 2)" placeholder="Search for Year">';
      echo '<input type="text" id="make" onkeyup="search(\'make\', 3)" placeholder="Search for Make">';
      echo '<input type="text" id="model" onkeyup="search(\'model\', 4)" placeholder="Search for Model">';

  		echo
      '<table id="vehicle-table" class="widefat fixed" cellspacing="0">
      <thead><tr>
      <th>Action</th>
      <th>VIN</th>
      <th>Year</th>
      <th>Make</th>
      <th>Model</th>
      <th>Color</th>
      <th>Last Serviced</th>
      </tr></thead><tbody>';
//
//        <th>Odometer Last Serviced</th>


      foreach ($vehicles as $vehicle) {
          $color = ( self::is_hex_color($vehicle->color)) ? "<div style='background-color: ".$vehicle->color."; width:100%; height:25px'></div>" : "Unknown" ;

        echo "<td><a href=".esc_url( admin_url('admin.php?page=add-vehicle&view=' . $vehicle->id ) ).">View</a> | ";
        echo "<a href=".esc_url( admin_url('admin.php?page=add-vehicle&edit=' . $vehicle->id ) ).">Edit</a></td>";

        echo "<td>".$vehicle->vin."</td>";
        echo "<td>".$vehicle->year."</td>";
        echo "<td>".$vehicle->make."</td>";
        echo "<td>".$vehicle->model."</td>";
        echo "<td>".$color."</td>";
//        echo "<td>".$vehicle->last_serviced."</td>";
//        echo "<td>".$vehicle->odometer_at_last_serviced."</td>";
        echo "</tr>";

      }
  		echo "</tbody></table>";

    }

    /**
     * @param $deleted
     *
     * deletes vehicle by id
     */
    private function delete($deleted)
    {
        global $wpdb; //access wordpress instance

        $table_name = $wpdb->prefix . 'vehicle';

         $wpdb->delete(
             $table_name,
             ['id' => $deleted],
             ['%d']
         );

        //redirect back to dashboard
        echo 'This record is delete <a href="'.esc_url( admin_url('admin.php?page=vehicle-lookup-dashboard') ) .'">All Done Here </a> ';
        die();
    }

    /**
     * @param $vehicleID
     *
     *display chart for vehicle services
     */
    private function services($vehicleID)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'services';

        //get services by date and by vehicle id
        $services = $wpdb->get_results('SELECT * FROM '.$table_name.' WHERE `vehicle_id` = '.$vehicleID.' ORDER BY last_serviced DESC;');


//        echo $vehicleID;

        echo
        '<table id="vehicle-table" class="widefat fixed" cellspacing="0">
      <thead><tr>
      <th>Action</th>
      <th>Last Serviced</th>
      <th>Odometer At Last Serviced</th>
      <th>Memo</th>
      </tr></thead><tbody>';

//        <th>Status</th>

        echo "<tr><form action='". esc_url( admin_url("admin.php?page=add-vehicle&view=".$vehicleID) ) ."' method='POST'>";
        echo "<td><input type='submit' name='add-service' value='Add' style=\"background-color:#36D696; color:#ffffff;\"></td>";
//        echo "<td><input type='text' name='status' value='' required=''></td>";
        echo "<td><input type='date' name='last_serviced' value='' required=''></td>";
        echo "<td><input type='number' min='0' name='odometer_at_last_serviced' value='' required=''></td>";
        echo "<td><textarea name='memo' value=''></textarea></td>";
        echo "</form></tr>";


        foreach ($services as $service) {
            echo "<tr>";
            echo "<td></td>";
//            echo "<td>".$service->status."</td>";
            echo "<td>".$service->last_serviced."</td>";
            echo "<td>".$service->odometer_at_last_serviced."</td>";
            echo "<td>".$service->memo."</td>";
            echo "</tr>";

        }

        echo "</tbody></table>";

    }


    protected function save_service($vehicle_id, $last_serviced, $odometer, $memo)
    {
        global $wpdb; //access wordpress instance

        //the table to be inserted into
        $table_name = $wpdb->prefix . 'services';

        //format
        $format = array('%s','%d');

        //check if required fields are there
        if ($vehicle_id && $last_serviced && $odometer && $memo) {
            //insert into db

            //construct the data
            $data = array(
                'vehicle_id' => $vehicle_id,
                'status' => '0',
                'last_serviced'             => $last_serviced,
                'odometer_at_last_serviced' => $odometer,
                'memo'                      => $memo,
            );

//            print_r($data);

            $wpdb->insert($table_name, $data, $format);

            //add id
//            $wpdb->insert_id;


            echo '<div>';
            echo '<p><b style="color:#36D696">Nifty! You Just added a service record</b></p>';
            echo '</div>';


        } else {

//            echo '<div>';
//            echo '<p><b style="color:darkred">Oh On! Something went wrong?</b></p>';
//            echo '</div>';

        }


    }


    /**
     * add a vehicle form
     */
    function wpa_page_add_vehicle() {
        global $wpdb; //access wordpress instance

        //the table to be inserted into
        $table_name = $wpdb->prefix . 'vehicle';

        //reference delete.js
        wp_enqueue_script('delete', plugin_dir_url(__FILE__).'js/'.'delete.js');

        //services table
        $table_services = $wpdb->prefix . 'services';

        //format for the data
        $format = array('%s','%d');

        //get data on editing and viewing from the url using get method
        $edit  = strip_tags(sanitize_text_field( $_GET["edit"] ));
        $view = strip_tags(sanitize_text_field( $_GET["view"] ));
        $deleted = strip_tags(sanitize_text_field( $_GET["deleted"] ));

        //if adding (default)
        $title = "Add";
        $button = "Add Vehicle";
        $disabled = '';
        $cancelOrDone = "Cancel";

        //if editing
        if ($edit >= 1) {
            $title = "Edit";
            $button = "Edit Vehicle";
            $ifViewOrEdit =  $edit;

         //if viewing
        } elseif ($view >= 1) {
            $title = "View";
            $button = "Done";
            $ifViewOrEdit = $view;
            $disabled = 'disabled';

        }

        //delete record and redirect
        if ( is_numeric( $deleted ) ) {
            self::delete($deleted);
        }


        //save service
        if ($view >= 1) {

            $id              = strip_tags(sanitize_text_field( $_GET["view"] ));
            $status             = strip_tags(sanitize_text_field( $_POST["status"] ));
            $last_serviced             = strip_tags(sanitize_text_field( $_POST["last_serviced"] ));
            $odometer             = strip_tags(sanitize_text_field( $_POST["odometer_at_last_serviced"] ));
            $memo             = strip_tags(sanitize_text_field( $_POST["memo"] ));

//            test echo
//            echo $id .'</br>';
//            echo $status .'</br>';
//            echo $last_serviced .'</br>';
//            echo $odometer .'</br>';
//            echo $memo .'</br>';

            //save services
            self::save_service($id, $last_serviced, $odometer, $memo);
        }



        // if the submit button is clicked
        if ( isset( $_POST['submitted'] ) ) {

            //go back to vehicle lookup


            // sanitize data from POST method and strip tags
            $id              = strip_tags(sanitize_text_field( $_GET["edit"] ));
            $vin             = strip_tags(sanitize_text_field( $_POST["vin"] ));
            $year            = strip_tags(sanitize_text_field( $_POST["year"] ));
            $make            = strip_tags(sanitize_text_field( $_POST["make"] ));
            $model           = strip_tags(sanitize_text_field( $_POST["model"] ));
            $color           = strip_tags(sanitize_text_field( $_POST["color"] ));



            //check if required fields are there
            if ( $vin && $year && $make && $model) {
                //insert into db

                //construct the data
                $data = array(
                    'vin'                       => $vin,
                    'year'                      => $year,
                    'make'                      => $make,
                    'model'                     => $model,
                    'color'                     => $color,
//                    'odometer_at_last_serviced' => $odometer,
//                    'last_serviced'             => $lastServiced,
//                    'memo'                      => $memo,
                );

                //do the insert
                if ($edit >= 1) {

                    $wpdb->update($table_name, $data, array('id' => $id));

                    $cancelOrDone = "Done";

                    echo '<div>';
                    echo '<p><b style="color:#36D696">Nifty! You Just update a vehicle</b></p>';
                    echo '</div>';

                } else {
                    $wpdb->insert($table_name, $data, $format);

                    //add id
                    $wpdb->insert_id;


                    echo '<div>';
                    echo '<p><b style="color:#36D696">Nifty! You Just added a '. $make .' ' . $model . ', would you like to add another?</b></p>';
                    echo '</div>';
                }

            } else {

                echo '<div>';
                echo '<p><b style="color:darkred">Oh On! Something went wrong?</b></p>';
                echo '</div>';

            }


        }


        $table_name = $wpdb->prefix . 'vehicle';
        $vehicle = $wpdb->get_results('SELECT * FROM '.$table_name.'  WHERE `id` = '.$ifViewOrEdit)[0];
        $yearValue = ($edit >= 1) ? $vehicle->year : date("Y");

        //the form

        //this form will be ether in view mode, edit mode, or add mode depending on the url

        //title
        echo '<h2>'.$title.' Vehicle</h2>';
        echo '<table class="form-table"><tbody><form action="' . esc_url( admin_url(  ($edit >= 1 ) ? 'admin.php?page=add-vehicle&edit=' .$vehicle->id  : 'admin.php?page=add-vehicle'  ) ) . '" method="POST">';


        //action
        echo '<tr><th scope="row">Action</th>';
        echo '<td> <fieldset><legend class="screen-reader-text"><span>Action</span></legend><label for="submitted">';
        if ($view >= 1) {
            echo '<a href="' . esc_url( admin_url('admin.php?page=vehicle-lookup-dashboard') ) . ' ">'. $button . '</a>';
        } else {
            echo '<input type="submit" style="background-color:#36D696; color:#ffffff;" id="submitted" name="submitted" value="'.$button.'"/>';
            echo '<a style="padding-left: 50px;" href="' . esc_url( admin_url('admin.php?page=vehicle-lookup-dashboard') ) . ' ">'.$cancelOrDone.'</a>';
        }
        echo '</fieldset></td></tr>';


        //vin
        echo '<tr><th scope="row">VIN</th>';
        echo '<td> <fieldset><legend class="screen-reader-text"><span>VIN </span></legend><label for="vin">';
        echo ($view >= 1) ? $vehicle->vin : '<input name="vin" type="text" id="vin" value="'.$vehicle->vin.'"> <b style="color:darkred">*</b></label>';
        echo '</fieldset></td></tr>';

        //year
        echo '<tr><th scope="row">Year</th>';
        echo '<td> <fieldset><legend class="screen-reader-text"><span>Year </span></legend><label for="year">';
        echo ($view >= 1) ? $vehicle->year : '<input name="year" type="number" id="year" value="'. $yearValue .'" min="1953" max="2039" required=""> <b style="color:darkred">*</b></label>';
        echo '</fieldset></td></tr>';

        //make
        echo '<tr><th scope="row">Make</th>';
        echo '<td> <fieldset><legend class="screen-reader-text"><span>Make </span></legend><label for="make">';
        echo ($view >= 1) ? $vehicle->make : '<input name="make" type="text" id="make" value="'.$vehicle->make.'"  required=""> <b style="color:darkred">*</b></label>';
        echo '</fieldset></td></tr>';

        //model
        echo '<tr><th scope="row">Model</th>';
        echo '<td> <fieldset><legend class="screen-reader-text"><span>Model </span></legend><label for="model">';
        echo ($view >= 1) ? $vehicle->model : '<input name="model" type="text" id="model" value="'.$vehicle->model.'"  required=""> <b style="color:darkred">*</b></label>';
        echo '</fieldset></td></tr>';

        //Color
        echo '<tr><th scope="row">Color</th>';
        echo '<td><fieldset><legend class="screen-reader-text"><span>Color </span></legend><label for="color">';
        echo '<input name="color" type="color" id="color" value="'.$vehicle->color.'" '.$disabled.' >';
        echo '</fieldset></td></tr>';

//        //Odometer At Last Serviced
//        echo '<tr><th scope="row">Odometer At Last Serviced</th>';
//        echo '<td> <fieldset><legend class="screen-reader-text"><span>Odometer At Last Serviced </span></legend><label for="odometer_at_last_serviced">';
//        echo ($view >= 1) ? $vehicle->odometer_at_last_serviced : '<input name="odometer_at_last_serviced" type="number"  id="odometer_at_last_serviced" value="'.$vehicle->odometer_at_last_serviced.'"></label>';
//        echo '</fieldset></td></tr>';
//
//        //Last Serviced
//        echo '<tr><th scope="row">Last Serviced</th>';
//        echo '<td> <fieldset><legend class="screen-reader-text"><span>Last Serviced </span></legend><label for="last_serviced">';
//        echo ($view >= 1) ? $vehicle->last_serviced : '<input name="last_serviced" type="date" id="last_serviced" value="'.$vehicle->last_serviced.'"></label>';
//        echo '</fieldset></td></tr>';
//
//        //memo
//        echo '<tr><th scope="row">Memo</th>';
//        echo '<td> <fieldset><legend class="screen-reader-text"><span>Memo </span></legend><label for="memo">';
//        echo '<textarea name="memo" style="width: 350px; height: 250px;" id="memo" '.$disabled.'>'.$vehicle->memo.'</textarea></label>';
//        echo '</fieldset></td></tr>';

        //delete record only in view mode
        if ($view >= 1) {
            echo '<tr><th scope="row">Delete Record</th>';
            echo '<td> <fieldset><legend class="screen-reader-text"><span>Delete Record</span></legend><label for="delete">';

            echo '
            <div id="deleteButton">
                <a style="color: darkred;" onclick="deletePrompt(\'deleteButton\', \'deleteOptions\')">Delete</a>
            </div>
            ';

            echo '<div id="deleteOptions" style="display: none">  
            <a  style="color: #36D696;" onclick="deletePrompt(\'deleteButton\', \'deleteOptions\')">No</a>         
            <a  style="color: darkred; padding-left: 50px;" href="' . esc_url(admin_url('admin.php?page=add-vehicle&deleted='.$vehicle->id )) . ' ">Yes, delete it!</a>
            </div>';


            echo '</fieldset></td></tr>';
        } else {
            //required label
            echo '<b style="color:darkred">*</b> required';
        }

        //close table
        echo '</form></tbody></table>';


        //show post services when viewing
        if ($view >= 1) {
            //display chart for vehicle services
            //receives vehicle id as a perimeter
            self::services($vehicle->id);
        }



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
