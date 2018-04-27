/**
 * constructor
 */
var rapidStockManager = function (settings) {
	if (!settings) { return false;}

    if (jQuery(settings.common.clMainContainer).length) {
		this.setElements(settings);
        if (settings.init){
            this.init();
        }

    }else{
		this.setElements(settings);
	}

};

/**
 * main constructor
 */
rapidStockManager.prototype.init = function() {
    var self = this;
    //if not records dont show table
    this.removeTable();
    //simple
    this.updateSimpleQuantity();
    this.rowIsUpdated();

    //variant
    this.inputIsUpdated();
    this.updateVariantQuantity();
    //calculate the total rows
    this.calculateVariantTotalAllRows();
    //update entire row changes
    this.updateVariantEntireRow();

    //update text event listener to set or adjust for both simple and variant views
    this.updateActionChangeText();

    this.initFilter();
    this.sortBySubmit();

    //enable datepicker
    this.initDatePicker();

    //enable copy to clipboard
    this.canCopy = false;
    this.zeroClipboard = null;
    this.initCopyToClipboard();
};



/**
 * Initialise copy to clipboard using ZeroClipboard plugin
 * and attaches it to all copyToClipboardSelector elements
 */
rapidStockManager.prototype.initCopyToClipboard = function() {
    var $copyActionElement = jQuery(this.common.copyToClipboardSelector);
    if( !$copyActionElement || $copyActionElement.length < 1 ){
        return;
    }
    if( typeof ZeroClipboard === "undefined" || !ZeroClipboard ){
        this.canCopy = false;
        return;
    }
    this.canCopy = true;
    var self     = this;
    $copyActionElement.show();
    this.zeroClipboard = new ZeroClipboard($copyActionElement);
    this.zeroClipboard.on( 'ready', function(event) {
        self.zeroClipboard.on( 'copy', function(event) {
        } );

        self.zeroClipboard.on( 'aftercopy', function(event) {
            var $resultSpan = jQuery(event.target).next(".copy-result:first");
            $resultSpan.fadeIn('slow',function(){
                $resultSpan.animate({'opacity':1},1000,function(){
                    $resultSpan.fadeOut('slow');
                });
            });
        } );
    });
    this.zeroClipboard.on( 'error', function(e) {
        self.canCopy = false;
        $copyActionElement.hide();
        self.zeroClipboard.destroy();
    });
};

/**
 * set event listener for submit button
 */
rapidStockManager.prototype.sortBySubmit = function() {

    if (!this.warehouseClassEnabled) {

        jQuery(this.common.clSortBy).change(function(){
            jQuery(this).parent().submit();
        });
    }

};

/**
 * Enable jQuery DatePicker
 */

rapidStockManager.prototype.initDatePicker = function() {
    jQuery(".rsm_datepicker").datepicker({
        dateFormat:"yy-mm-dd"
    });
};


/**
 * filter table
 * get the attribute to search for sku
 */
rapidStockManager.prototype.initFilter = function() {
	

    var options = {};

    if (jQuery(this.common.clMainContainer).data(this.common.dataTableFilterEnabled)){
		var filterPlaceHolder = jQuery(this.common.clMainContainer).data(this.common.dataTableFilterInputText);
        var filterMinRows = jQuery(this.common.clMainContainer).data(this.common.dataTableFilterMinRows);
        var filterLabel = jQuery(this.common.clMainContainer).data(this.common.dataTableFilterLabel);

        options.placeholder = filterPlaceHolder;
        options.minRows = filterMinRows;
        options.label = filterLabel;

        jQuery('table').filterTable(options);
    }

};

/**
 * update action change text to let people know if they are adjusting or setting
 * update text event listener to set or adjust for both simple and variant views
 */
rapidStockManager.prototype.updateActionChangeText = function() {
    var self = this;

    //simple products selection
    jQuery(self.common.clSelectAction).change(function(){
		var text,$iconSet,$iconAdjust,$iconDeduct, $linkSpan, rowLinkTextAjust, rowLinkTextSet;

        var condition  = jQuery(this).val();
        var $container = jQuery(this).parents('table');
		var $parentRow = jQuery(this).parents('tr');
		var tableView  = $container.data(self.common.attrTableView);
        var $variantLink = $parentRow.find(self.variant.clLinksAction);
		var $variantLinkSpan = $parentRow.find(self.variant.clVariantRow + ' span');
		if (tableView === 'variant') {
			$linkSpan = $variantLink.find(self.variant.clLinksAction + ' span');
		}
		else {
            $linkSpan = $parentRow.find(self.simple.clLinksAction + ' span');
		}

        $iconSet = $parentRow.find('.fa-bolt');
        $iconAdjust = $parentRow.find('.fa-arrows-v');
        $iconDeduct = $parentRow.find('.fa-long-arrow-down');

        switch(condition) {

            case"set":
                text = $container.data(self.common.attrTextSet);
				$iconSet.show();
				$iconAdjust.hide();
                $iconDeduct.hide();
				if (tableView === 'variant'){
                    rowLinkTextAjust = $container.data('text-row-set');
                    $parentRow.find('.action-link span').text(rowLinkTextAjust);
				}

            break;

            case"adjust":
                text = $container.data(self.common.attrTextAdjust);
				$iconSet.hide();
				$iconAdjust.show();
                $iconDeduct.hide();

				if (tableView == 'variant'){
					rowLinkTextAjust = $container.data('text-row-adjust');
                    $parentRow.find('.action-link span').text(rowLinkTextAjust);
				}

            break;

            case"deduct":
                text = $container.data(self.common.attrTextDeduct);
                $iconSet.hide();
                $iconAdjust.hide();
                $iconDeduct.show();

                if (tableView == 'variant'){
                    rowLinkTextAjust = $container.data('text-row-deduct');
                    $parentRow.find('.action-link span').text(rowLinkTextAjust);
                }

            break;
        }
        
        if( $variantLink.length > 0 ) {
            $variantLink.attr('title', text);
        }

        if( $linkSpan.length ) {
            $linkSpan.text(text);
        }

        //trigger the change value when adjusting or setting the update value
        self.updateNewVariantQtyLoop($parentRow);

    });

};

/**
 * @param row - triggers the updated
 */
rapidStockManager.prototype.updateNewVariantQtyLoop = function(row) {

    //trigger the change value when adjusting or setting the update value
    row.find('.wc-qty-change').each(function(i,e) {

        var qtyValue = jQuery(this);
        if (qtyValue.val() !== '' ) {
            qtyValue.trigger('change');
        }

    });

};

/**
 * hide records of table
 */
rapidStockManager.prototype.removeTable = function() {

    jQuery(this.common.clMainContainer + ' table').each(function() {
        if (!jQuery(this).find('tbody tr').length){
            jQuery(this).remove();
        }
    });

};




/**
 * set elements required for rapid stock manager
 */
rapidStockManager.prototype.setElements = function(settings) {
    this.common = settings.common;
    this.variant = settings.variant;
    this.simple = settings.simple;
    this.tooltipSettings = settings.tooltipSettings;

    if (settings.classesEnabled.warehouse) {
        this.warehouseClassEnabled = true;
    }

    this.totalElements = jQuery(this.variant.attrCalculateTotal);
};

/**
 * event listener to check if the quantity has been updated if so let user knows the row needs to be updated
 */
rapidStockManager.prototype.rowIsUpdated = function () {

    var self = this;
    var stockSimpleQuantity = jQuery(self.simple.attrAdjustQuantity);
    var stockVariantQuantity = jQuery(self.variant.clQtyChange);
    var stockQuantityRow;

    stockSimpleQuantity.on('keypress', function (event) {
        if(event.which === 13){
            jQuery(this).parents('tr').find(self.simple.clLinksAction).trigger('click');

        }
    });

    //simple row notification
    stockSimpleQuantity.change(function() {

        var value = jQuery(this).val();
        stockQuantityRow = jQuery(this).parents('tr');

        if (value == '') {
            stockQuantityRow.removeClass(self.common.clRequiresUpdating);
        }
        else {
            stockQuantityRow.addClass(self.common.clRequiresUpdating);
        }

    });

    //adjust row notification
    stockVariantQuantity.change(function() {

        var value = jQuery(this).val(),
        stockQuantityRow = jQuery(this).parents('tr'),
        stockQuantityColumn = jQuery(this).parents('td');

        //check all rows inputs
        stockQuantityRow.addClass(self.common.clRequiresUpdating);

        if (value == '') {
            //check all the input fields that are empty
            var valueEntered = false;

            stockQuantityRow.find(stockVariantQuantity).each(function() {
                if (jQuery(this).val() != '') {
                    valueEntered = true;
                    return false;
                }
            });

            if (valueEntered == false){
                stockQuantityRow.removeClass(self.common.clRequiresUpdating);
            }
        }

        self.newAdjustedQtyValue(stockQuantityColumn,stockQuantityRow,value);

    });

};

/**
 * @param stockQuantityRow
 * @param stockQuantityColumn
 */
rapidStockManager.prototype.newAdjustedQtyValue = function (stockQuantityColumn, stockQuantityRow,value) {
    var self = this;

    //display new adjusted value to be displayed
    var updateAction = stockQuantityRow.find(self.common.clSelectAction).val();

    if (updateAction === 'adjust') {

        if (value !== '') {

            var newQuantity = 0;
            var productQuantity = stockQuantityColumn.find(self.variant.clOriginalQty).data(self.variant.attrDataOriginalQty);

            //positive number
            if (productQuantity >= 0) {
                newQuantity = (Number(productQuantity) + Number(value));
            }
            else {
                //negative number
                newQuantity = (Number(productQuantity) - Number(value));
            }
            stockQuantityColumn.find(self.variant.clNewQtyValue).html('(' +newQuantity + ')' );

        }
        else {
            stockQuantityColumn.find(self.variant.clNewQtyValue).empty();
        }

    }
    else {
        stockQuantityColumn.find(self.variant.clNewQtyValue).empty();
    }

};

/**
 * column or input field has been updated
 */
rapidStockManager.prototype.inputIsUpdated = function () {
    var self = this;
    var stockVariantQuantity = jQuery(self.variant.clQtyChange);

    stockVariantQuantity.change(function() {
        var value = jQuery(this).val();
        var parentColumn = jQuery(this).parents('td');
        var originalValue = parentColumn.find(self.variant.clOriginalQty).data(self.variant.attrDataOriginalQty);

        if (value == '') {
            parentColumn.removeClass(self.common.clRequiresUpdating);
        }
        else {
            parentColumn.addClass(self.common.clRequiresUpdating);
        }

    });

};

/**
 * update simple quantity
 */
rapidStockManager.prototype.updateSimpleQuantity = function () {
    var self = this;

    if (!this.warehouseClassEnabled) { //dont trigger this if warehouse enabled
        jQuery(this.simple.clLinksAction).click(function(e){

            e.preventDefault();
            var el = jQuery(this);
            var postId = el.data(self.simple.attrPostId);
            var parentRow = el.parents('tr');
            var stockQuantity = parentRow.find(self.simple.attrAdjustQuantity).val();
            var originalQtyValue = parentRow.find('td[data-update-input="true"]').data(self.simple.attrDataOriginalQty);
            var td = el.parents('td');

            //dont update if quantity value is nothing
            if (stockQuantity == '' ){ return false;}

            var updateAction = parentRow.find(self.common.clSelectAction).val();
            if (updateAction == '') { updateAction = 'set'; };

            //enale loading icon
            self.loadingIcons('start-loading',td);

            //check if enabled adjust then check if quantity is 0 - if so do nothing
            if (updateAction == 'adjust') {
                if (stockQuantity == 0) { return false;}
            }
            else { //set action

                //if the value is the exact same dont update and return false.
                if (originalQtyValue == stockQuantity ) { return false; }
            }

            // ajax here to update comment
            var data = {
                'post_id': postId,
                'stock_quantity': stockQuantity,
                'condition': updateAction,
                'action': 'update_quantity'
            };


            jQuery.post( ajaxurl, data, function(data) {

                if (data.status !== true) { alert('failed'); return false;
                    //try again logo icon
                }
                var total = parentRow.find(self.simple.attrTotal);

                total.attr('style', data.quantity_color);
                //update total quantity
                total.empty().html(data.stock_quantity);
                parentRow.find('td[data-update-input="true"]').data(self.simple.attrDataOriginalQty,data.stock_quantity);

                if (data.stock_status) {
                    var statusColor = data.stock_status == 'In stock' ? 'green' : 'red';
                    //update stock status
                    parentRow.find('td.stock-status').removeClass('green red').addClass(statusColor);
                    parentRow.find('td.stock-status').html(data.stock_status);

                }
            }, "json").done(function() {

                self.loadingIcons('finished-loading',td);
                self.resetAdjustInput(parentRow);

                //remove if row been modified
                parentRow.removeClass(self.common.clRequiresUpdating);
                parentRow.find('td.input-cell').removeClass(self.common.clRequiresUpdating);

            });

        });
    }


};

/**
 * loading and tick icon
 */
rapidStockManager.prototype.loadingIcons = function (condition,element) {
    if (typeof condition == 'undefined'){ return false;}
    var self = this;

    switch(condition) {
        case "start-loading":
            element.find(self.common.clSpinnerIcon).addClass('show');
            element.find(self.common.clSpinnerOverride+':visible').addClass('temp-hidden');
        break;
        case "finished-loading":
            //finish loading
            element.find(self.common.clSpinnerIcon).removeClass('show');

            element.find(self.common.clCheckIcon).addClass('show');

            setTimeout(function(){
                element.find(self.common.clCheckIcon).removeClass('show');
                element.find(self.common.clSpinnerOverride+'.temp-hidden').removeClass('temp-hidden');
            }, 1000);

        break;
    }

}

/**
 * update variant quantity
 */
rapidStockManager.prototype.updateVariantQuantity = function () {
    var self = this;

    if (!this.warehouseClassEnabled) {

        jQuery(self.variant.clLinksAction).click(function(e) {

            e.preventDefault();

            var postId = jQuery(this).data('post-id'),
                variantQuantity = jQuery(this).parent().find('.wc-qty-change').val(),
                parentVariantSingleContainer = jQuery(this).parents('.container-variant-single'),
                td = parentVariantSingleContainer.parents('td'),
                parentRow = jQuery(this).parents('tr');

            if (variantQuantity == '' ){ return false;}

            var updateAction = parentRow.find(self.common.clSelectAction).val();

            if (updateAction == '') { updateAction = 'set'; };

            if (updateAction == 'set') {
                //dont update if the value are the same
                if (variantQuantity == parentVariantSingleContainer.find('.original-qty').data('original-qty')){
                    return false;
                }
            }

            self.loadingIcons('start-loading',td);

            //add loader

            // ajax here to update comment
            var data = {
                'post_id': postId,
                'stock_quantity': variantQuantity,
                'condition': updateAction,
                'action': 'update_quantity'
            };

            jQuery.post( ajaxurl, data, function(data) {

                if (data.status !== true) { alert('failed'); return false;
                    //try again logo icon
                }

                //update color
                td.attr('style', data.quantity_color);
                //update total quantity
                parentVariantSingleContainer.find('.original-qty').data('original-qty', data.stock_quantity);
                parentVariantSingleContainer.find('.original-qty b').empty().html(data.stock_quantity);

                //color status
                //todo refactor code to make this better and cleaner
                if (data.stock_status) {
                    var color_status = data.stock_status == 'In stock' ? 'green' : 'red';
                    td.find(self.tooltipSettings.clToolTipContentHtml).find('span.stock-status').removeClass('green red').html(data.stock_status).addClass(color_status);
                }

                parentRow.removeClass(self.common.clRequiresUpdating);

                if (td.hasClass(self.common.clRequiresUpdating)){
                    td.removeClass(self.common.clRequiresUpdating);
                }

            }, "json").done(function() {

                //update the total calculatons total
                self.loadingIcons('finished-loading',td);
                self.calculateVariantTotalRow(parentRow);
                self.resetAdjustInput(parentVariantSingleContainer);
                parentRow.find('.new-qty-changes').empty(); //clear all new changes task
            });

        });

    }

};

/**
 * update the entire variant row and the values within that row
 * @param row
 */
rapidStockManager.prototype.updateVariantEntireRow = function () {
    var self = this;

    jQuery(self.variant.clVariantRow).click(function(e) {
        e.preventDefault();

        var row = jQuery(this).parents('tr');
        var td = jQuery(this).parents('td');

        var updateAction = row.find(self.common.clSelectAction).val();

        if (updateAction == '') { updateAction = 'set';};
        updateAction += '_entire_row';

        var variantsColumn = row.find(self.variant.attrDataVariantTotal);

        var data = {
            'condition': updateAction,
            'action': 'update_quantity',
            'quantities': []
        };

        variantsColumn.each(function(index) {
            var postId = jQuery(this).find('.variant-quantity-update').data('post-id');
            var quantity = jQuery(this).find('.wc-qty-change').val();
            var originalQty = jQuery(this).find('.original-qty').data('original-qty');

            if (typeof postId !== 'undefined' && typeof originalQty !== 'undefined' ) {

                if (quantity !== '' ) {

                    if (originalQty == quantity && updateAction == 'set_entire_row') {}
                    else {
                        data.quantities[index] = {
                            postId: postId,
                            quantity: quantity
                        };
                    }

                }

            }

        });



        if (jQuery.isEmptyObject(data.quantities)) { return false;}
        self.loadingIcons('start-loading',td);
        jQuery.post( ajaxurl, data, function(data) {

            if (data.status !== true) { alert('failed'); return false;
                //try again logo icon
            }

            for (var product in data.product) {

                var item = data.product[product];

                if (row.hasClass(self.common.clRequiresUpdating)){
                    row.removeClass(self.common.clRequiresUpdating);
                }

                if (row.find('a[data-post-id="'+  item.post_id +'"]').length) {

                    var column = jQuery(row.find('a[data-post-id="'+  item.post_id +'"]')).parents('td');

					column.attr('style', item.quantity_color);
                    //update total quantity
                    column.find('.original-qty').data('original-qty', item.stock_quantity);
                    column.find('.original-qty b').empty().html(item.stock_quantity);

                    //update stock in tool tip
                    if (item.stock_status) {
                        var color_status = item.stock_status == 'In stock' ? 'green' : 'red';
                        column.find(self.tooltipSettings.clToolTipContentHtml).find('span.stock-status').
                            removeClass('green red').
                            html(item.stock_status).addClass(color_status);
                    }

                    //remove variant class
                    if (column.hasClass(self.common.clRequiresUpdating)){
                        column.removeClass(self.common.clRequiresUpdating);
                    }

                }

                //change all input fields adjust to 0

            }

        }, "json").done(function(){

            self.loadingIcons('finished-loading',td);
            self.calculateVariantTotalRow(row);
            self.resetAdjustInput(row);
            self.updateNewVariantQtyLoop(row);
            row.find('.new-qty-changes').empty(); //clear all new changes task
        });


    });

};

/**
 * reset row input to 0
 * @param element
 */
rapidStockManager.prototype.resetAdjustInput = function (element) {

    element.find('input[type="number"]').val('');

};

/**
 * calculate total rows
 */
rapidStockManager.prototype.calculateVariantTotalRow = function (row) {
    var self = this;
    var totalRow = 0;
    var variantsColumn = row.find(self.variant.attrDataVariantTotal);

    variantsColumn.each(function(){
        if (typeof jQuery('.original-qty',this).data(self.variant.attrDataOriginalQty) !== 'undefined') {
            totalRow += parseInt(jQuery(this).find('.original-qty').data(self.variant.attrDataOriginalQty));
        }
    });

    //update the total
    row.find(self.variant.attrCalculateTotal).empty().html(totalRow);

};

/**
 * calculate the total rows on startup and anytime requires calculation
 * @returns totalAmountQuantity {number}
 */
rapidStockManager.prototype.calculateVariantTotalAllRows = function () {

    var totalRow = 0;
    var totalAmountQuantity = 0;
    var variantsColumn;
    var self = this;

    //loop through the total
    this.totalElements.each(function(){

        //find all items to calculate
        variantsColumn = jQuery(this).parent().find(self.variant.attrDataVariantTotal);

        variantsColumn.each(function(){

            if (typeof jQuery('.original-qty',this).data(self.variant.attrDataOriginalQty) !== 'undefined'){
                totalRow += parseInt(jQuery('.original-qty',this).data(self.variant.attrDataOriginalQty));
            }

        });

        totalAmountQuantity += totalRow;
        jQuery(this).empty().html(totalRow);
        totalRow = 0;

    });

    return totalAmountQuantity;

};

jQuery('document').ready(function(){
    var qTip = new rapidStockManagerQtip ({
             cltoolTipAttr: '.tooltip-qtip',
            dataTipContent: 'data-qtip-content',
            dataTipTitle: 'data-qtip-title',
            clToolTipHtml: '.tooltip-qtip-html',
            toolTipStyling: 'qtip-light qtip-shadow',
            clToolTipContentHtml: '.tool-tip-content'
    });

    qTip.init();

    new rapidStockManager({
        init: true,
        classesEnabled: {
          warehouse: false
        },
        //used to update things in module
        tooltipSettings: {
            cltoolTipAttr: '.tooltip-qtip',
            dataTipContent: 'data-qtip-content',
            dataTipTitle: 'data-qtip-title',
            clToolTipHtml: '.tooltip-qtip-html',
            toolTipStyling: 'qtip-light qtip-shadow',
            clToolTipContentHtml: '.tool-tip-content'
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
            attrTextDeductRow: 'text-row-deduct',
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

    });
});

/////////////// QTIP MODULE //////////////////////


var rapidStockManagerQtip = function (settings) {
    this.tooltipSettings = settings;
};

rapidStockManagerQtip.prototype.init = function () {
    var self = this;

    jQuery(self.tooltipSettings.cltoolTipAttr).qtip({
        content: {
            text: function(event, api) {
                // Retrieve content from custom attribute of the $('.selector') elements.
                return jQuery(this).attr(self.tooltipSettings.dataTipContent);
            },
            title: function(event, api) {
                // Retrieve content from custom attribute of the $('.selector') elements.
                return jQuery(this).attr(self.tooltipSettings.dataTipTitle);
            }
        },
        style: {
            classes: self.tooltipSettings.toolTipStyling
        }

    });

    jQuery(self.tooltipSettings.clToolTipHtml).qtip({
        content: {
            text: function(event, api) {
                var clone = jQuery(this).next(self.tooltipSettings.clToolTipContentHtml).clone();
                return clone;
            },
            title: function(event, api) {
                return jQuery(this).attr(self.tooltipSettings.dataTipTitle);
            }
        },
        style: {
            classes: self.tooltipSettings.toolTipStyling
        }

    });

};

/////////////// END QTIP MODULE //////////////////////