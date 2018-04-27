/**
 * constructor
 */
var rapidStockManagerWarehouse  = function (settings) {
    if (!settings) { return false;}
    this.setElements(settings);
    this.init();

};

/**
 * set elements required for rapid stock manager
 */
rapidStockManagerWarehouse.prototype.setElements = function(settings) {

    this.qtip = settings.classes.qtip;
    this.rapidStockManager = settings.classes.rapidStockManager;

};

/**
 * call all scripts
 * @param settings
 */
rapidStockManagerWarehouse.prototype.init = function (settings) {

    this.setEventListeners();
    this.setEventListenersTransferForm();

};

/**
 * set up event listeners for general for rapid warehouse stock manager
 */
rapidStockManagerWarehouse.prototype.setEventListeners = function () {

    var self = this,
        rsmContainer = jQuery('.woocommerce-rapid-stock-manager'),
        body = jQuery('body');


    self.setEventListenersPopUp();
    self.setEventListenersSearch();
    self.setEventListenersPagination();
    self.setVariantListeners();
    self.setSimpleListeners();

    //warehouse select drop down
    body.on('change', '#select-warehouse', function(e) {
        e.preventDefault();

        if (jQuery('.woocommerce-rapid-stock-manager-table').length > 0 ||
            jQuery('.simple-products-table').length > 0 ||
            jQuery('.variant-products-table').length > 0)
        {
            var pageLimit = jQuery(this).data('page-limit'),
                layoutType = jQuery(this).data('layout-type');

            self.updateProductDisplayTable(pageLimit, layoutType);
        }

    });


};

/**
 * set event listeners for variants items
 */
rapidStockManagerWarehouse.prototype.setVariantListeners = function () {

    var self = this,
        body = jQuery('body');

    //update details
    body.on('click', '.warehouse.variant-quantity-update', function(e) {
        e.preventDefault();

        var productId = jQuery(this).data('adjust-simple-quantity'),
            warehouse = jQuery(this).data('warehouse');

        self.update_warehouse_quantity(productId,warehouse, jQuery(this), 'variant');
    });

};


/**
 * only set event listeners for simple products
 */
rapidStockManagerWarehouse.prototype.setSimpleListeners = function () {


    var self = this,
        body = jQuery('body');

    //// transfer stock
    body.on('click', '.warehouse.simple-quantity-update', function(e) {
        e.preventDefault();

        var productId = jQuery(this).data('adjust-simple-quantity'),
            warehouse = jQuery(this).data('warehouse');

        self.update_warehouse_quantity(productId,warehouse, jQuery(this), 'simple');

    });

};

/**
 * set event prototypes event listeners
 */
rapidStockManagerWarehouse.prototype.setEventListenersPopUp = function () {

    var self = this,
        body = jQuery('body');

    jQuery(".rapid_stock_add_warehouse" ).blur(function() {

        var vals= jQuery(this).val().toLowerCase();

        if((vals != '') || (vals !=null)){

            jQuery('.rapid_stock_remove_warehouse option').each(function(){

                var dropdownText= jQuery(this).text().toLowerCase();
                if(dropdownText == vals){

                    jQuery(".rapid_stock_add_warehouse").after("<span class='warehouse_error' style='color:red'>This warehouse already exist.</span>");
                    return false;

                } else {

                    jQuery(".warehouse_error").hide();
                    return true;

                }
            });
        }
    });

    jQuery('#warehouse_close_popup').on('click', function(e) {
        e.preventDefault();
        self.closePopUp();
    });

    //when click on transfer button
    jQuery('#warehouse_transfer_popup').on('click', function(e){
        e.preventDefault();
        self.popup();
    });


    //////// POP UP EVENT LISTENERS FOR PRODUCT DETAILS //////
    body.on('keypress', 'input.input-warehouses', function(e) {

        return self.isNumber(e);

    });
    //////// POP UP EVENT LISTENERS FOR PRODUCT DETAILS //////

    //remove products to transfer from pop up
    body.on('click', '.products-transfer-warehouse .remove-product-transfer', function(e){
        jQuery(this).parents('tr').remove();
    });


};

/**
 * search search filter
 */
rapidStockManagerWarehouse.prototype.setEventListenersSearch = function () {

    var self = this,
        rsmContainer = jQuery('.woocommerce-rapid-stock-manager'),
        body = jQuery('body');

    //search products on enter
    body.on('keypress','#search-value',function(e){
        var $searchButton = jQuery('input.search-products');
        var warehouse = $searchButton.data('warehouse');
        var type = $searchButton.data('type');
        if (warehouse && e.which == 13) {
            e.preventDefault();
            if( type === "simple" ){
                self.searching_entire_products(warehouse);
                return;
            }
            if( type === "variant" ){
                self.search_variation_warehouse_product(warehouse);
                return;
            }
        }
    });

    //search single products
    body.on('click','.warehouse.simple-products.btn-search-entire-products', function(e) {
        var warehouse = jQuery(this).data('warehouse');
        self.searching_entire_products(warehouse);
        console.log('searching entire products ');
    });

    //single variants search
    body.on('click','.warehouse.variant-products.search-button', function(e) {
        var warehouse = jQuery(this).data('warehouse');
        self.search_variation_warehouse_product(warehouse);
    });

    //sort by for warehouse for both variants and single
    body.on('change', '.warehouse.sort-by', function(e) {

        var sort = jQuery(this),
            warehouse = sort.data('warehouse'),
            pagePerRecord = sort.data('per-page-record'),
            pageNumber = sort.data('page-number'),
            viewType = sort.data('view-type');

        self.sort_product(warehouse, pagePerRecord, pageNumber, viewType);

    });

    body.on('click', '.search-reset.variantion_search_reset input, #link-reset-search', function(e) {
            e.preventDefault();
            var resetBtn = jQuery('.search-reset.variantion_search_reset input');

            self.updateProductDisplayTable( resetBtn.data('page-limit'), resetBtn.data('layout-type'));

    });

    //single products reset search button for warehouse
    body.on('click', '.warehouse.search-reset.simple_searching_reset input.button, #simple-search-reset-link',function(e){
        e.preventDefault();

        var resetBtn = jQuery('.warehouse.search-reset.simple_searching_reset input.button');
        console.log(resetBtn.data('records-per-page'));
        console.log(resetBtn.data('type'));
        self.updateProductDisplayTable(resetBtn.data('records-per-page'),resetBtn.data('type'));
    });

};

/**
 * set event listeners for pagination
 */
rapidStockManagerWarehouse.prototype.setEventListenersPagination = function () {

    var self = this,
        body = jQuery('body');

    //pagination warehouse
    body.on('click','.pagination a.warehouse.counter', function (e){

        var e = jQuery(this),
            counter = e.data('counter'),
            page;

        self.get_next_records(e.data('warehouse'),
            e.data('counter'),
            e.data('show-per-record'),
            e.data('type'),
            e.data('search'));

    });

    body.on('click', '.pagination a.warehouse.right-arrow', function () {

        var e = jQuery(this),
            nextPage = e.data('next-page');

        self.get_next_records(e.data('warehouse'),
            nextPage,
            e.data('show-per-record'),
            e.data('type'),
            e.data('search')
        );

    });


    body.on('click', '.pagination a.warehouse.left-arrow', function () {

        var e = jQuery(this),
            prevPage = e.data('prev-page');

        self.get_next_records(e.data('warehouse'),
            prevPage,
            e.data('show-per-record'),
            e.data('type'));

    });

};

/**
 * set event listeners for transfer form pop up
 */
rapidStockManagerWarehouse.prototype.setEventListenersTransferForm = function () {

    var self = this,
        body = jQuery('body');

    //// transfer stock
/*    body.on('click', '.warehouse.simple-quantity-update', function(e) {
        e.preventDefault();

        var productId = jQuery(this).data('adjust-simple-quantity'),
            warehouse = jQuery(this).data('warehouse');

        self.update_warehouse_quantity(productId,warehouse, jQuery(this));

    });*/


    ///
    //on the pop up of the transfer warehouse
    //event is not there so look for body
    /*body.on('change', '#woocommerce_product_from', function (e) {
        e.preventDefault();
        self.getProductByWarehouse();
    });*/

    //clear changes on change select drop down for both
    body.on('change', '#warehouse-transfer-popup select', function(e){
        jQuery('.success-message').empty();
        return self.validateWarehouseNotSame(jQuery(this).val());

    });

    var delay = (function(){
        var timer = 0;
        return function(callback, ms){
            clearTimeout (timer);
            timer = setTimeout(callback, ms);
        };
    })();

    //search title in box
    //allow search functionality to work
    body.on('keyup', '.product-popup #productTitle', function (e) {
        var value = jQuery(this).val();

            delay(function() {

                if (value !== '') {
                    self.searchWarehouseProduct(value);
                } else {
                    jQuery('.warehouseproductLists').hide();
                }

            }, 500 );

    });

    body.on('click', '#warehouse_close_popup', function(e) {
        e.preventDefault();
        self.closePopUp('product-popup');
    });


    //update all stocks you want to transfer
    body.on('click','.update-stock-transfer-btn', function(e) {
        e.preventDefault();

        self.updateWarehouseValue();

    });

    body.on('click','.get-warehouse-product-tr', function (e) {

        var productId = jQuery(this).data('product-id'),
            warehouseFrom = jQuery(this).data('warehouse'),
            parentId = jQuery(this).data('parent-id');

        jQuery('.warehouseproductLists').hide();

        //append to product list for transfer
        self.getWareHouseProduct(productId, warehouseFrom, parentId);

    });

    body.on('keyup', '#warehouse-transfer-popup .warehouses-quantity input', function(e) {

        var element = jQuery(this),
            warehouseFromQty = element.data('warehouse-qty'),
            errorMessageContainer = jQuery('.error-message'),
            errorMessageNoStock = jQuery('#warehouse-transfer-popup').data('no-stock-transferable'),
            valueChanged = element.val();

        element.parent().find('.status').empty();

        errorMessageContainer.text('');

        if (self.check_product_qty_value(warehouseFromQty,valueChanged) === false) {
            errorMessageContainer.text(errorMessageNoStock);
        } else {
            errorMessageContainer.text('');
        }

        if (self.isNumber(e) === false) {
            element.val(null);
        }

    });

};


/**
 * open modal popup
 * integrated done * andy
 */
rapidStockManagerWarehouse.prototype.popup = function () {

    var self = this,
        data = {
        'condition': 'productPopUp',
        'action': 'update_quantity'
    };

    jQuery.post( ajaxurl, data, function(data) {

        if (data) {
            jQuery("body").append("<div class='modal-overlay js-modal-close' style='opacity:1;'></div>");
            jQuery('.product-popup').html(data).show();

            self.qtip.init();

        }

    }, "html").done(function() { });


};

/**
 * close popup
 * @param divId
 */
rapidStockManagerWarehouse.prototype.closePopUp = function (divId) {

    jQuery('.'+ divId).hide();
    jQuery('.js-modal-close').css({'opacity':'0','display':'none'});

};

/**
 * update warehouse values
 * @returns {boolean}
 */
rapidStockManagerWarehouse.prototype.updateWarehouseValue = function () {

    var warehousePopUp = jQuery('#warehouse-transfer-popup'),
        self = this,
        errorSameStock = warehousePopUp.data('error-same-warehouse'),
        errorValidWarehouse = warehousePopUp.data('error-valid-warehouse'),
        transferSuccess = warehousePopUp.data('transfer-success'),
        transferFailed = warehousePopUp.data('transfer-failed'),
        errorMessageNoStock = warehousePopUp.data('no-stock-transferable'),
        productPopUp = jQuery('.woocommerce_page_update_stock_rapid .product-popup'),
        errorContainer = jQuery('.error-message'),

        from =  productPopUp.find('#woocommerce_product_from').val(),
        to = productPopUp.find('#woocommerce_product_to').val(),
        loading = jQuery('#warehouse-transfer-popup .loading-container');


    errorContainer.empty();
    jQuery('.success-message').empty();

    if((parseInt(from) === 0) || (from === '')) {

        errorContainer.text(errorValidWarehouse);
        return false;

    } else if((parseInt(to) === 0) || (to === '')) {

        errorContainer.text(errorValidWarehouse);
        return false;

    } else if(from === to){

        errorContainer.text(errorSameStock);
        return false;

    }else {

        var productsTransfer = Array();

        jQuery('.products-transfer-warehouse tr .warehouses-quantity input').each(function(index) {

            var input = jQuery(this),
                qtyValue = parseInt(input.val()),
                status = input.parent().find('.status');

            status.empty();

            if (to !== input.data('warehouse')) {

                //check if value is valid and less that the current quantity
                if (qtyValue > 0 && ( qtyValue <= parseInt(input.data('warehouse-qty')) )) {

                    input.removeAttr('style');

                    productsTransfer.push({
                        parentId: input.data('parent'),
                        productId: input.data('value'),
                        warehouseQty: input.data('warehouse-qty'),
                        warehouseFrom: input.data('warehouse'),
                        warehouseTo: to,
                        qty: parseInt(input.val())
                    });
                    input.data('transfer-success');

                } else {
                    //value not entered
                    input.css({'border':'1px solid red'});
                    input.data('transfer-failed');
                }

            } else {
                //cannot transfer to the same warehouse
                input.css({'border':'1px solid red'});
                input.data('transfer-failed');

                //alert message not allowed to do this operation
            }

        });


        if (productsTransfer.length > 0) {

            self.applyLoading('show',loading);
            //send to transfer
            var data = {
                'productTransfer': productsTransfer,
                'condition': 'changeWareHouseValue',
                'action': 'update_quantity'
            };

            jQuery.post(ajaxurl, data, function(data) {

                jQuery.each(data.result, function(productId,detail) {

                    var row = productPopUp.find('.' + detail.warehouseFrom + '-' + productId),
                        input =  row.find('input'),
                        status = row.find('.status');


                    if (detail.result) {

                        row.find('.quantity-remaining').text(detail.remaining);

                        input.attr('data-warehouse-qty',detail.remaining);
                        input.data('warehouse-qty',detail.remaining);
                        input.val('');

                        status.text(transferSuccess);

                    } else {
                        status.text(transferFailed);
                        input.css({"border": "1px solid red"});
                    }


                });

                //find print button and update query string
                var url = warehousePopUp.find('.print-window').attr('href'),
                    newUrl = url.replace(/reference_no=(\d*|.*)/,'reference_no='+data.reference_no),
                    printButton = warehousePopUp.find('.print-window'),
                    printText = printButton.html();

                printButton.attr('href',newUrl);
                printButton.removeClass('disable_a_href');
                if(!printButton.data('text')) {
                    printButton.data('text', printText);
                }else{
                    printText = printButton.data('text');
                }
                printButton.html(printText + ': ' + data.reference_no);

            }, "json").done(function() {

                self.applyLoading('hide',loading);
            });


        }

    }

};

/**
 * get product by warehouse
 */
rapidStockManagerWarehouse.prototype.getProductByWarehouse = function () {

    jQuery('.woocommerce_page_update_stock_rapid .product-popup .marginZero').text('');

    var warehouse = jQuery(".woocommerce_page_update_stock_rapid .product-popup #woocommerce_product_from").val(),
        to = jQuery('.woocommerce_page_update_stock_rapid .product-popup #woocommerce_product_to').val(),
        search = jQuery(".woocommerce_page_update_stock_rapid .product-popup .searching").val(''),
        self = this,
        loading = jQuery('#warehouse-transfer-popup .loading-container');



    if((warehouse !== 0) || (warehouse !== '')) {

        if(warehouse !== to) {
            jQuery('.error-message').text('');
        }

        var data = {
            'warehouseFrom': warehouse,
            'warehouseTo': to,
            'condition': 'getproductByWarehouse',
            'action': 'update_quantity'
        };

        jQuery('.warehouseproductLists').empty();
        self.applyLoading('show',loading);
        jQuery.post( ajaxurl, data, function(data) {

            jQuery('.warehouseproductLists').show().html(data);

        }, 'html').done(function(){
            self.applyLoading('hide',loading);
            console.log('calling ajax function getProductByWarehouse');

        });


    }

};


/**
 * update product to
 * @param value
 */
rapidStockManagerWarehouse.prototype.validateWarehouseNotSame = function (value) {
    var errorMessage = jQuery('#warehouse-transfer-popup').data('error-same-warehouse');
    var from = jQuery('.woocommerce_page_update_stock_rapid .product-popup #woocommerce_product_from').val();
    var to = jQuery('.woocommerce_page_update_stock_rapid .product-popup #woocommerce_product_to').val();

    if(to === from) {

        jQuery('.error-message').text(errorMessage);
        return false;

    } else {

        jQuery('.error-message').text('');
        return true;

    }


};

/**
 * check product qty value
 * @param left_value
 * @param product_id
 * @param transferValue
 * @returns {boolean}
 */
rapidStockManagerWarehouse.prototype.check_product_qty_value = function (left_value, transferValue) {

    if((transferValue < 0) || (transferValue > parseInt(left_value))){

        return false;
    } else {
        return true;
    }
};

/**
 * get warehouse products ajax
 * @param productId
 * @param warehouseFrom
 * @param parentId
 */
rapidStockManagerWarehouse.prototype.getWareHouseProduct = function (productId, warehouseFrom, parentId) {

    jQuery('.woocommerce_page_update_stock_rapid .product-popup .disable_a_href').removeClass('disable_a_href');
        var self = this,
        popupContainer = jQuery('#warehouse-transfer-popup .loading-container');

    var data = {
        'warehouse': warehouseFrom,
        'productId': productId,
        'parentId': parentId,
        'condition': 'getWareHouseProduct',
        'action': 'update_quantity'
    };

    self.applyLoading('show',popupContainer);

    var allowTransferProduct = true;

    jQuery('.products-transfer-warehouse tr').each(function(index){

        var row = jQuery(this);
        //check if the product has already been entered
        if (row.data('product-id') === productId) {
            allowTransferProduct = false;
            return false;
        }

        if (typeof row.data('warehouse') !== 'undefined') {
            if (row.data('warehouse') !== warehouseFrom ) {
                allowTransferProduct = false;
                return false;
            }
        }


    });

    if (allowTransferProduct) {
        jQuery.post( ajaxurl, data, function(data) {
            //jQuery('.warehouseproductLists').html(data);
            //append to main transfer box
            jQuery('.products-transfer-warehouse').append(data);

        }, 'html').done(function(){
            self.applyLoading('hide',popupContainer);
        });

    } else {
        self.applyLoading('hide',popupContainer);
    }



};

/**
 * is number
 * @param evt
 * @returns {boolean}
 */
rapidStockManagerWarehouse.prototype.isNumber = function (evt) {
    evt = (evt) ? evt : window.event;
    var charCode = (evt.which) ? evt.which : evt.keyCode;
    if (charCode > 31 && (charCode < 48 || charCode > 57)) {
        return false;
    }
    return true;
};

/**
 * search warehouse product
 * @param values
 * @returns {boolean}
 */
rapidStockManagerWarehouse.prototype.searchWarehouseProduct = function(values) {

    var fromDropdown= jQuery('.woocommerce_page_update_stock_rapid .product-popup #woocommerce_product_from').val(),
        popupLoadingContainer = jQuery('#warehouse-transfer-popup .loading-container'),
        popup = jQuery('.woocommerce_page_update_stock_rapid .product-popup'),
        warehouseProductLists = jQuery('.warehouseproductLists'),
        self = this;
    if((fromDropdown == '') || (fromDropdown == 0)) {

        jQuery('.woocommerce_page_update_stock_rapid .product-popup .marginZero').text(popup.data('select-valid-warehouse'));
        jQuery('.woocommerce_page_update_stock_rapid .product-popup #productTitle').val('');

        return false;

    } else {

        if((values == '') || (values == null)) {
            jQuery('.woocommerce_page_update_stock_rapid .product-popup footer .btn-update').addClass('disable_a_href');
        }

        self.applyLoading('show',popupLoadingContainer);
        warehouseProductLists.show().empty();

        var data = {
            'warehouse': fromDropdown,
            'searchKey': values,
            'condition': 'searchWarehouseProduct',
            'action': 'update_quantity'
        };

        jQuery.post( ajaxurl, data, function(data) {

            warehouseProductLists.html(data).show();

        }, 'html').done(function(){

            self.applyLoading('hide',popupLoadingContainer);

        });

    }

};

/**
 * transfer simple product quantity
 * @param records_per_pages
 * @param type
 */
rapidStockManagerWarehouse.prototype.updateProductDisplayTable = function (records_per_pages, type) {
    var variations = '',
        self = this,
        container;

    if(type === 'simple') {
        var container = jQuery('.woocommerce_page_update_stock_rapid #col-container .simple-products-table'),
            sorting = jQuery('.woocommerce_page_update_stock_rapid .simple-products-table .sort-by #sort-by').val();
    } else {
        container = jQuery('.woocommerce_page_update_stock_rapid #col-container .variant-products-table'),
        variations= jQuery('.woocommerce_page_update_stock_rapid #col-container #product-variation').val(),
        sorting= jQuery('.woocommerce_page_update_stock_rapid .filter-rapid-stock-manager .sort-by #sort-by').val();
    }

    var warehouse= jQuery('.woocommerce_page_update_stock_rapid #col-container #select-warehouse').val(),
        data = {
            'warehouse': warehouse,
            'sort': sorting,
            'records_per_pages': records_per_pages,
            'type': type,
            'variation': variations,
            'condition': 'transfer_simple_product_quantity',
            'action': 'update_quantity'
        };
    
    container.empty();

    this.applyLoading('show');

    jQuery.post( ajaxurl, data, function(data) {

        if(data.length <= 0){

            data = '<h3 style="text-align:center;">' + jQuery('.woocommerce.woocommerce-rapid-stock-manager').data('no-products') + '</h3>';
        }

        container.html(data);

        self.rapidStockManager.init();
        self.qtip.init();

    }, 'html').done(function() {
        self.applyLoading('hide');
    });


};

/**
 * transfer quantity stock
 * @param productId
 * @param warehouse
 */
rapidStockManagerWarehouse.prototype.update_warehouse_quantity = function (productId, warehouse, elementClicked, condition) {

    var tableContainer = jQuery('.woocommerce_page_update_stock_rapid #col-container table'),
        adjusting = tableContainer.find('#select-update-action'+ productId).val(),
        totalQuantity = tableContainer.find('#total-quantity'+ productId).text(),
        updateValue = tableContainer.find('#update-value'+ productId).val(),
        td = jQuery(elementClicked).parents('td'),
        self = this,
        value;

    switch(adjusting) {
        case "set":
            value = updateValue;
        break;
        case "deduct":
            value = parseInt(totalQuantity) - Math.abs(parseInt(updateValue));
        break;
        default:
            value = parseInt(updateValue) + parseInt(totalQuantity);
        break;
    }

    if(!isNaN(updateValue) && (updateValue !== '')) {

        self.rapidStockManager.loadingIcons('start-loading',td);

        var data = {
            'productId': productId,
            'warehouse': warehouse,
            'action_calculate': adjusting,
            'action_amount': parseInt(updateValue),
            'stock_old_value': totalQuantity,
            'stock_new_value': value,
            'condition': 'transferQuantity',
            'action': 'update_quantity'
        };

        jQuery.post( ajaxurl, data, function(data) {
            
            if(data) {

                var tableContainer = jQuery('.woocommerce_page_update_stock_rapid #col-container table');
                tableContainer.find('#update-value'+ productId).val('');
                tableContainer.find('#total-quantity'+ productId).text(value);
                var $updatedRow = tableContainer.find('.requires-updating #stock-status'+ productId).parent('tr');
                $updatedRow.removeClass('requires-updating');
                $updatedRow.find('.requires-updating').removeClass('requires-updating');

                elementClicked.parents('tr').find('td[data-simple-total="true"]').attr('style', data.quantity_color);

                if(parseInt(value) <= 0) {
                    tableContainer.find('#stock-status'+ productId).removeClass('green').addClass('red').text('Out of stock');
                } else {
                    tableContainer.find('#stock-status'+ productId).removeClass('red').addClass('green').text('In stock');
                }
            }

        }, 'json').done(function() {

            self.rapidStockManager.loadingIcons('finished-loading',td);

        });

    }

};

/**
 * change action button
 * @param productId
 */
rapidStockManagerWarehouse.prototype.changeActionBtn = function (productId) {

    var adjusting = jQuery('.woocommerce_page_update_stock_rapid #col-container table #select-update-action'+ productId).val();

    jQuery('.woocommerce_page_update_stock_rapid #col-container table #action-btn' + productId).text(adjusting +' Quantity');

};

/**
 * get next records
 * @param warehouse
 * @param pagenumber
 * @param records_per_pages
 * @param type
 */
rapidStockManagerWarehouse.prototype.get_next_records = function (warehouse, pagenumber, records_per_pages, type, search) {


    var variation,
        colContainer =  jQuery('.woocommerce_page_update_stock_rapid #col-container'),
        sorting = jQuery('.woocommerce_page_update_stock_rapid .sort-by #sort-by').val(),
        self = this,
        container;

    if (type === 'variant') {
        container = colContainer.find('.variant-products-table');
        variation = colContainer.find('#product-variation').val();
    } else {
        container = colContainer.find('.simple-products-table');
    }

    var data = {
        'warehouse' : warehouse,
        'sort' : sorting,
        'records_per_pages': records_per_pages,
        'pagenumber': pagenumber,
        'type': type,
        'variation': variation,
        'search': search,
        'condition': 'transfer_simple_product_quantity',
        'action': 'update_quantity'

    };

    container.empty();
    self.applyLoading('show');

    jQuery.post( ajaxurl, data, function(data) {

        if(data === '') {
            data = '<h3>'+ jQuery('.woocommerce.woocommerce-rapid-stock-manager').data('no-products') +'</h3>';
        }

        container.html(data);

    }, 'html').done(function() {

        self.applyLoading('hide');
        self.rapidStockManager.init();
        self.qtip.init();

    });


};

/**
 * sort product
 * @param warehouse
 * @param records_per_pages
 * @param page_number
 * @param type
 */
rapidStockManagerWarehouse.prototype.sort_product = function (warehouse, records_per_pages, page_number, type) {

    var variation = '',
        sorting = jQuery('.warehouse.sort-by').val(),
        container = jQuery('.woocommerce_page_update_stock_rapid #col-container .simple-products-table'),
        self = this;

    if(type === 'variant') {
        variation= jQuery('.woocommerce_page_update_stock_rapid #col-container #product-variation').val();
        container = jQuery('.woocommerce_page_update_stock_rapid .variant-products-table');
    }

    self.applyLoading('show');
    container.empty();

    var data = {
        'warehouse' : warehouse,
        'sort' : sorting,
        'records_per_pages': records_per_pages,
        'pagenumber': page_number,
        'type': type,
        'variation': variation,
        'condition': 'transfer_simple_product_quantity',
        'action': 'update_quantity'

    };

    jQuery.post( ajaxurl, data, function(data) {

        if(data === 0){
            data= '<h3>'+ jQuery('.woocomerce.woocommerce-rapid-stock-manager').data('no-products') + '</h3>';
        }

        container.html(data);

    }, 'html').done(function() {
        self.applyLoading('hide');
    });

};

/**
 * remove function
 */
rapidStockManagerWarehouse.prototype.convert_uppear_case = function (str) {
    var lower = str.toLowerCase();
    return lower.replace(/(^| )(\w)/g, function(x) {
        return x.toUpperCase();
    });
};

/**
 * filter product
 * @param value
 */
rapidStockManagerWarehouse.prototype.filter_product = function (value){
    var value= convert_uppear_case(value);
    jQuery("#fbody tr:not(:contains('"+value+"'))").css("display", "none");
    jQuery("#fbody tr:contains('"+value+"')").css("display", "");
};

/**
 * search entire product
 * @param warehouse
 */
rapidStockManagerWarehouse.prototype.searching_entire_products = function (warehouse) {

    var search= jQuery('.woocommerce_page_update_stock_rapid #col-container #form-'+ warehouse +' #search-value').val(),
        sorting= jQuery('.woocommerce_page_update_stock_rapid .simple-products-table .sort-by #sort-by').val(),
        container = jQuery('.woocommerce_page_update_stock_rapid #col-container .simple-products-table'),
        self = this;

    var data = {
        'warehouse' : warehouse,
        'sort' : sorting,
        'search': search,
        'condition': 'transfer_simple_product_quantity',
        'action': 'update_quantity'

    };
    self.applyLoading('show');

    //jQuery('.woocommerce_page_update_stock_rapid #col-container .simple-products-table').empty();

    container.empty();

    jQuery.post( ajaxurl, data, function(data) {

        if(data) {

            container.html(data);

        } else {
            container.html('<h3>'+ jQuery('.woocommerce.woocommerce-rapid-stock-manager').data('no-products') + '</h3>');

        }

        //display show button provider
        jQuery('.woocommerce_page_update_stock_rapid #col-container .search-entire-container .simple_searching_reset').show();

        self.rapidStockManager.init();
        self.qtip.init();

    }, 'html').done(function() {

        self.applyLoading('hide');

    });

};

/**
 * search variation warehouse products
 * @param warehouse
 **/
rapidStockManagerWarehouse.prototype.search_variation_warehouse_product = function(warehouse) {

    var self = this,
        container = jQuery('.woocommerce_page_update_stock_rapid #col-container .variant-products-table'),
        search = jQuery('#search-value').val(),
        sorting = jQuery('.woocommerce_page_update_stock_rapid .simple-products-table .search_product_variation_product .sort-by #sort-by').val(),
        variations = jQuery('.woocommerce_page_update_stock_rapid #col-container #product-variation').val();

    var data = {
        'warehouse' : warehouse,
        'sort' : sorting,
        'search': search,
        'type': 'variant',
        'variation': variations,
        'condition': 'transfer_simple_product_quantity',
        'action': 'update_quantity'
    };

    if (search.length > 0) {

        self.applyLoading('show');
        container.empty();

        jQuery.post( ajaxurl, data, function(data) {

            if(data) {
                container.html(data);

            } else {
                container.html('<h3>'+ jQuery('.woocommerce.woocommerce-rapid-stock-manager').data('no-products') + '</h3>');
            }

            jQuery('.woocommerce_page_update_stock_rapid #col-container .search-entire-container .variantion_search_reset').show();
            self.rapidStockManager.init();
            self.qtip.init();


        }, 'html').done(function() {
            self.applyLoading('hide');
        });

    }



};

/**
 * append loading for simple loading details
 */
rapidStockManagerWarehouse.prototype.applyLoading = function (condition, elm) {
    var loadingContainer;

    if (elm) {
        loadingContainer = elm;
    } else {
        loadingContainer = jQuery('.loading-container');
    }

    if (condition === 'show') {
        loadingContainer.show();
    }
    else {
        loadingContainer.hide();
    }

};

(function (jQuery,rapidStockManagerQtip, rapidStockManager) {

    jQuery('document').ready(function() {

        new rapidStockManagerWarehouse({
            classes: {
                qtip: new rapidStockManagerQtip({
                    cltoolTipAttr: '.tooltip-qtip',
                    dataTipContent: 'data-qtip-content',
                    dataTipTitle: 'data-qtip-title',
                    clToolTipHtml: '.tooltip-qtip-html',
                    toolTipStyling: 'qtip-light qtip-shadow',
                    clToolTipContentHtml: '.tool-tip-content'
                }),

                rapidStockManager: new rapidStockManager({
                    init: false,
                    classesEnabled: {
                        warehouse: true
                    },
                    common: {
                        clRequiresUpdating: 'requires-updating',
                        attrTextAdjust: 'text-adjust',
                        attrTextSet: 'text-set',
                        attrTextDeduct: 'text-deduct',
                        dataTableFilterEnabled: 'table-filter-enabled',
                        clMainContainer: '.woocommerce-rapid-stock-manager',
                        dataTableFilterInputText: 'table-filter-input-text',
                        dataTableFilterMinRows: 'table-filter-minrows',
                        dataTableFilterLabel: 'table-filter-label',
                        clUpdateActionRow: '.select-update-action',
                        clSelectAction: 'select.select-update-action',
                        attrTableView: 'table-view',
                        clSortBy: '.sort-by',
                        clSpinnerIcon: '.loading.fa-spinner',
                        clCheckIcon: '.loading.fa-check',
                        copyToClipboardSelector: '.copy-to-clipboard',
                        clSpinnerOverride: '.spinner-override',
                        attrTextDeductRow: 'text-row-deduct'
                    },
                    variant: {
                        attrTextAjustRow: 'text-row-adjust',
                        attrTextSetRow: 'text-row-set',
                        attrDataOriginalQty: 'original-qty',
                        attrDataVariantTotal: 'td[data-variant-total="true"]',
                        attrCalculateTotal: 'td[data-rapid-calculate-total="true"]',
                        clLinksAction: 'a.variant-quantity-update',
                        clOriginalQty: '.original-qty',
                        clVariantRow: 'a.variant-update-row',
                        clQtyChange: '.wc-qty-change',
                        clNewQtyValue: '.new-qty-changes'
                    },
                    simple: {
                        attrDataOriginalQty: 'original-qty',
                        clTable: '.woocommerce-rapid-stock-manager-table',
                        clLinksAction: 'a.simple-quantity-update',
                        attrPostId: 'adjust-simple-quantity',
                        attrAdjustQuantity: 'input[data-simple-qty="true"]',
                        attrTotal: 'td[data-simple-total="true"]'
                    }
                })
            }
        });

    });

})(jQuery, rapidStockManagerQtip, rapidStockManager);