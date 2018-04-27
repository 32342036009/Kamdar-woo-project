<?php

/**
 * Class audit system class
 * @author ishouty ltd. London
 * @date 2017
 */
class rapid_warehouse_manager
{

    /**
     * @var string Id of this plugin for internationalisation used in __ and _e
     */
    public $id = 'woocommerce-rapid-stock-manager';

    /**
     * @var string Warehouse table name (without wp prefix)
     */
    public $db_table_woocommerce_product = "rsm_stock_woocommerce_product_table";

    /**
     * settings id for input fields
     * @var string
     */
    public $settings_id_prefix = 'wc_settings_tab_rapid_sm_';

    public $rapidStockManager;
    public $settings = array(
        'report_enable_warehouse' => null,
        'report_addd_warehouse' => null,
        'report_edit_warehouse' => null,
    );

    function __construct ($rapidStockManager) {
        $this->rapidStockManager = $rapidStockManager;
    }

    /**
     * @database
     * @return string
     */
    public function get_table_woocommerce_product()
    {
        global $wpdb;
        return $wpdb->prefix . $this->db_table_woocommerce_product;
    }

    /**
     * @database
     * create product table
     */
    public function create_product_database_table()
    {
        global $wpdb;
        $product_table_name = $this->get_table_woocommerce_product();

        if ($wpdb->get_var("SHOW TABLES LIKE '$product_table_name'") != $product_table_name) {
            $sql = "CREATE TABLE IF NOT EXISTS " . $product_table_name . "(
            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `product_id` int(11) NOT NULL,
            `product_parent_id` int(11) NOT NULL,
            `meta_key` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
            `meta_value` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
            `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `id` (`id`),
            UNIQUE KEY `product_id_meta_key` (`product_id`,`meta_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1";

            $wpdb->query($sql);
        }

    }

    /**
     * check if warehouse is enabled
     * @return bool
     */
    public function warehouse_enabled()
    {
        $warehouse_enabled = get_option('wc_settings_tab_rapid_sm_report_enable_warehouse');
        if ($warehouse_enabled === 'yes') {
            return true;
        }
        return false;
    }

    /**
     * @admin-settings
     * update warehouse admin settings
     */
    function update_warehouse_settings()
    {

        $json_data = array();
        $wc_settings_tab_rapid_add_warehouse = unserialize(get_option('wc_settings_tab_rapid_sm_report_addd_warehouse'));

        if (!empty($_REQUEST['wc_settings_tab_rapid_sm_report_edit_warehouse'])) {

            $values = $_REQUEST['wc_settings_tab_rapid_sm_report_edit_warehouse'];
            $values = $values - 1;

            $keys = $wc_settings_tab_rapid_add_warehouse[$values];
            unset($wc_settings_tab_rapid_add_warehouse[$values]);

            $wc_settings_tab_rapid_add_warehouse = array_merge($wc_settings_tab_rapid_add_warehouse);
            $this->deleteWarehouse($keys);

        }

        if (count($wc_settings_tab_rapid_add_warehouse) < 5) {
            $request_add_warehouse = array_key_exists('wc_settings_tab_rapid_sm_report_addd_warehouse', $_REQUEST) ? $_REQUEST['wc_settings_tab_rapid_sm_report_addd_warehouse'] : '';

            if (!in_array(strtolower($request_add_warehouse), $wc_settings_tab_rapid_add_warehouse)) {

                woocommerce_update_options($this->rapidStockManager->get_admin_settings());

                $add_warehouse = trim($request_add_warehouse, ' ');

                if (!empty($wc_settings_tab_rapid_add_warehouse) AND !empty($add_warehouse)) {

                    $json_data[] = strtolower($add_warehouse);
                    $result = array_merge($wc_settings_tab_rapid_add_warehouse, $json_data);
                    $result = serialize($result);
                    update_option('wc_settings_tab_rapid_sm_report_addd_warehouse', $result);

                } else if (!empty($wc_settings_tab_rapid_add_warehouse) AND empty($add_warehouse)) {

                    $wc_settings_tab_rapid_add_warehouse = serialize($wc_settings_tab_rapid_add_warehouse);
                    update_option('wc_settings_tab_rapid_sm_report_addd_warehouse', $wc_settings_tab_rapid_add_warehouse);

                } else if (empty($wc_settings_tab_rapid_add_warehouse) AND !empty($add_warehouse)) {

                    $json_data[] = strtolower($add_warehouse);
                    $wc_settings_tab_rapid_add_warehouse = serialize($json_data);
                    update_option('wc_settings_tab_rapid_sm_report_addd_warehouse', $wc_settings_tab_rapid_add_warehouse);

                }

            }

        } else {

            woocommerce_update_options($this->rapidStockManager->get_admin_settings());

            echo "<br><p>". __('You can only have a max of 5 warehouses. Please contact dev@ishouty.com for more warehouses.',$this->id) . "</p>";

            $wc_settings_tab_rapid_add_warehouse = serialize($wc_settings_tab_rapid_add_warehouse);
            update_option('wc_settings_tab_rapid_sm_report_addd_warehouse', $wc_settings_tab_rapid_add_warehouse);
        }

    }


    /**
     * delete
     * @param string $keys
     * @return int
     */
    public function deleteWarehouse($keys= '')
    {
        $output= 0;
        if(!empty($keys))
        {
            $keys= trim($keys, ' ');
            $keys= str_replace( ' ', '_', $keys );
            global $wpdb;
            $table_name = $this->get_table_woocommerce_product();
            $sql = "DELETE FROM %s WHERE `meta_key`= %s";
            $sqlResult= $wpdb->query($wpdb->prepare($sql,array($table_name,$keys)));

            if($sqlResult){
                $output= 1;
            }

        }
        return $output;

    }

    /**
     * @admin-settings
     * return back rapid stock manager settings
     * @return array
     */
    public function getBackWarehouseSettings()
    {

        $arr_data1 = unserialize(get_option('wc_settings_tab_rapid_sm_report_addd_warehouse'));
        if (!empty($arr_data1)) {
            $arr_data = array();
            foreach ($arr_data1 as $keys => $values) {
                $arr_data[] = ucwords($values);
            }
            array_unshift($arr_data, "--Select--");
        } else {
            $arr_data = array('--Select--');
        }

        $settings = array(

            //custom
            array(
                'name' => __('Add Ware House', $this->id),
                'type' => 'title',
                'id' => $this->settings_id_prefix . 'title_add_warehouse',
                'desc' => (isset($w_msg) ? '<br/>' . $w_msg : ''),
            ),
            array(
                'name' => __('Enable Ware House', $this->id),
                'id' => $this->settings_id_prefix . 'report_enable_warehouse',
                'type' => 'checkbox',
                'default' => $this->settings["report_enable_warehouse"],
                'class' => 'rapid_stock_enable_warehouses',
                'options' => array(
                    'checked' => __('checked', $this->id)
                )
            ),
            array(
                'name' => __('Add Warehouse', $this->id),
                'id' => $this->settings_id_prefix . 'report_addd_warehouse',
                'type' => 'text',
                'class' => 'rapid_stock_add_warehouse',
                'default' => $this->settings["report_addd_warehouse"],
            ),
            array(
                'name' => __('Delete Warehouse', $this->id),
                'id' => $this->settings_id_prefix . 'report_edit_warehouse',
                'type' => 'select',
                'default' => $this->settings["report_edit_warehouse"],
                'class' => 'rapid_stock_remove_warehouse',
                'options' => $arr_data

            ),
            array(
                'type' => 'sectionend',
                'id' => $this->settings_id_prefix . 'section_end_4	'
            )

        );

        return $settings;

    }



    /**
     * @warehouse-function
     * add custom warehouse inventory
     * this would display on each products->woocommerce->details
     */
    function custom_tab_options_spec()
    {
        global $post;

        $custom_tab_options_spec = array(
            'titlec' => get_post_meta($post->ID, 'custom_tab_title_spec', true),
            'contentc' => get_post_meta($post->ID, 'custom_tab_content_spec', true),
        );

        $output = '';
        global $post;
        $product_id = $post->ID;
        $warehouse_lists = unserialize(get_option('wc_settings_tab_rapid_sm_report_addd_warehouse'));
        $product = wc_get_product($product_id);
        $variations = array();
        if (!empty($warehouse_lists)) {
            if ($product->is_type('variable')) {
                $variations = $product->get_available_variations();
            }
        }

        ?>

        <script type="text/javascript" src="<?php echo plugins_url('/assets/js/woocommerce-rapid-warehouse-pd.js', __FILE__); ?>"></script>

        <div id="warehouse_tab_options" class="panel woocommerce_options_panel wc-metaboxes-wrapper">
            <div class="woocommerce_variations wc-metaboxes ui-sortable">
                <div class="toolbar toolbar-top">
                    <strong><?php _e('Warehouses and quantity per variation',$this->id); ?></strong>
                    <div class="variations-pagenav">
                        <span class="displaying-num"><?php echo count($variations) . ' ' . __('variations',$this->id); ?></span>
						<span class="expand-close">
							(<a href="#" class="expand_all"><?php _e('Expand',$this->id); ?></a> / <a href="#" class="close_all"><?php _e('Close',$this->id); ?></a>)
						</span>
                    </div>
                    <div class="clear"></div>
                </div>
            <?php

            if (!empty($warehouse_lists)) {
                if ($product->is_type('variable')) {

                    for ($var_data = 0; $var_data < count($variations); $var_data++) {
                        $variation_product_id = $variations[$var_data]['variation_id'];
                        echo '<div class="woocommerce_variation wc-metabox closed">';
                        $attributes_text = http_build_query($variations[$var_data]['attributes']);
                        $attributes_text = str_replace('attribute_pa_', '', $attributes_text);
                        $attributes_text = str_replace('=', ' ', $attributes_text);
                        $attributes_text = str_replace('&', ', ', $attributes_text);
                        $attributes_text = ucwords($attributes_text);
                        echo '<h3><strong>' . $variation_product_id . ': '. $attributes_text .'</strong><div class="handlediv" title="'.__('Click to toggle',$this->id).'"></div></h3>';
                        echo '
                <div class="woocommerce_variable_attributes wc-metabox-content">
                    <div class="data">';
                        $this->get_warehouses_html($warehouse_lists, $variation_product_id, $product_id);
                        echo '
                    </div>
                </div>';
                        echo '</div>';
                    }

                } else {

                    $this->get_warehouses_html($warehouse_lists, $product_id, 0);
                }
                ?>

            <?php } ?>
            </div>
        </div><br/>
        <?php

    }


    /**
     * @warehouse-function
     * render out the warehouse lists for product details
     * @param array $warehouse_lists
     * @param int $product_id
     * @param int $parent_id
     */
    public function get_warehouses_html($warehouse_lists = array(), $product_id = 0, $parent_id = 0)
    {
        $output = '';
        if (!empty($warehouse_lists)) {
            ?>
            <!-- h3><?php _e('Stock Quantity', $this->id); ?></h3 -->
            <span class="message-container" data-message-saved="<?php _e('Saved!', $this->id); ?>"
                  data-message-error="<?php _e('Saved!', $this->id); ?>"
                  data-message-wait="<?php _e('Wait..', $this->id); ?>"></span>
            <?php
            foreach ($warehouse_lists as $values) {
                $data = '';
                $values = trim($values, ' ');
                $vals = str_replace(' ', '_', $values);
                $data = $this->getWareHouseData($vals, $product_id);
                ?>
                    <p class="form-field" style="margin: 0; padding: 0">
                        <label style="width: 200px"><?php echo ucwords($values) . __(' Qty', $this->id); ?></label>

                        <input type="text" size="5" name="<?php echo $vals; ?>" class="input-warehouses" style="width: 40%"
                               value="<?php echo isset($data) ? $data : ''; ?>"
                               placeholder="<?php _e('Enter Qty.', $this->id); ?>"
                               data-product="<?php echo isset($product_id) ? $product_id : 0; ?>"
                               data-parent="<?php echo isset($parent_id) ? $parent_id : 0; ?>"/>
                        <span style="padding-left: 5px;" class="loading-qty-status"></span>
                    </p>
                    <span id="<?php echo $vals; ?>" class="warehouse_value_error"></span>

            <?php }
        }
    }

    /**
     * @warehouse-function
     * display all warehouses
     * @param string $vals
     * @param int $productId
     * @return string
     */
    public function getWareHouseData($vals = '', $productId = 0)
    {
        $output = '';
        if (!empty($vals) AND !empty($productId)) {
            global $wpdb;
            $table_name = $this->get_table_woocommerce_product();

            $sql = "SELECT * FROM $table_name WHERE `product_id`= %d AND `meta_key`= %s";
            $args = array($productId,$vals);
            $result = $wpdb->get_results($wpdb->prepare($sql,$args));

            foreach ($result as $data) {
                $output = $data->meta_value;
            }

        }
        return $output;

    }

    /**
     * ajax for front end
     * @param $condition
     */
    public function warehouse_action_callback($condition)
    {

        switch ($condition) {

            case "productPopUp":

                $this->rapidStockManager->warehouseTransfer->product_popUp();

                break;

            case "transfer_simple_product_quantity":

                $variation = isset($_REQUEST['variation']) ? $_REQUEST['variation'] : '';
                $type = isset($_REQUEST['type']) ? $_REQUEST['type'] : '';
                $warehouse = isset($_REQUEST['warehouse']) ? $_REQUEST['warehouse'] : '';
                $sort = isset($_REQUEST['sort']) ? $_REQUEST['sort'] : '';
                $per_page_records = isset($_REQUEST['records_per_pages']) ? (int)$_REQUEST['records_per_pages'] : 0;
                $pagenumber = isset($_REQUEST['pagenumber']) ? (int)$_REQUEST['pagenumber'] : 1;
                $search = isset($_REQUEST['search']) ? $_REQUEST['search'] : '';

                if ($type == 'variant') {
                    $output = $this->transfer_variant_product_quantity($warehouse, $sort, $per_page_records, $pagenumber, $search, $variation);
                } else {
                    $output = $this->transfer_simple_product_quantity($warehouse, $sort, $per_page_records, $pagenumber, $search);
                }

                echo $output;


                break;

            case "getproductByWarehouse":

                $warehouseFrom = isset($_REQUEST['warehouseFrom']) ? $_REQUEST['warehouseFrom'] : '';
                $warehouseTo = isset($_REQUEST['warehouseTo']) ? $_REQUEST['warehouseTo'] : '';
                $result = $this->get_product_warehouse($warehouseFrom);

                if (!empty($result)) {
                    $output = $this->get_product_warehouse_list($result, $warehouseFrom, $warehouseTo);
                } else {
                    $output = '<p class="error_msg">'.__('No product available of this warehouse.',$this->id).'</p>';
                }

                echo $output;

                break;

            case "searchWarehouseProduct":

                $searchKey = isset($_REQUEST['searchKey']) ? $_REQUEST['searchKey'] : '';
                $warehouse = isset($_REQUEST['warehouse']) ? $_REQUEST['warehouse'] : '';

                $result = $this->rapidStockManager->warehouseTransfer->search_warehouse_product($searchKey, $warehouse);

                if (!empty($result)) {

                    $output = $this->get_product_warehouse_list($result, $warehouse);

                } else {
                    $output = '<p class="error_msg">'.__('No product available.',$this->id).'</p>';
                }

                echo $output;

                break;

            case "productWarehouse":

                global $wpdb;

                $productId = isset($_REQUEST['productId']) ? (int)$_REQUEST['productId'] : '';
                $parentId = isset($_REQUEST['parentId']) ? (int)$_REQUEST['parentId'] : '';

                $table_name = $wpdb->prefix . 'rsm_stock_woocommerce_product_table';
                $user_id = get_current_user_id();

                $qty = isset($_REQUEST['qty']) ? (int)$_REQUEST['qty'] : '';
                $warehouse = isset($_REQUEST['warehouse']) ? $_REQUEST['warehouse'] : '';

                if (!empty($qty) && !empty($warehouse)) {

                    $query = "SELECT * FROM  $table_name WHERE `product_id`= %d AND `meta_key`= %s LIMIT 1";
                    $result = $wpdb->get_results($wpdb->prepare($query, array($productId, $warehouse)));

                    if (count($result) > 0) {
                        $query = "UPDATE $table_name SET `meta_value`= %s, `user_id`= %d, `product_parent_id`= %d WHERE `meta_key`= %s AND `product_id`= %d";

                        $sqlResult = $wpdb->query($wpdb->prepare($query,array($qty,$user_id,$parentId,$warehouse,$productId)));

                        if ($sqlResult) {
                            //updated succesfully
                            echo true;

                            //update set audit when create products
                            $args = array(
                                "action" => 'set',
                                "action_amount" => $qty,
                                "stock_old_value" => $result[0]->meta_value,
                                "stock_new_value" => $qty,
                                "warehouse" => $warehouse
                            );
                            $this->rapidStockManager->audit->update_audit($productId, $args, $this);

                        } else {
                            echo false;
                        }
                    } else {

                        $sqlResult = $wpdb->insert($table_name, array('meta_key' => $warehouse, 'meta_value' => $qty, 'product_id' => $productId, 'user_id' => $user_id, 'product_parent_id' => $parentId));

                        if ($sqlResult) {
                            //inserted successfully
                            echo true;

                            //update set audit when create products
                            $args = array(
                                "action" => 'set',
                                "action_amount" => $qty,
                                "stock_old_value" => $qty,
                                "stock_new_value" => $qty,
                                "warehouse" => $warehouse
                            );
                            $this->rapidStockManager->audit->update_audit($productId, $args, $this);

                        } else {
                            echo false;
                        }

                    }

                }

                break;

            case "getWareHouseProduct":

                $parentId = isset($_REQUEST['parentId']) ? (int)$_REQUEST['parentId'] : 0;
                $productId = isset($_REQUEST['productId']) ? (int)$_REQUEST['productId'] : 0;
                $warehouse = isset($_REQUEST['warehouse']) ? $_REQUEST['warehouse'] : '';

                $output = $this->rapidStockManager->warehouseTransfer->get_warehouse_product($productId, $warehouse, $parentId);

                echo $output;

                break;

            case "changeWareHouseValue":

                $productTransfer = isset($_REQUEST['productTransfer']) ? $_REQUEST['productTransfer'] : 0;
                $uniqueNumber = (date("dmygis")) . (get_current_user_id()); //generate unique id

                foreach ($productTransfer as $product) {

                    $result = $this->change_warehouse_value($product["productId"], $product["warehouseQty"], $product["qty"], $product["warehouseFrom"], $product["warehouseTo"], $product["parentId"]);
                    $remaining = $product["warehouseQty"] - $product["qty"];

                    $productResult[$product["productId"]] = array(
                        "result" => $result,
                        "warehouseFrom" => $product["warehouseFrom"],
                        "remaining" => $remaining
                    );

                    if ($result == 1) {

                        $args = array(
                            "action"          => "transfer_from",
                            'warehouse'       => $product["warehouseFrom"],
                            "action_amount"   => $product["qty"],
                            "stock_old_value" => $product["warehouseQty"],
                            "stock_new_value" => $remaining,
                            "reference_no"    => $uniqueNumber

                        );

                        $this->rapidStockManager->audit->update_audit($product["productId"], $args);

                        $args = array(
                            "action"          => "transfer_to",
                            'warehouse'       => $product["warehouseTo"],
                            "action_amount"   => $product["qty"],
                            "reference_no"    => $uniqueNumber
                        );


                        $this->rapidStockManager->audit->update_audit($product["productId"], $args);
                        

                    }

                }

                echo json_encode(array(
                    "reference_no" => $uniqueNumber,
                    "result" => $productResult
                ));

                break;

            case "transferQuantity":

                $productId = isset($_REQUEST['productId']) ? (int)$_REQUEST['productId'] : 0;
                $newValue = isset($_REQUEST['stock_new_value']) ? (int)$_REQUEST['stock_new_value'] : 0;
                $warehouse = isset($_REQUEST['warehouse']) ? $_REQUEST['warehouse'] : '';
                $action_calculate = isset($_REQUEST['action_calculate']) ? $_REQUEST['action_calculate'] : '';

                $actionAmount = isset($_REQUEST['action_amount']) ? (int)$_REQUEST['action_amount'] : 0;
                $stockOldValue = isset($_REQUEST['stock_old_value']) ? (int)$_REQUEST['stock_old_value'] : 0;

                $output = $this->transfer_quantity($productId, $warehouse, $newValue);

                $returnObject = array(
                    'output' => $output,
                    'quantity_color' => $this->rapidStockManager->get_table_color($newValue),
                );

                $args = array(
                    "action" => $action_calculate,
                    "action_amount" => $actionAmount,
                    "stock_old_value" => $stockOldValue,
                    "stock_new_value" => $newValue,
                    "warehouse" => $warehouse,
                );
                $this->rapidStockManager->audit->update_audit($productId, $args, $this);

                echo json_encode($returnObject);

                break;
        }

    }


    /**
     * @warehouse-action
     * Function to get the warehouse value
     * @param int $productId
     * @param string $warehouse
     * @return int
     */
    public function get_warehouse_value_by_productId($productId = 0, $warehouse = '')
    {
        $warehouse_value = 0;
        global $wpdb;
        $product_table_name = $this->get_table_woocommerce_product();
        if (!empty($productId) AND !empty($warehouse)) {

            $sql = "SELECT * FROM  `$product_table_name` WHERE `meta_key`= %s AND  `product_id`= %d";
            $result = $wpdb->get_results($wpdb->prepare($sql,array($warehouse,$productId)));
            if (!empty($result)) {
                foreach ($result as $data) {
                    $warehouse_value = $data->meta_value;
                }
            }
        }
        return $warehouse_value;

    }


    /**
     * @warehouse-action
     * Function transfer data from one warehouse to another warehouse
     * @param int $productId
     * @param string $left
     * @param string $qty
     * @param string $from
     * @param string $to
     * @param int $parentId
     * @return int
     */
    public function change_warehouse_value($productId = 0, $left = '', $qty = '', $from = '', $to = '', $parentId = 0)
    {
        $query = 0;
        global $wpdb;
        $table_name = $wpdb->prefix . 'postmeta';
        $product = wc_get_product($productId);
        $product_stock = $product->get_stock_quantity();

        if ($from == 'main_stock') {
            $product_stocks = $product_stock - $qty;
            /* Update the woocommerce product stock in `postmeta table` */
            $this->update_woocommerce_product_stock($table_name, $product_stocks, $productId);
            /* check the warehouse exist or not in `rsm_stock_woocommerce_product_table` table  */
            $warehouse_to_exist = $this->check_warehouse_exist($productId, $to);
            if (!empty($warehouse_to_exist)) {
                /*  Update the warehouse value  */
                $query = $this->update_warehouse_to_value($productId, $to, $qty);
            } else {
                /*  Insert the warehouse value  */
                $query = $this->add_warehouse_to_value($productId, $to, $qty, $parentId);
            }
        } else {
            $warehouse_from_value = $left - $qty;

            if ($to == 'main_stock') {
                /* check the woocommerce product stock value  */
                $result = $this->check_woocommerce_product_stock_exists($productId, $table_name);
                if ($result > 0) {

                    $product_stock = $product->get_stock_quantity();// Get the stock value of the woocmmerce product
                    $product_stock = $product_stock + $qty;
                    /* Update the woocommerce  */
                    $this->update_woocommerce_product_stock($table_name, $product_stock, $productId);

                } else {
                    $product_type = $product->get_type();
                    $sql = "UPDATE $table_name SET `meta_value`='yes' WHERE `meta_key`= '_manage_stock' AND `post_id`= %d";

                    $wpdb->query($wpdb->prepare($sql,array($productId)));

                    //todo test if variants are working for this
                    if ($product_type == 'simple') {
                        $this->update_woocommerce_product_stock($table_name, $qty, $productId);
                    } else {
                        $wpdb->insert($table_name, array('meta_key' => '_stock', 'meta_value' => $qty, 'post_id' => $productId));
                    }
                }
                /* Update warehouse data */
                $update_warehouse_from_value = $this->update_warehouse_from_value($from, $warehouse_from_value, $productId);
                $query = 1;

            } else {

                $update_warehouse_from_value = $this->update_warehouse_from_value($from, $warehouse_from_value, $productId);
                $warehouse_to_exist = $this->check_warehouse_exist($productId, $to);
                if (!empty($warehouse_to_exist)) {
                    $query = $this->update_warehouse_to_value($productId, $to, $qty);
                } else {
                    $query = $this->add_warehouse_to_value($productId, $to, $qty, $parentId);
                }
            }

        }

        return $query;
    }

    /**
     * @warehouse-action
     * Decrease bthe value from warehouse table
     * @param string $warehouse_from
     * @param int $value
     * @param int $productId
     * @return int
     */
    public function update_warehouse_from_value($warehouse_from = '', $value = 0, $productId = 0)
    {
        global $wpdb;
        $product_table_name = $this->get_table_woocommerce_product();
        $sql = "UPDATE `$product_table_name` SET `meta_value`=%s WHERE `meta_key`= %s AND `product_id`= %d";
        $query = $wpdb->query($wpdb->prepare($sql, array($value, $warehouse_from, $productId)));

        if ($query) {
            $result = 1;
        } else {
            $result = 0;
        }
        return $result;
    }


    /**
     * @warehouse-action
     * Update woocommerce product stock
     * @param string $table_name
     * @param int $product_stocks
     * @param string $productId
     * @return int
     */
    private function update_woocommerce_product_stock($table_name = '', $product_stocks = 0, $productId = '')
    {
        $output = 0;
        global $wpdb;
        $sql = "UPDATE `$table_name` SET `meta_value`= %s WHERE `meta_key`= '_stock' AND `post_id`= %d";

        $sqlResult = $wpdb->query($wpdb->prepare($sql,array($product_stocks,$productId)));
        if ($sqlResult) {
            $output = 1;
        }
        return $output;
    }


    /**
     * @warehouse-action
     * function to check the warehouse exist or not in `rsm_stock_woocommerce_product_table` table
     * @param int $productId
     * @param string $to
     * @return mixed
     */
    private function check_warehouse_exist($productId = 0, $to = '')
    {
        global $wpdb;
        $output = 0;
        $product_table_name = $this->get_table_woocommerce_product();
        if (!empty($productId) AND !empty($to)) {
            $sql = "SELECT * FROM  `$product_table_name` WHERE `meta_key`= %s AND  `product_id`= %d";
            $result = $wpdb->get_results($wpdb->prepare($sql,array($to,$productId)));
            $output = $wpdb->num_rows;
        }
        return $output;
    }

    /**
     * @warehouse-action
     * check the woocommerce product stock value
     * @param int $productId
     * @param string $table_name
     * @return int
     */
    private function check_woocommerce_product_stock_exists($productId = 0, $table_name = '')
    {
        $output = 0;
        global $wpdb;
        if (!empty($productId)) {
            $sql = "SELECT * FROM `$table_name` WHERE (`meta_key`='_manage_stock' AND `meta_value` = 'yes') AND `post_id`= %d";
            $wpdb->get_results($wpdb->prepare($sql,array($productId)));
            $output = $wpdb->num_rows;
        }
        return $output;

    }


    /**
     * @warehouse-action
     * function add to warehouse value in a `rsm_stock_woocommerce_product_table` table
     * @param int $productId
     * @param string $to
     * @param int $qty
     * @param int $parentId
     * @return int
     */
    private function add_warehouse_to_value($productId = 0, $to = '', $qty = 0, $parentId = 0)
    {
        global $wpdb;
        $product_table_name = $this->get_table_woocommerce_product();
        $user_id = get_current_user_id();
        $query = $wpdb->insert($product_table_name, array('meta_key' => $to, 'meta_value' => $qty, 'product_id' => $productId, 'user_id' => $user_id, 'product_parent_id' => $parentId));
        if ($query) {
            $result = 1;
        } else {
            $result = 0;
        }
        return $result;
    }

    /**
     * @warehouse-action
     * Update the warehouse value from `rsm_stock_woocommerce_product_table` table
     * @param int $productId
     * @param string $to
     * @param int $qty
     * @return int
     */
    protected function update_warehouse_to_value($productId = 0, $to = '', $qty = 0)
    {
        global $wpdb;
        $product_table_name = $this->get_table_woocommerce_product();
        $values = $this->get_warehouse_value_by_productId($productId, $to);
        $values = $values + $qty;

        $sql = "UPDATE `$product_table_name` SET `meta_value`='$values' WHERE `meta_key`= %s AND `product_id`= %d";

        $query = $wpdb->query($wpdb->prepare($sql,array($to,$productId)));
        if ($query) {
            $result = 1;
        } else {
            $result = 0;
        }
        return $result;

    }


    /**
     * @warehouse-action
     * transfer warehouse quantity
     * @param int $productId
     * @param int $warehouse
     * @param int $value
     * @return int
     */
    public function transfer_quantity($productId = 0, $warehouse = 0, $value = 0)
    {
        $output = 0;
        if (!empty($productId) AND !empty($warehouse)) {
            $output = $this->update_warehouse_from_value($warehouse, $value, $productId);
        }
        return $output;

    }

    /**
     * @warehouse-action
     * get product warehouse list
     * @param array $warehouse_lists
     * @param string $warehouse
     * @return string
     */
    function get_product_warehouse_list($warehouse_lists = array(), $warehouse = '')
    {
        $output = '';
        global $product;
        if (!empty($warehouse_lists)) {

            foreach ($warehouse_lists as $productId) {

                $product = wc_get_product($productId);
                $product_sku = $product->get_sku();
                $product_type = $product->product_type;
                $product_title = $product->get_title();
                //$product_stock = $product->get_stock_quantity();

                $price = $product->price;
                $price = sprintf('%0.2f', $price);

                //$product_stock = $product->get_stock_quantity();

                if ($product_type === 'variation') {

                    $product_title = $product->get_formatted_name();
                    $parentId = $product->id;

                } else {
                    $product_title = Ucfirst($product_sku) . '- ' . $product_title . '- ' . $price;
                    $parentId = 0;
                }

                $product_image = $product->get_image();

                $output .= '<tr class="get-warehouse-product-tr" data-product-id="' . $productId . '" data-warehouse="' . $warehouse . '" data-parent-id="' . $parentId . '" >';
                $output .= '<td class="product-image">' . $product_image . '<div><p>' . (!empty($product_sku) ? $product_sku : "N/A") . '</p></div></td>';
                $output .= '<td class="product_name"><b>' . (!empty($product_title) ? ucwords($product_title) : "N/A") . '</b></td>';
                $output .= '<td class="product_type"><b>' . $product_type . '</b></td>';
                $output .= '<td><i class="fa fa-plus-circle" aria-hidden="true"></i></td></tr>';
            }

        }
        return $output;
    }


    /**
     * @warehouse-action
     * Remove Unwanted warehouse data
     * @param int $productId
     * @return int
     */
    public function remove_warehouse_product($productId = 0)
    {
        global $wpdb;
        $output = 0;
        $product_table_name = $this->get_table_woocommerce_product();
        $sql = "DELETE FROM  `$product_table_name` WHERE `product_id`= %d";

        $query = $wpdb->get_results($wpdb->prepare($sql, array($productId)));
        if ($query) {
            $output = 1;
        }
        return $output;
    }

    /**
     * @param string $warehouseVal
     * @param int $type
     * @param string $sorting
     * @param int $per_page_records
     * @param int $pagenumber
     * @return array
     */
    public function get_product_warehouse($warehouseVal = '', $type = 0, $sorting = '', $per_page_records = 0, $pagenumber = 0)
    {

        global $wpdb;
        $output = array();
        $condition = $orderby = $limit = "";
        $warehouse_table_name = $this->get_table_woocommerce_product();
        $product_postmeta_table = $wpdb->prefix . 'postmeta';
        $product_post_table = $wpdb->prefix . 'posts';

        if ($warehouseVal == 'main_stock') {
            $sql = "SELECT `post_id` as `product_id` FROM `$product_postmeta_table` WHERE (`meta_key`= '_manage_stock' AND `meta_value`= 'yes') GROUP BY `post_id`";
            $result = $wpdb->get_results($sql);
        } else {

            if (!empty($type)) {
                $pagenumber = ($pagenumber - 1) * $per_page_records;
                $condition = "AND product.`product_parent_id`= 0 AND post.`post_status`= 'publish'";
                $orderby = $this->rapidStockManager->pagination_with_sorting($sorting);
                $limit = "LIMIT $pagenumber, $per_page_records";
            }

            $sql = "SELECT * FROM  $warehouse_table_name AS product LEFT JOIN `$product_post_table` AS post ON product.product_id = post.ID WHERE product.meta_key = %s $condition Group By product_id $orderby $limit";

            $result = $wpdb->get_results($wpdb->prepare($sql,array($warehouseVal)));

        }

        if (!empty($result)) {
            foreach ($result as $data) {
                $productId = $data->product_id;
                $status = get_post_status($productId);
                $product = wc_get_product($productId);
                $product_type = $product->product_type;

                $parent_ids = wp_get_post_parent_id($productId);

                if ($status === 'publish') {
                    if (($product_type === 'variation') || ($product_type === 'variable')) {
                        if (!empty($parent_ids)) {
                            if ($warehouseVal == 'main_stock') {
                                $output[] = $productId;
                            } else {
                                $parent_id = $this->get_product_parentId($warehouseVal, $productId);
                                if (!empty($parent_id)) {
                                    $output[] = $productId;
                                } else {
                                    $this->remove_warehouse_product($productId);
                                }
                            }
                        }
                    } else {
                        $output[] = $productId;
                    }
                }
            }
        }
        return $output;
    }

    /**
     * warehouse transfer for single products
     * @param string $warehouse
     * @param string $sorting
     * @param int $per_page_records
     * @param int $pagenumber
     * @param string $search
     * @return string
     */
    public function transfer_simple_product_quantity($warehouse = '', $sorting = '', $per_page_records = 0, $pagenumber = 0, $search = '')
    {
        $output = '';
        if (empty($warehouse)) {
            echo '<script type="text/javascript">window.location.reload();</script>';
            return;
        }

        if (!empty($search)) {
            $productId = $this->get_product_warehouse_bysearch($warehouse, $search, $sorting, $per_page_records, $pagenumber);
        } else {
            $productId = $this->get_product_warehouse($warehouse, 1, $sorting, $per_page_records, $pagenumber);
        }

        if (!empty($productId)) {
            echo '<div style="float:right;">';
            echo '<div class="simple_search_products">';
            $output .= $this->searching_entire_products('right', $warehouse, $search);
            echo '</div>';
            echo '<div class="clear"></div>';
            $output .= $this->rapidStockManager->display_sort_by('right', $warehouse, $pagenumber, $sorting, 'simple');
            echo '<div class="clear"></div>';
            echo '</div>';
            echo '<div class="clear"></div>';
            $output .= $this->rapidStockManager->display_pagination('pagination_warehouse_simple', '', $warehouse, $pagenumber);
            $output .= $this->rapidStockManager->render_simple_products_table_html($productId, 1, $warehouse);
            $output .= $this->rapidStockManager->display_pagination('pagination_warehouse_simple', '', $warehouse, $pagenumber);

        } else {

            //no products details render out addtional details
            $output .= '<div style="float:right;">';
            $output .= $this->searching_entire_products('right', $warehouse, $search);
            $output .= '<div class="clear"></div>';
            $output .= '<div class="simple_search_products">';
            $output .= '</div>';
            $output .= '</div>';
            $output .= '<div class="clear"></div>';
            $output .= '<h3>' . __('You have no products to display', $this->id) . '</h3>';
            $post_search = array_key_exists('search', $_POST) ? $_POST['search'] : '';
            if( $post_search && !empty($post_search) ){
                $output .= '<p>Try different search keyword than <strong>'.$post_search.'</strong> or <a href="#" id="simple-search-reset-link">Reset search</a></p>';
            }
        }


        return $output;
    }

    /**
     * @warehouse-action
     * @param string $warehouse
     * @param string $sorting
     * @param string $searchKey
     * @return array
     */
    public function get_product_warehouse_bysearch($warehouse = '', $searchKey = '', $sorting = '', $per_page_records = '', $pagenumber = '')
    {
        global $wpdb;
        $output = array();
        $warehouse_table_name = $this->get_table_woocommerce_product();
        $orderby = $this->rapidStockManager->pagination_with_sorting($sorting);

        $sql = "SELECT post.id as product_id
                FROM {$wpdb->posts} AS post
                INNER JOIN {$wpdb->term_relationships} ON (post.ID = {$wpdb->term_relationships}.object_id)
                INNER JOIN $warehouse_table_name as warehouse ON (post.ID = warehouse.product_id)
                INNER JOIN {$wpdb->postmeta} as postmeta ON (post.id = postmeta.post_id) INNER JOIN {$wpdb->term_taxonomy} ON ({$wpdb->term_relationships}.term_taxonomy_id = {$wpdb->term_taxonomy}.term_taxonomy_id)
                INNER JOIN {$wpdb->terms} ON ({$wpdb->term_taxonomy}.term_id = {$wpdb->terms}.term_id )
                WHERE {$wpdb->terms}.slug = '%s'
                AND warehouse.meta_key = '%s'
                AND 1=1
                AND postmeta.meta_key = '_sku'
                AND (postmeta.meta_key LIKE '%s' OR post.post_title LIKE '%s')
                AND post.post_type = 'product'
                AND post.post_status = 'publish' ";
        $sql .= $orderby;
        if (!empty($pagenumber) && !empty($per_page_records) ) {
            $limit = $this->rapidStockManager->settings['products_to_display'];
            $limit = ($limit == 'all') ? 1000 : $limit;
            $offset = ($pagenumber - 1) * $limit;

            $sql .= $limit = " LIMIT $offset, $limit";
        }

        $sqlPrepared = $wpdb->prepare($sql,array('simple',$warehouse,"%$searchKey%","%$searchKey%"));

        $result = $wpdb->get_results($sqlPrepared);

        if (!empty($result)) {
            foreach ($result as $data) {
                $productId = $data->product_id;
                $status = get_post_status($productId);
                $product = wc_get_product($productId);
                $product_type = $product->product_type;
                if ($status === 'publish') {
                    $output[] = $productId;
                }
            }
        }
        return $output;
    }

    /**
     * @warehouse-action
     * display warehouse list
     * @param string $align
     * @param string $type
     */
    public function display_warehouse_lists($align = '', $type = '', $warehouseSelected = '')
    {

        $warehouse_lists = unserialize(get_option('wc_settings_tab_rapid_sm_report_addd_warehouse'));
        $limit = $this->rapidStockManager->settings['products_to_display'];
        $selector_view = array_key_exists('rapid-selector-view', $_REQUEST) ? $_REQUEST['rapid-selector-view'] : '';
        ?>

        <?php if (!empty($warehouse_lists)) { ?>
        <div class="select-warehouse-container <?php echo $align; ?>">

            <?php _e('Select Warehouse: ', $this->id); ?>
            <select name="select-warehouse" id="select-warehouse" data-page-limit="<?php echo $limit; ?>"
                    data-layout-type="<?php echo $type; ?>">
                <?php if ($selector_view == 'stock_report') { ?>
                <option value="all"  <?php if ($warehouseSelected == "all") { ?> selected <?php } ?> ><?php _e('All', $this->id);?></option>
                <?php } ?>
                <option value="0"  <?php if ($warehouseSelected == "0") { ?> selected <?php } ?> ><?php _e('Main Warehouse', $this->id); ?></option>
                <?php if (!empty($warehouse_lists)) {
                    foreach ($warehouse_lists as $warehouse) {
                        $warehouse = trim($warehouse, ' ');
                        $vals = str_replace(' ', '_', $warehouse);
                        ?>
                        <option
                            value="<?php echo $vals; ?>" <?php if ($warehouseSelected == $vals) { ?> selected <?php } ?> >
                            <?php echo ucwords($warehouse); ?></option>
                    <?php }
                } ?>
            </select>

        </div>
    <?php } ?>

        <?php
    }

    /**
     * @param string $warehouse
     * @param string $sort
     * @param int $per_page_records
     * @param int $pagenumber
     * @param string $search
     * @param string $variation
     */
    public function transfer_variant_product_quantity($warehouse= '', $sort= '', $per_page_records= 0, $pagenumber= 1, $search= '', $variation= ''){

        if (empty($warehouse)) {
            echo '<script type="text/javascript">window.location.reload();</script>';
            return;
        }

        $where_attribute_in= explode("|", $variation);
        $per_page_records = ($per_page_records == 'all') ? 1000 : $per_page_records;
        $orderby= $this->rapidStockManager->pagination_with_variation_sorting($sort);
        $main_product_ids = $this->rapidStockManager->get_main_product_ids_with_variations(0, 1000, $where_attribute_in, $search, $orderby);

        if(!empty($main_product_ids) || !empty($warehouse)){

            $sql_total= $this->get_variation_products($main_product_ids, $warehouse, $search, $sort, $per_page_records, $pagenumber, '');
            $output= $this->get_variation_products($main_product_ids, $warehouse, $search, $sort, $per_page_records,$pagenumber, 1);

            if(!empty($output)){
                echo '<div style="float: right; padding-top: 10px">';
                echo '<div class="search_product_variation_products">';

                $this->rapidStockManager->display_search_entire_variant_products('', $warehouse, $search);

                echo '<div class="clear"></div>';
                $this->rapidStockManager->display_sort_by('right', $warehouse,  $pagenumber, $sort, 'variant');
                echo '<div class="clear"></div>';
                echo '</div>';
                echo '<div class="clear"></div>';
                echo '</div>';
                echo '<div class="clear"></div>';
                $this->rapidStockManager->display_pagination(null, $sql_total, $warehouse, $pagenumber, 1);

                $this->get_variation_product_html($output, $warehouse);
                $this->rapidStockManager->display_pagination(null, $sql_total, $warehouse, $pagenumber, 1);
                echo '</div>';

            } else {
                //todo change langauage
                echo '<div style="padding-top: 10px;"></div>';
                $this->rapidStockManager->display_search_entire_variant_products('', $warehouse, $search);
                echo '<div class="clear"></div>';
                echo '<br>';
                echo '<h3 style="margin-top: 40px;">' . __('You Have No Variation Products to Display', $this->id) . '</h3>';
                if (!empty($search)) {
                    echo '<p>Try different search keyword than <strong>' . $search . '</strong> or <a id="link-reset-search" href="' . admin_url('admin.php?' . $this->rapidStockManager->get_url_admin_params(null, true) . '&search=reset') . '">Reset search</a></p>';
                }
            }

        } else {
            //todo change language
            echo '<div style="padding-top: 10px;"></div>';
            $this->rapidStockManager->display_search_entire_variant_products('', $warehouse, $search);
            echo '<div class="clear"></div>';
            echo '<br>';
            echo '<h3 style="margin-top: 40px;">' . __('You Have No Variation Products to Display', $this->id) . '</h3>';
            if (!empty($search)) {
                echo '<p>Try different search keyword than <strong>' . $search . '</strong> or <a id="link-reset-search" href="' . admin_url('admin.php?' . $this->rapidStockManager->get_url_admin_params(null, true) . '&search=reset') . '">Reset search</a></p>';
            }
        }

    }

    /**
     * @param array $products
     * @param string $warehouse
     */
    function get_variation_product_html( $products= array(), $warehouse= ''){
        ?>
        <table class="widefat attributes-table wp-list-table ui-sortable woocommerce-rapid-stock-manager-table"
               data-table-view="variant" style="width:100%"
               data-text-set="<?php _e('Set Quantity', $this->id); ?>"
               data-text-deduct="<?php _e('Deduct Quantity', $this->id); ?>"
               data-text-adjust="<?php _e('Adjust Quantity', $this->id); ?>"
               data-text-row-set="<?php _e('Set Quantity', $this->id); ?>"
               data-text-row-deduct="<?php _e('Deduct Quantity', $this->id); ?>"
               data-text-row-adjust="<?php _e('Adjust Quantity', $this->id); ?>"
        >

            <thead>
            <tr>
                <th class="column-sku" scope="col"><strong><?php _e('SKU', $this->id) ?></strong></th>
                <th scope="col"><strong><?php _e('Product Name', $this->id) ?></strong></th>
                <th scope="col"><strong><?php _e('Variation', $this->id) ?></strong></th>
                <th scope="col"><strong><?php _e('Photo', $this->id) ?></strong></th>
                <th scope="col"><strong><?php _e('Manage Stock', $this->id) ?></strong></th>
                <th scope="col"><strong><?php _e('Stock Status', $this->id) ?></strong></th>
                <th scope="col"><strong><?php _e('Back Orders', $this->id) ?></strong></th>
                <th scope="col"><strong><?php _e('Update', $this->id) ?></strong></th>
                <th class="column-total" scope="col"><strong><?php _e('Total', $this->id) ?></strong></th>
                <th class="column-input" scope="col"><strong><?php _e('Update ', $this->id) ?></strong>
                    <i class="fa fa-question-circle tooltip-qtip" data-qtip-title="Update Action"
                       data-qtip-content="<?php _e('Adjust will allow to subtract or add to the existing quantity. Set will set the current value entered.', $this->id); ?>"></i>
                </th>
                <th scope="col"><strong><?php _e('Action', $this->id) ?></strong>
                    <i class="fa fa-question-circle tooltip-qtip" data-qtip-title="Action"
                       data-qtip-content="<?php _e('Edit main product | Second item will update quantity', $this->id); ?>"></i>
                </th>
            </tr>
            </thead>

            <tbody id="fbody">
            <?php
            foreach ($products as $product) {
                $productId= $product;
                $product = wc_get_product($productId);
                $row = $this->rapidStockManager->get_variation_row_details($product);
                $sku= !empty($row['sku'])?$row['sku']:'N/A';
                $title= $row['title'];
                $variation= $row['variant'];
                $image= $product->get_image();
                $original_variant_qty= $this->get_warehouse_value_by_productId($productId, $warehouse);
                $text_color = $this->settings["no_stock_highlight_text_color"];
                $text_color_style = '';
                if ($original_variant_qty < 1) {
                    $text_color_style = 'color:' . $text_color;
                    $stock_status= 'Out of stock';
                    $stock_status_color= 'red';
                    $cell_color= '#f5d2d0';
                }else{
                    $stock_status= 'In stock';
                    $stock_status_color= 'green';
                    $cell_color= '#fff';
                }

                ?>
                <tr>
                    <td class="sku-cell"><?php echo $sku; ?></td>
                    <td class="title-cell product"><?php echo $title; ?></td>
                    <td class="variation-cell product-variation"><?php echo $variation; ?></td>
                    <td class="image"><?php echo $product->get_image(); ?></td>

                    <?php $manage_stock_color = ($product->managing_stock() == true) ? 'green' : 'red'; ?>
                    <td class="manage-stock <?php echo $manage_stock_color; ?>">
                        <?php echo ($product->managing_stock() == true) ? _e('Yes', $this->id) : _e('No', $this->id); ?>
                    </td>

                    <td class="stock-status <?php echo $stock_status_color; ?>" id="stock-status<?= $productId;?>">
                        <?php echo $stock_status;?>
                    </td>
                    <td class="back-orders">
                        <?php echo ($product->is_on_backorder() == true) ? _e('yes', $this->id) : _e('No', $this->id); ?>
                    </td>
                    <td class="input-cell" data-update-input="true"
                        data-original-qty="<?php echo $original_variant_qty; ?>">
                        <div class="cell-highlighter"></div>
                        <div class="cell-content">
                            <label class="nowrap"><i
                                    class="fa fa-chevron-right input-indicator"></i>&nbsp;<input
                                    type="number" class="wc-qty-change" data-simple-qty="true"
                                    value="" id="update-value<?= $productId;?>"/></label>
                        </div>
                    </td>
                    <td data-simple-total="true"
                        style="background-color:<?php echo $cell_color . ';' . $text_color_style; ?>">
                        <strong id="total-quantity<?= $productId;?>"><?php echo $original_variant_qty; ?></strong></td>
                    <td>
                        <?php $this->rapidStockManager->display_select_action($productId); ?>
                    </td>
                    <td class="total action-cell">
                        <p>
                            <a href="<?php echo admin_url('post.php?post=' . $product->id . '&action=edit'); ?>">
                                <i class="fa fa-pencil-square-o"></i>
                                <?php echo _e('Edit Product', $this->id); ?>
                            </a>
                        </p>

                        <a href="javascript:;"
                           class="<?php if(!empty($warehouse)){ echo "warehouse ";}?>variant-quantity-update action-adjust action-set allow action-link"
                           data-adjust-simple-quantity="<?= $productId; ?>"
                           data-warehouse="<?php echo $warehouse; ?>"
                           style="cursor:pointer;">
                            <?php
                            $update_action = $this->rapidStockManager->settings['update_action'];
                            ?>

                            <i class="loading fa fa-spinner"></i>
                            <i class="loading fa fa-check"></i>
                            <i class="spinner-override fa fa-bolt <?php if ($update_action == 'adjust' || $update_action == 'deduct') {
                                echo 'display-none';
                            } ?>"></i>
                            <i class="spinner-override fa fa-arrows-v <?php if ($update_action== 'set' || $update_action == 'deduct') {
                                echo 'display-none';
                            } ?>"></i>
                            <i class="spinner-override fa fa-long-arrow-down <?php if ($update_action == 'adjust' || $update_action == 'set') {
                                echo 'display-none';
                            } ?>"></i>

											<span class="text" id="action-btn<?= $productId;?>">
											<?php
                                            if ($update_action == 'adjust') {
                                                _e('Adjust Quantity', $this->id);
                                            } else if ($update_action == 'deduct') {
                                                _e('Deduct Quantity', $this->id);
                                            } else {
                                                _e('Set Quantity', $this->id);
                                            }
                                            ?>
											</span>
                        </a>
                    </td>
                </tr>
                <?php
            }
            ?>
            </tbody>
        </table>
        <?php
    }


    /**
     * @warehouse-action
     * @param array $main_product_ids
     * @param string $warehouse
     * @param string $search
     * @param string $sort
     * @param int $per_page_records
     * @param int $pagenumber
     * @param string $limit
     * @return array|int
     */
    function get_variation_products($main_product_ids= array(), $warehouse= '', $search= '', $sort= '', $per_page_records= 0, $pagenumber= 0, $limit= ''){
        global $wpdb;
        $output= array();
        $sql_condition= '';

        $orderby= $this->rapidStockManager->pagination_with_sorting($sort);
        $productIds= implode(',', $main_product_ids);
        $warehouse_table_name= $this->get_table_woocommerce_product();
        $product_postmeta_table= $wpdb->prefix.'postmeta';
        $product_post_table= $wpdb->prefix.'posts';


        $per_page_records = ($per_page_records == 'all') ? 1000 : $per_page_records;
        $start_record= ($pagenumber -1) * $per_page_records;
        if(!empty($limit)){
            $limit= "LIMIT $start_record, $per_page_records";
        }else{
            $limit= "";
        }

        if(!empty($search)){
            $sql_condition= "AND ((postmeta.meta_key= '_sku' AND postmeta.meta_value LIKE '%%%s%%') OR (post.post_title LIKE '%%%s%%' ))";
            $sql_condition = $wpdb->prepare($sql_condition, array($wpdb->esc_like($search),$wpdb->esc_like($search)));
        }

        $sql= "SELECT * FROM `$warehouse_table_name` product 
                LEFT JOIN `$product_postmeta_table` postmeta ON product.product_id= postmeta.post_id 
                LEFT JOIN `$product_post_table` post ON product.product_id= post.ID 
                WHERE product.product_parent_id IN ($productIds) 
                AND product.meta_key= '$warehouse' $sql_condition
                GROUP BY product.product_id $orderby $limit";

        $result= $wpdb->get_results($sql);
        if(empty($limit)){
            $output= $wpdb->num_rows;
        }else{

            if($wpdb->num_rows > 0){
                foreach($result as $data){
                    $productid= $data->product_id;
                    $output[]= 	$productid;
                }
            }
        }
        return $output;
    }


    /**
     * @param string $warehouse
     * @param string $sort
     * @param int $per_page_records
     * @param int $pagenumber
     * @param string $search
     * @param string $variation
     * @return array
     */
    function get_variation_product_ids($warehouse= '', $sort= '', $per_page_records= 0, $pagenumber= 1, $search= '', $variation= ''){
        global $wpdb;
        $output= array();
        $warehouse_table_name= $this->get_table_woocommerce_product();
        $product_postmeta_table= $wpdb->prefix.'postmeta';
        $product_post_table= $wpdb->prefix.'posts';
        $where_attribute_in= explode("|", $variation);
        $per_page_records = ($per_page_records == 'all') ? 1000 : $per_page_records;
        $start_record= ($pagenumber -1) * $per_page_records;
        $orderby= $this->pagination_with_variation_sorting($sort);
        $main_product_ids = $this->get_main_product_ids_with_variations($start_record, $per_page_records, $where_attribute_in, $search, $orderby);
        if(!empty($main_product_ids)){
            $productIds= implode(',', $main_product_ids);
            $sql_joins= $sql_condition= '';
            if(!empty($search)){
                $sql_condition= "AND ((postmeta.meta_key= '_sku' AND postmeta.meta_value LIKE '%%%s%%') OR (post.post_title LIKE '%%%s%%' ))";
                $sql_condition = $wpdb->prepare($sql_condition, array($wpdb->esc_like($search),$wpdb->esc_like($search)));
            }
            $limit= "LIMIT $start_record, $per_page_records";
            $orderby= $this->pagination_with_sorting($sort);
            $sql= "SELECT * FROM `$warehouse_table_name` product 
                    LEFT JOIN `$product_postmeta_table` postmeta ON product.product_id= postmeta.post_id 
                    LEFT JOIN `$product_post_table` post ON product.product_id= post.ID 
                    WHERE product.product_parent_id IN ($productIds) 
                    AND product.meta_key= '$warehouse' $sql_condition GROUP BY product.product_id $orderby $limit";
            $result= $wpdb->get_results($sql);
            if(!empty($result)){
                foreach($result as $data){
                    $productid= $data->product_id;
                    $output[]= 	$productid;
                }
            }
        }
        return $output;
    }

    /**
     * Get the parent id of variation product
     * @param string $warehouse
     * @param int $productId
     * @return int
     */
    public function get_product_parentId($warehouse= '', $productId= 0){
        $parent_id= 0;
        global $wpdb;
        $product_table_name = $this->get_table_woocommerce_product();
        $sql = "SELECT * FROM  `$product_table_name` WHERE `meta_key`= %s AND `product_id`= %d";

        $query = $wpdb->get_results($wpdb->prepare($sql,array($warehouse,$productId)));
        foreach($query as $queries){
            $parent_id= $queries->product_parent_id;
        }
        return $parent_id;

    }

    /**
     * searching
     * @param string $align
     * @param string $warehouse
     * @param string $search
     */
    public function searching_entire_products($align = 'right', $warehouse= '', $search= ''){
        $limit = $this->rapidStockManager->settings['products_to_display'];
        ?>
        <div class="search-entire-container <?php echo $align; ?>">
            <form method="post" id="form-<?= $warehouse;?>">
                <?php _e('Search this set: ', $this->id); ?>
                <input type="search" value="<?= $search;?>" placeholder="<?php _e('e.g. SKU or title', $this->id); ?>" id="search-value" name="search-value">
                <input type="button" data-warehouse="<?php echo $warehouse; ?>" data-type="simple" value="Search" class="<?php if ($warehouse){ echo 'warehouse';} ?> simple-products search-products btn-search-entire-products wp-core-ui button">

                <div class="<?php if ($warehouse) { echo 'warehouse '; }?> search-reset simple_searching_reset" id="ware_<?= $warehouse;?>" style="display:none;"  >
                    <input type="button" value="Reset" class="wp-core-ui button" data-records-per-page="<?= $limit ?>" data-type="simple">
                </div>
            </form>
        </div>
        <?php


    }

}