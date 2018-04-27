<?php
/*
Plugin Name: Woocommerce Rapid Stock Manager
Plugin URI: http://www.ishouty.com/?from=RSM&plugins
Description: Rapid stock manager allows to update your stock inventory and variants sizes very quickly, displaying everything within one screen. Automatically updating without reloading the page, so your stock inventory gets updated automatically. Great friendly interface to allow you to access and update your simple or variant stock with ease. By activating of this plugin, you agree with our terms and conditions. Please read out readme.txt to accept terms and conditions.
Version: 2.0.2
Author: ishouty
Author URI: http://www.ishouty.com/?from=RSM&plugins
Copyright: ishouty
Tags: stock management, stock, inventory, ajax, bulk stock, variant stock, simple products, warehouses
Requires at least: 3.8.5
Tested up to: 4.6.1
WC requires at least: 2.0
WC tested up to: 2.6.4

License: GPLv2 or later License
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Text Domain: Woocommerce Rapid Stock Manager
Requires at least: woocommerce 2.0

Woocommerce Rapid Stock Manager WordPress
Copyright (C) 2008-2017, ishouty.com


@package   WooCommerce_rapid_stock_manager
@author    ishouty
@category  Plugin
@copyright Copyright (c) 2011-2017, ishouty ltd.
@license   http://www.gnu.org/licenses/gpl-2.0.html

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    return;
}
if (class_exists('woocommerce_rapid_stock_manager')) {
    return;
}
require_once(dirname(__FILE__) . '/core_rapid_stock_manager.php');

class woocommerce_rapid_stock_manager extends core_rapid_stock_manager
{
    public $url = array(
        'param' => array(
            'plugin_settings' => 'settings_rapid_stock_manager',
            'plugin_homepage' => 'update_stock_rapid'
        )
    );
    private $cache_total_products_found = null;
    public $admin_url = 'admin.php?page=update_stock_rapid';
    public $plugin_homepage = '';
    public $settings_id_prefix = 'wc_settings_tab_rapid_sm_';
    public $settings;
    public $default_settings = array(
        "no_stock_highlight_color" => '#da2820',
        "no_stock_highlight_text_color" => '#ffffff',
        "row_indicator_highlight_color" => '#df056f',
        "column_indicator_highlight_color" => '#df056f',
        "search_filter_highlight_color" => '#ffffcc',
        "no_stock_highlight_threshold" => 10,
        "report_column_delimiter" => 'tab',
        "products_to_display" => 20,
        "update_action" => 'adjust',
        "products_view" => 'variation_product',
        "products_order" => 'order_by_post_name_asc',
        "color_step" => 0,
        "theme" => 'theme-dark',
        "report_products_to_display" => 1000,
        "default_menu_position" => 'woocommerce',
        "report_edit_warehouse" => null,
        "report_add_warehouse" => null,
        "report_enable_delimiter" => null,
    );

    public $sql_product_queries = array(
        "simple_main" => "",
        "simple_main_search" => "",
        "variant_main" => "
					ON post.ID = postmeta.post_id
					WHERE post_type= %s
					AND post_status = %s
					AND postmeta.meta_key = %s
					AND post.post_type = %s
					AND postmeta.meta_value LIKE %s
					"
    );

    public function __construct()
    {
        parent::__construct();

        $this->settings = $this->default_settings;
        $this->plugin_homepage = admin_url('admin.php?page=' . $this->url['param']['plugin_homepage']);
        $this->settings = $this->get_settings_from_wc_admin();
        $this->settings["color_step"] = $this->get_color_step($this->settings["no_stock_highlight_threshold"]);

        global $wpdb;
        $this->sql_product_queries['simple_main'] = "
					 INNER JOIN {$wpdb->term_relationships} ON (post.ID = {$wpdb->term_relationships}.object_id)
					 INNER JOIN {$wpdb->term_taxonomy} ON ({$wpdb->term_relationships}.term_taxonomy_id = {$wpdb->term_taxonomy}.term_taxonomy_id)
					 INNER JOIN {$wpdb->terms} ON ({$wpdb->term_taxonomy}.term_id = {$wpdb->terms}.term_id )
					 WHERE {$wpdb->terms}.slug = '%s'
					 AND post.post_type = 'product'
					 AND post.post_status = 'publish'
					";
        $this->sql_product_queries['simple_main_search'] = "
					INNER JOIN {$wpdb->term_relationships} ON (post.ID = {$wpdb->term_relationships}.object_id)
					INNER JOIN {$wpdb->postmeta} as postmeta ON (post.id = postmeta.post_id)
					INNER JOIN {$wpdb->term_taxonomy} ON ({$wpdb->term_relationships}.term_taxonomy_id = {$wpdb->term_taxonomy}.term_taxonomy_id)
					INNER JOIN {$wpdb->terms} ON ({$wpdb->term_taxonomy}.term_id = {$wpdb->terms}.term_id )
					WHERE {$wpdb->terms}.slug = '%s'
					AND 1=1
					AND postmeta.meta_key = '_sku'
					AND (postmeta.meta_value LIKE %s OR post.post_title LIKE %s)
					AND post.post_type = 'product'
					AND post.post_status = 'publish'
					";
        
        //warehouse changes, filters and actions
        add_action('woocommerce_process_product_meta', array($this, 'process_product_meta_custom_tab', 10, 2));
        add_filter('woocommerce_product_tabs', array($this, 'woocommerce_product_custom_tab'));
        add_action('woocommerce_product_write_panels', array($this->warehouse, 'custom_tab_options_spec'));
        add_action('woocommerce_product_write_panel_tabs', array($this, 'custom_tab_options_tab_spec'));

        // settings admin hooks for general settings
        add_action('admin_menu', array($this, 'register_admin_menu'));

        //load script
        add_action('admin_init', array($this, 'my_plugin_admin_init'));
        add_action('wp_ajax_update_quantity', array($this, 'my_action_callback_update_quantity'));

        // settings admin hooks for general settings
        add_filter('woocommerce_settings_tabs_array', array($this, 'add_settings_tab'), 100);
        add_action('woocommerce_settings_tabs_settings_rapid_stock_manager', array($this, 'settings_tab'));
        add_action('woocommerce_update_options_settings_rapid_stock_manager', array($this, 'update_warehouse_settings'));
        //localisation
        add_action('plugins_loaded', array($this, 'localisation_load_textdomain'));
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'plugin_settings_link'));
        register_activation_hook(__FILE__, array($this, 'plugin_hook_activation'));
        register_deactivation_hook(__FILE__, array($this, 'plugin_hook_deactivation'));
        if( WP_DEBUG ) {
            register_uninstall_hook(__FILE__, 'plugin_hook_uninstall');
        }else{
            register_uninstall_hook(__FILE__, array($this, 'plugin_hook_uninstall'));
        }

        if (isset($_GET["csv"])) {
            $this->audit->get_csv_report_inventory_audit();
        }

        if (isset($_GET["print"])) {
            if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
                add_action('woocommerce_init', array($this, 'action_print_transfer_receipt'), 10);
            }
        }

        $this->audit->check_rsm_version_for_audit();

    }
    
    ///
    
    public function action_print_transfer_receipt()
    {
        $reference_no = array_key_exists('reference_no', $_GET) ? $_GET['reference_no'] : '';
        $this->warehouseTransfer->render_print_transfer_form($reference_no);
    }

    ///GENERAL SETTINGS FUNCTIONS  ////////////////////////////////////////////////////

    public function plugin_settings_link($links)
    {
        $url = admin_url('admin.php?page=wc-settings&tab=' . $this->url['param']['plugin_settings']);
        $settings_link = '<a href="' . $url . '">' . __('Settings', $this->id) . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    public function plugin_hook_activation()
    {

        $this->audit->check_rsm_version_for_audit();
        $this->warehouse->create_product_database_table();

    }

    public function plugin_hook_deactivation()
    {
    }

    public function plugin_hook_uninstall()
    {
        global $wpdb;

        $option_db = $this->get_option_rsm_version();
        delete_option($option_db);

        $table_name = $this->audit->get_table_audit();
        $sql = "DROP TABLE $table_name";
        $wpdb->query($sql);

        $product_table_name = $this->warehouse->get_table_woocommerce_product();
        $sql1 = "DROP TABLE $product_table_name";
        $wpdb->query($sql1);

    }

    private function my_action_adjust_entire_row($options)
    {
        $stock_meta = $options["stock_meta"];
        $stock_status_meta = $options["stock_status_meta"];
        $stock_status_value = $options["stock_status_value"];
        $stock_status = $options["stock_status"];
        
        $post_quantities = array_key_exists('quantities', $_POST) ? $_POST['quantities'] : '';
        if (empty($post_quantities)) {
            echo json_encode(array('status' => false));
            die();
        }

        $rowQuantities = $post_quantities;

        //loop through the amazon store
        $int = 0;
        $returnObject = array();

        foreach ($rowQuantities as $key) {

            if (!empty($key['postId']) || !empty($key['quantity'])) {

                $post_id = $key['postId'];
                $stockQuantityUpdate = $key['quantity'];

                //get the current stock item
                $currentQuantity = get_post_meta($post_id, $stock_meta);
                $currentQuantity = $currentQuantity[0];


                if (metadata_exists('post', $post_id, $stock_meta)) {

                    //if minus number subject
                    if ($stockQuantityUpdate < 0) {
                        //subject the stock number from total
                        $newQuantity = $currentQuantity - abs($stockQuantityUpdate);

                        if (update_post_meta($post_id, $stock_meta, $newQuantity)) {

                            if ($newQuantity <= 0) {
                                $stock_status = 'Out of stock';
                                update_post_meta($post_id, $stock_status_meta, $stock_status_value[1]);
                            } else {
                                update_post_meta($post_id, $stock_status_meta, $stock_status_value[0]);
                            }

                            $args = array(
                                "action" => "adjust",
                                "action_amount" => $stockQuantityUpdate,
                                "stock_old_value" => $currentQuantity,
                                "stock_new_value" => $newQuantity,
                            );

                            $this->audit->update_audit($post_id, $args);

                            $returnObject['product'][$int] = array('stock_quantity' => $newQuantity,
                                'post_id' => $post_id,
                                'quantity_color' => $this->get_table_color($newQuantity),
                                'stock_status' => $stock_status,

                            );

                        }

                    } //if plus number add to stock
                    else if ($stockQuantityUpdate > 0) {
                        $newQuantity = $stockQuantityUpdate + $currentQuantity;
                        if (update_post_meta($post_id, $stock_meta, $newQuantity)) {
                            if ($newQuantity <= 0) {
                                $stock_status = 'Out of stock';
                                update_post_meta($post_id, $stock_status_meta, $stock_status_value[1]);
                            } else {
                                update_post_meta($post_id, $stock_status_meta, $stock_status_value[0]);
                            }

                            $args = array(
                                "action" => "adjust",
                                "action_amount" => $stockQuantityUpdate,
                                "stock_old_value" => $currentQuantity,
                                "stock_new_value" => $newQuantity,
                            );

                            $this->audit->update_audit($post_id, $args);

                            $returnObject['product'][$int] = array('stock_quantity' => $newQuantity,
                                'post_id' => $post_id,
                                'quantity_color' => $this->get_table_color($newQuantity),
                                'stock_status' => $stock_status,
                            );
                        }

                    }

                }

                $int += 1;
            }

        }

        if ($int > 0) {
            $returnObject['status'] = true;
            echo json_encode($returnObject);
        }
    }

    private function my_action_deduct_entire_row($options)
    {
        $stock_meta = $options["stock_meta"];
        $stock_status_meta = $options["stock_status_meta"];
        $stock_status_value = $options["stock_status_value"];
        $post_quantities = array_key_exists('quantities', $_POST) ? $_POST['quantities'] : '';

        if ($post_quantities == '') {
            echo json_encode(array('status' => false));
            die();
        }
        $rowQuantities = $post_quantities;
        //loop through the amazon store
        $int = 0;
        $returnObject = array();
        $stock_status = 'In stock';
        foreach ($rowQuantities as $key) {
            if (empty($key['postId']) && empty($key['quantity'])) {
                continue;
            }
            //TODO: use private function my_action_deduct instead to avoid duplication

            $post_id = $key['postId'];
            $stockQuantityUpdate = $key['quantity'];
            $int += 1;

            if (!metadata_exists('post', $post_id, $stock_meta)) {
                continue;
            }
            // if existed, just update
            $stockQuantityBeforeUpdate = get_post_meta($post_id, $stock_meta, true);

            //deduct old value with new value
            $newDeductedValue = $stockQuantityBeforeUpdate - abs($stockQuantityUpdate);

            if (!update_post_meta($post_id, $stock_meta, $newDeductedValue)) {
                continue;
            }

            if ($stockQuantityUpdate <= 0) {
                $stock_status = 'Out of stock';
                update_post_meta($post_id, $stock_status_meta, $stock_status_value[1]);
            } else {
                update_post_meta($post_id, $stock_status_meta, $stock_status_value[0]);
            }

            $args = array(
                "action" => "deduct",
                "action_amount" => $stockQuantityUpdate,
                "stock_old_value" => $stockQuantityBeforeUpdate,
                "stock_new_value" => $newDeductedValue,
            );

            $this->audit->update_audit($post_id, $args);

            $returnObject['product'][($int - 1)] = array(
                'stock_quantity' => $newDeductedValue,
                'post_id' => $post_id,
                'quantity_color' => $this->get_table_color($newDeductedValue),
                'stock_status' => $stock_status,
            );
        }
        if ($int > 0) {
            $returnObject['status'] = true;
            echo json_encode($returnObject);
        }

        return;
    }

    private function my_action_set_entire_row($options)
    {
        $stock_meta = $options["stock_meta"];
        $stock_status_meta = $options["stock_status_meta"];
        $stock_status_value = $options["stock_status_value"];
        $post_quantities = array_key_exists('quantities', $_POST) ? $_POST['quantities'] : '';

        if ($post_quantities == '') {
            echo json_encode(array('status' => false));
            die();
        }
        $rowQuantities = $post_quantities;
        //loop through the amazon store
        $int = 0;
        $returnObject = array();
        $stock_status = 'In stock';
        foreach ($rowQuantities as $key) {
            if (!empty($key['postId']) || !empty($key['quantity'])) {
                $post_id = $key['postId'];
                $stockQuantityUpdate = $key['quantity'];
                // save post meta:
                if (metadata_exists('post', $post_id, $stock_meta)) {
                    // if existed, just update
                    $stockQuantityBeforeUpdate = get_post_meta($post_id, $stock_meta, true);
                    if (update_post_meta($post_id, $stock_meta, $stockQuantityUpdate)) {

                        if ($stockQuantityUpdate <= 0) {
                            $stock_status = 'Out of stock';
                            update_post_meta($post_id, $stock_status_meta, $stock_status_value[1]);
                        } else {
                            update_post_meta($post_id, $stock_status_meta, $stock_status_value[0]);
                        }

                        $args = array(
                            "action" => "set",
                            "action_amount" => $stockQuantityUpdate,
                            "stock_old_value" => $stockQuantityBeforeUpdate,
                            "stock_new_value" => $stockQuantityUpdate,
                        );

                        $this->audit->update_audit($post_id, $args);

                        $returnObject['product'][$int] = array(
                            'stock_quantity' => $stockQuantityUpdate,
                            'post_id' => $post_id,
                            'quantity_color' => $this->get_table_color($stockQuantityUpdate),
                            'stock_status' => $stock_status,
                        );
                    }
                }
                $int += 1;
            }
        }
        if ($int > 0) {
            $returnObject['status'] = true;
            echo json_encode($returnObject);
        }
    }

    /**
     * @param $options
     */
    private function my_action_adjust($options)
    {
        $stock_meta = $options["stock_meta"];
        $stock_status_meta = $options["stock_status_meta"];
        $stock_status_value = $options["stock_status_value"];
        $stock_status = $options["stock_status"];
        $post_id = array_key_exists('post_id', $_POST) ? $_POST['post_id'] : '';
        $post_stock_quantity = array_key_exists('stock_quantity', $_POST) ? $_POST['stock_quantity'] : '';

        if (empty($post_id) || $post_stock_quantity == '') {
            echo json_encode(array('status' => false));
            die();
        }
        $stockQuantityUpdate = $post_stock_quantity;
        //get the current stock item
        $currentQuantity = get_post_meta($post_id, $stock_meta);
        $currentQuantity = $currentQuantity[0];
        if (metadata_exists('post', $post_id, $stock_meta)) {
            //if minus number subject
            if ($stockQuantityUpdate < 0) {
                //subject the stock number from total
                $newQuantity = $currentQuantity - abs($stockQuantityUpdate);
                if (update_post_meta($post_id, $stock_meta, $newQuantity)) {
                    if ($newQuantity <= 0) {
                        $stock_status = 'Out of stock';
                        update_post_meta($post_id, $stock_status_meta, $stock_status_value[1]);
                    } else {
                        update_post_meta($post_id, $stock_status_meta, $stock_status_value[0]);
                    }
                    $args = array(
                        "action" => "adjust",
                        "action_amount" => $stockQuantityUpdate,
                        "stock_old_value" => $currentQuantity,
                        "stock_new_value" => $newQuantity,
                    );
                    $this->audit->update_audit($post_id, $args);
                    echo json_encode(array('stock_quantity' => $newQuantity,
                        'status' => true,
                        'quantity_color' => $this->get_table_color($newQuantity),
                        'stock_status' => $stock_status,
                    ));
                }

            } //if plus number add to stock
            else if ($stockQuantityUpdate > 0) {
                $newQuantity = $stockQuantityUpdate + $currentQuantity;
                if (update_post_meta($post_id, $stock_meta, $newQuantity)) {

                    if ($stockQuantityUpdate <= 0) {
                        $stock_status = 'Out of stock';
                        update_post_meta($post_id, $stock_status_meta, $stock_status_value[1]);
                    } else {
                        update_post_meta($post_id, $stock_status_meta, $stock_status_value[0]);
                    }
                    $args = array(
                        "action" => "adjust",
                        "action_amount" => $stockQuantityUpdate,
                        "stock_old_value" => $currentQuantity,
                        "stock_new_value" => $newQuantity,
                    );
                    $this->audit->update_audit($post_id, $args);
                    echo json_encode(array('stock_quantity' => $newQuantity,
                        'quantity_color' => $this->get_table_color($newQuantity),
                        'status' => true,
                        'stock_status' => $stock_status,
                    ));
                }
            }
        }
    }

    private function my_action_set($options)
    {
        $stock_meta = $options["stock_meta"];
        $stock_status_meta = $options["stock_status_meta"];
        $stock_status_value = $options["stock_status_value"];
        $stock_status = $options["stock_status"];
        $post_id = array_key_exists('post_id', $_POST) ? $_POST['post_id'] : '';
        $post_stock_quantity = array_key_exists('stock_quantity', $_POST) ? $_POST['stock_quantity'] : '';

        $stockQuantityUpdate = $post_stock_quantity;
        if ($post_stock_quantity == 0 || $post_stock_quantity == "0") {
            $stockQuantityUpdate = 'set_0';
        }
        if (empty($stockQuantityUpdate)) {
            echo json_encode(array('status' => false));
            die();
        }
        $stockQuantityUpdate = ($stockQuantityUpdate == 'set_0') ? 0 : $stockQuantityUpdate;
        // save post meta: comment
        if (metadata_exists('post', $post_id, $stock_meta)) {
            // if existed, just update
            $stockQuantityBeforeUpdate = get_post_meta($post_id, $stock_meta, true);
            if (update_post_meta($post_id, $stock_meta, $stockQuantityUpdate)) {
                if ($stockQuantityUpdate <= 0) {
                    $stock_status = 'Out of stock';
                    update_post_meta($post_id, $stock_status_meta, $stock_status_value[1]);
                } else {
                    update_post_meta($post_id, $stock_status_meta, $stock_status_value[0]);
                }

                echo json_encode(array('status' => true,
                        'stock_quantity' => $stockQuantityUpdate,
                        'quantity_color' => $this->get_table_color($stockQuantityUpdate),
                        'stock_status' => $stock_status)
                );

                $args = array(
                    "action" => "set",
                    "action_amount" => $stockQuantityUpdate,
                    "stock_old_value" => $stockQuantityBeforeUpdate,
                    "stock_new_value" => $stockQuantityUpdate,
                );
                $this->audit->update_audit($post_id, $args);

            } else {
                echo json_encode(array('status' => false));
            }
        }
    }

    private function my_action_deduct($options)
    {
        $stock_meta = $options["stock_meta"];
        $stock_status_meta = $options["stock_status_meta"];
        $stock_status_value = $options["stock_status_value"];
        $stock_status = $options["stock_status"];
        $post_id = array_key_exists('post_id', $_POST) ? $_POST['post_id'] : '';
        $stockQuantityUpdate = array_key_exists('stock_quantity', $_POST) ? $_POST['stock_quantity'] : '';

        if (empty($stockQuantityUpdate) && ($stockQuantityUpdate != 0 || $stockQuantityUpdate != "0")) {
            echo json_encode(array('status' => false));
            die();
        }

        if (!metadata_exists('post', $post_id, $stock_meta)) {
            echo json_encode(array('status' => false));
            die();
        }

        $stockQuantityBeforeUpdate = get_post_meta($post_id, $stock_meta, true);

        //if deduct by 0, then no need to do anything
        if ($stockQuantityUpdate == 0 || $stockQuantityUpdate == "0") {
            echo json_encode(array('status' => true,
                    'stock_quantity' => $stockQuantityBeforeUpdate)
            );
            die();
        }

        //deduct old value with new value. Use abs to always deduct
        $newDeductedValue = $stockQuantityBeforeUpdate - abs($stockQuantityUpdate);

        if (!update_post_meta($post_id, $stock_meta, $newDeductedValue)) {
            echo json_encode(array('status' => false));
            die();
        }


        if ($newDeductedValue <= 0) {
            $stock_status = 'Out of stock';
            update_post_meta($post_id, $stock_status_meta, $stock_status_value[1]);
        } else {
            update_post_meta($post_id, $stock_status_meta, $stock_status_value[0]);
        }

        echo json_encode(array('status' => true,
                'stock_quantity' => $newDeductedValue,
                'quantity_color' => $this->get_table_color($newDeductedValue),
                'stock_status' => $stock_status)
        );
        $args = array(
            "action" => "deduct",
            "action_amount" => $stockQuantityUpdate,
            "stock_old_value" => $stockQuantityBeforeUpdate,
            "stock_new_value" => $newDeductedValue,
        );
        $this->audit->update_audit($post_id, $args);

        return;
    }//my_action_deduct

    /**
     * ajax update settings
     */
    function my_action_callback_update_quantity()
    {
        $options = array(
            "stock_meta" => '_stock',
            "stock_status_meta" => '_stock_status',
            "stock_status_value" => array('instock', 'outofstock'),
            "stock_status" => 'In stock',
        );
        $post_condition = array_key_exists('condition', $_POST) ? $_POST['condition'] : '';

        switch ($post_condition) {

            //allows to update the current stock without effecting if users buy or update
            case "adjust_entire_row":
                $this->my_action_adjust_entire_row($options);
                break;//adjust_entire_row

            //allows to deduct from entire stock
            case "deduct_entire_row":
                $this->my_action_deduct_entire_row($options);
                break;//deduct_entire_row

            case "set_entire_row":
                $this->my_action_set_entire_row($options);
                break;//set_entire_row

            //allows to update the current stock without effecting if users buy or update
            case "adjust":
                $this->my_action_adjust($options);
                break;//adjust

            //update the current stock version with new quantity number
            case "set":
                $this->my_action_set($options);
                break;//set

            case "deduct":
                //TODO: refactor to pass POST params to function and then json from here, not from the function
                $this->my_action_deduct($options);
                break;//deduct

        }

        $this->warehouse->warehouse_action_callback($post_condition);

        die();
    }

    /**
     * @param $condition
     * @return array|mixed|null|object
     */
    function get_products($condition)
    {
        global $wpdb;

        switch ($condition) {

            case "variation":

                $query_part1 = "SELECT id, post_parent ";
                $query_part2 = "FROM {$wpdb->posts} AS post LEFT JOIN  {$wpdb->postmeta} AS postmeta " . $this->sql_product_queries["variant_main"];
                $query_part3 = "";

                $sort_by = array_key_exists('sort-by', $_REQUEST) ? $_REQUEST['sort-by'] : '';
                if( !$sort_by ){
                    $sort_by = $this->settings["products_order"];
                }

                switch ($sort_by) {
                    case"order_by_post_name_asc":
                        $query_part3 = "ORDER BY post.post_name ASC";
                        break;

                    case"order_by_post_name_dsc":
                        $query_part3 = "ORDER BY post.post_name DESC ";
                        break;
                    case"order_by_post_modified_asc":
                        $query_part3 = "ORDER BY post.post_modified ASC";
                        break;

                    case"order_by_post_modified_dsc":
                        $query_part3 = "ORDER BY post.post_modified DESC ";
                        break;
                    case"order_by_sku_asc":
                        $query_part3 = "ORDER BY post.post_modified ASC";
                        break;

                    case"order_by_sku_dsc":
                        $query_part3 = "ORDER BY post.post_modified DESC ";
                        break;
                }

                $pagination_details = $this->calculate_pagination_limit_page('variation');
                $query = $query_part1 . $query_part2 . $query_part3 . " LIMIT %d , %d";

                $search_value = array_key_exists('search-value', $_REQUEST) ? $_REQUEST['search-value'] : '';
                $search = array_key_exists('search', $_REQUEST) ? $_REQUEST['search'] : '';

                if ($search == 'entire' && !empty($search_value)) {
                    $params = array('product', 'publish', '_sku', 'product', "%$search_value", $pagination_details['offset'], $pagination_details['limit']);

                } else {
                    $params = array('product', 'publish', '_product_attributes', 'product', '%s:12:"is_variation";i:1%', $pagination_details['offset'], $pagination_details['limit']);
                }

                $sql = $wpdb->prepare($query, $params);

                return $wpdb->get_results($sql);

                break;

            default:
            case "pagination_simple":

                $query_part1 = "SELECT id, post_parent ";
                $search_value = array_key_exists('search-value', $_REQUEST) ? $_REQUEST['search-value'] : '';
                $search = array_key_exists('search', $_REQUEST) ? $_REQUEST['search'] : '';
                $sort_by = array_key_exists('sort-by', $_REQUEST) ? $_REQUEST['sort-by'] : '';

                if ( $search == 'entire' && !empty($search_value)) {
                    $query_part2 = "FROM {$wpdb->posts} AS post" . $this->sql_product_queries["simple_main_search"];
                } else {
                    $query_part2 = "FROM {$wpdb->posts} AS post" . $this->sql_product_queries["simple_main"];
                }

                $query_part3 = "";
                switch ($sort_by) {
                    case"order_by_post_name_asc":
                        $query_part3 = "ORDER BY post.post_name ASC";
                        break;

                    case"order_by_post_name_dsc":
                        $query_part3 = "ORDER BY post.post_name DESC ";
                        break;
                    case"order_by_post_modified_asc":
                        $query_part3 = "ORDER BY post.post_modified ASC";
                        break;

                    case"order_by_post_modified_dsc":
                        $query_part3 = "ORDER BY post.post_modified DESC ";
                        break;
                    case"order_by_sku_asc":
                        $query_part3 = "ORDER BY post.post_modified ASC";
                        break;

                    case"order_by_sku_dsc":
                        $query_part3 = "ORDER BY post.post_modified DESC ";
                        break;
                }

                $pagination_details = $this->calculate_pagination_limit_page('pagination_simple');

                $query = $query_part1 . $query_part2 . $query_part3 . " LIMIT %d , %d";

                if ($search == 'entire' && !empty($search_value)) {
                    $params = array("simple", "%$search_value%", "%$search_value%", $pagination_details['offset'], $pagination_details['limit']);

                } else {
                    $params = array("simple", $pagination_details['offset'], $pagination_details['limit']);

                }

                return $wpdb->get_results($wpdb->prepare($query, $params));

                break;

        }

    }

    /**
     * calculate the limit of the page
     * @param $condition {String}
     * @return array
     */
    function calculate_pagination_limit_page($condition = '', $warehouse = '')
    {
        global $wpdb;

        $page_number = isset($_GET['page_number']) ? absint($_GET['page_number']) : 1;
        $limit = $this->settings['products_to_display'];
        $limit = ($limit == 'all') ? 1000 : $limit;

        $offset = ($page_number - 1) * $limit;
        
        $search_value = array_key_exists('search-value', $_REQUEST) ? $_REQUEST['search-value'] : '';
        $search = array_key_exists('search', $_REQUEST) ? $_REQUEST['search'] : '';

        switch ($condition) {

            case "pagination_simple":

                if (($search == 'entire' || !empty($search_value))) {

                    $query = "SELECT COUNT(post.id) as total_count FROM {$wpdb->posts} AS post
						INNER JOIN {$wpdb->term_relationships} ON (post.ID = {$wpdb->term_relationships}.object_id)
						INNER JOIN {$wpdb->postmeta} as postmeta ON (post.id = postmeta.post_id)
						INNER JOIN {$wpdb->term_taxonomy} ON ({$wpdb->term_relationships}.term_taxonomy_id = {$wpdb->term_taxonomy}.term_taxonomy_id)
						INNER JOIN {$wpdb->terms} ON ({$wpdb->term_taxonomy}.term_id = {$wpdb->terms}.term_id )
						WHERE {$wpdb->terms}.slug = '%s'
						AND 1=1 AND postmeta.meta_key = '_sku'
						AND post.post_type = 'product'
						AND post.post_status = 'publish'
						AND (postmeta.meta_value LIKE '%s' OR post.post_title LIKE '%s')
						";

                    $sql = $wpdb->prepare($query, 'simple', "%$search_value%", "%$search_value%");


                    $total_record = $wpdb->get_results($sql);

                } else {

                    $query = "SELECT COUNT(post.id) as total_count FROM {$wpdb->posts} AS post
                          INNER JOIN {$wpdb->term_relationships} ON (post.ID = {$wpdb->term_relationships}.object_id)
                          INNER JOIN {$wpdb->term_taxonomy} ON ({$wpdb->term_relationships}.term_taxonomy_id = {$wpdb->term_taxonomy}.term_taxonomy_id)
                          INNER JOIN {$wpdb->terms} ON ({$wpdb->term_taxonomy}.term_id = {$wpdb->terms}.term_id )
                          WHERE {$wpdb->terms}.slug = '%s'
                          AND post.post_type = 'product'
                          AND post.post_status = 'publish'";

                    $total_record = $wpdb->get_results($wpdb->prepare($query, 'simple'));
                }


                break;
            case "variation":
                $query = "SELECT COUNT('id') as total_count " .
                    "FROM {$wpdb->posts} AS post LEFT JOIN  {$wpdb->postmeta} AS postmeta " .
                    $this->sql_product_queries["variant_main"];

                echo $wpdb->prepare($query,
                    'product', 'publish', '_product_attributes', 'product', '%s:12:"is_variation";i:1%');

                $total_record = $wpdb->get_results($wpdb->prepare($query,
                    'product', 'publish', '_product_attributes', 'product', '%s:12:"is_variation";i:1%'));

                break;
            case "pagination_warehouse_simple":
                $warehouse_table_name = $this->get_table_woocommerce_product();

                if (!empty($search) || !empty($search_value)) {

                    $query = "SELECT COUNT(post.id) as total_count FROM {$wpdb->posts} AS post
						INNER JOIN {$wpdb->term_relationships} ON (post.ID = {$wpdb->term_relationships}.object_id)
						INNER JOIN $warehouse_table_name as warehouse ON (post.ID = warehouse.product_id)
						INNER JOIN {$wpdb->postmeta} as postmeta ON (post.id = postmeta.post_id)
						INNER JOIN {$wpdb->term_taxonomy} ON ({$wpdb->term_relationships}.term_taxonomy_id = {$wpdb->term_taxonomy}.term_taxonomy_id)
						INNER JOIN {$wpdb->terms} ON ({$wpdb->term_taxonomy}.term_id = {$wpdb->terms}.term_id )
						WHERE {$wpdb->terms}.slug = '%s'
						AND warehouse.meta_key = '%s'
						AND 1=1 AND postmeta.meta_key = '_sku'
						AND (postmeta.meta_value LIKE '%s' OR post.post_title LIKE '%s')
						AND post.post_type = 'product'
						AND post.post_status = 'publish'";

                    $sql = $wpdb->prepare($query, 'simple',$warehouse, "%$search_value%", "%$search_value%");

                    $total_record = $wpdb->get_results($sql);
                } else {

                    $query = "SELECT COUNT(post.id) as total_count FROM  {$wpdb->posts} AS post
                          INNER JOIN {$wpdb->term_relationships} ON (post.ID = {$wpdb->term_relationships}.object_id)
                          INNER JOIN $warehouse_table_name as warehouse ON (post.ID = warehouse.product_id)
                          INNER JOIN {$wpdb->term_taxonomy} ON ({$wpdb->term_relationships}.term_taxonomy_id = {$wpdb->term_taxonomy}.term_taxonomy_id)
                          INNER JOIN {$wpdb->terms} ON ({$wpdb->term_taxonomy}.term_id = {$wpdb->terms}.term_id )
                          WHERE {$wpdb->terms}.slug = '%s'
                          AND warehouse.meta_key = '%s'
                          AND post.post_type = 'product'
                          AND post.post_status = 'publish'";

                    $total_record = $wpdb->get_results($wpdb->prepare($query, 'simple', $warehouse ));
                }

                break;

            case "pagination_report_data_table":

                $query = "SELECT COUNT(post.id) as total_count FROM {$wpdb->posts} AS post
                WHERE post.post_type = %s AND post.post_status = %s";

                $limit = $this->settings["report_products_to_display"];

                $total_record = $wpdb->get_results($wpdb->prepare($query, 'product', 'publish'));

            break;

        }

        $total_record = $total_record[0];
        $number_of_pages = ceil($total_record->total_count / $limit);
        return array(
            'limit' => $limit,
            'offset' => $offset,
            'total_record' => $total_record,
            'number_of_pages' => $number_of_pages
        );

    }


    /**
     * render the view for the pagination
     * @param $condition
     * @param null $sql_total
     * @param string $warehouse
     * @param int $pagenumber
     * @param int $type
     */
    public function display_pagination($condition, $sql_total = null, $warehouse = '', $pagenumber = 1, $type = 0)
    {

        if (!empty($condition)) {
            $product_type = 'simple';
        } else {
            $product_type = 'variant';
        }

        $request_page_number = $this->get_request_param('page_number');
        if (!empty($warehouse)) {
            $current_page_number = $pagenumber;
        } else {
            $current_page_number = isset($request_page_number) ? absint($request_page_number) : 1;
        }

        if ($condition && !$sql_total) {
            $pagination_details = $this->calculate_pagination_limit_page($condition, $warehouse);
            $total_records = $pagination_details['total_record']->total_count;

        } else {
            if (!empty($type)) {
                $total_records = $sql_total;
            } else {
                $total_records = $this->get_total_num_of_records($sql_total);
            }
            $limit = $this->settings['products_to_display'];
            $limit = ($limit == 'all') ? 1000 : $limit;

            $offset = ($current_page_number - 1) * $limit;
            $number_of_pages = ceil($total_records / $limit);
            $pagination_details = array(
                'number_of_pages' => $number_of_pages,
                'total_record' => $total_records
            );
        }


        $total_pages = $pagination_details['number_of_pages'];
        $prev_page = $current_page_number - 1;
        $next_page = $current_page_number + 1;

        echo $this->get_html_pagination($total_pages, $current_page_number, $total_records, $prev_page, $next_page, $warehouse, $product_type);
    }

    /**
     * @param $total_pages
     * @param $current_page_number
     * @param $total_records
     * @param $prev_page
     * @param $next_page
     * @return string
     */
    function get_html_pagination($total_pages = 0, $current_page_number = 0, $total_records, $prev_page, $next_page, $warehouse = '', $type = '')
    {

        $search = array_key_exists('search', $_REQUEST) ? $_REQUEST['search'] : '';
        $search_value = array_key_exists('search-value', $_REQUEST) ? $_REQUEST['search-value'] : '';
        $show_per_page_record = $this->settings['products_to_display'];
        if ($total_pages < 1) {
            return '';
        }
        $pagination = '';
        $adjacents = 3;
        $counter = 0;
        $params = $this->get_url_admin_params(true);
        $pagination .= "<div class=\"pagination\">";

        //previous button
        if ($current_page_number > 1) {
            if (!empty($warehouse)) {
                $pagination .= '<a class="warehouse left-arrow" href="javascript:;" style="cursor:pointer;" data-search="'.$search.'" data-search-value="'.$search_value.'" data-warehouse="' . $warehouse . '" data-prev-page="' . $prev_page . '" data-show-per-record="' . $show_per_page_record . '" data-current-page="' . $current_page_number . '" data-counter="' . $counter . '" data-type="' . $type . '">' . __('<<', $this->id) . '</a>';
            } else {
                $pagination .= "<a href=\"?$params&page=update_stock_rapid&page_number=$prev_page&search-value=$search_value\">" . __('<<', $this->id) . "</a> ";
            }
        } elseif ($total_pages > 1) {
            $pagination .= "<span class=\"disabled\">" . __('<<', $this->id) . "</span> ";
        }

        /*   pages Numbers */

        if (($total_pages < 7 + ($adjacents * 2)) && $total_pages > 1) {
            for ($counter = 1; $counter <= $total_pages; $counter++) {

                if ($counter == $current_page_number) {
                    $pagination .= "<span class=\"current\">$counter</span>";
                } else {
                    if (!empty($warehouse)) {
                        $pagination .= '<a class="warehouse counter" href="javascript:;" style="cursor:pointer;" data-search="'.$search.'" data-search-value="'.$search_value.'" data-warehouse="' . $warehouse . '" data-prev-page="' . $prev_page . '" data-next-page="' . $next_page . '" data-show-per-record="' . $show_per_page_record . '" data-current-page="' . $current_page_number . '" data-counter="' . $counter . '" data-type="' . $type . '">' . $counter . '</a>';
                        //$pagination .='<a style="cursor:pointer;" onclick="get_next_records(\'' .$warehouse . '\',' . $counter .', '. $show_per_page_record .', \'' .$type . '\');">' . $counter . '</a>';
                    } else {
                        $pagination .= " <a href=\"?$params&page=update_stock_rapid&page_number=$counter&search-value=$search_value\">$counter</a> ";
                    }
                }

            }
        } elseif ($total_pages > 5 + ($adjacents * 2)) {
            if ($current_page_number < 1 + ($adjacents * 2)) {
                for ($counter = 1; $counter < 4 + ($adjacents * 2); $counter++) {
                    if ($counter == $current_page_number)
                        $pagination .= " <span class=\"current\">$counter</span> ";
                    else
                        $pagination .= " <a href=\"?$params&page=update_stock_rapid&page_number=$counter&search-value=$search_value\">$counter</a> ";
                }
                $pagination .= " ... ";
                /*$pagination .= " jhhj<a href=\"?$params&page=update_stock_rapid&page_number=$total_records\">$total_records</a> ";*/
                $pagination .= "<a href=\"?$params&page=update_stock_rapid&page_number=$total_pages&search-value=$search_value\">$total_pages</a> ";
            } //in middle; hide some front and some back
            elseif ($total_pages - ($adjacents * 2) > $current_page_number && $current_page_number > ($adjacents * 2)) {

                $pagination .= " <a href=\"?$params&page=update_stock_rapid&page_number=1&search-value=$search_value\">1</a> ";
                $pagination .= " <a href=\"?$params&page=update_stock_rapid&page_number=2&search-value=$search_value\">2</a> ";
                $pagination .= " ... ";

                for ($counter = $current_page_number - $adjacents; $counter <= $current_page_number + $adjacents; $counter++) {
                    if ($counter == $current_page_number)
                        $pagination .= " <span class=\"current\">$counter</span> ";
                    else
                        $pagination .= " <a href=\"?$params&page=update_stock_rapid&page_number=$counter&search-value=$search_value\">$counter</a> ";
                }
                $pagination .= " ... ";
                /*$pagination .= " <a href=\"?$params&page=update_stock_rapid&page_number=$total_records\">$total_records</a> ";*/
                $pagination .= " <a href=\"?$params&page=update_stock_rapid&page_number=$total_pages&search-value=$search_value\">$total_pages</a> ";

            } else {
                $pagination .= " <a href=\"?$params&page=update_stock_rapid&page_number=1&search-value=$search_value\">1</a> ";
                $pagination .= " <a href=\"?$params&page=update_stock_rapid&page_number=2&search-value=$search_value\">2</a> ";
                $pagination .= " ... ";
                for ($counter = $total_pages - (2 + ($adjacents * 2)); $counter <= $total_pages; $counter++) {
                    if ($counter == $current_page_number) {

                        $pagination .= " <span class=\"current\">$counter</span> ";

                    } else {

                        $pagination .= " <a href=\"?$params&page=update_stock_rapid&page_number=$counter&search-value=$search_value\">$counter</a> ";

                    }

                }
            }
        }

        if ($current_page_number < $counter - 1) {
            if (!empty($warehouse)) {

                $pagination .= '<a class="warehouse right-arrow ' . $next_page . ' " href="javascript:;" style="cursor:pointer;" data-search="'.$search.'" data-search-value="'.$search_value.'" data-warehouse="' . $warehouse . '" data-prev-page="' . $prev_page . '" data-next-page="' . $next_page . '" data-show-per-record="' . $show_per_page_record . '" data-current-page="' . $current_page_number . '" data-counter="' . $counter . '" data-type="' . $type . '">' . __('>>', $this->id) . '</a>';

            } else {
                $pagination .= " <a class=\"warehouse-main\" href=\"?$params&page=update_stock_rapid&page_number=$next_page&search-value=$search_value\">" . __('>>', $this->id) . "</a>";
            }
        } elseif ($total_pages > 1) {
            $pagination .= " <span class=\"disabled\">" . __('>>', $this->id) . "</span>";
        }

        $pagination .= "<span class=\"total\">" . __('Total', $this->id) . ": $total_records</span>";
        $pagination .= "</div>";
        return $pagination;
    }

    /**
     * get variation details
     * @param $product_id
     * @return array
     */
    function get_variations_details($product_id)
    {

        // Get variations details for post details
        $args = array(
            'post_type' => 'product_variation',
            'post_status' => array('publish'),
            'numberposts' => -1,
            'orderby' => 'menu_order',
            'order' => 'asc',
            'post_parent' => $product_id
        );

        $variation = get_posts($args);

        return $variation;

    }

    /**
     * display select option for updating quantity
     */
    function display_select_action($projectId = 0)
    {

        ?>
        <select class="select-update-action" id="select-update-action<?= $projectId; ?>">
            <option value="adjust" <?php if ($this->settings['update_action'] == 'adjust') {
                echo 'selected';
            } ?> > <?php _e('Adjust', $this->id); ?></option>

            <option value="set" <?php if ($this->settings['update_action'] == 'set') {
                echo 'selected';
            } ?>><?php _e('Set', $this->id); ?></option>

            <option value="deduct" <?php if ($this->settings['update_action'] == 'deduct') {
                echo 'selected';
            } ?>><?php _e('Deduct', $this->id); ?></option>

        </select>
        <?php
    }

    /**
     * display select option for updating quantity
     */
    function get_html_select_action()
    {
        ob_start();
        ?>
        <select class="select-update-action">
            <option value="adjust" <?php if ($this->settings['update_action'] == 'adjust') {
                echo 'selected';
            } ?> > <?php _e('Adjust', $this->id); ?></option>
            <option value="set" <?php if ($this->settings['update_action'] == 'set') {
                echo 'selected';
            } ?>><?php _e('Set', $this->id); ?></option>
            <option value="deduct" <?php if ($this->settings['update_action'] == 'deduct') {
                echo 'selected';
            } ?>><?php _e('Deduct', $this->id); ?></option>
        </select>
        <?php
        return ob_get_clean();
    }


    /**
     * Returns formatted title for all available product attributes
     * @param array $attributes Product attributes
     * @return string Formatted string
     */
    public function get_attribute_for_variation_table($attributes = null)
    {
        global $wpdb;
        if (!$attributes) {
            return 'N/A';
        }
        $attributes_count_total = sizeof($attributes);
        if ($attributes_count_total < 1) {
            return 'N/A';
        }
        $attribute_title = '';
        $attributes_count = 0;
        foreach ($attributes as $attribute_name => $attribute_value) {
            $attributes_count++;

            $attribute_name_clean = str_replace('attribute_', '', str_replace('pa_', '', $attribute_name));
            $attribute_tile_sql = "SELECT attribute_label FROM {$wpdb->prefix}woocommerce_attribute_taxonomies
									WHERE attribute_name = %s LIMIT 1";
            $attribute_title_sql_params = array($attribute_name_clean);
            $attribute_title_sql_result = $wpdb->get_row($wpdb->prepare($attribute_tile_sql, $attribute_title_sql_params));

            $attribute_title .= '<span title="';
            if ($attribute_title_sql_result && property_exists($attribute_title_sql_result, 'attribute_label')) {
                $attribute_title .= $attribute_title_sql_result->attribute_label;
            } else {
                $attribute_title .= $attribute_name_clean;
            }
            $attribute_title .= '">';

            $attribute_value_slug = get_term_by('slug', $attribute_value, str_replace('attribute_', '', $attribute_name));
            if ($attribute_value_slug) {
                $attribute_title .= $attribute_value_slug->name;
            } else {
                $attribute_title .= $attribute_value;
            }
            $attribute_title .= '</span>';

            if ($attributes_count < $attributes_count_total) {
                $attribute_title .= " ";
            }
        }
        return $attribute_title;
    }

    /**
     * Returns one formatted product for report - as an array with attributes
     * @param array $product Product object to transform to an array
     * @return array
     */
    public function get_variation_row_details($product = null)
    {
        //Default format of a row
        $row = array(
            "title" => "N/A",
            "sku" => "N/A",
            "variant" => "N/A",
            "stock" => "N/A",
            "id" => 0,
            "parent_id" => 0,
            "main" => false,
            "continue" => false,
        );
        if (!$product) {
            return $row;
        }
        if (!$product->exists()) {
            return $row;
        }

        $product_sku = $product->get_sku();
        $product_title = $product->get_title();
        $product_total_stock = $product->get_total_stock();
        $product_type = $product->product_type;
        $row["title"] = $product_title;
        $row["sku"] = $product_sku;
        $row["stock"] = $product_total_stock;
        $row["id"] = $product->id;
        $row["variation_id"] = $product->variation_id;

        if ($product_type === 'variation') {
            $attributes = $product->get_variation_attributes();
            $row["variant"] = $this->get_attribute_for_variation_table($attributes);
            return $row;
        }

        if ($product_type !== 'variable') {
            $row["main"] = true;
            return $row;
        }

        if (!$product->has_child()) {
            $row["main"] = true;
            return $row;
        }

        $row["variant"] = __('Main product - total', $this->id);
        $row["continue"] = true;
        $row["main"] = true;
        return $row;
    }

    /**
     * @param $combinations
     * @param $selected
     * @return string
     */
    private function get_html_attributes_combinations_dropdown($combinations, $selected)
    {
        global $wpdb;
        $output = '<select name="attributes" id="product-variation">';
        foreach ($combinations as $combination) {
            $attributes_set = join('|', $combination);
            $combination_labels = array();
            foreach ($combination as $attribute) {
                $attribute = str_replace('attribute_pa_', '', $attribute);
                $attribute_label = $wpdb->get_var("SELECT attribute_label FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name = '{$attribute}' LIMIT 1");
                if (!$attribute_label) {
                    $attribute_label = $attribute;
                }
                array_push($combination_labels, $attribute_label);
            }
            $output .= '<option value="' . $attributes_set . '" ' . ($attributes_set === $selected ? 'selected' : '') . '>' . join(' + ', $combination_labels) . '</option>';
        }
        $output .= '</select>';
        return $output;
    }

    private function get_attributes_combinations()
    {
        global $wpdb;

        $sql = "
			SELECT DISTINCT GROUP_CONCAT( DISTINCT meta_key SEPARATOR  ',' ) combinations
			FROM {$wpdb->postmeta}
			WHERE meta_key LIKE  'attribute_pa_%'
			GROUP BY post_id";

        $results = $wpdb->get_results($sql);

        $combinations = array();
        foreach ($results as $result) {
            $new_combination = explode(',', $result->combinations);
            $new_combination = array_unique($new_combination);
            $different = false;
            foreach ($combinations as $combination) {
                if (sizeof($combination) != sizeof($new_combination)) {
                    continue;
                }
                $array_diff = array_diff($new_combination, $combination);
                if (sizeof($array_diff) < 1) {
                    $different = true;
                }
            }
            if ($different) {
                continue;
            }
            $combinations[] = $new_combination;
        }

        return $combinations;
    }

    /**
     * @param null $products_order
     * @param string $posts_prefix
     * @return string
     */
    private function get_sql_orderby_posts($products_order = null, $posts_prefix = 'posts')
    {
        if (!$products_order) {
            $products_order = $this->settings["products_order"];
        }
        switch ($products_order) {
            case "order_by_post_name_asc":
                return " ORDER BY {$posts_prefix}.post_name ASC";
                break;
            case "order_by_post_name_dsc":
                return " ORDER BY {$posts_prefix}.post_name DESC ";
                break;
            case "order_by_post_modified_asc":
                return " ORDER BY {$posts_prefix}.post_modified ASC";
                break;
            case "order_by_post_modified_dsc":
                return " ORDER BY {$posts_prefix}.post_modified DESC ";
                break;
            case "order_by_sku_asc":
                return " ORDER BY {$posts_prefix}.post_modified ASC";
                break;
            case "order_by_sku_dsc":
                return " ORDER BY {$posts_prefix}.post_modified DESC ";
                break;
            default:
                return " ORDER BY {$posts_prefix}.post_name ASC ";
                break;
        }
    }

    private function get_total_num_of_records($sql)
    {
        global $wpdb;
        if ($this->cache_total_products_found) {
            return $this->cache_total_products_found;
        }
        $results = $wpdb->get_row($sql);
        $this->cache_total_products_found = $results->total;
        return $this->cache_total_products_found;
    }

    private function get_sql_main_product_ids_with_variations($variations = array(), $search = '')
    {
        global $wpdb;

        $where_attribute_in = "'" . join("','", $variations) . "'";
        $count_variations = sizeof($variations);

        $like_clause = 'attribute_pa_%';
        $sql_search = '';

        if (!empty($search) && $search != '') {
            $sql_join = " LEFT JOIN {$wpdb->postmeta}  pms ON (pms.post_id = p.id OR pms.post_id = px.id )AND pms.meta_key = '_sku' ";
            $sql_search = " AND ( (pms.meta_key = '_sku' AND pms.meta_value LIKE %s) OR p.post_title LIKE %s OR px.post_title LIKE %s )";
            $params = array($like_clause, "%$search%", "%$search%", "%$search%");
        } else {
            $sql_join = '';
            $sql_search_inner = '';
            $sql_search_outer = '';
            $params = array($like_clause);
        }

        $sql_body = "
				FROM {$wpdb->posts} p
				RIGHT JOIN {$wpdb->posts} px ON px.id = p.post_parent AND px.post_status =  'publish'
				{$sql_join}
				,
				(
					SELECT pm.post_id FROM {$wpdb->postmeta} pm
					WHERE pm.meta_key LIKE %s
					GROUP BY pm.post_id
					HAVING
						COUNT(pm.meta_key) = {$count_variations} AND
						MAX(pm.meta_key) IN({$where_attribute_in})
				) j
				WHERE j.post_id = p.id
				AND p.post_parent <> 0
				AND p.post_status = 'publish'
				{$sql_search}
		";
        //(SELECT px.id FROM {$wpdb->posts} px WHERE px.post_status = 'publish') parent

        $sql_results = "
			SELECT DISTINCT p.post_parent {$sql_body}
		";

        $sql_total = "
			SELECT count(distinct p.post_parent) total {$sql_body}
		";

        $sql_total = $wpdb->prepare($sql_total, $params);
        $sql_results = $wpdb->prepare($sql_results, $params);

        return array(
            'records' => $sql_results,
            'total' => $sql_total
        );
    }

    /**
     *
     * @param int $start
     * @param int $limit
     * @param array $variations
     * @param string $search
     * @return array
     * get all the products with variation
     */
    public function get_main_product_ids_with_variations($start = 0, $limit = 50, $variations = array(), $search = '', $orderBy = '')
    {
        global $wpdb;
        $sqls = $this->get_sql_main_product_ids_with_variations($variations, $search);
        $sql_results = $sqls["records"];
        $sql_addition = '';
        if (!empty($orderBy)) {
            $sql_orderby = $orderBy;
        } else {
            $sql_orderby = $this->get_sql_orderby_posts(null, 'p');
        }
        $sql_addition .= $sql_orderby;
        $sql_limit = " LIMIT %d , %d";

        $sql_addition = $sql_addition . $wpdb->prepare($sql_limit, $start, $limit);

        $sql = $sql_results . $sql_addition;

        $results = $wpdb->get_results($sql);
        $product_ids = array();
        foreach ($results as $row) {
            array_push($product_ids, $row->post_parent);
        }

        return $product_ids;
    }

    /**
     * @param string $attributes_combination_selected
     * @return string
     */
    private function get_html_variations_form($attributes_combinations = null, $attributes_combination_selected = '')
    {
        if (!$attributes_combinations) {
            $attributes_combinations = $this->get_attributes_combinations();
        }

        if (sizeof($attributes_combinations) < 1) {
            return '<div class="error"><p>' . __('No attributes groups found. Have you set up global attributes and used them for variations? Main menu: Products > Attributes.', $this->id) . '</p></div>';
        }

        if (!$attributes_combination_selected) {
            $attributes_combination_selected = join("|", $attributes_combinations[0]);
        }


        $html = '<form class="form-variation" method="get">';
        $html .= '<b>' . __('Select Variation set: ', $this->id) . ' </b>';
        $html .= $this->get_html_attributes_combinations_dropdown($attributes_combinations, $attributes_combination_selected);

        $html .= '
			<input name="page" value="update_stock_rapid" type="hidden">
			<input name="rapid-selector-view" value="variation_product" type="hidden">
			<button type="submit" class="button">'.__('Display',$this->id).'</button>
			</form>
		';
        return $html;
    }

    /**
     * get variation table view
     * @param array $product_ids
     * @return array
     */
    private function get_variation_view_table($product_ids = array())
    {
        $table = array();
        $main_products_count = 0;
        $variants_count = 0;
        $low_stock_count = 0;
        $low_stock_threshold = $this->settings["no_stock_highlight_threshold"];

        foreach ($product_ids as $product_id) {

            $product = wc_get_product($product_id);
            $row = $this->get_variation_row_details($product);
            array_push($table, $row);
            if ($row["main"]) {
                $main_products_count++;
            }
            if ($row["stock"] <= $low_stock_threshold) {
                $low_stock_count++;
            }
            if (!$row["continue"]) {
                continue;
            }
            $children_products = $product->get_children();
            foreach ($children_products as $children_product) {
                $children_product = wc_get_product($children_product);
                $row = $this->get_variation_row_details($children_product);
                $row["parent_id"] = $product_id;
                array_push($table, $row);
                $variants_count++;
                if ($row["stock"] <= $low_stock_threshold) {
                    $low_stock_count++;
                }
            }
        }

        return array(
            "products" => $table,
            "variants_count" => $variants_count,
            "main_products_count" => $main_products_count,
            "low_stock_count" => $low_stock_count,
        );
    }

    /**
     * Returns formatted string for textarea - grid of products with variants as columns
     * @param array $table_data Data to transfer to rows and columns
     * @param string $delimiter Default is Tab
     * @param string $line_delimiter Line/Row Default is \n
     * @return string
     */
    private function display_variation_grid($table_data = array(), $delimiter = "\t", $line_delimiter = "\n")
    {
        if (sizeof($table_data) < 1) {
            $search_text = '';
            $search_value = $this->get_request_param('search-value');
            if ($search_value) {
                $search_text = __('Try to reset search or search different text.', $this->id);
                $search_text .= ' ' . __('Searching for: ', $this->id) . $search_value;
            }
            return '<div class="error"><p>' . __('No products available to list. #1 ', $this->id) . $search_text . ' 
            To see <a href="'.$this->help->get_help_link('no-variations').'">help</a>, please <a href="'.$this->help->get_help_link('no-variations').'">click here</a>.</p></div>';
        }

        //Transform data array to variant-based array and to parent_id-based array
        $variants_data = array();
        $products_parent_data = array();

        foreach ($table_data as $row) {
            //array based on a key as a variant name
            if (!array_key_exists($row["variant"], $variants_data) || !is_array($variants_data[$row["variant"]])) {
                $variants_data[$row["variant"]] = array();
            }
            array_push($variants_data[$row["variant"]], $row);

            //array based on a key as a parent_id
            if (!array_key_exists($row["parent_id"], $products_parent_data) || !is_array($products_parent_data[$row["parent_id"]])) {
                $products_parent_data[$row["parent_id"]] = array();
            }
            $products_parent_data[$row["parent_id"]][$row["variant"]] = $row;
        }
        //get just headers - available variants
        $variants_headers = array_keys($variants_data);
        $total_header = array_shift($variants_headers);
        sort($variants_headers);
        array_unshift($variants_headers,$total_header);

        //print headers with variant names
        $output = '
		<table class="widefat attributes-table wp-list-table ui-sortable woocommerce-rapid-stock-manager-table"  
		data-table-view="variant" 
		data-text-row-adjust="' . __('Adjust Row', $this->id) . '" 
		data-text-row-deduct="' . __('Deduct Row', $this->id) . '"   
		data-text-row-set="' . __('Set Row', $this->id) . '" 
		data-text-set="' . __('Set', $this->id) . '" 
		data-text-adjust="' . __('Adjust', $this->id) . '" 
		data-text-deduct="' . __('Deduct', $this->id) . '" 
		style="width:100%">
		<thead>
		<tr>
			<th class="column-sku" scope="col"><strong>' . __('SKU', $this->id) . '</strong></th>
			<th scope="col"><strong>' . __('Product Name', $this->id) . '</strong></th>
		';

        foreach ($variants_headers as $header) {
            if ($header == 'Main product - total') {
                continue;
            }
            $output .= '
				<th class="column-input" scope="col">
								<strong>' . $header . '</strong></th>
				';
        }
        $output .= '


			<th class="column-total" scope="col"><strong>' . __('Total', $this->id) . '</strong></th>
			<th scope="col"><strong>' . __('Update', $this->id) . '</strong>
				<i class="fa fa-question-circle tooltip-qtip" data-qtip-title="Update Action" data-qtip-content="' . __('Adjust will allow to subtract or add to the existing quantity so if users are buying it wont effect the quantity <br><br> Set will set the current quantity to the value you have entered.<br><br> Deduct - will deduct the current value with the value you have entered', $this->id) . '"></i>
			</th>
			<th scope="col"><strong>' . __('Action', $this->id) . '</strong>
				<i class="fa fa-question-circle tooltip-qtip" data-qtip-title="Action" data-qtip-content="' . __('Edit main product details <br> Update entire row for the items', $this->id) . '"></i>
			</th>
		</tr>
		</thead>
		<tbody>
		';

        $product_variant = null;
        $variation_product = null;

        //loop through all available products
        foreach ($table_data as $product) {
            //because every row=one main product, we work only with the main products
            if (!$product["main"]) {
                continue;
            }

            $output .= '
				<tr>
				<td class="sku-cell">' . $product["sku"] . '</td>
				<td class="title-cell">' . $product["title"] . '</td>
			';


            //output information about the main product
            //$output .= $product["sku"].$delimiter.$product["title"].$delimiter.$product["stock"].$delimiter;
            //check if the main product has any child products
            if (!array_key_exists($product["id"], $products_parent_data) || !is_array($products_parent_data[$product["id"]])) {
                $output .= $line_delimiter;
                continue;
            }
            $is_first = true;
            //loop through variant names and...
            foreach ($variants_headers as $header) {
                //...find associated products
                $had_variant = false;
                foreach ($variants_data[$header] as $variant) {
                    //check if found product is the product which belongs to the current loop main product
                    if ($variant["parent_id"] != $product["id"]) {
                        continue;
                    }
                    $had_variant = true;
                    if (!$product_variant || $product_variant->variation_id != $variant["variation_id"]) {
                        $product_variant = wc_get_product($variant["variation_id"]);
                    }
                    if (!$variation_product || $variation_product->id != $product["id"]) {
                        $variation_product = wc_get_product($product["id"]);
                    }
                    $output .= $this->get_html_variation_product_table_cell($variation_product, $product_variant, $variant["stock"]);
                    //$output .= '<td>'.$variant["stock"] . '</td>';
                }
                if (!$had_variant && !$is_first) {
                    $output .= '<td>n/a</td>';
                }
                $is_first = false;
            }
            $output .= '
				<td class="calculate-total" data-rapid-calculate-total="true"></td>
				<td>' . $this->get_html_select_action() . '</td>

				' . $this->get_html_variation_action_cell($product["id"]) . '

			</tr>
			';
        }

        $output .= '</tbody></table>';

        return $output;
    }

    /**
     * Prints Stock Report page in textarea
     * @param string $delimiter How data is delimited for Excel. Default is Tab character.
     */
    public function render_report($delimiter = "\t")
    {

        $report_data = $this->get_report_data_table($this->settings["no_stock_highlight_threshold"]);
        $table_data = $report_data["products"];
        
        echo '<h1>'.__('Reports and Audit', $this->id).'</h1>';

        echo '<form action="" method="post">';

        echo '<h2 style="font-size: 1.4em;">' . __('Inventory audit', $this->id) . '</h2>';
        echo '<p>' . __('Time frame and SKU are not required, however the file size may become too large and cause errors.', $this->id) . '</p>';

        if ($this->warehouse->warehouse_enabled()) {
            $this->warehouse->display_warehouse_lists('', '', (isset($_POST["select-warehouse"])?$_POST["select-warehouse"]:''));
            $this->audit->display_action_lists((isset($_POST["select-action-list"])?$_POST["select-action-list"]:''));
            ?>
            <?php
        }

        echo '<br><label for="from">' . __('From', $this->id) . '</label>
		<input type="text" class="rsm_datepicker" id="from" name="from" placeholder="' . __('From', $this->id) . '" value="' . (isset($_POST["from"]) ? $_POST["from"] : "") . '" />

		<label for="to">' . __('To', $this->id) . '</label>
		<input type="text" class="rsm_datepicker" id="to" name="to" placeholder="' . __('To', $this->id) . '" value="' . (isset($_POST["to"]) ? $_POST["to"] : "") . '"  />

		<label class="label_sku" for="sku">' . __('SKU', $this->id) . '</label>
		<input type="text" id="sku" name="sku" placeholder="' . __('SKU filter', $this->id) . '" value="' . (isset($_POST["sku"]) ? $_POST["sku"] : "") . '" />

		<input type="hidden" name="inventory_audit" value="1" />
		<input type="submit" value="Generate audit" class="button"/>
		';

        if ($this->warehouse->warehouse_enabled()) {
            $this->warehouseTransfer->display_reference_report();
        }

        echo '</form>';

        if (isset($_POST["inventory_audit"])) {

            $filter_args = array();

            if ((isset($_POST["select-warehouse"])) && ($_POST['select-warehouse'] != "0")) {
                //get only warehouse results
                $filter_args["warehouse"] = $_POST["select-warehouse"];
            }

            if ((isset($_POST["from"])) && ($_POST["from"] != "")) {
                list($year, $month, $day) = explode('-', $_POST["from"]);
                $timestamp_from = mktime(0, 0, 0, $month, $day, $year);
                $filter_args["from"] = htmlspecialchars($timestamp_from);

            }
            if ((isset($_POST["to"])) && ($_POST["to"] != "")) {
                list($year, $month, $day) = explode('-', $_POST["to"]);
                $timestamp_to = mktime(0, 0, 0, $month, $day, $year);
                $filter_args["to"] = htmlspecialchars($timestamp_to);
            }

            if ((isset($_POST["sku"])) && ($_POST["sku"] != "")) {
                $filter_args["sku"] = htmlspecialchars($_POST["sku"]);
            }

            if (isset($filter_args["to"]) && isset($filter_args["from"])) {
                if ($filter_args["from"] > $filter_args["to"]) {
                    echo '<div class="error"><p>' . __('"To" date must be higher then "From" date!', $this->id) . '</p></div>';
                }
            }

            if (isset($filter_args["to"]) && isset($filter_args["from"])) {
                if ($filter_args["from"] > $filter_args["to"]) {
                    echo '<div class="error"><p>' . __('"To" date must be higher then "From" date!', $this->id) . '</p></div>';
                }
            }

            //transfer functionality
            if ((isset($_POST["select-action-list"])) && ($_POST["select-action-list"] != "")) {
                $filter_args["select-action-list"] = htmlspecialchars($_POST["select-action-list"]);
            }

            if ((isset($_POST["reference_no"])) && ($_POST["reference_no"] != "")) {
                $filter_args["reference_no"] = htmlspecialchars($_POST["reference_no"]);
            }

            if (!empty($filter_args)) {

                echo '<div class="textarea-heading"><strong>' . __('Inventory audit list report', $this->id) . '</strong>
<a href="javascript:void();" class="copy-to-clipboard button" data-clipboard-target="rsm-audit-list"> <i class="fa fa-clipboard"></i>
				' . __('Copy to clipboard', $this->id) . '</a>
		<span class="copy-result">' . __('Copied to clipboard!', $this->id) . '</span></div>';
                echo '<textarea class="stock-report" id="rsm-audit-list">';
                echo $this->audit->get_report_inventory_audit($filter_args, $delimiter);
                echo '</textarea>';

                if ((isset($_POST["reference_no"])) && ($_POST["reference_no"] != "")) {
                    echo '<a id="audit-print-transfer" style="margin-left: 5px;" target="_blank" href="http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . '&print=print-transfer&reference_no=' . $_POST["reference_no"] . '" class="audit-print-transfer button right button-primary">' . __('Print', $this->id) . '</a>';
                }

                echo '<br /><a href="http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . '&csv=1&filter-from=' . @$filter_args['from'] . '&filter-to=' . @$filter_args['to'] .
                    '&filter-sku=' . @$filter_args['sku'] . '&filter-warehouse=' . @$filter_args['warehouse'] . '" class="button right button-primary">' . __('Export to CSV', $this->id) . '</a><br /><br />';


            } else {
                echo '<div class="error"><p>' . __('You need to specify filters! Either SKU or date range ot both.', $this->id) . '</p></div>';
            }


        }

        echo '<br/>';
        echo '<h2 style="font-size: 1.4em;">' . __('Main Stock amounts', $this->id) . '</h2>';

        echo '<h3 style="font-size: 1.1em;">' . __('Total', $this->id) . ' ' . $report_data["variants_count"] . '
		' . __('variants of products and', $this->id) . ' ' . $report_data["main_products_count"] . '
		' . __('main products of page ', $this->id) . $report_data["page_number"] . '</h3>';

        echo '<p>' . __('Please copy content of below white text areas and paste it to Excel (or Google
		Docs or Mac OS Numbers) spreadsheet and then you can perform sorting, filtering and calculations.', $this->id) . '</p>';

        $low_stock_count = $report_data["low_stock_count"];
        if ($low_stock_count > 0) {
            echo '<p>' . __('There is ', $this->id) . ' ' . $low_stock_count . '
			' . __('Products with low stock! (Below quantity ', $this->id) . $this->settings["no_stock_highlight_threshold"] . '
			' . __(' in stock)', $this->id) . '</p>';
        }

        $this->display_pagination('pagination_report_data_table', '');

        echo '<div class="textarea-heading"><strong>' . __('List report ', $this->id) . '</strong>';
        echo '<a href="javascript:" class="copy-to-clipboard button" data-clipboard-target="rsm-report-list"> <i class="fa fa-clipboard"></i>
        ' . __('Copy to clipboard', $this->id) . '</a>
		<span class="copy-result">' . __('Copied to clipboard!', $this->id) . '</span></div>';

        echo '<textarea class="stock-report" id="rsm-report-list">';
        echo $this->get_report_content_list($table_data, $delimiter);
        echo '</textarea>';

        echo '<br />';

        echo '<div class="textarea-heading"><strong>' . __('Grid report', $this->id) . '</strong>
		<a href="javascript:" class="copy-to-clipboard button" data-clipboard-target="rsm-report-grid"> <i class="fa fa-clipboard"></i>
		' . __('Copy to clipboard', $this->id) . '</a>
		<span class="copy-result">' . __('Copied to clipboard!', $this->id) . '</span></div>';
        echo '<textarea class="stock-report" id="rsm-report-grid">';
        echo $this->get_report_content_grid($table_data, $delimiter);
        echo '</textarea>';

        $this->display_pagination('pagination_report_data_table', '');
    }

    /**
     * @param int $start_record
     * @param int $limit
     */
    public function display_variations_table_view($start_record = 0, $limit = 0)
    {
        $temp_param = $this->get_request_param('attributes');


        $attributes_combinations = null;
        if (empty($temp_param)) {
            $attributes_combinations = $this->get_attributes_combinations();
            $attributes_combination_selected = $attributes_combinations[0][0];
        } else {
            $attributes_combination_selected = $this->get_request_param('attributes');
        }

        $search = '';
        $temp_param = $this->get_request_param('search-value');
        if (!empty($temp_param)) {
            $search = $this->get_request_param('search-value');
        }
        $temp_param = $this->get_request_param('search');
        if (!empty($temp_param) && $temp_param === 'reset') {
            $search = '';
        }

        $where_attribute_in = explode('|', $attributes_combination_selected);

        $main_product_ids = $this->get_main_product_ids_with_variations($start_record, $limit, $where_attribute_in, $search);
        if (!$main_product_ids || sizeof($main_product_ids) < 1) {
            echo '<div style="border: 1px solid red">';
            echo $this->get_html_variations_form($attributes_combinations, $attributes_combination_selected);
            echo '<div class="clear"></div>';
            echo '</div>';
            $search_text = '';
            $search_value = $this->get_request_param('search-value');
            if ($search_value) {
                $search_text = __('Try to reset search or search different text.', $this->id);
                $search_text .= ' ' . __('Searching for: ', $this->id) . '<b>' . $search_value . '</b>';
            }
            echo '<div class="error"><p>' . __('Not products available to list. Please select different variation set. #2 ', $this->id) . $search_text . ' 
            To see <a href="'.$this->help->get_help_link('no-variations').'">help</a>, please <a href="'.$this->help->get_help_link('no-variations').'">click here</a>.</p></div>';
            if ($search) {
                $this->display_search_entire_products();
            }
            $this->num_displayed_products = 0;
            return;
        }
        echo '<div style="float: left">';
        $sqls = $this->get_sql_main_product_ids_with_variations($where_attribute_in, $search);
        $sql_total = $sqls["total"];
        $total_products = intval($this->get_total_num_of_records($sql_total));
        $this->num_displayed_products = $total_products;
        echo $this->get_html_variations_form($attributes_combinations, $attributes_combination_selected);
        echo '
		<script>
		mixpanel.track("display_variations_table_view", {"Total products": ' . $total_products . ', "Attributes selected": "' . $attributes_combination_selected . '"});
		</script>
		';
        echo '<div class="clear"></div>';
        if ($this->warehouse->warehouse_enabled()) {
            $this->warehouse->display_warehouse_lists('left', 'variant');
        }
        echo '<div class="clear"></div>';
        echo '</div>';
        echo '<div class="variant-products-table">';
        echo '<div style="float: right; padding-top: 10px">';
        $this->display_search_entire_products();
        echo '<div class="clear"></div>';
        $this->display_sort_by();
        echo '<div class="clear"></div>';
        echo '</div>';
        echo '<div class="clear"></div>';
        $this->display_pagination(null, $sql_total);
        $products_data_table = $this->get_variation_view_table($main_product_ids);
        echo $this->display_variation_grid($products_data_table["products"]);
        $this->display_pagination(null, $sql_total);
        echo '</div>';

    }


    /**
     * search functionality search function
     * @param string $align
     * @param string $warehouse
     */
    public function display_search_entire_variant_products($align = 'right', $warehouse = '', $value = '')
    {
        $limit = $this->settings['products_to_display'];
        ?>

        <div class="search-entire-container <?php echo $align; ?>">
            <form method="post" action="" id="variation-search-form">
                <?php _e('Search this set: ', $this->id); ?>
                <input name="search-value" id="search-value" type="search"
                       placeholder="<?php _e('e.g. SKU or title', $this->id); ?>"
                       value="<?php echo $value; ?>">
                <input type="button" class="<?php if ($warehouse) {
                    echo 'warehouse ';
                } ?>variant-products search-button search-products variantwp-core-ui button" <?php if ($warehouse) { ?> data-warehouse="<?php echo $warehouse; ?>" data-type="variant" <?php } ?>
                       value="<?php _e('Search', $this->id); ?>"/>

                <div class="search-reset variantion_search_reset" style="display:none;">
                    <input type="button" class="wp-core-ui button" value="<?php _e('Reset', $this->id); ?>"
                           data-page-limit="<?php echo $limit; ?>" data-layout-type="variant"/>
                </div>
            </form>

        </div>
        <?php
    }

    /**
     * @param string $sorting
     * @return string
     */
    public function pagination_with_variation_sorting($sorting = '')
    {
        $output = '';
        if (!empty($sorting)) {
            switch ($sorting) {
                case "order_by_post_name_asc":
                    $output = "ORDER BY p.`post_name` ASC";
                    break;
                case "order_by_post_name_dsc":
                    $output = "ORDER BY p.`post_name` DESC";
                    break;
                case "order_by_post_modified_asc":
                    $output = "ORDER BY p.`post_modified` ASC";
                    break;
                case "order_by_post_modified_dsc":
                    $output = "ORDER BY p.`post_modified` DESC";
                    break;
                case "order_by_sku_asc":
                    $output = "ORDER BY p.`post_modified` ASC";
                    break;
                case "order_by_sku_dsc":
                    $output = "ORDER BY p.`post_modified` DESC";
                    break;
                default:
                    break;
            }
        }
        return $output;
    }

    /**
     * render out sort by display
     */
    function display_sort_by($align = 'right', $warehouse = '', $pagenumber = 1, $sorting = '', $type = '')
    {
        $show_per_page_record = $this->settings['products_to_display'];
        $changeFunction = '';
        $warehouseSorting = '';
        if (!empty($warehouse)) {
            $warehouseSorting = " data-warehouse='$warehouse' data-per-page-record='$show_per_page_record' data-page-number='$pagenumber' data-view-type='$type'";
        } else {
            $sorting = $this->settings["products_order"];
        }
        ?>
        <div class="filter-rapid-stock-manager <?php echo $align; ?>">
            <div class="sort-by">
                <form method="post"
                      action="<?php echo empty($warehouse) ? admin_url('admin.php?' . $this->get_url_admin_params(true)) : ''; ?>">

                    <!-- selected item is needed to remember -->
                    <?php _e('Sort by:', $this->id); ?>
                    <select id="sort-by" name="sort-by" class="<?php if ($warehouse) {
                        echo 'warehouse ';
                    } ?>sort-by"
                        <?= $warehouseSorting; ?> >

                        <option
                            value="order_by_post_name_asc" <?php if ($sorting == 'order_by_post_name_asc') {
                            echo 'selected';
                        } ?>>
                            <?php _e('Product Ascending', $this->id) ?>
                        </option>
                        <option
                            value="order_by_post_name_dsc" <?php if ($sorting == 'order_by_post_name_dsc') {
                            echo 'selected';
                        } ?>>
                            <?php _e('Product Descending', $this->id) ?>
                        </option>
                        <option
                            value="order_by_post_modified_asc" <?php if ($sorting == 'order_by_post_modified_asc') {
                            echo 'selected';
                        } ?>>
                            <?php _e('Product Modified Ascending', $this->id) ?>
                        </option>
                        <option
                            value="order_by_post_modified_dsc" <?php if ($sorting == 'order_by_post_modified_dsc') {
                            echo 'selected';
                        } ?>>
                            <?php _e('Product Modified Descending', $this->id) ?>
                        </option>
                        <!-- NOTE, SKU is so complicated to do, not worthy
                        <option
                            value="order_by_sku_asc" <?php if ($sorting == 'order_by_sku_asc') {
                            echo 'selected';
                        } ?>>
                            <?php _e('SKU Ascending', $this->id) ?>
                        </option>
                        <option
                            value="order_by_sku_dsc" <?php if ($sorting == 'order_by_sku_dsc') {
                            echo 'selected';
                        } ?>>
                            <?php _e('SKU Descending', $this->id) ?>
                        </option>
                        -->

                    </select>

                </form>

            </div>
        </div>

    <?php }

    /**
     * render out form to display the view required
     */
    function display_grid_view_button()
    { ?>

        <?php //have options to set the default feature of layout when starting
        $rapid_selector_view = array_key_exists('rapid-selector-view', $_REQUEST) ? $_REQUEST['rapid-selector-view'] : '';
        ?>

        <div class="filter-rapid-stock-manager">

            <form method="post" action="<?php echo admin_url('admin.php?page=update_stock_rapid'); ?>">

                <!-- selected item is needed to remember -->
                <select id="rapid-selector-view" name="rapid-selector-view" class="rapid-selector-view"
                        data-view-selector="">

                    <option
                        value="simple_product" <?php if ($rapid_selector_view == 'simple_product') {
                        echo 'selected';
                    } ?> >
                        <?php _e('Simple Products', $this->id) ?>
                    </option>

                    <option
                        value="variation_product" <?php if ($rapid_selector_view == 'variation_product') {
                        echo 'selected';
                    } ?> >
                        <?php _e('Variation Products', $this->id) ?>
                    </option>

                </select>

                <input type="submit" value="Go">
            </form>

        </div>

        <?php
    }

    /**
     * render out menu navigation view
     */
    function menu_navigation_view()
    {
        $rapid_selector_view = array_key_exists('rapid-selector-view', $_REQUEST) ? $_REQUEST['rapid-selector-view'] : '';
        ?>
        <input type="hidden" value="<?php echo get_site_url(); ?>" id="rapid-stock-base-url">
        <a href="<?php echo admin_url($this->admin_url); ?>&rapid-selector-view=simple_product"
           class="nav-tab <?php if ($rapid_selector_view == 'simple_product') {
               echo ' nav-tab-active';
           } ?>">
            <i class="fa fa-bars"></i> <?php _e('Simple Products', $this->id) ?>
        </a>
        <a href="<?php echo admin_url($this->admin_url . '&rapid-selector-view=variation_product'); ?>"
           class="nav-tab <?php if ($rapid_selector_view == 'variation_product') {
               echo ' nav-tab-active';
           } ?>">
            <i class="fa fa-cubes"></i> <?php _e('Variant Products', $this->id) ?>
        </a>
        <a href="<?php echo admin_url($this->admin_url . '&rapid-selector-view=stock_report'); ?>"
           class="nav-tab <?php if ($rapid_selector_view == 'stock_report') {
               echo ' nav-tab-active';
           } ?>">
            <i class="fa fa-bar-chart"></i> <?php _e('Stock Report', $this->id) ?>
        </a>
        <?php if ($this->product_generator->enabled) { ?>
        <a href="<?php echo admin_url($this->admin_url . '&rapid-selector-view=product_generator'); ?>"
           class="nav-tab <?php if ($rapid_selector_view == 'product_generator') {
               echo ' nav-tab-active';
           } ?>">
            <i class="fa fa-cogs"></i> <?php _e('Product Generator', $this->id) ?>
        </a>
    <?php } ?>
        <?php if ($this->warehouse->warehouse_enabled()) {
        ?>
        <a href="javascript:;" id="warehouse_transfer_popup" class="add-new-h2" data-modal-id="popup1">
            <i class="fa fa-exchange"></i> <?php _e('Transfer Warehouse', $this->id) ?>
        </a>
    <?php } ?>
        <a href="<?php echo admin_url('post-new.php?post_type=product') ?>" class="add-new-h2 ">
            <i class="fa fa-plus-square"></i> <?php _e('Add Product', $this->id) ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=' . $this->url['param']['plugin_settings']); ?>"
           class="add-new-h2 ">
            <i class="fa fa-wrench"></i> <?php _e('Settings', $this->id) ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=update_stock_rapid&rapid-selector-view=help'); ?>"
           class="add-new-h2 ">
            <i class="fa fa-question-circle"></i>
        </a>

        <?php
    }

    /**
     * get table colour returns string styles
     * @param $quantity
     * @return string
     */
    function get_table_color($quantity)
    {

        $cell_color = $this->get_adjusted_color_brightness($this->settings["no_stock_highlight_color"], ($this->settings["color_step"] * $quantity));

        $text_color = $this->settings["no_stock_highlight_text_color"];
        $text_color_style = '';

        if ($quantity < 1) {
            $text_color_style = "color: $text_color;";
        }
        return "background-color: $cell_color; $text_color_style ";

    }

    /**
     * Display <td> for variation
     * @param $variation_product
     * @param $product_variant
     * @param $original_variant_qty
     */
    private function get_html_variation_product_table_cell($variation_product, $product_variant, $original_variant_qty)
    {

        ob_start();
        ?>

        <td class="product update-cell input-cell" data-variant-total="true"
            style="<?php echo $this->get_table_color($original_variant_qty); ?>">
            <div class="cell-highlighter"></div>
            <div class="container-variant-single cell-content">

                <div class="sku-detail nowrap">
                    <?php $this->display_tool_tip('variant', $product_variant); ?>
                    <span
                        title="<?php _e('SKU', $this->id); ?>: <?php echo $product_variant->get_sku(); ?>"><?php echo $product_variant->get_sku(); ?></span>
                </div>

                <?php if ($product_variant->manage_stock === 'no') { ?>
                    <div class="manage-stock">
                        <?php _e('Manage Stock: No', $this->id); ?>
                    </div>
                    <?php
                } else { ?>

                    <div class="original-qty"
                         data-original-qty="<?php echo $original_variant_qty; ?>">
                        <?php _e('Qty', $this->id); ?>:
                        <b><?php echo $original_variant_qty; ?></b>
                        <span class="new-qty-changes"></span>
                    </div>

                    <div class="nowrap">
                        <?php
                        if ($this->settings['update_action'] == 'adjust') {
                            $alt_text = __('Adjust', $this->id);
                        } else {
                            $alt_text = __('Set', $this->id);
                        }
                        ?>
                        <label class="nowrap">
                            <!--<i class="fa fa-sort input-indicator"></i>&nbsp;--><input
                                type="number" class="wc-qty-change"
                                value=""/></label>
                        <a href="#"
                           class="variant-quantity-update nowrap button"
                           data-post-id="<?php echo $product_variant->variation_id; ?>"
                           title="<?php echo $alt_text; ?>">
                            <?php
                            $update_action = $this->settings['update_action'];
                            ?>

                            <!-- span class="icon"-->
                            <i class="loading fa fa-spinner"></i>
                            <i class="loading fa fa-check"></i>
                            <!-- /span -->
                            <i class="spinner-override fa fa-bolt <?php if ($update_action == 'adjust' || $update_action == 'deduct') {
                                echo 'display-none';
                            } ?>"></i>
                            <i class="spinner-override fa fa-arrows-v <?php if ($update_action == 'set' || $update_action == 'deduct') {
                                echo 'display-none';
                            } ?>"></i>
                            <i class="spinner-override fa fa-long-arrow-down <?php if ($update_action == 'adjust' || $update_action == 'set') {
                                echo 'display-none';
                            } ?>"></i>
                        </a>
                    </div>
                    <?php
                }
                ?>

            </div>
        </td>

        <?php

        return ob_get_clean();
    }

    /**
     * Display <td> for action cell for variation product
     * @param $product_id
     */
    private function get_html_variation_action_cell($product_id)
    {
        ob_start();
        ?>

        <td class="action-cell">
            <p>
                <a href="<?php echo admin_url('post.php?post=' . $product_id . '&action=edit'); ?>"
                   class="edit-main">
                    <i class="fa fa-pencil-square-o"></i>
                    <?php echo _e('Edit Main', $this->id); ?>
                </a>
            </p>
            <a href="#" class="variant-update-row action-link"
               adjust-update-row-qty="<?php echo $product_id; ?>">
                <?php
                $update_action = $this->settings['update_action'];
                ?>
                <i class="loading fa fa-spinner"></i>
                <i class="loading fa fa-check"></i>
                <i class="spinner-override fa fa-bolt <?php if ($update_action == 'adjust' || $update_action == 'deduct') {
                    echo 'display-none';
                } ?>"></i>
                <i class="spinner-override fa fa-arrows-v <?php if ($update_action == 'set' || $update_action == 'deduct') {
                    echo 'display-none';
                } ?>"></i>
                <i class="spinner-override fa fa-long-arrow-down <?php if ($update_action == 'adjust' || $update_action == 'set') {
                    echo 'display-none';
                } ?>"></i>
				<span class="text">
				<?php
                if ($update_action == 'adjust') {
                    _e('Adjust Row', $this->id);
                } else if ($update_action == 'deduct') {
                    _e('Deduct Row', $this->id);
                } else {
                    _e('Set Row', $this->id);
                }
                ?>
				</span>
            </a>
        </td>

        <?php
        return ob_get_clean();
    }


    /**
     * display variant layout view
     */
    public function variation_layout_view()
    {

        $page_number = isset($_GET['page_number']) ? absint($_GET['page_number']) : 1;
        $limit = $this->settings['products_to_display'];
        $limit = ($limit == 'all') ? 1000 : $limit;
        $start_record = $limit * ($page_number - 1);
        $this->display_variations_table_view($start_record, $limit);

    }//end function

    /**
     * search entire products
     * @param string $align
     */
    public function display_search_entire_products($align = 'right')
    {
        ?>

        <div class="search-entire-container <?php echo $align; ?>">
            <form method="post"
                  action="<?php echo admin_url('admin.php?' . $this->get_url_admin_params(true, true) . '&search=entire'); ?>"
                  onsubmit="document.location.href='<?php echo admin_url('admin.php?' . $this->get_url_admin_params(true, true) . '&search=entire&search-value='); ?>'+document.getElementById('search-value').value; return false; ">
                <?php _e('Search this set: ', $this->id); ?>

                <input name="search-value" id="search-value" type="search"
                       placeholder="<?php _e('e.g. SKU or title', $this->id); ?>"
                       value="<?php echo $this->get_request_param('search-value'); ?>">
                <input type="button" class="wp-core-ui button" value="<?php _e('Search', $this->id); ?>"
                       onclick="document.location.href='<?php echo admin_url('admin.php?' . $this->get_url_admin_params(true, true) . '&search=entire&search-value='); ?>'+document.getElementById('search-value').value; "/>

                <!-- if search entire allow reset button -->
                <?php if ($this->get_request_param('search') == 'entire' && $this->get_request_param('search-value') != '') { ?>
                    <div class="search-reset">
                        <input type="button" class="wp-core-ui button" value="<?php _e('Reset', $this->id); ?>"
                               onclick="document.location.href='<?php echo admin_url('admin.php?' . $this->get_url_admin_params(null, true) . '&search=reset'); ?>'; "/>
                    </div>
                <?php } ?>
            </form>


        </div>
        <?php
    }

    /**
     * @param null $products
     * @param int $type
     * @param string $warehouse
     */
    public function render_simple_products_table_html($products = null, $type = 0, $warehouse = '')
    {
        if (!$products) {
            return;
        }
        //HTML
        ?>
        <table class="widefat attributes-table wp-list-table ui-sortable woocommerce-rapid-stock-manager-table"
               data-table-view="simple" style="width:100%"
               data-text-set="<?php _e('Set Quantity', $this->id); ?>"
               data-text-deduct="<?php _e('Deduct Quantity', $this->id); ?>"
               data-text-adjust="<?php _e('Adjust Quantity'); ?>"
               data-text-row-set="<?php _e('Set Quantity', $this->id); ?>"
               data-text-row-deduct="<?php _e('Deduct Quantity', $this->id); ?>"
               data-text-row-adjust="<?php _e('Adjust Quantity'); ?>"
        >

            <thead>
            <tr>
                <th class="column-sku" scope="col"><strong><?php _e('SKU', $this->id) ?></strong></th>
                <th scope="col"><strong><?php _e('Product Name', $this->id) ?></strong></th>
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
                if (!empty($type)) {
                    $productId = $product;
                } else {
                    $productId = $product->id;
                }
                $product = wc_get_product($productId);
                $original_variant_qty = $product->get_total_stock();
                //$cell_color = $this->get_adjusted_color_brightness($this->settings["no_stock_highlight_color"], ($this->settings["color_step"] * $original_variant_qty));
                $text_color = $this->settings["no_stock_highlight_text_color"];
                $text_color_style = '';
                if (!empty($type)) {
                    $original_variant_qty = $this->warehouse->get_warehouse_value_by_productId($productId, $warehouse);

                }

                if ($original_variant_qty < 1) {
                    $text_color_style = 'color:' . $text_color;
                    $stock_status = 'Out of stock';
                    $stock_status_color = 'red';
                    $cell_color = '#f5d2d0';
                } else {
                    $stock_status = 'In stock';
                    $stock_status_color = 'green';
                    $cell_color = '#fff';
                }

                if (!$product->is_type('simple')) {
                    continue;
                }
                ?>
                <tr>
                    <td class="sku-cell"><?php echo $product->get_sku(); ?>
                        <?php $this->display_tool_tip('simple', $product); ?>
                    </td>
                    <td class="title-cell product"><?php echo Ucfirst($product->get_title()); ?></td>
                    <td class="image"><?php echo $product->get_image(); ?></td>

                    <?php $manage_stock_color = ($product->managing_stock() == true) ? 'green' : 'red'; ?>
                    <td class="manage-stock <?php echo $manage_stock_color; ?>">
                        <?php echo ($product->managing_stock() == true) ? _e('Yes', $this->id) : _e('No', $this->id); ?>
                    </td>

                    <td class="stock-status <?php echo $stock_status_color; ?>" id="stock-status<?= $productId; ?>">
                        <?php echo $stock_status; ?>
                    </td>
                    <td class="back-orders">
                        <?php echo ($product->is_on_backorder() == true) ? _e('yes', $this->id) : _e('No', $this->id); ?>
                    </td>
                    <td class="input-cell" data-update-input="true"
                        data-original-qty="<?php echo $original_variant_qty; ?>">
                        <div class="cell-highlighter"></div>
                        <div class="cell-content">
                            <label class="nowrap">
                                <i class="fa fa-chevron-right input-indicator"></i>&nbsp
                                <input type="number" class="wc-qty-change" data-simple-qty="true" value=""
                                       id="update-value<?= $productId; ?>"/>
                            </label>
                        </div>
                    </td>
                    <td data-simple-total="true"
                        style="background-color:<?php echo $cell_color . ';' . $text_color_style; ?>">
                        <strong id="total-quantity<?= $productId; ?>"><?php echo $original_variant_qty; ?></strong>
                    </td>
                    <td>

                        <?php $this->display_select_action($productId, $type); ?>
                    </td>
                    <td class="total action-cell">
                        <p>
                            <a href="<?php echo admin_url('post.php?post=' . $product->id . '&action=edit'); ?>">
                                <i class="fa fa-pencil-square-o"></i>
                                <?php echo _e('Edit Product', $this->id); ?>
                            </a>
                        </p>

                        <a href="javascript:;"
                           class="<?php if (!empty($type)) {
                               echo "warehouse ";
                           } ?>simple-quantity-update action-adjust action-set allow action-link"
                           data-adjust-simple-quantity="<?php echo $product->id; ?>"
                           data-warehouse="<?php echo $warehouse; ?>"
                           style="cursor:pointer;">
                            <?php
                            $update_action = $this->settings['update_action'];
                            ?>

                            <i class="loading fa fa-spinner"></i>
                            <i class="loading fa fa-check"></i>
                            <i class="spinner-override fa fa-bolt <?php if ($update_action == 'adjust' || $update_action == 'deduct') {
                                echo 'display-none';
                            } ?>"></i>
                            <i class="spinner-override fa fa-arrows-v <?php if ($update_action == 'set' || $update_action == 'deduct') {
                                echo 'display-none';
                            } ?>"></i>
                            <i class="spinner-override fa fa-long-arrow-down <?php if ($update_action == 'adjust' || $update_action == 'set') {
                                echo 'display-none';
                            } ?>"></i>

                                        <span class="text" id="action-btn<?= $productId; ?>">
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
            }//foreach

            ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * stock report layout view
     * */
    public function stock_report_view()
    {
        echo '
		<script>
		mixpanel.track("stock_report_view");
		</script>
		';
        switch ($this->settings["report_column_delimiter"]) {
            case 'semicolon':
                $delimiter = ";";
                break;
            case 'pipe':
                $delimiter = "|";
                break;
            case 'comma':
                $delimiter = ",";
                break;
            case 'tab':
            default:
                $delimiter = "\t";
        }
        ?>

        <div class="col-wrap variation-container">
            <?php $this->render_report($delimiter); ?>
        </div>

    <?php }

    /**
     * simple layout view
     */
    public function simple_layout_view()
    {
        ?>
        <div class="col-wrap simple-products-container">
            <?php
            $products = $this->get_products('pagination_simple');
            if (!empty($products)) {
                echo '<div style="float:left;">';
                if ($this->warehouse->warehouse_enabled()) {
                    $this->warehouse->display_warehouse_lists('left', 'simple');
                }
                echo '<div class="clear"></div>';
                echo '</div>';
                echo '<div class="simple-products-table">';
                echo '<div style="float:right;">';
                $this->display_search_entire_products('left');
                $this->display_sort_by('right');
                echo '<div class="clear"></div>';
                echo '</div>';
                echo '<div class="clear"></div>';
                $this->display_pagination('pagination_simple');
                $this->render_simple_products_table_html($products);
                $this->display_pagination('pagination_simple');
                echo '</div>';
            } else {
                $this->display_search_entire_products('left');
                ?>
                <div class="clear"></div>
                <br>
                <h3 style="margin-top: 40px;"><?php _e('You Have No Simple Products to Display', $this->id); ?></h3>
                To see <a href="<?php echo $this->help->get_help_link('no-simple'); ?>">help</a>, please <a href="<?php echo $this->help->get_help_link('no-simple'); ?>">click here</a>.
                <?php
                $search_value = array_key_exists('search-value', $_GET) ? $_GET['search-value'] : '';
                if ($search_value && !empty($search_value)) {
                    echo '<p>Try different search keyword than <strong>' . $search_value . '</strong> or <a href="' . admin_url('admin.php?' . $this->get_url_admin_params(null, true) . '&search=reset') . '">Reset search</a></p>';
                }
                ?>
            <?php } ?>

        </div>

        <?php
    }

    /**
     * Display Rapid Stock Manager
     */
    function rapid_stock_manager()
    {

        $this->validate_requests_params();
        ?>

        <div class="wrap woocommerce woocommerce-rapid-stock-manager" data-table-filter-enabled="true"
             data-table-filter-label="<?php _e('Filter below:', $this->id) ?>"
             data-table-filter-minrows="0"
             data-table-filter-input-text="<?php _e('by title or Sku', $this->id) ?>"
             data-no-products="<?php _e('You have no products to Display', $this->id); ?>"
             data-warehouse-exists="<?php _e('This warehouse already exist.', $this->id); ?>"
             data-please-wait-print="<?php _e("Wait to generate document for printing..", $this->id); ?>"

        >

            <?php $this->get_style_stock_manager(); ?>


            <h2 class="nav-tab-wrapper woo-nav-tab-wrapper"><?php //_e('Rapid Stock Manager', $this->id)
                ?> &nbsp;
                &nbsp; <?php $this->menu_navigation_view(); ?> </h2>

            <div id="col-container" class="<?php echo $this->settings["theme"]; ?>">

                <?php
                $rapid_selector_view = array_key_exists('rapid-selector-view', $_REQUEST) ? $_REQUEST['rapid-selector-view'] : '';
                switch ($rapid_selector_view) {

                    case "variation_product":
                        $this->variation_layout_view();
                        break;
                    case "simple_product":
                        $this->simple_layout_view();
                        break;
                    case "stock_report":
                        $this->stock_report_view();
                        break;
                    case "product_generator":
                        $this->product_generator->view();
                        break;
                    case "help":
                        $help_page = array_key_exists('help-page', $_REQUEST) ? $_REQUEST['help-page'] : '';
                        $this->help->view($help_page);
                        break;
                    default:
                        $this->simple_layout_view();
                        break;
                }
                ?>

                <div class="loading-container" style="display: none;">
                    <?php _e('Loading', $this->id) ?> <i class="fa fa-refresh" aria-hidden="true"></i>
                </div>

            </div>
            <div class="ishouty-credits">
                <?php _e('Made by ', $this->id); ?><a
                    href="http://www.ishouty.com?from=RSM&amp;version=<?php echo $this->get_version(); ?>">ishouty.com</a>,
                version <?php echo $this->get_version(); ?>
            </div>
        </div>
        <div class="product-popup" data-select-valid-warehouse="<?php _e('Please select the warehouse value from the dropdown.',$this->id); ?>"></div>
        <?php
    }

    /**
     * Product Sorting
     * @param string $sorting
     * @return string
     */
    public function pagination_with_sorting($sorting = '')
    {
        $output = '';
        if (!empty($sorting)) {
            switch ($sorting) {
                case "order_by_post_name_asc":
                    $output = "ORDER BY post.`post_name` ASC";
                    break;
                case "order_by_post_name_dsc":
                    $output = "ORDER BY post.`post_name` DESC";
                    break;
                case "order_by_post_modified_asc":
                    $output = "ORDER BY post.`post_modified` ASC";
                    break;
                case "order_by_post_modified_dsc":
                    $output = "ORDER BY post.`post_modified` DESC";
                    break;
                case "order_by_sku_asc":
                    $output = "ORDER BY post.`post_modified` ASC";
                    break;
                case "order_by_sku_dsc":
                    $output = "ORDER BY post.`post_modified` DESC";
                    break;

            }
        }
        return $output;
    }


    /**
     * displays the content for tool tip
     * @param $condition
     * @param $product
     */
    public function display_tool_tip($condition, $product)
    {

        if ($condition == 'simple') { ?>

            <i class="fa fa-info-circle tooltip-qtip-html"
               data-qtip-title="<?php echo $product->get_title(); ?>"></i>
            <div class="tool-tip-content display-none">

                <div class="details">

                    <p>
                        <?php _e('Price: ', $this->id); ?> <?php echo $product->get_price_html(); ?>

                        <?php
                        $sale_price = $product->get_sale_price();
                        if (!empty($sale_price)) { ?>
                            <br/>
                            <?php _e('Sale Price:  ', $this->id); ?><?php echo $product->get_sale_price(); ?>
                        <?php } ?>
                    </p>

                    <?php
                    $audit = $this->audit->stock_audit_info($product->id);
                    ?>
                    <p>
                        <?php _e('Last adjusted:  ', $this->id); ?> <?php echo(isset($audit["last_adjusted"]) ? date("d M Y", strtotime($audit["last_adjusted"])) : "n/a"); ?>
                        <br/>
                        <?php _e('Adjusted:  ', $this->id); ?> <?php echo(isset($audit["adjusted"]) ? $audit["adjusted"] : "n/a"); ?>
                        <br/>
                        <?php _e('Last set:  ', $this->id); ?> <?php echo(isset($audit["last_set"]) ? date("d M Y", strtotime($audit["last_set"])) : "n/a"); ?>
                        <br/>
                        <?php _e('Set:  ', $this->id); ?> <?php echo(isset($audit["set"]) ? $audit["set"] : "n/a"); ?>
                    </p>

                    <?php
                    $shipping_class = $product->get_shipping_class();
                    if (!empty($shipping_class)) { ?>
                        <p><?php _e('Shipping Class: ', $this->id); ?><?php echo $shipping_class; ?></p>
                    <?php } ?>

                    <?php if ($product->has_dimensions()) { ?>
                        <p><?php _e('Dimensions: ', $this->id); ?><?php echo $product->get_dimensions(); ?></p>
                    <?php } ?>

                    <?php if ($product->has_weight()) { ?>
                        <p><?php _e('Has Weight: ', $this->id); ?><?php echo $product->get_weight(); ?></p>
                    <?php } ?>

                </div>

            </div>

        <?php } else { ?>

            <i class="fa fa-info-circle tooltip-qtip-html"
               data-qtip-title="<?php _e('Variant Details for SKU', $this->id); ?>: <?php echo $product->get_sku(); ?>"></i>
            <div class="tool-tip-content display-none">

                <div class="image-details"> <?php echo $product->get_image(); ?> </div>

                <div class="details">

                    <p>
                        <?php _e('Product id: ', $this->id); ?> <?php echo $product->id; ?>,
                        <?php _e('Variation id: ', $this->id); ?> <?php echo $product->variation_id; ?>
                    </p>

                    <p>
                        <?php _e('Price: ', $this->id); ?> <?php echo $product->get_price_html(); ?>
                        <?php
                        $sale_price = $product->get_sale_price();
                        if (!empty($sale_price)) { ?>
                            <br/>
                            <?php _e('Sale Price:  ', $this->id); ?><?php echo $product->get_sale_price(); ?>
                        <?php } ?>
                    </p>

                    <?php
                    $audit = $this->audit->stock_audit_info($product->variation_id);
                    ?>
                    <p>
                        <?php _e('Last adjusted:  ', $this->id); ?> <?php echo(isset($audit["last_adjusted"]) ? date("d M Y", strtotime($audit["last_adjusted"])) : "n/a"); ?>
                        <br/>
                        <?php _e('Adjusted:  ', $this->id); ?> <?php echo(isset($audit["adjusted"]) ? $audit["adjusted"] : "n/a"); ?>
                        <br/>
                        <?php _e('Last set:  ', $this->id); ?> <?php echo(isset($audit["last_set"]) ? date("d M Y", strtotime($audit["last_set"])) : "n/a"); ?>
                        <br/>
                        <?php _e('Set:  ', $this->id); ?> <?php echo(isset($audit["set"]) ? $audit["set"] : "n/a"); ?>
                    </p>

                    <p><?php _e('Manage Stock: ', $this->id); ?>
                        <?php $manage_stock_color = ($product->managing_stock() == 1) ? 'green' : 'red'; ?>
                        <span
                            class="manage-stock <?php echo $manage_stock_color; ?>"><?php echo ($product->managing_stock() == 1) ? 'Yes' : 'No'; ?></span>
                        <br>

                        <?php _e('Stock Status: ', $this->id); ?>
                        <?php $stock_status_color = ($product->is_in_stock() == true) ? 'green' : 'red'; ?>
                        <span
                            class="stock-status <?php echo $stock_status_color; ?>"><?php echo ($product->is_in_stock() == true) ? _e('In stock', $this->id) : _e('Out of stock', $this->id); ?></span>
                        <br>

                        <?php _e('Back Orders Allowed: ', $this->id); ?> <?php echo ($product->backorders_allowed() == 1) ? 'Yes' : 'No' ?>
                    </p>

                    <?php $shipping_class = $product->get_shipping_class();
                    if (!empty($shipping_class)) { ?>
                        <p><?php _e('Shipping Class: ', $this->id); ?><?php echo $shipping_class; ?></p>
                    <?php } ?>

                    <?php if ($product->has_dimensions()) { ?>
                        <p><?php _e('Dimensions: ', $this->id); ?><?php echo $product->get_dimensions(); ?></p>
                    <?php } ?>

                    <?php if ($product->has_weight()) { ?>
                        <p><?php _e('Has Weight: ', $this->id); ?><?php echo $product->get_weight(); ?></p>
                    <?php } ?>

                </div>

            </div>

        <?php }

    }

}

$rsm_woocommerce = new woocommerce_rapid_stock_manager();

?>