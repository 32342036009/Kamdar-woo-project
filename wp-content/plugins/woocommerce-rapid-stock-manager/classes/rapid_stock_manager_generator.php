<?php

/**
 * Class audit system class
 * @author ishouty ltd. London
 * @date 2017
 */
class rapid_stock_manager_generator extends core_rapid_stock_manager
{
    /**
     * @var string Id of this plugin for internationalisation used in __ and _e
     */
    public $id = 'woocommerce-rapid-stock-manager';

    public $enabled = false;

    function __construct ($rapidStockManager) {
    }
    
    public function view()
    {

        echo '<div class="col-wrap variation-container">';
        echo '<h3>' . __('Sample Product Generator', $this->id) . '</h3>';
        echo '<p>' . __('This generates sample variant products for testing.', $this->id) . '</p>';
        echo '<h4>' . __('Set parameters:', $this->id) . '</h4>';
        if( array_key_exists("generate", $_POST) ){
            echo '<a href="">Reset attributes</a><br>';
            $this->generator($_POST);
        }else {
            ?>
            <style>
                .label-spacer{display:inline-block; width:150px;}
            </style>
            <form method="post" name="generator_settings" action="">
                <label for="stock_status" class="label-spacer">In stock: </label><input type="checkbox" name="stock_status" id="stock_status" value="instock"
                                        checked="checked"><br>
                <label for="weight_from" class="label-spacer">Weight from: </label><input type="number" name="weight_from" id="weight_from" value="0">
                <label for="weight_to">Weight to: </label><input type="number" name="weight_to" id="weight_to" value="10"><br>
                <label for="sku" class="label-spacer">SKU: </label><input type="text" name="sku" id="sku" value="vpg"><br>
                <label for="stock_from" class="label-spacer">Stock amount from: </label><input type="number" name="stock_from" id="stock_from" value="1">
                <label for="stock_to">Stock amount to: </label><input type="number" name="stock_to" id="stock_to" value="50"><br>
                <label for="visibility" class="label-spacer">Visible: </label><input type="checkbox" name="visibility" id="visibility" value="visible" checked="checked"><br>
                <label for="variations_count" class="label-spacer">How many variations: </label><input type="number" name="variations_count" id="variations_count" value="3"><br>
                <label for="how_many_products" class="label-spacer">How many Products: </label><input type="number" name="how_many_products" id="how_many_products" value="20"><br>
                <label for="attributes_set_name" class="label-spacer">Attributes set name: </label><input type="text" name="attributes_set_name" id="attributes_set_name"
                                                   value="Size + Colour + Screen Size"><br>
                <label for="product_type" class="label-spacer">Variable: </label><input type="checkbox" name="product_type" id="product_type" value="variable"
                                                                                        checked="checked"><br>
                <label for="attribute_set" class="label-spacer">Variation settings JSON: </label><br>
                <textarea name="attribute_set" id="attribute_set" rows="6" cols="60">
{
"pa_colour":["red","blue","white"],
"pa_size":["2xl","xl","lg"],
"pa_screen-size":["19","20","21"]
}
                </textarea>
                <h3>Warehouse</h3>
                <p>Tips: Please create warehouse with underscore e.g london_warehouse</p>
                <label for="product_type" class="label-spacer">Warehouse Generate: </label>
                <input type="checkbox" name="warehouse_generate" id="warehouse_generate" value="enabled" checked/>
                <br>
                <label for="attribute_set" class="label-spacer">Add Warehouses JSON: </label><br>
                <textarea name="attribute_warehouse" id="attribute_warehouse" rows="3" cols="60">
{
"warehouse":["kho_21","kho_33","kho_44", "kho_55"]
}
                </textarea>
                <br><br>
                <input type="hidden" name="generate" value="1">
                <input type="submit" value="Generate" class="button">
                <br>
            </form>
            <?php
        }
        echo '</div>';
    }

    public function generator($post)
    {

        $product_options = new stdClass();
        $product_options->product_type = $post["product_type"];
        $product_options->stock_status = $post["stock_status"];
        $product_options->weight = rand($post["weight_from"], $post["weight_to"]);
        $product_options->sku = $post["sku"];
        $product_options->stock = rand($post["stock_from"], $post["stock_to"]);
        $product_options->stock_from = $post["stock_from"];
        $product_options->stock_to = $post["stock_to"];
        $product_options->visibility = $post["visibility"];
        $product_options->variations_count = $post["variations_count"];
        $product_options->how_many_products = $post["how_many_products"];
        $product_options->attributes_set_name = $post["attributes_set_name"];
        $post_attribute_set_json = stripslashes($post["attribute_set"]);
        $product_options->warehouse_generate = $post["warehouse_generate"];
        $product_options->attribute_set = json_decode($post_attribute_set_json,JSON_UNESCAPED_SLASHES);
        $post_attribute_warehouse_json = stripslashes($post["attribute_warehouse"]);
        $product_options->attribute_warehouse = json_decode($post_attribute_warehouse_json,JSON_UNESCAPED_SLASHES);


        echo '<pre>';
        $this->screen_log('start');
        if($product_options->product_type == "variable") {
            $i = 1;
            while ($i <= $product_options->how_many_products) {
                $this->generator_add_product($product_options);
                $i++;
            }

        } else {
            $this->generator_add_simple_product($product_options);
        }
        echo '</pre>';


    }

    /**
     * Add simple products
     * @param null $options
     */
    public function generator_add_simple_product($options = null)
    {
        global $wp_error;
        if (!$options) {
            return;
        }

        $number_of_products = $options->how_many_products;
        $attributes_set_name = $options->attributes_set_name;
        $this->screen_log('starting loop through ' . $number_of_products . ' products');

        $i = 1;
        while ($i <= $number_of_products) {
            $time = time();
            $stock_amount = rand($options->stock_from, $options->stock_to);
            $post = array(
                'post_title'   => "$attributes_set_name SIMPLE #gen# product $time-$i",
                'post_content' => "Product content #generator# set name: $attributes_set_name - time: $time - loop: $i",
                'post_status'  => "publish",
                'post_excerpt' => "Product excerpt content goes here... - $attributes_set_name - $time - $i",
                'post_name'    => "gen-product-simple-$time-$i", //name/slug
                'post_type'    => "product"
            );
            $this->screen_log('inserting post '. $i);
            $new_post_id = wp_insert_post($post, $wp_error);
            $this->screen_log('post id ' . $new_post_id . ' inserted');
            wp_set_object_terms($new_post_id, 'simple', 'product_type');
            wp_set_object_terms($new_post_id, 25, 'product_cat');
            update_post_meta($new_post_id, '_manage_stock', 'yes');
            update_post_meta($new_post_id, '_stock_status', $options->stock_status);
            update_post_meta($new_post_id, '_weight', $options->weight);
            update_post_meta($new_post_id, '_sku', $options->sku . "-$time-$i");
            update_post_meta($new_post_id, '_stock', $stock_amount);
            update_post_meta($new_post_id, '_price', rand(1, 20) + $i);
            update_post_meta($new_post_id, '_visibility', $options->visibility);
            update_post_meta( $new_post_id, 'total_sales', '0' );
            update_post_meta( $new_post_id, '_downloadable', 'no' );
            update_post_meta( $new_post_id, '_virtual', 'no' );
            update_post_meta( $new_post_id, '_regular_price', rand(1, 20) + $i );
            update_post_meta( $new_post_id, '_sale_price', '' );
            update_post_meta( $new_post_id, '_purchase_note', '' );
            update_post_meta( $new_post_id, '_featured', 'no' );
            update_post_meta( $new_post_id, '_length', '' );
            update_post_meta( $new_post_id, '_width', '' );
            update_post_meta( $new_post_id, '_height', '' );
            update_post_meta( $new_post_id, '_product_attributes', array() );
            update_post_meta( $new_post_id, '_sale_price_dates_from', '' );
            update_post_meta( $new_post_id, '_sale_price_dates_to', '' );
            update_post_meta( $new_post_id, '_sold_individually', '' );
            update_post_meta( $new_post_id, '_backorders', 'no' );


            if ($options->warehouse_generate == 'enabled') {
                $this->screen_log('add warehouse');
                $this->add_warehouse_products($options->attribute_warehouse, array(product_parent_id => 0, product_id => $new_post_id, meta_value => $stock_amount, user_id => 1));
            }

            $i++;
        }
        $this->screen_log('end while loop');
    }

    /**
     * use to help generate products for warehouse
     */
    public function add_warehouse_products ($attribute_warehouse, $values = array()) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rsm_stock_woocommerce_product_table';

        $attributes_warehouse = array_values($attribute_warehouse);

        //loop through the warehouse
        foreach($attributes_warehouse as $warehouses) {

            foreach($warehouses as $warehouse) {
                $this->screen_log('add warehouse => '+ $warehouse);

                $wpdb->insert($table_name, array('meta_key' => $warehouse, 'meta_value' => $values["meta_value"], 'product_id' => $values["product_id"], 'user_id' => $values["user_id"], 'product_parent_id' => $values["product_parent_id"]));

            }

        }
    }

    /**
     * @param null $options
     */
    private function generator_add_product($options = null)
    {
        global $wp_error;
        if (!$options) {
            return;
        }
        global $wpdb;
        $time = time();
        $attributes_set_name = $options->attributes_set_name;
        $number_of_variations = $options->variations_count;
        $attributes_set = $options->attribute_set;
        $cats = array(25);
        $post = array(
            'post_title' => "$attributes_set_name variation #gen# product $time",
            'post_content' => "Product content #generator# set name: $attributes_set_name - time: $time - count: $number_of_variations - attributes_set: " . print_r($attributes_set, true),
            'post_status' => "publish",
            'post_excerpt' => "Product excerpt content goes here... - $attributes_set_name - $time",
            'post_name' => "gen-product-variation-$time", //name/slug
            'post_type' => "product"
        );
        $this->screen_log('inserting post');
        $new_post_id = wp_insert_post($post, $wp_error);
        $this->screen_log('post id ' . $new_post_id . ' inserted');
        wp_set_object_terms($new_post_id, 'variable', 'product_type');
        wp_set_object_terms($new_post_id, 25, 'product_cat');
        $thedata = array();
        $attributes_keys = array_keys($attributes_set);
        foreach ($attributes_keys as $attribute_pa_name) {
            wp_set_object_terms($new_post_id, $attributes_set[$attribute_pa_name], $attribute_pa_name);
            $thedata[$attribute_pa_name] = array(
                'name' => $attribute_pa_name,
                'value' => '',
                'is_visible' => '1',
                'is_variation' => '1',
                'is_taxonomy' => '1'
            );
        }
        update_post_meta($new_post_id, '_manage_stock', 'yes');
        update_post_meta($new_post_id, '_product_attributes', $thedata);
        update_post_meta($new_post_id, '_stock_status', $options->stock_status);
        update_post_meta($new_post_id, '_weight', $options->weight);
        update_post_meta($new_post_id, '_sku', $options->sku . "-$time");
        update_post_meta($new_post_id, '_stock', $options->stock);
        update_post_meta($new_post_id, '_visibility', $options->visibility);
        $i = 1;
        $variation_options = new stdClass();
        $variation_options->time = $time;
        $variation_options->attribute_categories = $attributes_keys;
        $variation_options->attributes_set = $attributes_set;
        $attributes_buffer = array();
        $this->screen_log('starting loop through ' . $number_of_variations . ' variations');
        while ($i <= $number_of_variations) {
            $post_slug = 'product-' . $new_post_id . '-variation-' . $i;
            $my_post = array(
                'post_title' => 'Variation #gen# #' . $i . ' of ' . $number_of_variations . ' for prdct#' . $new_post_id,
                'post_name' => $post_slug,
                'post_status' => 'publish',
                'post_parent' => $new_post_id,//post is a child post of product post
                'post_type' => 'product_variation',//set post type to product_variation
                'guid' => home_url() . '/?product_variation=' . $post_slug
            );
            $this->screen_log('inserting variation ' . $i);
            $variation_post_id = wp_insert_post($my_post);
            $this->screen_log('variation ' . $i . ' inserted as post id ' . $variation_post_id);
            $variation_options->price = rand(1, 20) + $i;
            $variation_options->stock = rand(1, 20) + $i;
            $variation_options->variation_id = $variation_post_id;
            $selected_attributes = array();
            $variation_sku = '';
            $this->screen_log('calling get_unique_attributes_combination');
            $combination_results = $this->get_unique_attributes_combination($attributes_set, $attributes_buffer);
            if (!$combination_results) {
                $i++;
                continue;
            }
            $variation_sku = $combination_results['variation_sku'];
            $selected_attributes = $combination_results['selected_attributes'];
            $attributes_buffer = $combination_results['attributes_buffer'];


            $variation_options->selected_attributes = $selected_attributes;
            $variation_options->sku = $options->sku . "-$time-" . $variation_sku;

            $variation_options->warehouse_generate = $options->warehouse_generate;
            $variation_options->attribute_warehouse = $options->attribute_warehouse;

            $this->screen_log('calling generator_set_variation for loop ' . $i);

            $this->generator_set_variation($variation_options);

            if ($options->warehouse_generate == 'enabled') {
                $this->screen_log('add warehouse for variations');
                $this->add_warehouse_products($options->attribute_warehouse, array(product_parent_id => $new_post_id, product_id => $new_post_id, meta_value => $variation_options->stock, user_id => 1));
            }

            $this->screen_log('end while loop');
            $i++;
        }
    }


    private function get_unique_attributes_combination($attributes_set, $attributes_buffer = array())
    {
        $this->screen_log('get_unique_attributes_combination(), buffer length ' . sizeof($attributes_buffer));
        $attributes_keys = array_keys($attributes_set);
        $exists = true;
        $loop_count = 1;
        while ($exists) {
            $this->screen_log('get_unique_attributes_combination() while count ' . $loop_count);
            $selected_attributes = array();
            $variation_sku = '';
            $attributes_string = '';
            foreach ($attributes_keys as $attribute_pa_name) {
                $this->screen_log('get_unique_attributes_combination() while count ' . $loop_count . ' foreach: ' . $attribute_pa_name);
                $attributes_string .= $attribute_pa_name;
                $attribute_options = $attributes_set[$attribute_pa_name];
                $rand_attribute_number = rand(0, sizeof($attribute_options) - 1);
                $attributes_string .= $attribute_options[$rand_attribute_number];
                $selected_attributes[$attribute_pa_name] = $attribute_options[$rand_attribute_number];
                $variation_sku .= $attribute_options[$rand_attribute_number];
            }
            $exists = in_array($attributes_string, $attributes_buffer);
            if (!$exists) {
                $this->screen_log('get_unique_attributes_combination() while count ' . $loop_count . ', adding to buffer: ' . $attributes_string);
                array_push($attributes_buffer, $attributes_string);
                break;
            } elseif ($loop_count >= 10) {
                $this->screen_log('get_unique_attributes_combination() while count ' . $loop_count . ', over limit 10 - break');
                return false;
                break;
            } else {
                $this->screen_log('get_unique_attributes_combination() while count ' . $loop_count . ', will do another loop');
            }
            $loop_count++;
        }
        $this->screen_log('get_unique_attributes_combination() returning results');
        return array(
            'variation_sku' => $variation_sku,
            'selected_attributes' => $selected_attributes,
            'attributes_buffer' => $attributes_buffer
        );
    }

    private function generator_set_variation($options = null)
    {
        if ($options === null) {
            return;
        }
        $variation_id = $options->variation_id;
        $attribute_categories = $options->attribute_categories;
        $selected_attributes = $options->selected_attributes;
        $price = $options->price;
        $attributes_set = $options->attributes_set;
        $time = $options->time;
        $thedata = array();
        foreach ($attribute_categories as $attribute_pa_name) {
            update_post_meta($variation_id, 'attribute_' . $attribute_pa_name, $selected_attributes[$attribute_pa_name]);
            $thedata[$attribute_pa_name] = array(
                'name' => $selected_attributes[$attribute_pa_name],
                'value' => '',
                'is_visible' => '1',
                'is_variation' => '1',
                'is_taxonomy' => '1'
            );
            $this->screen_log('wp_set_object_terms for variation_id ' . $variation_id . ' and attribute_category ' . $attribute_pa_name . ' and attribute_name ' . $selected_attributes[$attribute_pa_name]);
            wp_set_object_terms($variation_id, $attributes_set[$attribute_pa_name], $attribute_pa_name);
        }
        update_post_meta($variation_id, '_price', $price);
        update_post_meta($variation_id, '_regular_price', $price);
        update_post_meta($variation_id, '_sku', $options->sku);
        update_post_meta($variation_id, '_stock', $options->stock);
        update_post_meta($variation_id, '_manage_stock', 'yes');

        $this->screen_log('updating postmeta for variation_id ' . $variation_id);
        update_post_meta($variation_id, '_product_attributes', $thedata);
    }
    
}
?>