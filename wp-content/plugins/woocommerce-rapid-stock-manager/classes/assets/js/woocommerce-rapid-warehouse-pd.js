var rapidStockManagerWarehousePD = function () {

    this.setEventListeners();

};

/**
 * set event listeners
 */
rapidStockManagerWarehousePD.prototype.setEventListeners = function () {

    this.saveWarehouseQuantity();

};

/**
 * save warehouse quantity
 */
rapidStockManagerWarehousePD.prototype.saveWarehouseQuantity = function () {

    var messageContainer = jQuery('#warehouse_tab_options .message-container'),
        self = this;

    var delay = (function(){
        var timer = 0;
        return function(callback, ms){
            clearTimeout (timer);
            timer = setTimeout(callback, ms);
        };
    })();

    jQuery('body').on('keyup','#warehouse_tab_options input.input-warehouses', function(e) {
        var el = jQuery(this);

        if (self.isNumber(e)) {

                var qty = el.val(),
                warehouse = el.attr('name'),
                productId = el.data('product'),
                loadingContainer = el.parent().find('.loading-qty-status'),
                parentId = el.data('parent');

            jQuery('.loading-qty-status').empty();

            delay(function(){

                if (qty !== '') {

                    var data = {
                        'parentId': parentId,
                        'productId': productId,
                        'qty': qty,
                        'warehouse': warehouse,
                        'condition': 'productWarehouse',
                        'action': 'update_quantity'
                    };

                    loadingContainer.text(messageContainer.data('message-wait'));
                    //messageContainer.text(messageContainer.data('message-wait'));

                    jQuery.post(ajaxurl, data, function (data) {
                        loadingContainer.text(messageContainer.data('message-saved'));

                    }, "html").done(function () {
                        console.log('function setEventlisteners warehouses');
                    });

                }

            }, 500);

        } else {
            el.val('');
            return false;
        }





    });


};

/**
 * is number
 * @param evt
 * @returns {boolean}
 */
rapidStockManagerWarehousePD.prototype.isNumber = function (evt) {
    evt = (evt) ? evt : window.event;
    var charCode = (evt.which) ? evt.which : evt.keyCode;
    if (charCode > 31 && (charCode < 48 || charCode > 57)) {
        return false;
    }
    return true;
};



jQuery(function(){

   new rapidStockManagerWarehousePD();

});