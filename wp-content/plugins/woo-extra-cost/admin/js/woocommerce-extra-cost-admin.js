(function($) {
	$(window).load(function() {
		$('body').on('change', '.check_value_only_alphabetic', function(e) {

			if (this.value.match(/[^a-zA-Z]/g)) {
				this.value = this.value.replace(/[^a-zA-Z]/g, '');
			}
			var current_selected_id = this.id;
			var extra_cost_title_id = current_selected_id.substring(27);
			$.ajax({
				type: "POST",
				url: ajaxurl,
				async: false,
				data: ({
					action: 'get_custom_table_values',
					extra_cost_table_value: 'country',
					extra_cost_title_id: extra_cost_title_id
				}),
				success: function(data) {
					var exsitst_values = data;
					var country_chk_value = $('#country_values_array').html();
					var country_chk_array = country_chk_value.split(',');
					var selected_country = $('#' + current_selected_id).attr('value');
					if (selected_country != '') {
						for (var i = 0; i < country_chk_array.length; i++) {
							if (country_chk_array[i] == selected_country) {
								var wc_ec_country_err_msg = $('#wc_ec_country_err_msg').html();
								var country_val_arr = wc_ec_country_err_msg.split('/');
								alert(country_val_arr[0] + '' + selected_country + '' + country_val_arr[1]);
								var set_current_selected_id = '#' + current_selected_id;
								if (exsitst_values != '') {
									$(set_current_selected_id).attr('value', exsitst_values);
								} else {
									$(set_current_selected_id).val('');
								}
							}
						}
					}
				}
			});
		});

		$('body').on('keyup', '.only_alphabetic_values', function(e) {
			var regex = /^[a-zA-Z 0-9 ]*$/;
			if (!regex.test($(this).val())) {
				var remove_special_chr = $(this).val().replace(/[^a-zA-Z 0-9  ]/gi, '');
				$(this).val(remove_special_chr);
			}
		});

		$('body').on('change', '.check_cart_total', function(e) {
			var phone = $(this).val();
			intRegex = /[0-9 -()+]+$/;
			if (e.keyCode != 8) {
				if ((!intRegex.test(phone))) {
					if ((phone.length == 0) && (!intRegex.test(phone))) {
						var get_valid_charge = $('#wc_ec_values_err_msg').html();
						alert(get_valid_charge);
						$(this).val('');
						return false;
					}
				}
			}
			var current_selected_id = this.id;
			var extra_cost_title_id = current_selected_id.substring(30);
			$.ajax({
				type: "POST",
				url: ajaxurl,
				async: false,
				data: ({
					action: 'get_custom_table_values',
					extra_cost_table_value: 'cart_toal',
					extra_cost_title_id: extra_cost_title_id
				}),
				success: function(data) {

					var exsitst_values = data;
					var cart_total_chk_value = $('#cart_total_values_array').html();
					var cart_total_chk_array = cart_total_chk_value.split(',');
					var selected_cart_total = $('#' + current_selected_id).attr('value');

					if (selected_cart_total == 0 || selected_cart_total < 0) {
						var get_valid_charge = $('#wc_ec_values_err_msg').html();
						alert(get_valid_charge);
						$('#' + current_selected_id).val(exsitst_values);
						return false;
					} else {
						if (selected_cart_total != '') {
							for (var i = 0; i < cart_total_chk_array.length; i++) {
								if (cart_total_chk_array[i] == selected_cart_total) {
									var wc_ec_cart_total_err_msg = $('#wc_ec_cart_total_err_msg').html();
									var cart_total_val_arr = wc_ec_cart_total_err_msg.split('/');
									alert(cart_total_val_arr[0] + '' + selected_cart_total + '' + cart_total_val_arr[1]);
									var set_current_selected_id = '#' + current_selected_id;
									if (exsitst_values != '') {
										$(set_current_selected_id).attr('value', exsitst_values);
										return false;
									} else {
										$(set_current_selected_id).val('');
										return false;
									}
								}
							}
						}
					}
				}
			});
		});

		$('body').on('change', '.check_cart_item', function(e) {
			var phone = $(this).val();
			intRegex = /[0-9 -()+]+$/;
			if (e.keyCode != 8) {
				if ((!intRegex.test(phone))) {
					if ((phone.length == 0) && (!intRegex.test(phone))) {
						var get_valid_charge = $('#wc_ec_values_err_msg').html();
						alert(get_valid_charge);
						$(this).val('');
						return false;
					}
				}
			}
			var current_selected_id = this.id;
			var extra_cost_title_id = current_selected_id.substring(29);
			$.ajax({
				type: "POST",
				url: ajaxurl,
				async: false,
				data: ({
					action: 'get_custom_table_values',
					extra_cost_table_value: 'cart_item',
					extra_cost_title_id: extra_cost_title_id
				}),
				success: function(data) {
					var exsitst_values = data;
					var cart_item_chk_value = $('#cart_item_values_array').html();
					var cart_item_chk_array = cart_item_chk_value.split(',');
					var selected_cart_item = $('#' + current_selected_id).attr('value');
					if (selected_cart_item == 0 || selected_cart_item < 0) {
						var get_valid_charge = $('#wc_ec_values_err_msg').html();
						alert(get_valid_charge);
						$('#' + current_selected_id).val(exsitst_values);
						return false;
					} else {
						if (selected_cart_item != '') {
							for (var i = 0; i < cart_item_chk_array.length; i++) {
								if (cart_item_chk_array[i] == selected_cart_item) {
									var wc_ec_cart_qty_err_msg = $('#wc_ec_cart_quantity_err_msg').html();
									var cart_qty_val_arr = wc_ec_cart_qty_err_msg.split('/');
									alert(cart_qty_val_arr[0] + '' + selected_cart_item + '' + cart_qty_val_arr[1]);
									var set_current_selected_id = '#' + current_selected_id;
									if (exsitst_values != '') {
										$(set_current_selected_id).attr('value', exsitst_values);
										return false;
									} else {
										$(set_current_selected_id).val('');
										return false;
									}
								}
							}
						}
					}
				}
			});
		});

		$('body').on('change', '.check_cart_weight', function(e) {
			var phone = $(this).val();
			intRegex = /[0-9 -()+]+$/;
			if (e.keyCode != 8) {
				if ((!intRegex.test(phone))) {
					if ((phone.length == 0) && (!intRegex.test(phone))) {
						var get_valid_charge = $('#wc_ec_values_err_msg').html();
						alert(get_valid_charge);
						$(this).val('');
						return false;
					}
				}
			}
			var current_selected_id = this.id;
			var extra_cost_title_id = current_selected_id.substring(31);
			$.ajax({
				type: "POST",
				url: ajaxurl,
				async: false,
				data: ({
					action: 'get_custom_table_values',
					extra_cost_table_value: 'cart_weight',
					extra_cost_title_id: extra_cost_title_id
				}),
				success: function(data) {
					var exsitst_values = data;
					var cart_weight_chk_value = $('#cart_weight_values_array').html();
					var cart_weight_chk_array = cart_weight_chk_value.split(',');
					var selected_cart_weight = $('#' + current_selected_id).attr('value');
					if (selected_cart_weight == 0 || selected_cart_weight < 0) {
						var get_valid_charge = $('#wc_ec_values_err_msg').html();
						alert(get_valid_charge);
						$('#' + current_selected_id).val(exsitst_values);
						return false;
					} else {
						if (selected_cart_weight != '') {
							for (var i = 0; i < cart_weight_chk_array.length; i++) {
								if (cart_weight_chk_array[i] == selected_cart_weight) {
									var wc_ec_cart_qty_err_msg = $('#wc_ec_cart_weight_err_msg').html();
									var cart_qty_val_arr = wc_ec_cart_qty_err_msg.split('/');
									alert(cart_qty_val_arr[0] + '' + selected_cart_weight + '' + cart_qty_val_arr[1]);
									var set_current_selected_id = '#' + current_selected_id;
									if (exsitst_values != '') {
										$(set_current_selected_id).attr('value', exsitst_values);
										return false;
									} else {
										$(set_current_selected_id).val('');
										return false;
									}
								}
							}
						}
					}
				}
			});
		});

		// Remove extra cost based on country

		$('.wc_extra_cost_tbl .remove_tax_rates').click(function() {
			var $tbody = $('.wc_extra_cost_tbl').find('tbody');
			var wc_ec_delete_confirm_err_msg = $('#wc_ec_delete_confirm_err_msg').html();
			var confirm_remove = confirm(wc_ec_delete_confirm_err_msg);
			if (confirm_remove == true) {
				var $tbody = $('.wc_extra_cost_tbl').find('tbody');
				if ($tbody.find('tr.current').size() > 0) {
					$current = $tbody.find('tr.current');
					$current.find('input').val('');
					$current.find('input.remove_cost_rate').val('1');

					$current.each(function() {
						if ($(this).is('.new'))
						$(this).remove();
						else
						$(this).hide();
					});
				}
				return false;
			} else {
				return;
			}

		});
		
		
		
		// Remove extra cost based on paymentgateway

		$('.wc_extra_cost_based_paymentgateway .remove_tax_rates').click(function() {
			var $tbody = $('.wc_extra_cost_based_paymentgateway').find('tbody');
			if ($tbody.find('tr.current').size() > 0) {
				var wc_ec_delete_confirm_err_msg = $('#wc_ec_delete_confirm_err_msg').html();
				var confirm_remove = confirm(wc_ec_delete_confirm_err_msg);

				if (confirm_remove == true) {
					$current = $tbody.find('tr.current');
					$current.find('input').val('');
					$current.find('input.remove_cost_rate').val('1');

					$current.each(function() {
						if ($(this).is('.new')) {
							$(this).remove();
						}else{
							$(this).remove();
							//$(this).hide();
						}
					});
				}
			} else {
				var wc_ec_not_select_row_err_msg = $('#wc_ec_not_select_row_err_msg').html();
				alert(wc_ec_not_select_row_err_msg);
			}
		});



		// Remove extra cost based on product coupon
		$('.wc_extra_cost_based_coupon .remove_tax_rates').click(function() {
			var $tbody = $('.wc_extra_cost_based_coupon').find('tbody');
			if ($tbody.find('tr.current').size() > 0) {
				var wc_ec_delete_confirm_err_msg = $('#wc_ec_delete_confirm_err_msg').html();
				var confirm_remove = confirm(wc_ec_delete_confirm_err_msg);

				if (confirm_remove == true) {
					$current = $tbody.find('tr.current');
					$current.find('input').val('');
					$current.find('input.remove_cost_rate').val('1');

					$current.each(function() {
						if ($(this).is('.new')) {
							$(this).remove();
						}else{
							$(this).remove();
							//$(this).hide();
						}
					});
				}
			} else {
				var wc_ec_not_select_row_err_msg = $('#wc_ec_not_select_row_err_msg').html();
				alert(wc_ec_not_select_row_err_msg);
			}
		});

		// Remove extra cost based on quantity

		$('.wc_extra_cost_based_qty .remove_tax_rates').click(function() {
			var $tbody = $('.wc_extra_cost_based_qty').find('tbody');
			if ($tbody.find('tr.current').size() > 0) {
				var wc_ec_delete_confirm_err_msg = $('#wc_ec_delete_confirm_err_msg').html();
				var confirm_remove = confirm(wc_ec_delete_confirm_err_msg);

				if (confirm_remove == true) {
					$current = $tbody.find('tr.current');
					$current.find('input').val('');
					$current.find('input.remove_cost_cart_total').val('1');

					$current.each(function() {
						if ($(this).is('.new'))
						$(this).remove();
						else
						$(this).hide();
					});
				}
			} else {
				var wc_ec_not_select_row_err_msg = $('#wc_ec_not_select_row_err_msg').html();
				alert(wc_ec_not_select_row_err_msg);
			}

		});


		// Remove extra cost based on product tag

		$('.wc_extra_cost_based_product_tag .remove_tax_rates').click(function() {
			var $tbody = $('.wc_extra_cost_based_product_tag').find('tbody');
			if ($tbody.find('tr.current').size() > 0) {
				var wc_ec_delete_confirm_err_msg = $('#wc_ec_delete_confirm_err_msg').html();
				var confirm_remove = confirm(wc_ec_delete_confirm_err_msg);
				if (confirm_remove == true) {
					$current = $tbody.find('tr.current');
					$current.find('input').val('');
					$current.find('input.remove_cost_rate').val('1');

					$current.each(function() {
						if ($(this).hasClass('.new')) {
							$(this).remove();
						}else{
							$(this).remove();
							//$(this).hide();
						}
					});
				}
			} else {
				var wc_ec_not_select_row_err_msg = $('#wc_ec_not_select_row_err_msg').html();
				alert(wc_ec_not_select_row_err_msg);
			}
		});

		var max_chars = 17;
		$('.main.wc_extra_cost_main input[type="number"]').keydown(function(e) {
			if ($(this).val().length >= max_chars) {
				$(this).val($(this).val().substr(0, max_chars));
			}
		});

		$('.main.wc_extra_cost_main input[type="number"]').keyup(function(e) {
			if ($(this).val().length >= max_chars) {
				$(this).val($(this).val().substr(0, max_chars));
			}
		});

		// Remove extra cost based on country

		$('.wc_extra_cost_based_product_cat .remove_tax_rates').click(function() {
			var $tbody = $('.wc_extra_cost_based_product_cat').find('tbody');
			if ($tbody.find('tr.current').size() > 0) {
				var wc_ec_delete_confirm_err_msg = $('#wc_ec_delete_confirm_err_msg').html();
				var confirm_remove = confirm(wc_ec_delete_confirm_err_msg);

				if (confirm_remove == true) {
					$current = $tbody.find('tr.current');
					$current.find('input').val('');
					$current.find('input.remove_cost_rate').val('1');

					$current.each(function() {
						if ($(this).hasClass('.new')) {
							$(this).remove();
						}else {
							$(this).remove();
						}
					});
				}
			} else {
				var wc_ec_not_select_row_err_msg = $('#wc_ec_not_select_row_err_msg').html();
				alert(wc_ec_not_select_row_err_msg);
			}

		});

		// Remove extra cost based on product

		/*jQuery(document).on('keyup', ".wc_extra_cost_main input[type='text']", function() {
			jQuery.validate();
		});

		jQuery(document).on('click', "form#mainform", function() {
			jQuery.validate();
		});*/

		jQuery("input[name='save']").on("click", function() {

			var temp = 0;
			//jQuery.validate();
			jQuery('.globalcon .ui-accordion-content').each(function() {				
				var len = jQuery(this).find(".error").length;
				
				if (len >= 1) {
					temp = 1;
					jQuery(this).addClass('ui-accordion-content-active');
					jQuery(this).css('display', 'block');
				}

			});

			if (temp === 0) {
				jQuery("form#mainform").submit();
			} else {
				return false;
			}

		});

		jQuery(document).on('change', "select.ddl_coupon", function() {
			var selectval = jQuery(this).val();
			jQuery(this).addClass('this1');
			jQuery('.ddl_coupon').not(this).each(function() {
				var compval = jQuery(this).val();
				var comptxt = jQuery(this).find("option:selected").text();
				if (compval === selectval) {
					alert(comptxt + " value is already selected");
					selectval = 0;
				}

			});

			if (selectval == 0) {
				jQuery('.ddl_coupon.this1').val("");
			}
			jQuery(this).removeClass('this1');

		});


		jQuery(document).on('change', "select.product_ddl", function() {
			var selectval = jQuery(this).val();
			jQuery(this).addClass('this1');
			jQuery('.product_ddl').not(this).each(function() {
				var compval = jQuery(this).val();
				var comptxt = jQuery(this).find("option:selected").text();
				if (compval === selectval) {
					alert(comptxt + " value is already selected");
					selectval = 0;
				}
			});

			if (selectval == 0) {
				jQuery('.product_ddl.this1').val("");
			}
			jQuery(this).removeClass('this1');

		});
		
		
		jQuery(document).on('change', "select.ddl_paymentgateway", function() {
			var selectval = jQuery(this).val();
			jQuery(this).addClass('this1');
			jQuery('.ddl_paymentgateway').not(this).each(function() {
				var compval = jQuery(this).val();
				var comptxt = jQuery(this).find("option:selected").text();
				if (compval === selectval) {
					alert(comptxt + " value is already selected");
					selectval = 0;
				}
			});

			if (selectval == 0) {
				jQuery('.ddl_paymentgateway.this1').val("");
			}
			jQuery(this).removeClass('this1');

		});

		jQuery(document).on('change', "select.ddl_rolebase", function() {
			var selectval = jQuery(this).val();
			jQuery(this).addClass('this1');
			jQuery('.ddl_rolebase').not(this).each(function() {
				var compval = jQuery(this).val();
				var comptxt = jQuery(this).find("option:selected").text();
				if (compval === selectval) {
					alert(comptxt + " value is already selected");
					selectval = 0;
				}
			});

			if (selectval == 0) {
				jQuery('.ddl_rolebase.this1').val("");
			}
			jQuery(this).removeClass('this1');

		});

		jQuery(document).on('change', "select.ddl_product_cat", function() {
			var selectval = jQuery(this).val();
			jQuery(this).addClass('this1');
			jQuery('.ddl_product_cat').not(this).each(function() {
				var compval = jQuery(this).val();
				var comptxt = jQuery(this).find("option:selected").text();
				if (compval === selectval) {
					alert(comptxt + " value is already selected");
					selectval = 0;
				}

			});

			if (selectval == 0) {
				jQuery('.ddl_product_cat.this1').val("");
			}
			jQuery(this).removeClass('this1');

		});

		jQuery(document).on('change', "select.ddl_coupon", function() {
			var selectval = jQuery(this).val();
			jQuery(this).addClass('this1');
			jQuery('.ddl_coupon').not(this).each(function() {
				var compval = jQuery(this).val();
				var comptxt = jQuery(this).find("option:selected").text();
				if (compval === selectval) {
					alert(comptxt + " value is already selected");
					selectval = 0;
				}
			});

			if (selectval == 0) {
				jQuery('.ddl_coupon.this1').val("");
			}
			jQuery(this).removeClass('this1');

		});

		jQuery(document).on('change', "select.ddl_product_tag", function() {
			var selectval = jQuery(this).val();
			jQuery(this).addClass('this1');
			jQuery('.ddl_product_tag').not(this).each(function() {
				var compval = jQuery(this).val();
				var comptxt = jQuery(this).find("option:selected").text();
				if (compval === selectval) {
					alert(comptxt + " value is already selected");
					selectval = 0;
				}

			});

			if (selectval == 0) {
				jQuery('.ddl_product_tag.this1').val("");
			}
			jQuery(this).removeClass('this1');
		});

		$('.wc_extra_cost_based_product .remove_tax_rates').click(function() {
			var $tbody = $('.wc_extra_cost_based_product').find('tbody');
			if ($tbody.find('tr.current').size() > 0) {
				var wc_ec_delete_confirm_err_msg = $('#wc_ec_delete_confirm_err_msg').html();
				var confirm_remove = confirm(wc_ec_delete_confirm_err_msg);

				if (confirm_remove == true) {
					$current = $tbody.find('tr.current');
					$current.find('input').val('');
					$current.find('input.remove_cost_rate').val('1');
					var removeItem = $current.find('select').val();
					$current.each(function() {
						if ($(this).hasClass('.new')) {
							$(this).remove();
							/*selectValues = jQuery.grep(selectValues, function(value) {
								return value != parseInt(removeItem);
							});*/

						} else {
							$(this).remove();
							
							//$(this).hide();
							/*if(typeof selectValues != 'undefined') {
								selectValues = jQuery.grep(selectValues, function(value) {
									//console.log(value);
									return value != parseInt(removeItem);
								});
							}*/

						}
					});
				}
			} else {
				var wc_ec_not_select_row_err_msg = $('#wc_ec_not_select_row_err_msg').html();
				alert(wc_ec_not_select_row_err_msg);
			}
		});


		// Remove extra cost based on country

		$('.wc_extra_cost_tbl_country .remove_tax_rates').click(function() {
			var $tbody = $('.wc_extra_cost_tbl_country').find('tbody');
			if ($tbody.find('tr.current').size() > 0) {
				var wc_ec_delete_confirm_err_msg = $('#wc_ec_delete_confirm_err_msg').html();
				var confirm_remove = confirm(wc_ec_delete_confirm_err_msg);

				if (confirm_remove == true) {
					$current = $tbody.find('tr.current');
					$current.find('input').val('');
					$current.find('input.remove_cost_rate').val('1');

					$current.each(function() {						
						if ($(this).hasClass('.new')) {
							
							$(this).remove();
						}
						else {
							$(this).remove();
							//$(this).hide();
						}
					});
				}
			} else {
				var wc_ec_not_select_row_err_msg = $('#wc_ec_not_select_row_err_msg').html();
				alert(wc_ec_not_select_row_err_msg);
			}
		});


		// Remove extra cost based on cart weight

		$('.wc_extra_cost_based_weight .remove_tax_rates').click(function() {
			var $tbody = $('.wc_extra_cost_based_weight').find('tbody');
			if ($tbody.find('tr.current').size() > 0) {
				var wc_ec_delete_confirm_err_msg = $('#wc_ec_delete_confirm_err_msg').html();
				var confirm_remove = confirm(wc_ec_delete_confirm_err_msg);

				if (confirm_remove == true) {
					$current = $tbody.find('tr.current');
					$current.find('input').val('');
					$current.find('input.remove_cost_rate').val('1');

					$current.each(function() {
						if ($(this).is('.new'))
						$(this).remove();
						else
						$(this).hide();
					});
				}
			} else {
				var wc_ec_not_select_row_err_msg = $('#wc_ec_not_select_row_err_msg').html();
				alert(wc_ec_not_select_row_err_msg);
			}
		});

		// Remove extra cost based on user role

		$('.wc_extra_cost_based_user_role .remove_tax_rates').click(function() {
			var $tbody = $('.wc_extra_cost_based_user_role').find('tbody');
			if ($tbody.find('tr.current').size() > 0) {
				var wc_ec_delete_confirm_err_msg = $('#wc_ec_delete_confirm_err_msg').html();
				var confirm_remove = confirm(wc_ec_delete_confirm_err_msg);
				if (confirm_remove == true) {
					$current = $tbody.find('tr.current');
					$current.find('input').val('');
					$current.find('input.remove_cost_rate').val('1');

					$current.each(function() {
						if ($(this).hasClass('.new')) {
							$(this).remove();
						} else {
							$(this).remove();
							//$(this).hide();
						}
					});
				}
			} else {
				var wc_ec_not_select_row_err_msg = $('#wc_ec_not_select_row_err_msg').html();
				alert(wc_ec_not_select_row_err_msg);
			}
		});

		// Remove extra cost based on cart total

		$('.wc_extra_cost_cart_total .remove_tax_rates').click(function() {
			var $tbody = $('.wc_extra_cost_cart_total').find('tbody');
			if ($tbody.find('tr.current').size() > 0) {
				var wc_ec_delete_confirm_err_msg = $('#wc_ec_delete_confirm_err_msg').html();
				var confirm_remove = confirm(wc_ec_delete_confirm_err_msg);
				if (confirm_remove == true) {
					$current = $tbody.find('tr.current');
					$current.find('input').val('');
					$current.find('input.remove_cost_cart_total').val('1');
					$current.each(function() {
						if ($(this).is('.new'))
						$(this).remove();
						else
						$(this).hide();
					});
				}
			} else {
				var wc_ec_not_select_row_err_msg = $('#wc_ec_not_select_row_err_msg').html();
				alert(wc_ec_not_select_row_err_msg);
			}
		});

		// Remove extra cost based on cart item
		$('.wc_extra_cost_cart_item .remove_tax_rates_item_row').click(function() {
			var wc_ec_delete_confirm_err_msg = $('#wc_ec_delete_confirm_err_msg').html();
			var confirm_remove = confirm(wc_ec_delete_confirm_err_msg);
			if (confirm_remove == true) {
				var $tbody = $('.wc_extra_cost_cart_item').find('tbody');
				if ($tbody.find('tr.current').size() > 0) {
					$current = $tbody.find('tr.current');
					$current.find('input').val('');
					$current.find('input.remove_cost_cart_item').val('1');
					$current.each(function() {
						if ($(this).is('.new'))
						$(this).remove();
						else
						$(this).hide();
					});
				} else {
					var wc_ec_not_select_row_err_msg = $('#wc_ec_not_select_row_err_msg').html();
					alert(wc_ec_not_select_row_err_msg);
				}
				return false;
			} else {
				return;
			}
		});

		// Remove extra cost based on cart weight

		$('.wc_extra_cost_cart_weight .remove_tax_rates_weight_row').click(function() {
			var wc_ec_delete_confirm_err_msg = $('#wc_ec_delete_confirm_err_msg').html();
			var confirm_remove = confirm(wc_ec_delete_confirm_err_msg);
			if (confirm_remove == true) {
				var $tbody = $('.wc_extra_cost_cart_weight').find('tbody');
				if ($tbody.find('tr.current').size() > 0) {
					$current = $tbody.find('tr.current');
					$current.find('input').val('');
					$current.find('input.remove_cost_cart_weight').val('1');
					$current.each(function() {
						if ($(this).is('.new'))
						$(this).remove();
						else
						$(this).hide();
					});
				} else {
					var wc_ec_not_select_row_err_msg = $('#wc_ec_not_select_row_err_msg').html();
					alert(wc_ec_not_select_row_err_msg);
				}
				return false;
			} else {
				return;
			}
		});

		/*
		*add new row based on product tag
		*/
		$('.wc_extra_cost_based_product_tag .insert').click(function() {
			var $tbody = $('.wc_extra_cost_based_product_tag').find('tbody');
			var size = $tbody.find('tr').size();
			var get_select_cat = $('.cls_get_product_tag').html();

			var code_html = '';
			code_html += '<tr class="new"><td class="name" width="8%"><input   type="text" id="" class="" name="extra_cost_based_product_tag_title[]" /></td>';
			code_html += ' <td class="name" width="40%"><select class="wc_product_chk_value ddl_product_tag" name="extra_cost_based_product_tag_name[]" id="">';
			code_html += get_select_cat;
			code_html += '</select></td>';
			code_html += '<td class="rate" width="48%"><input type="number"  step="any"  class="check_valid_charge" placeholder="0" name="extra_cost_based_product_tag_amount[]" /></td>';

			if ($tbody.find('tr.current').size() > 0) {
				$tbody.find('tr.current').after(code_html);
			} else {
				$tbody.append(code_html);
			}
			return false;
		});

		// Add new row based on product category

		$('.wc_extra_cost_based_product_cat .insert').click(function() {
			var $tbody = $('.wc_extra_cost_based_product_cat').find('tbody');
			var size = $tbody.find('tr').size();
			var get_select_cat = $('.cls_get_product_cat').html();
			var code_html = '';
			code_html += '<tr class="new"><td class="name" width="8%"><input   type="text" id="" class="" name="extra_cost_based_product_category_title[]" /></td>';
			code_html += ' <td class="name" width="40%"><select class="wc_product_chk_value ddl_product_cat" name="extra_cost_based_product_category_name[]" id="">';
			code_html += get_select_cat;
			code_html += '</select></td>';
			code_html += '<td class="rate" width="48%"><input type="number"  step="any"  class="check_valid_charge" placeholder="0" name="extra_cost_based_product_category_amount[]" /></td>';

			if ($tbody.find('tr.current').size() > 0) {
				$tbody.find('tr.current').after(code_html);
			} else {
				$tbody.append(code_html);
			}
			return false;
		});

		// Add new row based on user role

		$('.wc_extra_cost_based_user_role .insert').click(function() {
			var $tbody = $('.wc_extra_cost_based_user_role').find('tbody');
			var size = $tbody.find('tr').size();
			var get_select = $('.cls_get_user_role').html();

			var code_html = '';
			code_html += '<tr class="new"><td class="name" width="8%"><input   type="text" id="" class="" name="extra_cost_based_user_role_title[]" /></td>';
			code_html += ' <td class="name" width="40%"><select class="wc_product_chk_value ddl_rolebase"  name="extra_cost_based_user_role_name[]" id="">';
			code_html += get_select;
			code_html += '</select></td>';
			code_html += '<td class="rate" width="48%"><input  type="number"  step="any"  class="check_valid_charge" placeholder="0" name="extra_cost_based_user_role_amount[]" /></td>';

			if ($tbody.find('tr.current').size() > 0) {
				$tbody.find('tr.current').after(code_html);
			} else {
				$tbody.append(code_html);
			}
			return false;
		});

		// Add new row based on product coupon

		$('.wc_extra_cost_based_coupon .insert').click(function() {
			var $tbody = $('.wc_extra_cost_based_coupon').find('tbody');
			var size = $tbody.find('tr').size();
			var get_select = $('.cls_get_product_coupon').html();
			var code_html = '';
			code_html += '<tr class="new"><td class="name" width="8%"><input   type="text" id="" class="" name="extra_cost_based_product_coupon_cost_title[]" /></td>';
			code_html += ' <td class="name" width="40%"><select class="wc_product_chk_value ddl_coupon"  name="extra_cost_based_product_coupon_name[]" id="">';
			code_html += get_select;
			code_html += '</select></td>';
			code_html += '<td class="rate" width="48%"><input  type="number" step="any"  class="check_valid_charge" placeholder="0" name="extra_cost_based_product_coupon_amount[]" /></td>';

			if ($tbody.find('tr.current').size() > 0) {
				$tbody.find('tr.current').after(code_html);
			} else {
				$tbody.append(code_html);
			}

			return false;
		});


		// Add new row based on product

		$('.wc_extra_cost_based_product .insert').click(function() {
			var $tbody = $('.wc_extra_cost_based_product').find('tbody');
			var size = $tbody.find('tr').size();
			var get_select = $('.cls_get_product').html();
			var code_html = '';
			code_html += '<tr class="new"><td class="name" width="8%"><input   type="text" id="" class="" name="extra_cost_based_product_cost_title[]" /></td>';
			code_html += ' <td class="name" width="40%"><select class="wc_product_chk_value product_ddl"   name="extra_cost_based_product_name[]" id="">';
			code_html += get_select;
			code_html += '</select></td>';
			code_html += '<td class="rate" width="48%"><input type="number" step="any"  class="check_valid_charge" placeholder="0" name="extra_cost_based_product_amount[]" /></td>';

			if ($tbody.find('tr.current').size() > 0) {
				$tbody.find('tr.current').after(code_html);
			} else {
				$tbody.append(code_html);
			}
			return false;
		});
		
		// Add new row based on payment gateway

		$('.wc_extra_cost_based_paymentgateway .insert').click(function() {
			var $tbody = $('.wc_extra_cost_based_paymentgateway').find('tbody');
			var size = $tbody.find('tr').size();
			var get_select = $('.cls_get_paymentgateway').html();
			var code_html = '';
			code_html += '<tr class="new"><td class="name" width="8%"><input   type="text" id="" class="" name="extra_cost_based_paymentgateway_title[]" /></td>';
			code_html += ' <td class="name" width="40%"><select class="wc_product_chk_value ddl_paymentgateway"   name="extra_cost_based_paymentgateway_name[]" id="">';
			code_html += get_select;
			code_html += '</select></td>';
			code_html += '<td class="rate" width="48%"><input type="number" step="any"  class="check_valid_charge" placeholder="0" name="extra_cost_based_paymentgateway_amount[]" /></td>';

			if ($tbody.find('tr.current').size() > 0) {
				$tbody.find('tr.current').after(code_html);
			} else {
				$tbody.append(code_html);
			}
			return false;
		});
		
		
		
		

		// Add new row based on country
		$('.wc_extra_cost_tbl .insert').click(function() {
			var $tbody = $('.wc_extra_cost_tbl').find('tbody');
			var size = $tbody.find('tr').size();
			var code = '<tr class="new">\
				<td class="name" width="8%">\
					<input type="text" id=""   class="" name="extra_cost_based_cart_total_cost_title[]" />\
				</td>\
				<td class="name" width="40%">\
					<input type="number"  step="any" class="check_valid_charge" name="extra_cost_based_cart_total_cart_total[]" />\
				</td>\
				<td class="rate" width="48%">\
					<input type="number"  step="any"  class="check_valid_charge" placeholder="0" name="extra_cost_based_cart_total_amount[]" />\
				</td>\
			</tr>';

			if ($tbody.find('tr.current').size() > 0) {
				$tbody.find('tr.current').after(code);
			} else {
				$tbody.append(code);
			}

			return false;
		});


		// Add new row based on quantity

		$('.wc_extra_cost_based_qty .insert').click(function() {
			var $tbody = $('.wc_extra_cost_based_qty').find('tbody');
			var size = $tbody.find('tr').size();
			var code = '<tr class="new">\
				<td class="name" width="8%">\
					<input type="text" id="" class=""   name="extra_cost_based_cart_quantity_cost_title[]" />\
				</td>\
				<td class="name" width="40%">\
					<input type="number" step="any"  class="check_valid_charge" name="extra_cost_based_cart_quantity_qty[]" />\
				</td>\
				<td class="rate" width="48%">\
					<input type="number" step="any"   class="check_valid_charge" placeholder="0" name="extra_cost_based_cart_quantity_amount[]" />\
				</td>\
			</tr>';

			if ($tbody.find('tr.current').size() > 0) {
				$tbody.find('tr.current').after(code);
			} else {
				$tbody.append(code);
			}

			return false;
		});


		// Add new row based on cart weight

		$('.wc_extra_cost_based_weight .insert').click(function() {
			var $tbody = $('.wc_extra_cost_based_weight').find('tbody');
			var size = $tbody.find('tr').size();
			var code = '<tr class="new">\
				<td class="name" width="8%">\
					<input type="text" id=""    class="" name="extra_cost_based_cart_weight_title[]" />\
				</td>\
				<td class="name" width="40%">\
					<input type="number" step="any"  class="check_valid_charge" name="extra_cost_based_cart_weight_total_weight[]" />\
				</td>\
				<td class="rate" width="48%">\
					<input type="number" step="any"   class="check_valid_charge" placeholder="0" name="extra_cost_based_cart_weight_amount[]" />\
				</td>\
			</tr>';

			if ($tbody.find('tr.current').size() > 0) {
				$tbody.find('tr.current').after(code);
			} else {
				$tbody.append(code);
			}

			return false;
		});

		// Add new row based on cart weight

		$('.wc_extra_cost_tbl_country .insert').click(function() {
			console.log('hello');
			var $tbody = $('.wc_extra_cost_tbl_country').find('tbody');
			var size = $tbody.find('tr').size();
			var code = '<tr class="new">\
				<td class="name" width="8%">\
					<input type="text" id="wc_ec_country_chk_validate"   class="" name="extra_cost_based_country_code[]" />\
				</td>\
				<td class="name" width="40%">\
					<input type="text"  class=""   name="extra_cost_based_country_cost_title[]" />\
				</td>\
				<td class="rate" width="48%">\
					<input type="number" step="any"  class="check_valid_charge" placeholder="0" name="extra_cost_based_country_amount[]" />\
				</td>\
			</tr>';

			if ($tbody.find('tr.current').size() > 0) {
				$tbody.find('tr.current').after(code);
			} else {
				$tbody.append(code);
			}
			return false;
		});

		// Add new row based on cart total
		$('.wc_extra_cost_cart_total .insert').click(function() {
			var $tbody = $('.wc_extra_cost_cart_total').find('tbody');
			var size = $tbody.find('tr').size();
			var code = '<tr class="new">\
				<td class="name" width="8%">\
					<input type="text" class=""   name="extra_cost_based_cart_total_cost_title[]" />\
				</td>\
				<td class="name" width="40%">\
					<input type="number" id="" step="any"  min="0" class="check_cart_total" name="extra_cost_based_cart_total_cart_total[]" />\
				</td>\
				<td class="rate" width="48%">\
					<input type="number" step="any"  class="check_valid_charge" name="extra_cost_based_cart_total_amount[]" />\
				</td>\
			</tr>';

			if ($tbody.find('tr.current').size() > 0) {
				$tbody.find('tr.current').after(code);
			} else {
				$tbody.append(code);
			}
			return false;
		});


		// Remove new row based on cart product

		$('body').on('click', '#wc_remove_product_row_new', function() {
			location.reload();
		});


		// Remove new row in product category tab

		$('body').on('click', '#wc_remove_product_cat_row_new', function() {
			location.reload();
		});

		//Remove added row in product category tab
		$('body').on('click', '#wc_remove_product_cat_row', function() {
			var get_remove_key = $(this).attr('name');
			var explode_key = get_remove_key.substring(28);
			var get_remove_option_id = explode_key.slice(0, -1);
			var wc_ec_delete_confirm_err_msg = $('#wc_ec_delete_confirm_err_msg').html();
			var confirm_remove = confirm(wc_ec_delete_confirm_err_msg);
			if (confirm_remove == true) {
				if (get_remove_key != 'new_wc_extra_product_cat_remove') {
					$.ajax({
						type: "POST",
						url: ajaxurl,
						async: false,
						data: ({
							action: 'remove_extra_cost_product_charge',
							get_remove_option_id: get_remove_option_id
						}),
						success: function(data) {
							location.reload();
						}
					});
				}

			} else {
				return;
			}
		});

		// Check product category validation
		$('body').on('change', '.wc_pro_cat_chk_value', function() {
			var current_selected_id = this.id;
			var extra_cost_title_id = current_selected_id.substring(27);
			$.ajax({
				type: "POST",
				url: ajaxurl,
				async: false,
				data: ({
					action: 'get_option_values',
					extra_cost_title_id: extra_cost_title_id
				}),
				success: function(data) {
					var exsitst_values = data.substring(26);
					var pro_cat_chk_value = $('#selected_pro_cat_value_chk_array').html();
					var pro_cat_chk_array = pro_cat_chk_value.split(',');
					var selected_pro_cat = $('#' + current_selected_id).attr('value');
					for (var i = 0; i < pro_cat_chk_array.length; i++) {
						if (pro_cat_chk_array[i] == selected_pro_cat) {
							var wc_ec_cart_product_cat_err_msg = $('#wc_ec_cart_product_cat_err_msg').html();
							var cart_product_cat_val_arr = wc_ec_cart_product_cat_err_msg.split('/');
							alert(cart_product_cat_val_arr[0] + '' + selected_pro_cat + '' + cart_product_cat_val_arr[1]);
							var set_current_selected_id = '#' + current_selected_id;
							if (exsitst_values != '') {
								$(set_current_selected_id).attr('value', exsitst_values);
							} else {
								$(set_current_selected_id).val('');
							}
						}
					}
				}
			});
		});

		// Add new row in product tag tab

		$('body').on('click', '#wc_add_new_row_pro_tag', function() {
			var products_value = $('#product_tag_values_array').html();
			var product_array = products_value.split(',');
			var get_delete_img_path = $('#delete_img').html();
			var $tbody = $('.wc_extra_cost_on_product_tag').find('tbody');
			var size = $tbody.find('tr').size();
			var code_html = '';
			code_html += '<tr><td class="counter" width="5%"></td><td class="title" width="30%">';
			code_html += '<select name="wc_extra_product_tag_title[' + size + ']" id="wc_ec_pro_tag_chk_validate" class="wc_pro_tag_chk_value"><option value="">-- Please select product Tag --</option>';
			for (var i = 0; i < product_array.length; i++) {
				if (product_array[i] != '') {
					code_html += '<option value="' + product_array[i] + '">' + product_array[i] + '</option>';
				}
			}
			code_html += '</select></td>';
			code_html += '<td class="charge" width="30%"><input type="number"  step="any"  class="check_valid_charge" id="wc_chk_pro_tag_charge_validate" name="wc_extra_product_tag_charge[' + size + ']" value=""></td>';
			code_html += '<td class="remove" width="5%"><a href="javascript:void(0);" id="wc_remove_product_tag_row_new" name="new_wc_extra_product_tag_remove" class="extra_cost_product_tag_remove"><img src="' + get_delete_img_path + '" alt="shareimage" id="share-image-logo" width="30px" height="30px" /></a></td></tr>';
			if ($tbody.find('tr.current').size() > 0) {
				$('.wc_extra_cost_on_product_tag .wc_extra_cost_pro_tag_rates').after(code_html);

			} else {
				$('.wc_extra_cost_on_product_tag .wc_extra_cost_pro_tag_rates').append(code_html);
			}
		});

		// Remove new row in product tag

		$('body').on('click', '#wc_remove_product_tag_row_new', function() {
			location.reload();
		});

		// Remove added row in product tag

		$('body').on('click', '#wc_remove_product_tag_row', function() {
			var get_remove_key = $(this).attr('name');
			var explode_key = get_remove_key.substring(28);
			var get_remove_option_id = explode_key.slice(0, -1);
			var wc_ec_delete_confirm_err_msg = $('#wc_ec_delete_confirm_err_msg').html();
			var confirm_remove = confirm(wc_ec_delete_confirm_err_msg);
			if (confirm_remove == true) {
				if (get_remove_key != 'new_wc_extra_product_tag_remove') {
					$.ajax({
						type: "POST",
						url: ajaxurl,
						async: false,
						data: ({
							action: 'remove_extra_cost_product_charge',
							get_remove_option_id: get_remove_option_id
						}),
						success: function(data) {
							location.reload();
						}
					});
				}

			} else {
				return;
			}
		});

		// Check validation for product tag exsits or not

		$('body').on('change', '.wc_pro_tag_chk_value', function() {
			var current_selected_id = this.id;
			var extra_cost_title_id = current_selected_id.substring(27);
			$.ajax({
				type: "POST",
				url: ajaxurl,
				async: false,
				data: ({
					action: 'get_option_values',
					extra_cost_title_id: extra_cost_title_id
				}),
				success: function(data) {
					var exsitst_values = data.substring(26);
					var pro_tag_chk_value = $('#selected_pro_tag_value_chk_array').html();
					var pro_tag_chk_array = pro_tag_chk_value.split(',');
					var selected_pro_tag = $('#' + current_selected_id).attr('value');
					for (var i = 0; i < pro_tag_chk_array.length; i++) {
						if (pro_tag_chk_array[i] == selected_pro_tag) {
							var wc_ec_cart_product_tag_err_msg = $('#wc_ec_cart_product_tag_err_msg').html();
							var cart_product_tag_val_arr = wc_ec_cart_product_tag_err_msg.split('/');
							alert(cart_product_tag_val_arr[0] + '' + selected_pro_tag + '' + cart_product_tag_val_arr[1]);
							var set_current_selected_id = '#' + current_selected_id;
							if (exsitst_values != '') {
								$(set_current_selected_id).attr('value', exsitst_values);
							} else {
								$(set_current_selected_id).val('');
							}
						}
					}
				}
			});
		});

		// Add new row in product sku tab

		$('body').on('click', '#wc_add_new_row_pro_sku', function() {
			var products_value = $('#product_sku_values_array').html();
			var product_array = products_value.split(',');
			var get_delete_img_path = $('#delete_img').html();
			var $tbody = $('.wc_extra_cost_on_product_sku').find('tbody');
			var size = $tbody.find('tr').size();
			var code_html = '';
			code_html += '<tr><td class="counter" width="5%"></td><td class="title" width="30%">';
			code_html += '<select name="wc_extra_product_sku_title[' + size + ']" class="wc_pro_sku_chk_value" id="wc_ec_pro_sku_chk_validate"><option value="">-- Please select product sku --</option>';
			for (var i = 0; i < product_array.length; i++) {
				if (product_array[i] != '') {
					code_html += '<option value="' + product_array[i] + '">' + product_array[i] + '</option>';
				}
			}
			code_html += '</select></td>';
			code_html += '<td class="charge" width="30%"><input type="number" step="any" class="check_valid_charge"  id="wc_chk_pro_sku_charge_validate" name="wc_extra_product_sku_charge[' + size + ']" value=""></td>';
			code_html += '<td class="remove" width="5%"><a href="javascript:void(0);" id="wc_remove_product_sku_row_new" name="new_wc_extra_product_sku_remove" class="extra_cost_product_sku_remove"><img src="' + get_delete_img_path + '" alt="shareimage" id="share-image-logo" width="30px" height="30px" /></a></td></tr>';
			if ($tbody.find('tr.current').size() > 0) {
				$('.wc_extra_cost_on_product_sku .wc_extra_cost_pro_sku_rates').after(code_html);

			} else {
				$('.wc_extra_cost_on_product_sku .wc_extra_cost_pro_sku_rates').append(code_html);
			}
		});


		// Remove new tab for product sku

		$('body').on('click', '#wc_remove_product_sku_row_new', function() {
			location.reload();
		});

		// Remove added tab for product sku
		$('body').on('click', '#wc_remove_product_sku_row', function() {
			var get_remove_key = $(this).attr('name');
			var explode_key = get_remove_key.substring(28);
			var get_remove_option_id = explode_key.slice(0, -1);
			var wc_ec_delete_confirm_err_msg = $('#wc_ec_delete_confirm_err_msg').html();
			var confirm_remove = confirm(wc_ec_delete_confirm_err_msg);
			if (confirm_remove == true) {
				if (get_remove_key != 'new_wc_extra_product_sku_remove') {
					$.ajax({
						type: "POST",
						url: ajaxurl,
						async: false,
						data: ({
							action: 'remove_extra_cost_product_charge',
							get_remove_option_id: get_remove_option_id
						}),
						success: function(data) {
							location.reload();
						}
					});
				}

			} else {
				return;
			}
		});

		// Check validation for product sku exsits or not
		$('body').on('change', '.wc_pro_sku_chk_value', function() {
			var current_selected_id = this.id;
			var extra_cost_title_id = current_selected_id.substring(27);
			$.ajax({
				type: "POST",
				url: ajaxurl,
				async: false,
				data: ({
					action: 'get_option_values',
					extra_cost_title_id: extra_cost_title_id
				}),
				success: function(data) {
					var exsitst_values = data.substring(26);
					var pro_sku_chk_value = $('#selected_pro_sku_value_chk_array').html();
					var pro_sku_chk_array = pro_sku_chk_value.split(',');
					var selected_pro_sku = $('#' + current_selected_id).attr('value');
					for (var i = 0; i < pro_sku_chk_array.length; i++) {
						if (pro_sku_chk_array[i] == selected_pro_sku) {
							var wc_ec_cart_product_sku_err_msg = $('#wc_ec_cart_product_sku_err_msg').html();
							var cart_product_sku_val_arr = wc_ec_cart_product_sku_err_msg.split('/');
							alert(cart_product_sku_val_arr[0] + '' + selected_pro_sku + '' + cart_product_sku_val_arr[1]);
							var set_current_selected_id = '#' + current_selected_id;
							if (exsitst_values != '') {
								$(set_current_selected_id).attr('value', exsitst_values);
							} else {
								$(set_current_selected_id).val('');
							}
						}
					}
				}
			});
		});

		// Add new row in user tab

		$('body').on('click', '#wc_add_new_row_user', function() {
			var user_value = $('#user_values_array').html();
			var user_array = user_value.split(',');
			var get_delete_img_path = $('#delete_img').html();
			var $tbody = $('.wc_extra_cost_on_user').find('tbody');
			var size = $tbody.find('tr').size();
			var code_html = '';
			code_html += '<tr><td class="counter" width="5%"></td><td class="title" width="30%">';
			code_html += '<select class="wc_user_chk_value" name="wc_extra_user_title[' + size + ']" id="wc_ec_user_chk_validate"><option value="">-- Please select User --</option>';
			for (var i = 0; i < user_array.length; i++) {
				if (user_array[i] != '') {
					code_html += '<option value="' + user_array[i] + '">' + user_array[i] + '</option>';
				}
			}
			code_html += '</select></td>';
			code_html += '<td class="charge" width="30%"><input type="number" step="any"  class="check_valid_charge" id="wc_chk_user_charge_validate" name="wc_extra_user_charge[' + size + ']" value=""></td>';
			code_html += '<td class="remove" width="5%"><a href="javascript:void(0);" id="wc_remove_user_row_new" name="new_wc_extra_user_remove" class="extra_cost_user_remove"><img src="' + get_delete_img_path + '" alt="shareimage" id="share-image-logo" width="30px" height="30px" /></a></td></tr>';
			if ($tbody.find('tr.current').size() > 0) {
				$('.wc_extra_cost_on_user .wc_extra_cost_user_rates').after(code_html);

			} else {
				$('.wc_extra_cost_on_user .wc_extra_cost_user_rates').append(code_html);
			}
		});

		// Remove new user tab

		$('body').on('click', '#wc_remove_user_row_new', function() {
			location.reload();
		});

		// Remove added user tab

		$('body').on('click', '#wc_remove_user_row', function() {
			var get_remove_key = $(this).attr('name');
			var explode_key = get_remove_key.substring(21);
			var get_remove_option_id = explode_key.slice(0, -1);
			var wc_ec_delete_confirm_err_msg = $('#wc_ec_delete_confirm_err_msg').html();
			var confirm_remove = confirm(wc_ec_delete_confirm_err_msg);
			if (confirm_remove == true) {
				if (get_remove_key != 'new_wc_extra_user_remove') {
					$.ajax({
						type: "POST",
						url: ajaxurl,
						async: false,
						data: ({
							action: 'remove_extra_cost_product_charge',
							get_remove_option_id: get_remove_option_id
						}),
						success: function(data) {
							location.reload();
						}
					});
				}

			} else {
				return;
			}
		});

		// Check validation for user are exsits or not

		$('body').on('change', '.wc_user_chk_value', function() {
			var current_selected_id = this.id;
			var extra_cost_title_id = current_selected_id.substring(24);
			$.ajax({
				type: "POST",
				url: ajaxurl,
				async: false,
				data: ({
					action: 'get_option_values',
					extra_cost_title_id: extra_cost_title_id
				}),
				success: function(data) {
					var exsitst_values = data.substring(19);
					var user_chk_value = $('#selected_user_value_chk_array').html();
					var user_chk_array = user_chk_value.split(',');
					var selected_user = $('#' + current_selected_id).attr('value');
					for (var i = 0; i < user_chk_array.length; i++) {
						if (user_chk_array[i] == selected_user) {
							var wc_ec_cart_user_err_msg = $('#wc_ec_cart_user_err_msg').html();
							var cart_user_val_arr = wc_ec_cart_user_err_msg.split('/');
							alert(cart_user_val_arr[0] + '' + selected_user + '' + cart_user_val_arr[1]);
							var set_current_selected_id = '#' + current_selected_id;
							if (exsitst_values != '') {
								$(set_current_selected_id).attr('value', exsitst_values);
							} else {
								$(set_current_selected_id).val('');
							}
						}
					}
				}
			});
		});

		// Add new row in user tab

		$('body').on('click', '#wc_add_new_row_user_role', function() {
			var user_role_value = $('#user_role_values_array').html();
			var user_role_array = user_role_value.split(',');
			var get_delete_img_path = $('#delete_img').html();
			var $tbody = $('.wc_extra_cost_on_user_role').find('tbody');
			var size = $tbody.find('tr').size();
			var code_html = '';
			code_html += '<tr><td class="counter" width="5%"></td><td class="title" width="30%">';
			code_html += '<select class="wc_user_role_chk_value" id="wc_ec_user_role_chk_validate" name="wc_extra_user_role_title[' + size + ']"><option value="">-- Please select User --</option>';
			for (var i = 0; i < user_role_array.length; i++) {
				if (user_role_array[i] != '') {
					code_html += '<option value="' + user_role_array[i] + '">' + user_role_array[i] + '</option>';
				}
			}
			code_html += '</select></td>';
			code_html += '<td class="charge" width="30%"><input type="number" class="check_valid_charge" step="any"  class="check_valid_charge" id="wc_chk_user_role_charge_validate" name="wc_extra_user_role_charge[' + size + ']" value=""></td>';
			code_html += '<td class="remove" width="5%"><a href="javascript:void(0);" id="wc_remove_user_role_row_new" name="new_wc_extra_user_role_remove" class="extra_cost_user_role_remove"><img src="' + get_delete_img_path + '" alt="shareimage" id="share-image-logo" width="30px" height="30px" /></a></td></tr>';
			if ($tbody.find('tr.current').size() > 0) {
				$('.wc_extra_cost_on_user_role .wc_extra_cost_user_role_rates').after(code_html);

			} else {
				$('.wc_extra_cost_on_user_role .wc_extra_cost_user_role_rates').append(code_html);
			}
		});

		// Remove new user role tab

		$('body').on('click', '#wc_remove_user_role_row_new', function() {
			location.reload();
		});

		// Remove added user role tab

		$('body').on('click', '#wc_remove_user_role_row', function() {

			var get_remove_key = $(this).attr('name');
			var explode_key = get_remove_key.substring(26);
			var get_remove_option_id = explode_key.slice(0, -1);
			var wc_ec_delete_confirm_err_msg = $('#wc_ec_delete_confirm_err_msg').html();
			var confirm_remove = confirm(wc_ec_delete_confirm_err_msg);
			if (confirm_remove == true) {
				if (get_remove_key != 'new_wc_extra_user_role_remove') {
					$.ajax({
						type: "POST",
						url: ajaxurl,
						async: false,
						data: ({
							action: 'remove_extra_cost_product_charge',
							get_remove_option_id: get_remove_option_id
						}),
						success: function(data) {
							location.reload();
						}
					});
				}

			} else {
				return;
			}
		});

		// Check validation for user role exsists or not
		$('body').on('change', '.wc_user_role_chk_value', function() {
			var current_selected_id = this.id;
			var extra_cost_title_id = current_selected_id.substring(29);
			$.ajax({
				type: "POST",
				url: ajaxurl,
				async: false,
				data: ({
					action: 'get_option_values',
					extra_cost_title_id: extra_cost_title_id
				}),
				success: function(data) {
					var exsitst_values = data.substring(24);
					var user_role_chk_value = $('#selected_user_role_value_chk_array').html();
					var user_role_chk_array = user_role_chk_value.split(',');
					var selected_user_role = $('#' + current_selected_id).attr('value');
					for (var i = 0; i < user_role_chk_array.length; i++) {
						if (user_role_chk_array[i] == selected_user_role) {
							var wc_ec_cart_user_role_err_msg = $('#wc_ec_cart_user_role_err_msg').html();
							var cart_user_role_val_arr = wc_ec_cart_user_role_err_msg.split('/');
							alert(cart_user_role_val_arr[0] + '' + selected_user_role + '' + cart_user_role_val_arr[1]);
							var set_current_selected_id = '#' + current_selected_id;
							if (exsitst_values != '') {
								$(set_current_selected_id).attr('value', exsitst_values);
							} else {
								$(set_current_selected_id).val('');
							}
						}
					}
				}
			});
		});


		// Extra cost based on products coupons
		// Add new row in products coupons tab
		$('body').on('click', '#wc_add_new_row_coupon_product', function() {
			var product_coupon_value = $('#coupon_product_values_array').html();
			var product_coupon_array = product_coupon_value.split(',');
			var get_delete_img_path = $('#delete_img').html();
			var $tbody = $('.wc_extra_cost_on_coupon_product').find('tbody');
			var size = $tbody.find('tr').size();
			var code_html = '';
			code_html += '<tr><td class="counter" width="5%"></td><td class="title" width="30%">';
			code_html += '<select name="wc_extra_coupon_product_title[' + size + ']" id="wc_ec_coupon_product_chk_validate" class="wc_coupon_chk_value"><option value="">-- Please select coupon --</option>';
			for (var i = 0; i < product_coupon_array.length; i++) {
				if (product_coupon_array[i] != '') {
					code_html += '<option value="' + product_coupon_array[i] + '">' + product_coupon_array[i] + '</option>';
				}
			}
			code_html += '</select></td>';
			code_html += '<td class="charge" width="30%"><input step="any"  class="check_valid_charge" type="number" id="wc_chk_coupon_product_charge_validate" name="wc_extra_coupon_product_charge[' + size + ']" value=""></td>';
			code_html += '<td class="remove" width="5%"><a href="javascript:void(0);"  id="wc_remove_coupon_product_row_new" name="new_wc_extra_coupon_product_remove" class="extra_cost_coupon_product_remove"><img src="' + get_delete_img_path + '" alt="shareimage" id="share-image-logo" width="30px" height="30px" /></a></td></tr>';
			if ($tbody.find('tr.current').size() > 0) {
				$('.wc_extra_cost_on_coupon_product .wc_extra_cost_coupon_product_rates').after(code_html);

			} else {
				$('.wc_extra_cost_on_coupon_product .wc_extra_cost_coupon_product_rates').append(code_html);
			}

		});

		$('body').on('click', '#wc_remove_coupon_product_row_new', function() {
			location.reload();
		});
		$('body').on('click', '#wc_remove_coupon_product_row', function() {
			var get_remove_key = $(this).attr('name');
			var explode_key = get_remove_key.substring(31);
			var get_remove_option_id = explode_key.slice(0, -1);
			var wc_ec_delete_confirm_err_msg = $('#wc_ec_delete_confirm_err_msg').html();
			var confirm_remove = confirm(wc_ec_delete_confirm_err_msg);
			if (confirm_remove == true) {
				if (get_remove_key != 'new_wc_extra_coupon_product_remove') {
					$.ajax({
						type: "POST",
						url: ajaxurl,
						async: false,
						data: ({
							action: 'remove_extra_cost_product_charge',
							get_remove_option_id: get_remove_option_id
						}),
						success: function(data) {
							location.reload();
						}
					});
				}
			} else {
				return;
			}
		});

		$('body').on('click', '#wc_remove_coupon_product_row_new', function() {
			location.reload();
		});

		$('body').on('change', '.wc_coupon_chk_value', function() {
			var current_selected_id = this.id;
			var extra_cost_title_id = current_selected_id.substring(34);
			$.ajax({
				type: "POST",
				url: ajaxurl,
				async: false,
				data: ({
					action: 'get_option_values',
					extra_cost_title_id: extra_cost_title_id
				}),
				success: function(data) {
					var exsitst_values = data.substring(29);
					var coupon_chk_value = $('#selected_coupon_product_value_chk_array').html();
					var coupon_chk_array = coupon_chk_value.split(',');
					var selected_coupon = $('#' + current_selected_id).attr('value');
					for (var i = 0; i < coupon_chk_array.length; i++) {
						if (coupon_chk_array[i] == selected_coupon) {
							var wc_ec_cart_coupon_err_msg = $('#wc_ec_cart_coupon_err_msg').html();
							var wc_coupon_val_arr = wc_ec_cart_coupon_err_msg.split('/');
							alert(wc_coupon_val_arr[0] + '' + selected_coupon + '' + wc_coupon_val_arr[1]);
							var set_current_selected_id = '#' + current_selected_id;
							if (exsitst_values != '') {
								$(set_current_selected_id).attr('value', exsitst_values);
							} else {
								$(set_current_selected_id).val('');
							}
						}
					}
				}
			});
		});
		
		// Below function is for master settings
		$('body').on('change', '.ddl_master_setting', function(e) {
			alert('test');
	
		});
	});
	
	

})(jQuery);