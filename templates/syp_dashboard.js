/**
 * Disable full functionnality for non-premium users
 */
jQuery('#syp_dashboard_data_content').on('click', 'button', function()
{
	if (SYP_ABO_STATE == 'free')
	{
		jQuery(document)
		.ajaxStart(function() {
			jQuery('button[name="synchronize"]').attr("disabled", true);
			jQuery('button[name="search_update"]').attr("disabled", true);
		})
		.ajaxComplete(function() {
			jQuery('button[name="synchronize"]').removeAttr("disabled");
			jQuery('button[name="search_update"]').removeAttr("disabled");
			jQuery('.no_update').attr('disabled', true);
		});
	}
	else
	{
		jQuery(document).ajaxComplete(function() 
		{
			jQuery('.no_update').attr('disabled', true);
		});
	}
});


/**
 * Trigger click event on ALL "Search update" buttons
 */
jQuery('#syp_dashboard').on('click', '#search_all_updates', function()
{
	var search_update_btns = jQuery('.syp_btn_search_product_updates[name="search_update"]');
	jQuery(search_update_btns).each(function()
	{
		jQuery(this).trigger('click');
	});
});


/**
 * Trigger click event on ALL "Synchronize" buttons
 */
jQuery('#syp_dashboard').on('click', '#synchronize_all', function()
{
	var synchronize_btns = jQuery('.syp_btn_search_product_updates[name="synchronize"]');
	
	synchronize_btns.each(function()
	{
		jQuery(this).trigger('click');
	});
});


/**
 * Synchronize a product with SYP
 * (Calls add_product_to_syp)
 */
jQuery('#syp_control_panel').on('click', '.syp_btn_search_product_updates[name="synchronize"]', function()
{
	jQuery('#product_accordion_content').hide();
	
	var thisbtn = jQuery(this);
	var h3_row = thisbtn.parent().parent().children();
	
	var product_id = null;
	var product_barcode = null;
	var last_update_date = null;
	var state = null;
	
	for (var i = 0; i < h3_row.length; i++) 
	{
		if (h3_row[i].className == "list-element dashboard_product_id")
		{
			product_id = jQuery(h3_row[i]).attr('value');
		}
		if (h3_row[i].className == "list-element dashboard_last_update_date")
		{
			last_update_date = jQuery(h3_row[i]);
		}
		if (h3_row[i].className == "list-element dashboard_state")
		{
			state = jQuery(h3_row[i]);
		}
	    if (h3_row[i].className == "list-element dashboard_product_barcode")
	    {
	    	product_barcode = jQuery(h3_row[i]).html();
	    }
	}
	
	var spinner = jQuery('#syp_search_product_by_barcode_spinner_' + product_barcode);
	spinner.css('display', 'inline');
	thisbtn.hide();
	
	var data = 
	{
			action: "syp_dashboard_synchronize",
			product_id: product_id,
	};
			
	jQuery.post(ajaxurl, data, function(response)
	{
		if(response == 0 || response == 'false')
		{	
			spinner.hide();
			thisbtn.show();
			thisbtn.removeAttr("name");
			thisbtn.html("Sync. error");
		}
		else
		{
			thisbtn.show();
			spinner.hide();
			thisbtn.html('Search update');
			thisbtn.attr('name', 'search_update');
			thisbtn.val(product_barcode);
			last_update_date.html(response);
			state.html(SYP_STATE_SYNCHRONIZED);
		}
	});
});

/**
 * Trigger AJAX request for retrieving product updates.
 * This one is for Dashboard page.
 */
jQuery('#syp_control_panel').on('click', 'button[name="search_update"]', function()
{
	var thisbtn = jQuery(this);
	thisbtn.hide();
	jQuery('#product_accordion_content').hide();
	var barcode = jQuery(this).attr('value');
	var spinner = jQuery('#syp_search_product_by_barcode_spinner_' + barcode);
	
	spinner.css('display', 'inline');
	
	var data = 
	{
			action: "syp_search_product_updates",
			product_barcode: barcode,
	};
	
	jQuery.post(ajaxurl, data, function(response)
	{		
		if(response == 'noupdates')
		{	
			spinner.hide();
			thisbtn.show();
			thisbtn.html("No new update");
			thisbtn.attr("class", 'no_update button-secondary');
			thisbtn.attr("disabled", true);
			thisbtn.removeAttr("name");
		}
		else
		{
			thisbtn.show();
			spinner.hide();
			thisbtn.attr('class','success button-secondary success_btn');
			thisbtn.html("Hide");
			thisbtn.removeAttr("name");
			jQuery("#product_accordion_content"+ barcode).html(response);
			jQuery(".clear").css('display','none');
			jQuery('#product_accordion_content').show();
			jQuery('.syp_btn_search_product_updates[value="'+barcode+'"]').slideDown();
		}
	});
	return false;
});

/**
 * Show or hide the product content when a update is found
 */
jQuery('#syp_control_panel').on('click', '.success_btn', function()
{
	var barcode = jQuery(this).attr('value');
	var div_update_found = jQuery("#product_accordion_content"+ barcode);
	if (div_update_found.css('display') == 'none')
	{
		jQuery(this).html('Hide');
		div_update_found.slideDown();
	}
	else
	{
		jQuery(this).html('Show updates');
		div_update_found.slideUp();
	}
});


/**
 * Update the local product with new information from Dashboard
 */
jQuery('#syp_control_panel').on('click', '#syp_import_update', function()
{
	var product = 
	{
		product_name   : jQuery.trim(jQuery("#syp_search_import_product_name").text()),
		product_content: jQuery.trim(jQuery("#syp_search_import_product_content").text()),
		product_height : jQuery.trim(jQuery("#syp_search_import_product_height").text()),
		product_weight : jQuery.trim(jQuery("#syp_search_import_product_weight").text()),
		product_width  : jQuery.trim(jQuery("#syp_search_import_product_width").text()),
		product_reference  : jQuery.trim(jQuery("#syp_search_import_product_reference").text()),
		tx_tva : jQuery.trim(jQuery("#syp_search_import_tx_tva").text()),
		barcode: jQuery(this).attr('value'),
	};

	var data = 
	{
		action: "syp_dashboard_update",
		product: product,
	};
	jQuery(document)
	.ajaxStart(function() {
		jQuery('#syp_import_update').attr("disabled", true);
		jQuery('#update_spinner').css('display','inline');
	})
	.ajaxComplete(function() {
		jQuery('#update_spinner').html('<h3 class="btn_success">Product data have been updated !</h4>')
	});
	jQuery.post(ajaxurl, data, function(response){

	});
	return false;
});

jQuery('#syp_dashboard_data_content').on('change', 'input[name="auto_update"]', function()
{
	var input = jQuery(this);
	
	if(input.attr('disabled') == 'disabled')
	{
		return false;
	}
	else
	{	
		var auto_update = input.is(':checked');
		var data = 
		{
			action: "syp_dashboard_auto_update",
			product_id: input.val(),
			auto_update: auto_update
		};
		
		jQuery.post(ajaxurl, data, function(response){});
		return false;
	}
});
