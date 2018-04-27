<?php



/**
 * @author ishouty ltd. London
 * @date 2017
 */
class rapid_warehouse_manager_transfer {

    /**
     * @var string Id of this plugin for internationalisation used in __ and _e
     */
    public $id = 'woocommerce-rapid-stock-manager';

    public $helpMessages = array(
        "transfer_form" => 'Transform form will allow you to transfer stock between multiple warehouses.
		Select From and To which warehouse and then transfer quantity.
		All transactions will be taken from the Main Warehouse.'
    );

    /**
     * class for rapid stock manager
     * @var
     */
    public $rapidStockManager;

    function __construct ($rapidStockManager) {
        $this->rapidStockManager = $rapidStockManager;
    }

    /**
     * @var string Warehouse table name (without wp prefix)
     */
    public $db_table_woocommerce_product = "rsm_stock_woocommerce_product_table";

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
     * stock report - render
     */
    public function display_reference_report() { ?>

        <div>
            <h3 style="font-size: 1.1em;"> <?php _e('Transfer Reference No Audit', $this->id); ?></h3>
            <p> <?php _e('Search all transfers with reference number, will allow to print transfer form for operations to make sure managers who control the stock are responsible with these transfers.', $this->id); ?> </p>
            <label class="label_reference_no" for="reference_no"> <?php _e('Reference No', $this->id); ?> </label>
            <input type="text" id="reference_no" name="reference_no" placeholder="<?php _e('Transfer Reference No', $this->id) ?>" value="<?php echo (isset($_POST["reference_no"]) ? $_POST["reference_no"] : ""); ?>" />
            <input type="submit" value="Generate audit" class="button"/>

            <br>
        </div>


        <?php
    }


    /**
     * all_transfers - find all transfers to find out to the form
     * warehouse_to - Find the first transfer to of the warehouse
     * @param $reference_no
     * @return array|mixed|null|object
     */
    private function get_data_transfer_form($reference_no, $condition) {
        global $wpdb;

        $table = $this->rapidStockManager->audit->get_table_audit();

        if ($condition == 'all_transfers') {
            $where_query = ' WHERE reference_no = %s AND action = "transfer_from"';
            $where_query = $wpdb->prepare($where_query, $reference_no);
            $sql = 'SELECT * FROM ' . $table . ' ' . $where_query . ' ORDER BY id ASC';
        }
        elseif ($condition == 'warehouse_to') {
            $where_query = ' WHERE reference_no = %s AND action = "transfer_to"';
            $where_query = $wpdb->prepare($where_query, $reference_no);
            $sql = 'SELECT * FROM ' . $table . ' ' . $where_query . ' ORDER BY id ASC LIMIT 0,1';
        }
        else {
            return false;
        }
        
        $get_audit = $wpdb->get_results($sql, ARRAY_A);

        return $get_audit;
    }

    /**
     * render the transfer form to display to clients
     * @param $reference_no
     */
    public function render_print_transfer_form ($reference_no) {

        $get_audit = $this->get_data_transfer_form($reference_no, 'all_transfers');
        $get_warehouse_to = $this->get_data_transfer_form($reference_no, 'warehouse_to');

        if ( !is_admin() || (empty($get_audit) && empty($get_warehouse_to))) {
            return;
        }
        ?>

            <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
            "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
            <html>
            <head>
                <meta http-equiv="Content-type" content="text/html; charset=utf-8" />
                <title><?php _e('Transfer Warehouse Form',$this->id);?></title>
                <script src="<?php echo substr(__DIR__,strpos(__DIR__,'/wp-content/')); ?>/../assets/js/analytics.js"></script>
            </head>
            <style>
                body {
                    padding: 0;
                    margin: 0;
                    color: #444;
                    font-family: "Open Sans",sans-serif;
                }

                h3 {
                    text-align: center;
                }

                table {
                    width: 100%;
                    padding: 0px;
                }

                table th {
                    text-align: left;
                }

                .container {
                    width: 100%;
                }

            </style>
            
            <script>
                mixpanel.track("warehouse_print_transfer_form");
            </script>

            <script type="text/javascript">
                window.print();
            </script>

            <body>

            <div class="container">

                <h3><?php _e('Transfer Warehouse Form',$this->id);?></h3>

                <table>
                    <tr>

                        <td width="50%">
                            <b><?php _e('From:',$this->id);?></b>  <?php echo $get_audit[0]["warehouse"]; ?>
                            <br>
                            <b><?php _e('To:',$this->id);?></b> <?php echo $get_warehouse_to[0]["warehouse"]; ?>
                        </td>

                        <td width="50%">
                            <b><?php _e('Reference No:',$this->id);?></b> <?php echo $reference_no; ?><br>
                            <b><?php _e('Date:',$this->id);?></b> <?php echo $get_audit[0]["timestamp"];?>
                        </td>
                    </tr>
                </table>

                <br>

                <table cellpadding="0" cellspacing="0">
                    <thead>
                    <tr style="background-color:gray; color: white;">
                        <th><?php _e('SKU',$this->id);?></th>
                        <th><?php _e('Product Name',$this->id);?></th>
                        <th><?php _e('Price',$this->id);?></th>
                        <th><?php _e('Quantity',$this->id);?></th>
                        <th><?php _e('Sub Total',$this->id);?></th>
                    </tr>

                    </thead>
                    <body>


                     <?php
                        $totalQuantity = 0;
                        $totalPrice = 0;
                        $subTotal = 0;
                        foreach ($get_audit as $audit) {
                            $product = wc_get_product($audit["product_id"]);
                            if( $product ){
                                $product_price = $product->get_price();
                                $subTotal = $audit["action_amount"] * $product_price;
                            }else{
                                $product_price = '-';
                            }
                        ?>

                        <tr>
                            <th><?php echo $audit["product_sku"]; ?> </th>
                            <td><?php echo $audit["product_name"]; ?></td>
                            <td><?php echo $product_price; ?></td>
                            <td><?php echo $audit["action_amount"]; ?></td>
                            <td><?php echo $subTotal; ?></td>
                        </tr>

                        <?php
                            $totalQuantity += $audit["action_amount"];
                            $totalPrice += $subTotal;
                        ?>

                        <?php } ?>

                         <tr>
                             <th><b><?php echo _e("Total", $this->id); ?></b> </th>
                             <td></td>
                             <td></td>
                             <td><?php echo $totalQuantity; ?></td>
                             <td><?php echo $totalPrice; ?></td>
                         </tr>

                    </body>
                </table>

                <br>
                <table>
                    <tr>
                        <td width="50%">
                            <?php _e('Prepared by:',$this->id);?><br>
                            <?php _e('Signature',$this->id);?>
                        </td>

                        <td width="50%">
                            <?php _e('Checked by:',$this->id);?><br>
                            <?php _e('Signature:',$this->id);?>
                        </td>
                    </tr>
                </table>

            </div>

            </body>
            </html>

        <?php 

        die();
    }


    // Transfer Form //////////////////////////////////////////

    /**
     * @warehouse-action
     * product pop up
     */
    public function product_popUp()
    {
        ?>

        <div id="warehouse-transfer-popup" class="modal-box"
             data-error-same-warehouse="<?php _e('You cannot transfer stock from the same warehouse!', $this->id); ?>"
             data-error-valid-warehouse="<?php _e('Select valid warehouse!', $this->id); ?>"
             data-success-transferred="<?php _e('Stock has been successfully transferred!', $this->id); ?>"
             data-no-stock-transferable="<?php _e('There is not enough stock to transfer that quantity entered!', $this->id); ?>"
             data-transfer-success="<?php _e('Transferred Success!', $this->id); ?>"
             data-transfer-failed="<?php _e('Transferred Failed!', $this->id); ?>"

        >
            <header>
                <h3>
                    <?php _e('TRANSFER FORM', $this->id); ?>
                    <i class="fa fa-question-circle tooltip-qtip"
                       data-qtip-title="<?php _e('Transfer Form', $this->id); ?>"
                       data-qtip-content="<?php _e($this->helpMessages['transfer_form'], $this->id); ?>"></i>
                    <a href="#" id="warehouse_close_popup"
                       class="btn btn-small js-modal-close"><?php _e('Close', $this->id); ?></a>
                </h3>
            </header>
            <div class="modal-body">
                <p class="error-message"></p>

                <p class="success-message"></p>
                <?php $allWarehouse = unserialize(get_option('wc_settings_tab_rapid_sm_report_addd_warehouse'));
                if (!empty($allWarehouse)) {
                    array_unshift($allWarehouse, 'main stock');
                }
                ?>
                <div class="box-container">
                    <div class="box-from">
                        <label for="fname"><?php _e('From warehouse:', $this->id) ?></label>

                        <select class="to" id="woocommerce_product_from">
                            <option value="0"><?php _e('Select Warehouse Transfer From', $this->id); ?></option>
                            <?php if (!empty($allWarehouse)) {
                                foreach ($allWarehouse as $warehouse) {
                                    $warehouse = trim($warehouse, ' ');
                                    $vals = str_replace(' ', '_', $warehouse);
                                    ?>
                                    <option value="<?php echo $vals; ?>"><?php echo ucwords($warehouse); ?></option>
                                <?php }
                            } ?>

                        </select>
                    </div>
                    <div class="box-to dropdown-warehouses">
                        <label for="fname"><?php _e('To warehouse:', $this->id) ?></label>
                        <select class="to" id="woocommerce_product_to">
                            <option value="0"><?php _e('Select Warehouse Transfer To', $this->id); ?></option>
                            <?php if (!empty($allWarehouse)) {

                                foreach ($allWarehouse as $warehouse) {

                                    $warehouse = trim($warehouse, ' ');
                                    $vals = str_replace(' ', '_', $warehouse);
                                    ?>
                                    <option value="<?php echo $vals; ?>"><?php echo ucwords($warehouse); ?></option>
                                <?php }
                            } ?>
                        </select>
                    </div>
                </div>

                <div class="box-container">
                    <p class="marginZero"></p>
                    <label for="fname"><?php _e('Search in selected warehouse:', $this->id) ?></label>
                    <input type="text" id="productTitle" name="productTitle" class="searching"
                           placeholder="Type in sku or title">
                </div>

                <div class="box-container products-transfer-warehouse-container">
                    <table border="0;" cellpadding="0" class="products-transfer-warehouse">
                        <tbody>
                        <tr style="background-color:gray;height:31px;">
                            <th class="sku"> <?php _e('SKU', $this->id); ?></th>
                            <th class="product_name"> <?php _e('Product Name', $this->id); ?></th>
                            <th style="width:50px;"> <?php _e('Type', $this->id) ?> </th>
                            <th style="width:50px;"> <?php _e('Warehouse', $this->id) ?> </th>
                            <th style="width:50px;"> <?php _e('Qty', $this->id) ?> </th>
                            <th style="width:50px;"> <?php _e('Transfer', $this->id) ?> </th>
                            <th style="width:50px;"> <?php _e('Action', $this->id) ?> </th>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <div class="loading-container" style="display: none;">
                    <?php _e('Loading', $this->id) ?> <i class="fa fa-refresh" aria-hidden="true"></i>
                </div>

                <div class="box-container warehouse-products-list">
                    <table border="0;" cellpadding="0" class="warehouseproductLists" style="display: none;">
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>

            <footer>
                <a href="<?php echo 'http://' . $_SERVER['HTTP_HOST'] . str_replace( "admin-ajax.php", "admin.php",$_SERVER['REQUEST_URI']) . '?&print=print-transfer&reference_no='?>" target="_blank" class="btn btn-small js-modal-close print-window disable_a_href">
                    <i class="fa fa-print" aria-hidden="true"></i> <?php _e('Print', $this->id); ?></a>

                <a href="#" class="update-stock-transfer-btn btn btn-small btn-update disable_a_href"><i
                        class="fa fa-bolt" aria-hidden="true"></i> <?php _e('Update', $this->id); ?></a>

            </footer>
        </div>
        <?php

    }

    /**
     * @popup-transferform
     * @param string $searchKey
     * @param string $warehouse
     * @return array
     */
    public function search_warehouse_product($searchKey = '', $warehouse = '')
    {
        $output = array();
        if (!empty($searchKey) AND !empty($warehouse)) {
            global $wpdb;
            $table1 = $this->get_table_woocommerce_product();
            $table2 = $wpdb->prefix . 'postmeta';
            $table3 = $wpdb->prefix . 'posts';
            $groupBy = "GROUP BY t2.`post_id`";
            if ($warehouse == 'main_stock') {
                $condition = "t3.`post_status`= 'publish'";
                $sql = "SELECT t3.*, t3.`ID` as `product_id`, t2.* FROM `$table3` t3 LEFT JOIN `$table2` t2 ON t3.ID = t2.post_id WHERE $condition AND ((t2.meta_key='_sku' AND t2.meta_value LIKE '%%%s%%') OR (t3.post_title LIKE '%%%s%%')) $groupBy";
                $sql .= " LIMIT 40";
                $query = $wpdb->get_results($wpdb->prepare($sql,array($wpdb->esc_like($searchKey),$wpdb->esc_like($searchKey))));

            } else {
                $condition = "t3.`post_status`= 'publish' AND t1.`meta_key`= '$warehouse'";
                $sql = "SELECT t1.*, t2.*, t3.* FROM `$table1` t1 LEFT JOIN `$table2` t2 ON t1.product_id= t2.post_id LEFT JOIN `$table3` t3 ON t1.product_id= t3.ID WHERE $condition AND ((t2.meta_key='_sku' AND t2.meta_value LIKE '%%%s%%') OR (t3.post_title LIKE '%%%s%%')) $groupBy";
                $sql .= " LIMIT 40";
                $query = $wpdb->get_results($wpdb->prepare($sql,array($wpdb->esc_like($searchKey),$wpdb->esc_like($searchKey))));

            }
            if ($wpdb->num_rows) {
                foreach ($query as $result) {
                    $product = wc_get_product($result->product_id);
                    if ($warehouse == 'main_stock') {
                        if ($product->managing_stock()) {
                            $output[] = $result->product_id;
                        }
                    } else {
                        $output[] = $result->product_id;
                    }
                }
            }
        } else {
            $output = $this->get_product_warehouse($warehouse);
        }
        return $output;
    }

    /**
     * displays warehouse product need to transfer
     * @param int $productId
     * @param string $warehouse
     * @param int $parentId
     * @return string
     */
    public function get_warehouse_product($productId = 0, $warehouse = '', $parentId = 0)
    {
        $output = '';

        if (!empty($productId) AND !empty($warehouse)) {

            $product = wc_get_product($productId);
            $product_sku = $product->get_sku();
            $product_type = $product->product_type;
            $product_title = $product->get_title();
            $product_stock = $product->get_stock_quantity();
            $price = $product->price;
            $price = sprintf('%0.2f', $price);

            if ($product_type === 'variation') {
                $product_title = $product->get_formatted_name();
            } else {
                $product_title = Ucfirst($product_sku) . '- ' . $product_title . '- ' . $price;
            }

            $product_image = $product->get_image();

            if ($warehouse == 'main_stock') {
                $warehouse_value = $product_stock;
            } else {
                $warehouse_value = $this->rapidStockManager->warehouse->get_warehouse_value_by_productId($productId, $warehouse);
            }

            $table_body = '<tr class="' . $warehouse . '-' . $productId . '" data-product-id="' . $productId . '" data-warehouse="'. $warehouse .'">';
            $table_body .= '<td class="product-image">' . $product_image . '<p>' . (!empty($product_sku) ? $product_sku : "N/A") . '</p></td>';
            $table_body .= '<td class="product_name"><b>' . (!empty($product_title) ? ucwords($product_title) : "N/A") . '</b></td>';
            $table_body .= '<td class="product-type">' . $product_type . '</td>';
            $table_body .= '<td>' . $warehouse . '</td>';
            $table_body .= '<td class="quantity-remaining">' . $warehouse_value . '</td>';
            $table_body .= '<td class="warehouses-quantity"><input type="text" value="" data-warehouse="' . $warehouse . '" data-warehouse-qty="' . $warehouse_value . '" id="product-qty-value' . $productId . '" data-value="' . $productId . '" data-parent= "' . $parentId . '" >';
            $table_body .= '<span class="status"></span></td>';
            $table_body .= '<td class="action"><i class="remove-product-transfer fa fa-times" aria-hidden="true" title="'.__('Remove from list', $this->id).'"></i></td>';
            $table_body .= '</tr>';

            $output = $table_body;
        }

        return $output;

    }


    // Transfer Form //////////////////////////////////////////

}
?>