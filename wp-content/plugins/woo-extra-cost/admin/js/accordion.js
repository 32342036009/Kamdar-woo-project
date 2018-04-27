jQuery(document).ready(function () {

    jQuery("#accordian_global").accordion({
        collapsible: true, heightStyle: "content",
    });
    jQuery("#accordion_product").accordion({
        collapsible: true, heightStyle: "content"
    });
    jQuery("#accordion_cart").accordion({
        collapsible: true, heightStyle: "content"
    });
    jQuery('.plusbtn').click(function () {
        jQuery(".wc_input_table_own").append('<tr><td><input type="text" class="txtbox" value="" /></td><td><input type="text" class="txtbox" value="" /></td><td><input type="text" class="txtbox" value="" /></td><td><input type="text" class="txtbox" value="" /></td></tr>');
    });
    jQuery('.minusbtn').click(function () {
        if (jQuery(".wc_input_table_own tr").length != 2)
        {
            //jQuery(".wc_input_table_own tr:last-child").remove();
            jQuery('.wc_input_table_own').closest('tr').remove();
        }
        else
        {
            alert("You cannot delete first row");
        }
    });

});