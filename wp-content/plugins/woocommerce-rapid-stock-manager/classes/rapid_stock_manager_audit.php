<?php

/**
 * Class audit system class
 * @author ishouty ltd. London
 * @date 2017
 */
class rapid_stock_manager_audit {

    /**
     * @var string Id of this plugin for internationalisation used in __ and _e
     */
    public $id = 'woocommerce-rapid-stock-manager';

    public $rapidStockManager;

    /**
     * @var string Base of the CSV file export of stock audit
     */
    public $stock_audit_csv_filename = "rsm-stock-audit-report";

    /**
     * @var string Stock Audit table name (without wp prefix)
     */
    public $db_table_audit = "ishouty_rsm_audit";


    function __construct ($rapidStockManager) {
        $this->rapidStockManager = $rapidStockManager;
    }

    /**
     * @create-audit
     * get audit csv file
     * @return string
     */
    public function get_stock_audit_csv_filename()
    {
        return $this->stock_audit_csv_filename . '-' . date("YmdHis") . '.csv';
    }

    /**
     * update latest version
     */
    public function check_latest_version_installed() {

        global $wpdb;
        $table_name = $this->get_table_audit();

        //check if column table is installed into the warehouse
        if ($wpdb->get_row("SHOW COLUMNS FROM $table_name LIKE 'warehouse'")) {
            return true;
        }

        return false;

    }

    /**
     * update existing users latest audit version table version
     */
    public function update_audit_for_existing_users () {

        global $wpdb;
        $table_name = $this->get_table_audit();

        $query = "ALTER TABLE $table_name ADD warehouse varchar(30) NULL";
        $wpdb->query($query);

        $query = "ALTER TABLE $table_name ADD reference_no varchar(50)";
        $wpdb->query($query);

        $query = "ALTER TABLE $table_name CHANGE action action enum('adjust','set', 'deduct', 'transfer_from', 'transfer_to')";
        $wpdb->query($query);
        
        $query = "ALTER TABLE $table_name CHANGE  `action_amount`  `action_amount` INT( 11 ) NULL";
        $wpdb->query($query);

        $query = "ALTER TABLE $table_name CHANGE  `stock_old_value`  `action_amount` INT( 11 ) NULL";
        $wpdb->query($query);

        $query = "ALTER TABLE $table_name CHANGE  `stock_new_value`  `action_amount` INT( 11 ) NULL";
        $wpdb->query($query);

    }


    /**
     * @create-audit
     * Gets Table name for stock audit (wp_ishouty_rsm_audit)
     * @return string Table name for stock audit (wp_ishouty_rsm_audit)
     */
    public function get_table_audit()
    {
        global $wpdb;
        return $wpdb->prefix . $this->db_table_audit;
    }

    /**
     * @create-audit
     * True/False Does the stock audit table exist?
     * @return bool false if no audit table presengetproductByWarehouset
     */
    public function has_rsm_audit_table()
    {
        global $wpdb;
        $table_name = $this->get_table_audit();

        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return false;
        }

        return true;
    }


    /**
     * @create-audit
     */
    public function add_rsm_table_audit() {

        global $wpdb;

        $table_name = $this->get_table_audit();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                timestamp timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL,
                username varchar(50) NOT NULL,
                user_id INT(11) NOT NULL,
                product_sku varchar(30) NOT NULL,
                product_name varchar(70) NOT NULL,
                product_id INT(11) NOT NULL,
                variation varchar(150) NOT NULL,
                action enum('adjust','set', 'deduct', 'transfer_from', 'transfer_to') NOT NULL,
                reference_no varchar(50),
                action_amount INT(11) NULL,
                stock_old_value INT(11) NULL,
                stock_new_value INT(11) NULL,
                warehouse varchar(30) NULL,
                UNIQUE KEY uk_id (id)
                ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $results = dbDelta( $sql );
        if( $results && is_array($results) && array_key_exists($table_name,$results)){
            echo '<div class="notice"><p>' . __($results[$table_name], $this->id) . '</p></div>';
        }

    }

    /**
     * @create-audit
     */
    public function check_rsm_audit_table()
    {
        if (!$this->has_rsm_audit_table()) {
            $this->add_rsm_table_audit();
        }

        if (!$this->check_latest_version_installed()) {
            $this->update_audit_for_existing_users();
        }

    }

    /**
     * @param $rapidStockManager - class to get the various function calls
     */
    public function check_rsm_version_for_audit() {
        $option_db = $this->rapidStockManager->get_option_rsm_version();

        if (!get_option($option_db)) {
            add_option($option_db, $this->rapidStockManager->get_version());
            $this->check_rsm_audit_table();
        } else {
            if (get_option($option_db) != $this->rapidStockManager->get_version()) {
                update_option($option_db, $this->rapidStockManager->get_version());
                $this->check_rsm_audit_table();
            }
        }

    }


    /**
     * @create-audit
     * Returns formatted string for textarea - list of products with variants as rows
     * @param array $table_data Data to transfer to rows and columns
     * @param string $delimiter Default is Tab
     * @param string $line_delimiter Line/Row Default is \n
     * @return string
     */
    public function get_report_inventory_audit($filter = array(), $delimiter = "\t", $line_delimiter = "\n")
    {

        global $wpdb;
        $table = $this->get_table_audit();
        $results = array();
        if (empty($filter)) {
            return __('No products available to list.', $this->id);
        }

        $output = __('Date', $this->id) . $delimiter . __('Product SKU', $this->id) .
            $delimiter . __('Product ID', $this->id) .
            $delimiter . __('Product', $this->id) .
            $delimiter . __('Variant', $this->id) .
            $delimiter . __('Action', $this->id) .
            $delimiter . __('Action amount', $this->id) .
            $delimiter . __('Old value', $this->id) .
            $delimiter . __('New value', $this->id) .
            $delimiter . __('Username', $this->id) .
            $delimiter . __('User ID', $this->id) .
            $delimiter . __('Warehouse', $this->id) .
            $delimiter . __('Reference No', $this->id) .
            $line_delimiter;

        $where_query = "WHERE 1=1";

        if ((isset($filter["reference_no"])) && ($filter["reference_no"] != "")) {

            $where_query .= ' AND reference_no = %s';
            $where_query = $wpdb->prepare($where_query, trim($filter["reference_no"]));

        } else {

            if ((isset($filter["warehouse"])) && ($filter["warehouse"] == "all")) {
                $where_query .= '';
            } elseif ((isset($filter["warehouse"])) && ($filter["warehouse"] != "")) {
                $where_query .= ' AND warehouse = %s';
                $where_query = $wpdb->prepare($where_query, $filter["warehouse"]);
            }  else {
                $where_query .= ' AND (warehouse = "" OR warehouse is NULL OR warehouse = "main_stock")';
            }

            if ((isset($filter["from"])) && (isset($filter["to"])) && ($filter["from"] != "") && ($filter["to"] != "")) {
                $where_query .= " AND (unix_timestamp(`timestamp`) BETWEEN %s AND %s)";
                $where_query = $wpdb->prepare($where_query, $filter["from"], $filter["to"]);
            }


            if ((isset($filter["sku"])) && ($filter["sku"] != "")) {
                $get_sku = explode(" ", $filter["sku"]);
                if (!empty($get_sku)) {
                    $where_query .= " AND (";
                    $sn = 0;
                    foreach ($get_sku as $sku) {
                        $where_query .= " product_sku = %s";
                        $where_query = $wpdb->prepare($where_query, $sku);
                        $sn++;
                        if ($sn != count($get_sku)) {
                            $where_query .= " OR";
                        }
                    }
                    $where_query .= ")";
                }
            }

            if ((isset($filter["select-action-list"])) && ($filter["select-action-list"] != "")) {

                if ($filter["select-action-list"] == 'transfers') {
                    $where_query .= ' AND (action = "transfer_from" OR action = "transfer_to")';
                } elseif ($filter["select-action-list"] !== 'all')  {
                    $where_query .= ' AND action = %s';
                    $where_query = $wpdb->prepare($where_query, $filter["select-action-list"]);
                }

            }

        }


        $sql = 'SELECT * FROM ' . $table . ' ' . $where_query . ' ORDER BY id ASC';
        $get_audit = $wpdb->get_results($sql, ARRAY_A);

        foreach ($get_audit as $audit) {
            $output .= $audit["timestamp"] .
                $delimiter . $audit["product_sku"] .
                $delimiter . $audit["product_id"] .
                $delimiter . $audit["product_name"] .
                $delimiter . $audit["variation"] .
                $delimiter . $audit["action"] .
                $delimiter . $audit["action_amount"] .
                $delimiter . $audit["stock_old_value"] .
                $delimiter . $audit["stock_new_value"] .
                $delimiter . $audit["username"] .
                $delimiter . $audit["user_id"] .
                $delimiter . $audit["warehouse"] .
                $delimiter . $audit["reference_no"] .
                $line_delimiter;
        }


        return $output;
    }

    /**
     * @create-audit
     * Returns CSV with inventory audit
     */
    public function get_csv_report_inventory_audit()
    {

        $filename = $this->get_stock_audit_csv_filename();

        $filter_args = array();
        $filter_from = array_key_exists('filter-from', $_GET) ? $_GET['filter-from'] : '';
        $filter_to = array_key_exists('filter-to', $_GET) ? $_GET['filter-to'] : '';
        $filter_sku = array_key_exists('filter-sku', $_GET) ? $_GET['filter-sku'] : '';
        $filter_warehouse = array_key_exists('filter-warehouse', $_GET) ? $_GET['filter-warehouse'] : '';
        $filter_args["from"] = htmlspecialchars($filter_from);
        $filter_args["to"] = htmlspecialchars($filter_to);
        $filter_args["sku"] = htmlspecialchars($filter_sku);
        $filter_args["warehouse"] = htmlspecialchars($filter_warehouse);

        $content = $this->get_report_inventory_audit($filter_args, ',');

        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=" . $filename . "");

        $output = fopen('php://output', 'w');
        fwrite($output, $content);
        fclose($output);
        die();
    }

    /**
     * @create-audit
     * update audit operations
     * @param $product_id
     * @param $args
     */
    public function update_audit($product_id, $args) {
        global $wpdb;
        $table = $this->get_table_audit();
        $user = wp_get_current_user();
        if (!isset($args["variation"])) {
            if (get_post_type($product_id) == "product_variation") {
                $product_object = wc_get_product($product_id);
                $variant = $this->rapidStockManager->get_variation_row_details($product_object);
                $args["variation"] = $variant["variant"];
                $product_name = $product_object->get_title() . ' [variant]';
            } else {
                $args["variation"] = '';
                $product_name = get_the_title($product_id);
            }
        }else{
            $product_name = get_the_title($product_id);
        }

        $data = array(
            "username" => $user->user_login,
            "user_id" => $user->ID,
            "product_sku" => get_post_meta($product_id, "_sku", true),
            "product_name" => $product_name,
            "product_id" => $product_id,
            "variation" => trim(strip_tags($args["variation"])),
            "action" => $args["action"],
            "action_amount" => $args["action_amount"],
            "stock_old_value" => $args["stock_old_value"],
            "stock_new_value" => $args["stock_new_value"],
            "warehouse" => $args["warehouse"],
            "reference_no" => $args["reference_no"]
        );

        $wpdb->insert($table, $data);

    }

    /**
     * returns stock audit information
     * @param $product_id
     */
    public function stock_audit_info($product_id) {
        global $wpdb;
        $table = $this->get_table_audit();
        $results = array();

        $last_set = $wpdb->get_results('SELECT * FROM ' . $table . ' WHERE product_id = ' . intval($product_id) . ' AND action = "set" ORDER BY id DESC LIMIT 1', ARRAY_A);
        if (!empty($last_set)) {
            $results["last_set"] = $last_set[0]["timestamp"];
            $results["set"] = $last_set[0]["stock_new_value"];
        }

        $last_adjusted = $wpdb->get_results('SELECT *,DATE(timestamp) as sameday FROM ' . $table . ' WHERE product_id = ' . intval($product_id) . ' AND action = "adjust" ORDER BY id DESC LIMIT 1', ARRAY_A);
        if (!empty($last_adjusted)) {
            $results["last_adjusted"] = @$last_adjusted[0]["timestamp"];
            $results["adjusted"] = @$last_adjusted[0]["action_amount"];
        }

        $adjusted = $wpdb->get_results('SELECT * FROM ' . $table . ' WHERE product_id = "' . intval($product_id) . '" AND action = "adjust" AND date(timestamp) = "' . @$last_adjusted[0]["sameday"] . '" AND id != "' . intval(@$last_adjusted[0]["id"]) . '" ORDER BY id DESC', ARRAY_A);
        if (!empty($adjusted)) {
            foreach ($adjusted as $stock_update) {
                $results["adjusted"] = $results["adjusted"] + $stock_update["action_amount"];
            }
        }

        return $results;

    }


    /**
     * display audit action list
     * @param $actionSelected
     */
    public function display_action_lists($actionSelected)
    {
        $actions_list = array ('all', 'set', 'adjust', 'deduct','transfers');
        ?>

        <div class="select-action-list-container inline-block">

            <?php _e('Select Action List: ', $this->id); ?>
            <select name="select-action-list" id="select-action-list" >
                <?php if (!empty($actions_list)) {
                    foreach ($actions_list as $action) {?>
                        <option value="<?php echo $action; ?>" <?php if ($actionSelected == $action) { ?> selected <?php } ?> >
                            <?php echo ucwords($action); ?></option>
                    <?php }
                } ?>
            </select>

        </div>

        <?php
    }







}