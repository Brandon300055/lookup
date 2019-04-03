<?php
/**
 * Plugin Name: Vehicle Lookup
 * Plugin URI: https://suwdesign.com
 * Description: Looks vehicles up by there VIN.
 * Version: 1.0
 * Author: Brandon Stewart
 * Author URI: http://brandonsreusme.ml
 */




/**
 * Class WP_Vehicle_Lookup
 *
 * this class manages the plugin
 */

class WP_Vehicle_Lookup{


    // Constructor
    /**
     * WP_Vehicle_Lookup constructor.
     */
    function __construct() {

        //access wordpress db
        global $wpdb;

        //class wide variables for tables in the db
        $wpdb->table_vehicle = $wpdb->prefix . 'vehicle_vl';
        $wpdb->table_services = $wpdb->prefix . 'services_vl';
//        $wpdb->table_services_list = $wpdb->prefix . 'services_list_vl';


        //build the plugin
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
    private function is_hex_color($hexColor)
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


    /**
     * this method generates the dropdown of all the services offered
     */
    private function services_dropdown()
    {

        //services list
        //needs to be pulled from a db in a future update
        $servicesList = array(
            "Engine oil",
            "Transmission",
            "Front diff",
            "Lube",
            "Carb",
            "Battery",
            "Valve adjustment",
            "Shocks",
            "Radiator",
            "Thermostat",
            "Belt",
            "Primary clutch",
            "Secondary clutch",
            "Brakes",
            "Tires",
            "Other",
        );


        echo "<td><select name='service'>";
        //loop over all the serves in the list
        foreach ($servicesList as $serviceItem) {
            echo "<option value='".$serviceItem."'>".$serviceItem."</option>";
        }

        echo "</select></td>";

    }


    /*
     * Actions perform on loading of menu pages
     */
    function wpa_page_vehicle_lookup_dashboard() {

        global $wpdb;

        //reference search.js
        wp_enqueue_script('search', plugin_dir_url(__FILE__).'js/'.'search.js');

        //get vehicles
        $vehicles = $wpdb->get_results('SELECT * FROM '.$wpdb->table_vehicle);

        echo '<h2>Vehicle Lookup</h2>';

        //the search fields use functions found in search.js. works by setting all non machetes to display non
        //search() function will take the id of the input and the column number of the table being searched.
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
      </tr></thead><tbody>';

        //create vehicle table
        foreach ($vehicles as $vehicle) {
            $color = ( self::is_hex_color($vehicle->color)) ? "<div style='background-color: ".$vehicle->color."; width:100%; height:25px'></div>" : "Unknown" ;

            echo "<td><a href=".esc_url( admin_url('admin.php?page=add-vehicle&view=' . $vehicle->id ) ).">View</a> | ";
            echo "<a href=".esc_url( admin_url('admin.php?page=add-vehicle&edit=' . $vehicle->id ) ).">Edit</a></td>";

            echo "<td>".$vehicle->vin."</td>";
            echo "<td>".$vehicle->year."</td>";
            echo "<td>".$vehicle->make."</td>";
            echo "<td>".$vehicle->model."</td>";
            echo "<td>".$color."</td>";
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

        //preform the delete
        $wpdb->delete(
            $wpdb->table_vehicle,
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

        //get services by date and by vehicle id
        $services = $wpdb->get_results('SELECT * FROM '.$wpdb->table_services.' WHERE `vehicle_id` = '.$vehicleID.' ORDER BY last_serviced DESC;');


//        echo $vehicleID;

        echo
        '<table id="vehicle-table" class="widefat fixed" cellspacing="0">
      <thead><tr>
      <th>Action</th>
      <th>Service</th>
      <th>Last Serviced</th>
      <th>Hours</th>
      <th>Memo</th>
      </tr></thead><tbody>';

        //create form for a service
        echo "<tr><form action='". esc_url( admin_url("admin.php?page=add-vehicle&view=".$vehicleID) ) ."' method='POST'>";
        echo "<td><input type='submit' name='add-service' value='Add' style=\"background-color:#36D696; color:#ffffff;\"></td>";

        //have a dropdown list for services
        self::services_dropdown();

        echo "<td><input type='date' name='last_serviced' value='' required=''></td>";
        echo "<td><input type='number' min='0' name='odometer_at_last_serviced' value='' required=''></td>";
        echo "<td><textarea name='memo' value=''></textarea></td>";
        echo "</form></tr>";

        //loop over all the past services for this vehicle and create a table
        foreach ($services as $service) {
            echo "<tr>";
            echo "<td><a>Edit</a> | <a style='color: darkred'>Drop</a></td>";
            echo "<td>".$service->service."</td>";
            echo "<td>".$service->last_serviced."</td>";
            echo "<td>".$service->odometer_at_last_serviced."</td>";
            echo "<td>".$service->memo."</td>";
            echo "</tr>";

        }

        echo "</tbody></table>";

    }


    protected function save_service($vehicle_id, $last_serviced, $odometer, $memo, $service)
    {
        global $wpdb; //access wordpress instance

        //check if required fields are there
        if ($vehicle_id && $last_serviced && $odometer && $memo && $service) {

            //format
            $format = array('%s','%d');

            //construct the data
            $data = array(
                'vehicle_id' => $vehicle_id,
                'status' => '1',
                'service' => $service,
                'last_serviced'             => $last_serviced,
                'odometer_at_last_serviced' => $odometer,
                'memo'                      => $memo,
            );

            //test the printed data
//            print_r($data);

            //insert into db
            $wpdb->insert($wpdb->table_services, $data, $format);

            //add id
            $wpdb->insert_id;


            //success message
            echo '<div>';
            echo '<p><b style="color:#36D696">Nifty! You Just added a service record</b></p>';
            echo '</div>';


        } else {

//            echo '<div>';
//            echo '<p><b style="color:darkred">Oh On! Something went wrong?</b></p>';
//            echo '</div>';

        }


    }


    protected function add_edit_vehicle(array $POST, array $GET)
    {
        global $wpdb; //access wordpress instance

        $format = array('%s','%d');

        // if the submit button is clicked
        if ( isset( $POST['submitted'] ) ) {

            //go back to vehicle lookup

            // sanitize data from POST method and strip tags
            $id              = strip_tags(sanitize_text_field( $GET["edit"] ));
            $vin             = strip_tags(sanitize_text_field( $POST["vin"] ));
            $year            = strip_tags(sanitize_text_field( $POST["year"] ));
            $make            = strip_tags(sanitize_text_field( $POST["make"] ));
            $model           = strip_tags(sanitize_text_field( $POST["model"] ));
            $color           = strip_tags(sanitize_text_field( $POST["color"] ));

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
                );

                //do the insert
                if ($id >= 1) {

                    $wpdb->update($wpdb->table_vehicle, $data, array('id' => $id));

                    $cancelOrDone = "Done";

                    echo '<div>';
                    echo '<p><b style="color:#36D696">Nifty! You Just update a vehicle</b></p>';
                    echo '</div>';

                } else {
                    $wpdb->insert($wpdb->table_vehicle, $data, $format);

                    //add id
                    $wpdb->insert_id;

                    //success message
                    echo '<div>';
                    echo '<p><b style="color:#36D696">Nifty! You Just added a '. $make .' ' . $model . ', would you like to add another?</b></p>';
                    echo '</div>';
                }

            } else {

                //failure massage
                echo '<div>';
                echo '<p><b style="color:darkred">Oh On! Something went wrong?</b></p>';
                echo '</div>';

            }
        }
    }



    /**
     * add a vehicle form
     */
    function wpa_page_add_vehicle() {
        global $wpdb; //access wordpress instance

        //reference delete.js
        wp_enqueue_script('delete', plugin_dir_url(__FILE__).'js/'.'delete.js');


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
            $service             = strip_tags(sanitize_text_field( $_POST["service"] ));
            $last_serviced             = strip_tags(sanitize_text_field( $_POST["last_serviced"] ));
            $odometer             = strip_tags(sanitize_text_field( $_POST["odometer_at_last_serviced"] ));
            $memo             = strip_tags(sanitize_text_field( $_POST["memo"] ));


            //save services
            self::save_service($id, $last_serviced, $odometer, $memo, $service);
        }

        self::add_edit_vehicle($_POST, $_GET);

        $vehicle = $wpdb->get_results('SELECT * FROM '.$wpdb->table_vehicle.'  WHERE `id` = '.$ifViewOrEdit)[0];
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


    /**
     * creates the database for the vehicles on install
     */
    private function create_vehicle_db() {

        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $wpdb->table_vehicle (
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
    private function create_services_db() {

        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $wpdb->table_services (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        vehicle_id mediumint(9) NOT NULL,
        status text NULL,
        service text NOT NULL,
        odometer_at_last_serviced int(20)  NULL,
        last_serviced datetime DEFAULT '0000-00-00' NULL,
        memo longtext  NULL,
        time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
  		UNIQUE KEY id (id)
 	) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }


//    /**
//     * creates the services table in the db
//     */
//    private function create_services_list_db() {
//
//        global $wpdb;
//        $charset_collate = $wpdb->get_charset_collate();
//        $sql = "CREATE TABLE $wpdb->services_list (
//        id mediumint(9) NOT NULL AUTO_INCREMENT,
//        text longtext  NULL,
//        time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
//  		UNIQUE KEY id (id)
// 	) $charset_collate;";
//
//        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
//        dbDelta( $sql );
//    }

    private function delete_db_tables()
    {

    }

    /*
     * Actions perform on activation of plugin
     */
    function wpa_install() {

        //create vehicle db
        self::create_vehicle_db();

        //create services db
        self::create_services_db();

        //create services list db
//        self::create_services_list_db();

        //seed services list db


    }

    /*
     * Actions perform on de-activation of plugin
     */
    function wpa_uninstall() {

        //delete tables
//        self::delete_db_tables();
    }

}

new WP_Vehicle_Lookup();
