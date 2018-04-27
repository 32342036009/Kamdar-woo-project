<?php


require('classes/rapid_warehouse_manager.php');
require('classes/rapid_warehouse_manager_transfer.php');
require('classes/rapid_stock_manager_audit.php');
require('classes/rapid_stock_manager_generator.php');
require('classes/rapid_stock_manager_help.php');


/**
 * Class core_rapid_stock_manager
 * @author ishouty ltd. London
 * @date 2015
 */
class core_rapid_stock_manager
{

    /**
     * @var string Id of this plugin for internationalisation used in __ and _e
     */
    public $id = 'woocommerce-rapid-stock-manager';

    /**
     * @var string RSM Version - please change every release
     */
    public $version = '2.0.2';

    /**
     * @var string Environemnt (dev|prod)
     */
    public $environment = 'prod';

    /**
     * @var string Warehouse table name (without wp prefix)
     */
    public $db_table_woocommerce_product = "rsm_stock_woocommerce_product_table";

    /**
     * @var string Name of the version attribute in wpoptions table
     */
    public $option_db = "ishouty_rsm_version";

    /**
     * @var audit core functionality
     */
    public $audit;

    /**
     * @var warehouse core functionality
     */
    public $warehouse;

    /**
     * @var warehouseTransfer
     */
    public $warehouseTransfer;

    /**
     * @var product_generator instance
     */
    public $product_generator;

    /**
     * @var help instance
     */
    public $help;

    function __construct () {
        $this->audit = new rapid_stock_manager_audit($this);
        $this->warehouse = new rapid_warehouse_manager($this);
        $this->warehouseTransfer = new rapid_warehouse_manager_transfer($this);
        $this->product_generator = new rapid_stock_manager_generator($this);
        $this->help = new rapid_stock_manager_help();
    }

    /**
     * Gets RSM version (2.0.0)
     * @return string RSM version (2.0.0)
     */
    public function get_version() {
        return $this->version;
    }

    /**
     * get environment
     * @return string
     */
    public function get_environment()
    {
        return $this->environment;
    }

    /**
     * @warehouse-function
     * @return string
     */
    public function get_table_woocommerce_product()
    {
        global $wpdb;
        return $wpdb->prefix . $this->db_table_woocommerce_product;
    }

    /**
     * Gets Attribute name for RMS version for wp options table
     * @return string Attribute name in options table
     */
    public function get_option_rsm_version()
    {
        return $this->option_db;
    }


    /** ADMIN ****************************************/


    /**
     * @param $settings_tabs
     * @return mixed
     */
    public function add_settings_tab($settings_tabs)
    {

        $settings_tabs['settings_rapid_stock_manager'] = __('Rapid Stock Manager', $this->id);
        return $settings_tabs;
    }

    /**
     * get settings tabs
     */
    public function settings_tab()
    {
        woocommerce_admin_fields($this->get_admin_settings());
        $this->render_donate();
    }

    public function render_donate() {
    ?>
        <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=GXBUW9QC5Y97J" target="_blank" >
            <img src="https://www.paypal.com/en_GB/i/btn/btn_donateCC_LG.gif" border="0" name="submit" title="Support rapid stock manager - PayPal - The safer, easier way to pay online!" alt="Donate" />
        </a>
    <?php
    }

    /**
     * update settings for wordpress
     */
    function update_settings() {
        woocommerce_update_options($this->get_admin_settings());
    }

    /**
     * update warehouse settings
     */
    public function update_warehouse_settings () {
        $this->warehouse->update_warehouse_settings($this);
    }

    /**
     * upper string replace
     * @return mixed
     */
    function upper($str = '') {
        $str = ucwords($str);
        return $str;

    }

    public function get_admin_settings() {

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
        ?>

        <script type="text/javascript">
            jQuery(function(){
                jQuery('#wc_settings_tab_rapid_sm_report_addd_warehouse').val('');
            });
        </script>

        <?php

        if ((count($arr_data1) > 4) AND isset($_POST['save']) AND !empty($_POST['wc_settings_tab_rapid_sm_report_addd_warehouse'])) {
            $w_msg = '<font style="color:red">'.__('You add 5 warehouses and maximum limit is 5. Please contact dev@ishouty.com for pricing for more.', $this->id).'</font>';
        }
        echo '
        <script>
        mixpanel.track("get_admin_settings");
        </script>
        ';

        $rsm_version = $this->get_version();
        if (!$this->audit->has_rsm_audit_table()) {
            $this->audit->add_rsm_table_audit();
            echo '<div class="error"><p>' . __('Stock Audit table does not exist! Adding table now...', $this->id) . '</p></div>';
        }
        $settings = array(
            'section_title' => array(
                'name' => __('Rapid Stock Manager', $this->id) . ' ' . $rsm_version,
                'type' => 'title',
                'desc' => '<a href="' . $this->plugin_homepage . '" class="" style="text-decoration:none;">' . __('Open Rapid Stock Manager &#x21E8;', $this->id) . '</a>',
                'id' => $this->settings_id_prefix . 'section_title'
            ),
            array(
                'name' => __('Default View', $this->id),
                'id' => $this->settings_id_prefix . 'default_view_options',
                'desc' => __('Default view to display when loading rapid stock manager', $this->id),
                'desc_tip' => true,
                'type' => 'select',
                'default' => $this->settings["products_view"],
                'options' => array(
                    'simple_product' => __('Simple Products View', $this->id),
                    'variation_product' => __('Variation Products View', $this->id)
                )
            ),

            array(
                'name' => __('Default Update Action', $this->id),
                'id' => $this->settings_id_prefix . 'default_update_action',
                'desc' => __('Default Update Action for updating products. Adjust will add or subtract from the current stock, if you are concerned if users are buying that product. Set will update the stock to the quantity given.', $this->id),
                'desc_tip' => true,
                'type' => 'select',
                'default' => $this->settings["update_action"],
                'options' => array(
                    'adjust' => __('Adjust', $this->id),
                    'set' => __('Set', $this->id),
                    'deduct' => __('Deduct', $this->id)
                )
            ),

            array(
                'name' => __('Default Product Order', $this->id),
                'id' => $this->settings_id_prefix . 'default_products_order',
                'desc' => __('Default Product ordering.', $this->id),
                'desc_tip' => true,
                'type' => 'select',
                'default' => $this->settings["products_order"],
                'options' => array(
                    'order_by_post_name_asc' => __('Product Ascending', $this->id),
                    'order_by_post_name_dsc' => __('Product Descending', $this->id),
                    'order_by_post_modified_asc' => __('Product Modified Ascending', $this->id),
                    'order_by_post_modified_dsc' => __('Product Modified Descending', $this->id),
                    //SKU is very complicated, not doing
                    //'order_by_sku_asc' => __('SKU Ascending', $this->id),
                    //'order_by_sku_dsc' => __('SKU Descending', $this->id)
                )
            ),

            array(
                'name' => __('Display Products Per Page', $this->id),
                'id' => $this->settings_id_prefix . 'products_to_display',
                'desc' => __('The amount of products that will be displayed per page.', $this->id),
                'desc_tip' => true,
                'type' => 'select',
                'default' => $this->settings["products_to_display"],
                'options' => array(
                    '5' => __('5', $this->id),
                    '10' => __('10', $this->id),
                    '15' => __('15', $this->id),
                    '20' => __('20', $this->id),
                    '25' => __('25', $this->id),
                    'all' => __('All', $this->id),
                )
            ),

            array(
                'name' => __('Stock Report Products Per Page ', $this->id),
                'id' => $this->settings_id_prefix . 'report_products_to_display',
                'desc' => __('The amount of products that will be displayed per page for reporting.', $this->id),
                'desc_tip' => true,
                'type' => 'select',
                'default' => $this->settings["report_products_to_display"],
                'options' => array(
                    '5' => __('5', $this->id),
                    '10' => __('10', $this->id),
                    '15' => __('15', $this->id),
                    '20' => __('20', $this->id),
                    '25' => __('25', $this->id),
                    '50' => __('50', $this->id),
                    '100' => __('100', $this->id),
                    '250' => __('250', $this->id),
                    '500' => __('500', $this->id),
                    '1000' => __('1000', $this->id),
                )
            ),

            /*
             * Complex, there are references in code to hardcoded URL which would need to be replaced to dynamic
            array(
                'name' => __('Default Menu Position ', $this->id),
                'id' => $this->settings_id_prefix . 'default_menu_position',
                'desc' => __('Where the RSM menu going to be displayed.', $this->id),
                'desc_tip' => true,
                'type' => 'select',
                'default' => $this->settings["default_menu_position"],
                'options' => array(
                    'woocommerce' => __('WooCommerce', $this->id),
                    'products' => __('Products', $this->id),
                )
            ),
            */

            array(
                'type' => 'sectionend',
                'id' => $this->settings_id_prefix . 'section_end_1'
            ),

            'title' => array(
                'name' => __('Table Colours and Highlighting', $this->id),
                'type' => 'title',
                'desc' => __('Change the default colors for grid highlighting and color indication.', $this->id),
                'id' => $this->settings_id_prefix . 'title_highlighting'
            ),

            array(
                'title' => __('Low Stock Notification Color', $this->id),
                'desc' => __('Color highlighting the stock which are low in stock. Default ', $this->id) . '<code>' . $this->default_settings["no_stock_highlight_color"] . '</code>.',
                'id' => $this->settings_id_prefix . 'no_stock_highlight_color',
                'type' => 'color',
                'css' => 'width:6em;',
                'default' => $this->settings["no_stock_highlight_color"],
                'autoload' => false
            ),

            array(
                'title' => __('Low Stock Notification Text Color', $this->id),
                'desc' => __('Text color when the stock are low in stock. Default ', $this->id) . '<code>' . $this->default_settings["no_stock_highlight_text_color"] . '</code>.',
                'id' => $this->settings_id_prefix . 'no_stock_highlight_text_color',
                'type' => 'color',
                'css' => 'width:6em;',
                'default' => $this->settings["no_stock_highlight_text_color"],
                'autoload' => false
            ),

            array(
                'title' => __('Low Stock Highlighting threshold', $this->id),
                'desc' => __('The minimum amount in stock when the highlighting starts. Default ', $this->id) . '<code>' . $this->default_settings["no_stock_highlight_threshold"] . '</code>.',
                'id' => $this->settings_id_prefix . 'no_stock_highlight_threshold',
                'type' => 'number',
                'css' => 'width:6em;',
                'default' => $this->settings["no_stock_highlight_threshold"]
            ),

            array(
                'title' => __('Row Color Highlight indicator', $this->id),
                'desc' => __('Color which states when a item has been changed within the row. Default ', $this->id) . '<code>' . $this->default_settings["row_indicator_highlight_color"] . '</code>.',
                'id' => $this->settings_id_prefix . 'row_indicator_highlight_color',
                'type' => 'color',
                'css' => 'width:6em;',
                'default' => $this->settings["row_indicator_highlight_color"],
                'autoload' => false
            ),

            array(
                'title' => __('Column Color Highlight Indicator', $this->id),
                'desc' => __('Color which states when a item has been changed within the Column. Variant products view. Default ', $this->id) . '<code>' . $this->default_settings["column_indicator_highlight_color"] . '</code>.',
                'id' => $this->settings_id_prefix . 'column_indicator_highlight_color',
                'type' => 'color',
                'css' => 'width:6em;',
                'default' => $this->settings["column_indicator_highlight_color"],
                'autoload' => false
            ),

            array(
                'title' => __('Search Filter Color', $this->id),
                'desc' => __('Search Filter when item has been matched. Default ', $this->id) . '<code>' . $this->default_settings["search_filter_highlight_color"] . '</code>.',
                'id' => $this->settings_id_prefix . 'search_filter_highlight_color',
                'type' => 'color',
                'css' => 'width:6em;',
                'default' => $this->settings["search_filter_highlight_color"],
                'autoload' => false
            ),

            array(
                'name' => __('Theme', $this->id),
                'id' => $this->settings_id_prefix . 'theme',
                'desc' => __('Default theme is dark.', $this->id),
                'desc_tip' => true,
                'type' => 'select',
                'default' => $this->settings["theme"],
                'options' => array(
                    'theme-dark' => __('Dark', $this->id),
                    'theme-light' => __('Light', $this->id)
                )
            ),

            array(
                'type' => 'sectionend',
                'id' => $this->settings_id_prefix . 'section_end_2'
            ),

            array(
                'name' => __('Stock Report', $this->id),
                'type' => 'title',
                'id' => $this->settings_id_prefix . 'title_stock_report'
            ),

            array(
                'name' => __('Column delimiter', $this->id),
                'id' => $this->settings_id_prefix . 'report_column_delimiter',
                'desc' => __('Delimiter for values in stock report.', $this->id),
                'desc_tip' => true,
                'type' => 'select',
                'default' => $this->settings["report_column_delimiter"],
                'options' => array(
                    'tab' => __('Tab', $this->id),
                    'semicolon' => __('Semicolon (;)', $this->id),
                    'pipe' => __('Pipe (|)', $this->id),
                    'comma' => __('Comma (,)', $this->id)
                )
            ),
            array(
                'type' => 'sectionend',
                'id' => $this->settings_id_prefix . 'section_end_3'
            )
        );

        //add warehouse settings
        $settings = array_merge($settings,$this->warehouse->getBackWarehouseSettings());

        return apply_filters(trim($this->settings_id_prefix, '_'), $settings);

    }


    /**
     * register admin scripts
     */
    public function my_plugin_admin_init()
    {
        $request_page = array_key_exists('page', $_REQUEST) ? $_REQUEST['page'] : '';
        $request_tab = array_key_exists('tab', $_REQUEST) ? $_REQUEST['tab'] : '';
        if
        (
            $request_page == 'update_stock_rapid'
            ||
            ($request_page == 'wc-settings' && $request_tab == 'settings_rapid_stock_manager')
        ) {

            /* Register our script. */
            wp_register_script('woocomerce-rapid-stock-manager-init', plugins_url('/assets/js/init.php?version=' . $this->get_version() . '&environment=' . $this->get_environment(), __FILE__));
            wp_enqueue_script('woocomerce-rapid-stock-manager-init');

            wp_register_script('woocomerce-rapid-stock-manager-analytics', plugins_url('/assets/js/analytics.js', __FILE__),array(),$this->get_version());
            wp_enqueue_script('woocomerce-rapid-stock-manager-analytics');

            wp_register_script('woocomerce-rapid-stock-manager-jquery-filter-script', plugins_url('/assets/js/jquery.filtertable.min.js', __FILE__),array(),$this->get_version());
            wp_enqueue_script('woocomerce-rapid-stock-manager-jquery-filter-script');

            wp_register_script('woocomerce-rapid-stock-manager-jquery-qtip2-script', plugins_url('/assets/js/jquery.qtip2.js', __FILE__),array(),$this->get_version());
            wp_enqueue_script('woocomerce-rapid-stock-manager-jquery-qtip2-script');

            wp_register_script('woocomerce-rapid-stock-manager-ZeroClipboard-script', plugins_url('/assets/js/ZeroClipboard.js', __FILE__),array(),$this->get_version());
            wp_enqueue_script('woocomerce-rapid-stock-manager-ZeroClipboard-script');

            wp_register_script('woocomerce-rapid-stock-manager-script', plugins_url('/assets/js/woocommerce-rapid-stock-manager.js', __FILE__),array(),$this->get_version());
            wp_enqueue_script('woocomerce-rapid-stock-manager-script');

            wp_register_script('woocomerce-rapid-warehouse-manager-script', plugins_url('/assets/js/woocommerce-rapid-warehouse-manager.js', __FILE__),array(),$this->get_version());
            wp_enqueue_script('woocomerce-rapid-warehouse-manager-script');

            wp_enqueue_script('jquery-ui-datepicker');
            wp_enqueue_style('jquery-ui-datepicker-css', plugins_url(basename(dirname(__FILE__))) . '/assets/css/jquery-ui-1.10.4.custom.min.css',array(),$this->get_version());

            wp_enqueue_style('woocomerce-rapid-stock-manager-qtip-styles', plugins_url(basename(dirname(__FILE__))) . '/assets/css/jquery.qtip2.min.css',array(),$this->get_version());
            wp_enqueue_style('woocomerce-rapid-stock-manager-styles-icons', plugins_url(basename(dirname(__FILE__))) . '/assets/css/font-awesome.min.css',array(),$this->get_version());
            wp_enqueue_style('woocomerce-rapid-stock-manager-styles', plugins_url(basename(dirname(__FILE__))) . '/assets/css/admin.css',array(),$this->get_version());


        }
    }

    /**
     * register admin menu
     */
    function register_admin_menu()
    {
        $menu_placement = get_option($this->settings_id_prefix . 'default_menu_position');
        if (empty($menu_placement)) {
            $menu_placement = $this->default_settings['default_menu_position'];
        }
        if( $menu_placement === "products" )
            add_submenu_page( 'edit.php?post_type=product', __('Rapid Stock Manager', $this->id), __('Rapid Stock Manager', $this->id), 'manage_product_terms', 'update_stock_rapid', array( $this, 'rapid_stock_manager' ) );
        else
            add_submenu_page('woocommerce', __('Rapid Stock Manager', $this->id), __('Rapid Stock Manager', $this->id), 'manage_woocommerce', 'update_stock_rapid', array($this, 'rapid_stock_manager'));
    }


    function woocommerce_product_custom_tab($tabs)
    {

        global $post, $product;

        $custom_tab_options = array(
            'enabled' => get_post_meta($post->ID, 'custom_tab_enabled', true),
            'title' => get_post_meta($post->ID, 'custom_tab_title', true),
            'content' => get_post_meta($post->ID, 'custom_tab_content', true),
        );
        if ($custom_tab_options['enabled'] != 'no') {
            $tabs['custom-tab-first'] = array(
                'title' => $custom_tab_options['title'],
                'priority' => 25,
                'callback' => 'custom_product_tabs_panel_content',
                'content' => $custom_tab_options['content']
            );
        }

        return $tabs;
    }

    function custom_product_tabs_panel_content($key, $custom_tab_options)
    {
        echo '<h2>' . $custom_tab_options['title'] . '</h2>';
        echo $custom_tab_options['content'];
    }

    function custom_tab_options_tab_spec()
    {

        echo '<li class="warehouse_product_options"><a href="#warehouse_tab_options">' . __('Warehouse Inventory', $this->id) . '</a></li>';

    }

    /**
     * Get default settings else get it from user settings
     * @return array
     */
    public function get_settings_from_wc_admin()
    {
        $settings = array();

        //no_stock_highlight_color
        $settings["no_stock_highlight_color"] = get_option($this->settings_id_prefix . 'no_stock_highlight_color');
        if (empty($settings["no_stock_highlight_color"])) {
            $settings["no_stock_highlight_color"] = $this->default_settings['no_stock_highlight_color'];
        }
        //end no_stock_highlight_color

        //no_stock_highlight_threshold
        $threshold = get_option($this->settings_id_prefix . 'no_stock_highlight_threshold');
        $settings["no_stock_highlight_threshold"] = intval($threshold);
        if (empty($threshold) || $settings['no_stock_highlight_threshold'] < 0) {
            $settings["no_stock_highlight_threshold"] = $this->default_settings['no_stock_highlight_threshold'];
        }
        //end no_stock_highlight_threshold

        //no_stock_highlight_text_color
        $settings["no_stock_highlight_text_color"] = get_option($this->settings_id_prefix . 'no_stock_highlight_text_color');
        if (empty($settings["no_stock_highlight_text_color"])) {
            $settings["no_stock_highlight_text_color"] = $this->default_settings['no_stock_highlight_text_color'];
        }
        //end no_stock_highlight_text_color

        //products_to_display
        $settings["products_to_display"] = get_option($this->settings_id_prefix . 'products_to_display');
        if (empty($settings["products_to_display"])) {
            $settings["products_to_display"] = $this->default_settings['products_to_display'];
        }
        //end products_to_display

        //report_products_to_display for reporting
        $settings["report_products_to_display"] = get_option($this->settings_id_prefix . 'report_products_to_display');
        if (empty($settings["report_products_to_display"])) {
            $settings["report_products_to_display"] = $this->default_settings['report_products_to_display'];
        }
        //end products_to_display

        //default_update_action
        $settings["update_action"] = get_option($this->settings_id_prefix . 'default_update_action');
        if (empty($settings["update_action"])) {
            $settings["update_action"] = $this->default_settings['update_action'];
        }
        //end default_update_action

        //default_products_order
        $settings["products_order"] = get_option($this->settings_id_prefix . 'default_products_order');

        $sort_by = array_key_exists('sort-by', $_REQUEST) ? $_REQUEST['sort-by'] : '';
        if (empty($sort_by)) {

            if (empty($settings["products_order"])) {
                $settings["products_order"] = $this->default_settings['products_order'];
            }

        } else {
            $settings["products_order"] = $sort_by;
        }
        //end default_products_order

        //default_view_options
        $settings["products_view"] = get_option($this->settings_id_prefix . 'default_view_options');
        if (empty($settings["products_view"])) {
            $settings["products_view"] = $this->default_settings['products_view'];
        }
        //end default_view_options

        //row_indicator_highlight_color
        $settings["row_indicator_highlight_color"] = get_option($this->settings_id_prefix . 'row_indicator_highlight_color');
        if (empty($settings["row_indicator_highlight_color"])) {
            $settings["row_indicator_highlight_color"] = $this->default_settings['row_indicator_highlight_color'];
        }
        //end row_indicator_highlight_color

        //column_indicator_highlight_color
        $settings["column_indicator_highlight_color"] = get_option($this->settings_id_prefix . 'column_indicator_highlight_color');
        if (empty($settings["column_indicator_highlight_color"])) {
            $settings["column_indicator_highlight_color"] = $this->default_settings['column_indicator_highlight_color'];
        }
        //end column_indicator_highlight_color

        //search_filter_highlight_color
        $settings["search_filter_highlight_color"] = get_option($this->settings_id_prefix . 'search_filter_highlight_color');
        if (empty($settings["search_filter_highlight_color"])) {
            $settings["search_filter_highlight_color"] = $this->default_settings['search_filter_highlight_color'];
        }
        //end search_filter_highlight_color

        //report_column_delimiter
        $settings["report_column_delimiter"] = get_option($this->settings_id_prefix . 'report_column_delimiter');
        if (empty($settings["report_column_delimiter"])) {
            $settings["report_column_delimiter"] = $this->default_settings['report_column_delimiter'];
        }
        //end report_column_delimiter

        // enable warehouse
        $settings["report_enable_warehouse"] = get_option($this->settings_id_prefix . 'report_enable_warehouse');
        if (empty($settings["report_enable_warehouse"])) {
            $settings["report_enable_delimiter"] = $this->default_settings['report_enable_warehouse'];
        }
        //end enable warehouse

        // add warehouse
        $settings["report_addd_warehouse"] = get_option($this->settings_id_prefix . 'report_addd_warehouse');
        if (empty($settings["report_addd_warehouse"])) {
            $settings["report_add_warehouse"] = $this->default_settings['report_addd_warehouse'];
        }
        //end add warehouse

        // Edit warehouse
        $settings["report_edit_warehouse"] = get_option($this->settings_id_prefix . 'report_edit_warehouse');
        if (empty($settings["report_edit_warehouse"])) {
            $settings["report_edit_warehouse"] = $this->default_settings['report_edit_warehouse'];
        }
        //end editwarehouse

        // RSM menu position
        $settings["default_menu_position"] = get_option($this->settings_id_prefix . 'default_menu_position');
        if (empty($settings["default_menu_position"])) {
            $settings["default_menu_position"] = $this->default_settings['default_menu_position'];
        }
        //end RSM menu position


        //theme
        $settings["theme"] = get_option($this->settings_id_prefix . 'theme');
        if (empty($settings["theme"])) {
            $settings["theme"] = $this->default_settings['theme'];
        }
        //end theme

        return $settings;


    }

    /** END ADMIN ****************************************/


    /** START REPORT ****************************************/

    /**
     * Returns formatted title for all available product attributes
     * @param array $attributes Product attributes
     * @return string Formatted string
     */
    public function get_attribute_title_for_report($attributes = null)
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

            if ($attribute_title_sql_result && property_exists($attribute_title_sql_result, 'attribute_label')) {
                $attribute_title .= $attribute_title_sql_result->attribute_label;
            } else {
                $attribute_title .= $attribute_name_clean;
            }

            $attribute_value_slug = get_term_by('slug', $attribute_value, str_replace('attribute_', '', $attribute_name));
            if ($attribute_value_slug) {
                $attribute_title .= ": " . $attribute_value_slug->name;
            } else {
                $attribute_title .= ": " . $attribute_value;
            }

            if ($attributes_count < $attributes_count_total) {
                $attribute_title .= " & ";
            }
        }
        return $attribute_title;
    }

    /**
     * Returns one formatted product for report - as an array with attributes
     * @param array $product Product object to transform to an array
     * @return array
     */
    public function get_report_row($product = null)
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
            $row["variant"] = $this->get_attribute_title_for_report($attributes);
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
     * Returns data for report rendering in array format
     * @param int $low_stock_threshold Minimum in stock to report on
     * @param int $low_stock_threshold Minimum in stock to report on
     * @return array Data to be parsed and displayed. Empty array if no data.
     */
    public function get_report_data_table($low_stock_threshold = 5)
    {
        global $wpdb;

        $sql = "SELECT * FROM {$wpdb->posts} AS post
                WHERE post.post_type = %s AND post.post_status = %s";

        $page_number = isset($_REQUEST["page_number"]) ? $_REQUEST["page_number"] : 1;
        $limit = $this->settings["report_products_to_display"];
        $offset = ($page_number - 1) * $limit;

        $sql .= " LIMIT %d,%d";

        $params = array('product', 'publish', $offset, $limit);
        $sql_prepared = $wpdb->prepare($sql, $params);
        $products = $wpdb->get_results($sql_prepared);

        $table = array();
        $main_products_count = 0;
        $variants_count = 0;
        $low_stock_count = 0;
        foreach ($products as $product) {
            $product_id = $product->ID;
            $product = wc_get_product($product_id);
            $row = $this->get_report_row($product);
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
                $row = $this->get_report_row($children_product);
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
            "page_number" => $page_number
        );
    }

    /**
     * Returns formatted string for textarea - list of products with variants as rows
     * @param array $table_data Data to transfer to rows and columns
     * @param string $delimiter Default is Tab
     * @param string $line_delimiter Line/Row Default is \n
     * @return string
     */
    public function get_report_content_list($table_data = array(), $delimiter = "\t", $line_delimiter = "\n")
    {
        if (sizeof($table_data) < 1) {
            return __('No products available to list.', $this->id);
        }
        $output = __('SKU', $this->id) . $delimiter . __('Product', $this->id) . $delimiter . __('Variant', $this->id) . $delimiter . __('Stock', $this->id) . $line_delimiter;
        foreach ($table_data as $row) {
            $output .= $row["sku"] . $delimiter . $row["title"] . $delimiter . $row["variant"] . $delimiter . $row["stock"] . $line_delimiter;
        }
        return $output;
    }


    /**
     * Returns formatted string for textarea - grid of products with variants as columns
     * @param array $table_data Data to transfer to rows and columns
     * @param string $delimiter Default is Tab
     * @param string $line_delimiter Line/Row Default is \n
     * @return string
     */
    public function get_report_content_grid($table_data = array(), $delimiter = "\t", $line_delimiter = "\n")
    {
        if (sizeof($table_data) < 1) {
            return __('No products available to list.', $this->id);
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

        //print headers with variant names
        $output = __('SKU', $this->id) . $delimiter . __('Product Name', $this->id) . $delimiter;
        foreach ($variants_headers as $header) {
            $output .= $header . $delimiter;
        }
        $output .= $line_delimiter;

        //loop through all available products
        foreach ($table_data as $product) {
            //because every row=one main product, we work only with the main products
            if (!$product["main"]) {
                continue;
            }
            //output information about the main product
            $output .= $product["sku"] . $delimiter . $product["title"] . $delimiter . $product["stock"] . $delimiter;
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
                    $output .= $variant["stock"] . $delimiter;
                }
                if (!$had_variant && !$is_first) {
                    $output .= $delimiter;
                }
                $is_first = false;
            }
            $output .= $line_delimiter;
        }

        return $output;
    }

    /** END REPORT ****************************************/

    /**
     * Loads plugin translation to another language
     */
    public function localisation_load_textdomain()
    {
        load_plugin_textdomain($this->id, false, plugin_basename(dirname(__FILE__)) . '/languages');
    }

    /** HELPERS ****************************************/

    /**
     * debug tool
     * @param $message
     */
    function debug_html_log($message)
    {
        echo '<!-- DEBUG: ';
        print_r($message);
        echo ' -->';
    }


    public function screen_log($message)
    {
        echo '[' . time() . '] ' . $message . "\n";
    }

    /** END HELPERS ****************************************/

    /** ROUTING & PARAM CHECKING ****************************************/

    public function get_request_param($param_key = null, $request_type = 'request')
    {

        if (!isset($param_key) || $param_key === null) {
            return;
        }
        if (!$request_type || !in_array($request_type, array('request', 'get', 'post'))) {
            return;
        }
        switch ($request_type) {
            case 'request':
                if ($_REQUEST && array_key_exists($param_key, $_REQUEST) && isset($_REQUEST[$param_key])) {
                    return $_REQUEST[$param_key];
                }
                return;
                break;
            case 'get':
                if ($_GET && array_key_exists($param_key, $_GET) && isset($_GET[$param_key])) {
                    return $_GET[$param_key];
                }
                return;
                break;
            case 'post':
                if ($_POST && array_key_exists($param_key, $_POST) && isset($_POST[$param_key])) {
                    return $_POST[$param_key];
                }
                return;
                break;
            default:
                return;
        }
    }

    /**
     * generate a list params on url
     * @param boolean $omit_page
     * @return string
     */
    public function get_url_admin_params($omit_page = false, $omit_search_value = false)
    {

        $url = 'page=update_stock_rapid';

        $temp_param = $this->get_request_param('rapid-selector-view');
        if (!empty($temp_param)) {
            $url .= '&rapid-selector-view=' . $this->get_request_param('rapid-selector-view');
        }

        $temp_param = $this->get_request_param('page_number');
        if (!empty($temp_param) && !$omit_page) {
            $url .= '&page_number=' . $this->get_request_param('page_number');
        }

        $temp_param = $this->get_request_param('search');
        if (!empty($temp_param)) {
            $url .= '&search=' . $this->get_request_param('search');
        }

        $temp_param = $this->get_request_param('attributes');
        if (!empty($temp_param)) {
            $url .= '&attributes=' . $this->get_request_param('attributes');
        }

        $temp_param = $this->get_request_param('search-value');
        if (!empty($temp_parcolumn_indicator_highlight_coloram) && !$omit_search_value) {
            $url .= '&search-value=' . $this->get_request_param('search-value');
        }

        $temp_param = $this->get_request_param('sort-by');
        if (!empty($temp_param)) {
            $url .= '&sort-by=' . $this->get_request_param('sort-by');
        }

        return $url;

    }

    /**
     * check requests items are not empty else fill in default
     */
    function validate_requests_params()
    {

        $selector_view = array_key_exists('rapid-selector-view', $_REQUEST) ? $_REQUEST['rapid-selector-view'] : '';
        if (empty($selector_view)) {

            //check
            $user_option_view = $this->settings["products_view"];

            if (!empty($user_option_view)) {
                $_REQUEST['rapid-selector-view'] = $user_option_view;
            } else {
                $_REQUEST['rapid-selector-view'] = 'simple_product';
            }

        }

        $sort_by = array_key_exists('sort-by', $_REQUEST) ? $_REQUEST['sort-by'] : '';
        if (empty($sort_by)) {

            $_REQUEST['sort-by'] = 'order_by_post_name_asc';

        }

    }

    /** END & PARAM CHECKING ****************************************/

    /** COLORS & STYLING ****************************************/

    /**
     * @param int $threshold
     * @return float|int
     */
    public function get_color_step($threshold = 10)
    {
        if (!$threshold || $threshold < 1) {
            return 0;
        }
        $max = 255;
        $threshold_adjust = 5; //to make the colour visible
        $step = $max / ($threshold + $threshold_adjust);
        return $step;
    }



    /**
     * @param $hex
     * @param $steps
     * @return string
     */
    public function get_adjusted_color_brightness($hex, $steps)
    {
        // Steps should be between -255 and 255. Negative = darker, positive = lighter
        $steps = max(-255, min(255, $steps));

        // Normalize into a six character long hex string
        $hex = str_replace('#', '', $hex);
        if (strlen($hex) == 3) {
            $hex = str_repeat(substr($hex, 0, 1), 2) . str_repeat(substr($hex, 1, 1), 2) . str_repeat(substr($hex, 2, 1), 2);
        }

        // Split into three parts: R, G and B
        $color_parts = str_split($hex, 2);
        $return = '#';

        foreach ($color_parts as $color) {
            $color = hexdec($color); // Convert to decimal
            $color = max(0, min(255, $color + $steps)); // Adjust color
            $return .= str_pad(dechex($color), 2, '0', STR_PAD_LEFT); // Make two char hex code
        }

        return $return;
    }


    /**
     * Custom styles get generated
     */
    function get_style_stock_manager()
    { ?>
        <style type="text/css">
            /* row highlighting  */
            .woocommerce-rapid-stock-manager tr.requires-updating {
                border-left: 5px solid <?php echo $this->settings['row_indicator_highlight_color']?>;
                border-right: 5px solid <?php echo $this->settings['row_indicator_highlight_color']?>;
            }

            .woocommerce-rapid-stock-manager tr.requires-updating .action-cell .action-link {
                color: <?php echo $this->settings['row_indicator_highlight_color']?>;
            }

            /* column  highlighting  */
            .woocommerce-rapid-stock-manager tr td.requires-updating .cell-highlighter {
                border-top: 3px solid <?php echo $this->settings['column_indicator_highlight_color']?>;
                border-bottom: 3px solid <?php echo $this->settings['column_indicator_highlight_color']?>;
            }

            .woocommerce-rapid-stock-manager tr td.requires-updating .wc-qty-change {
                border: 1px solid <?php echo $this->settings['column_indicator_highlight_color']?>;
            }

            .woocommerce-rapid-stock-manager tr td.requires-updating .input-indicator {
                color: <?php echo $this->settings['column_indicator_highlight_color']?>;
            }

            /* generic table styling */
            table.woocommerce-rapid-stock-manager-table {
                border-collapse: collapse;
            }

            /* filter-table specific styling */
            .woocommerce-rapid-stock-manager-table td.alt {
                background-color: <?php echo $this->settings["search_filter_highlight_color"]; ?>;
            }
        </style> <?php
    }

    /** END COLORS ****************************************/
}