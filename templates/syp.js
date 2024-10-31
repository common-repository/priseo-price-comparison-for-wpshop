/*	Define the jQuery noConflict var for the plugin	*/
var wpshop_syp = jQuery.noConflict();

wpshop_syp(document).ready( function()
{	

	/**
	 * Synchronize barcode value between SYP input and WPS input.
	 */
	jQuery(document).on('change', '.wpshop_product_attribute_barcode', function()
	{
		var barcode = jQuery('.wpshop_product_attribute_barcode').val();
		jQuery("#syp_product_barcode_id").val(barcode);
	});
	
	jQuery(document).on('change', '#syp_product_barcode_id', function()
	{
		var barcode = jQuery('#syp_product_barcode_id').val();
		jQuery(".wpshop_product_attribute_barcode").val(barcode);
	});
		
	
	
	/**
	 * Fill the different WPS fields with the product information retrieved previously.
	 */
	jQuery('#syp_product_box').on('click', '#syp_import_btn', function()
	{
		var product = 
		{
			name   : jQuery.trim(jQuery("#syp_search_import_product_name").text()),
			content: jQuery.trim(jQuery("#syp_search_import_product_content").text()),
			height : jQuery.trim(jQuery("#syp_search_import_product_height").text()),
			weight : jQuery.trim(jQuery("#syp_search_import_product_weight").text()),
			width  : jQuery.trim(jQuery("#syp_search_import_product_width").text()),
			tx_tva : jQuery.trim(jQuery("#syp_search_import_tx_tva").text()),
		};
		
		jQuery('#title-prompt-text').addClass('screen-reader-text');
		
		if(product['name'] != '')
		{
			jQuery('#title').attr('value', product['name']);
			jQuery('#title').html(product['name']);
		}
		
		if(product['content'] != '')
		{
			tinyMCE.execCommand('mceSetContent', false, product['content']);
		}
		
		if(product['height'] != '')
		{
			jQuery('.wpshop_product_product_height_input input').attr('value', product['height']);
		}

		if(product['weight'] != '')
		{
			var weight = product['weight'];
			jQuery('.wpshop_product_product_weight_input input').attr('value', weight);
		}
		
		if(product['width'] != '')
		{
			jQuery('.wpshop_product_product_width_input input').attr('value', product['width']);
		}
		
		if(product['tx_tva'] != '')
		{
			var new_tva = product['tx_tva'];			
			
			// Affectation Valeur
			jQuery('.wpshop_product_attribute_tx_tva option').attr('selected', false);
			jQuery('.wpshop_product_attribute_tx_tva option:contains("' + new_tva + '")').attr('selected', true);
			
			// Affichage
			jQuery('.wpshop_product_tx_tva_input .chzn-results li').attr('class', 'active-result');
			jQuery('.wpshop_product_tx_tva_input .chzn-results li:contains("' + new_tva + '")').attr('class', 'active-result result-selected');
			jQuery('#wpshop_product_attribute_tx_tva_current_value').attr('value', new_tva);
			jQuery('.wpshop_product_tx_tva_input .chzn-single span').html(product['tx_tva']);
		}
		return false;
	});
	
	
	/**
	 * Trigger AJAX request for retrieving product information.
	 */
	jQuery(document).on('click', '#syp_btn_search_product_by_barcode', function()
	{
		jQuery('#syp_btn_search_product_by_barcode').hide();
		jQuery('#syp_search_product_by_barcode_spinner').css('display', 'inline');
		jQuery('#syp_product_barcode_id').attr('readonly', 'readonly');

		var data = 
		{
			action: "syp_search_product_by_barcode",
			product_barcode: jQuery("#syp_product_barcode_id").val()
		};
		
		jQuery.post(ajaxurl, data, function(response)
		{
			jQuery('#syp_search_product_by_barcode_spinner')
			.ajaxStop(function() 
			{
				jQuery(this).hide();
				jQuery('#syp_btn_search_product_by_barcode').show();
				jQuery('#syp_search_product_prices').show();
				jQuery('#syp_product_barcode_id').removeAttr('readonly');
			});	
			if(response == false)
			{
				jQuery("#syp_search_product_result").html(SYP_NO_PRODUCT + data['product_barcode']);
				jQuery('#syp_search_product_import').hide();
				jQuery('#syp_search_product_parameters').show();
			}
			else
			{
				if(response.match('already_inserted'))
				{
					response = response.split('||');
					var user_product_url = response[1];
					var user_product_name = response[2];
					
					jQuery("#syp_search_product_result").html(SYP_ALREADY_HAVE_PRODUCT + data['product_barcode']
					+ "<br />"+ SYP_GO_TO +" : <b><a href='"+ user_product_url +"'>"+ user_product_name +"</a></b>");
				}
				else
				{
					jQuery("#syp_search_product_result").html(response);
					jQuery('#syp_search_product_import').show();
					jQuery('#syp_search_product_parameters').show();
				}
			}
		});
		return false;
	});
	

	/**
	 * Trigger AJAX request for retrieving product updates.
	 * This one is for Edit page.
	 */
	jQuery('#syp_product_box').on('click', '#syp_btn_search_product_updates', function()
	{
		jQuery('#syp_btn_search_product_updates').hide();
		jQuery('#syp_search_product_by_barcode_spinner').css('display', 'inline');
		jQuery('#syp_product_barcode_id').attr('readonly', 'readonly');
		
		var data = 
		{
			action: "syp_search_product_updates",
			product_barcode: jQuery("#syp_product_barcode_id").val(),
		};
		
		jQuery.post(ajaxurl, data, function(response)
		{
			jQuery('#syp_search_product_by_barcode_spinner')
			.ajaxStop(function() 
			{
				jQuery(this).hide();
				jQuery('#syp_btn_search_product_updates').show();
			});	
			
			if(response == false)
			{
				jQuery("#syp_search_product_result").html(SYP_NO_PRODUCT + data['product_barcode']);
				jQuery('#syp_search_product_import').hide();
				jQuery('#syp_search_product_parameters').show();
			}
			else if(response == 'noupdates')
			{
				jQuery("#syp_search_product_result").html(SYP_NO_UPDATE);
				jQuery('#syp_search_product_import').hide();
				jQuery('#syp_search_product_parameters').show();
				jQuery('#syp_search_product_prices').show();
			}
			else
			{
				jQuery("#syp_search_product_result").html(response);
				jQuery('#syp_search_product_import').show();
				jQuery('#syp_search_product_parameters').show();
				jQuery('#syp_search_product_prices').show();
				jQuery('#syp_import_update').hide();
			}
		});
		return false;
	});
	
	
	
	/**
	 * Trigger AJAX request for retrieving product prices.
	 */
	jQuery(document).on('click', '#syp_search_product_prices_btn', function()
	{ 
		jQuery('#syp_search_product_prices_btn').hide();
		jQuery('#syp_search_product_prices_spinner').css('display', 'inline');
		
		var data = 
		{
			action: "syp_search_product_prices",
			product_barcode: jQuery("#syp_product_barcode_id").val(),
		};
		
		jQuery.post(ajaxurl, data, function(response)
		{
			jQuery('#syp_search_product_prices_spinner')
			.ajaxStop(function() 
			{
				jQuery(this).hide();
			});	
			
			if(response == false)
			{
				jQuery('#syp_prices_table_legend').after('<td row="3">'+ SYP_NO_RESULT +'</td>');
				jQuery('#syp_search_product_prices_result').show();
			}
			else
			{
				jQuery('#syp_prices_table_legend').after(response);
				jQuery('#syp_search_product_prices_result').show();
			}
		});
		return false;
	});
	
	
	
	/**
	 * Trigger AJAX request for linking WP account to SYP.
	 */
	jQuery(document).on('click', '#submitAdmin', function()
	{ 
		var passInput = jQuery("#passSYP");
		var loginInput = jQuery("#loginSYP");
		var spinner = jQuery('#spinner');
		var btn_link = jQuery('#submitAdmin');
		
		if(btn_link.attr('name') == 'save_options')
		{
			if(passInput.val() == "" || loginInput.val() == "") 
			{
				alert(SYP_FILL_FIELDS);
				return false;
			};
			
			spinner.show();
			btn_link.hide();
			loginInput.attr('readonly', true);
			passInput.attr('readonly', true);
			var data = 
			{
				action: "exec_form_admin",
				loginSYP: loginInput.val(),
				passSYP: passInput.val(),
			};
		
			jQuery.post(ajaxurl, data, function(response)
			{
				spinner
					.ajaxStart(function() {
						spinner.show();
						btn_link.hide();
					})
					.ajaxStop(function() {
						spinner.hide();
						btn_link.show();
			    });
				if(response == false)
				{
					loginInput.css('background-color', '#EF9999');
					passInput.css('background-color', '#EF9999');
					
					loginInput.attr('readonly', false);
					passInput.attr('readonly', false);
					
					jQuery('#link_form_text').css('color', '#BA4100');
					jQuery('#link_form_text').html(SYP_ACCOUNTS_ERROR);
				}
				else
				{
					btn_link.attr('name', 'change_options');
					btn_link.attr('value', SYP_CHANGE_ACCOUNT);
					
					loginInput.attr('readonly', true);
					passInput.attr('readonly', true);
					
					loginInput.css('background-color', '#C5EFC2');
					passInput.css('background-color', '#C5EFC2');
					
					jQuery('#link_form_inputs').hide('slow');
					jQuery('#link_form_text').css('color', '#1BA038');
					jQuery('#link_form_text').html(SYP_ACCOUNTS_LINKED + "<br />" + SYP_CURRENT_ACCOUNT + loginInput.val());
				}
			});
			return false;
		}
		else
		{
			btn_link.attr('name', 'save_options');
			btn_link.attr('value', SYP_SYNCHRONIZE);
			
			loginInput.attr('readonly', false);
			passInput.attr('readonly', false);
			
			loginInput.css('background-color', '#FFFFFF');
			passInput.css('background-color', '#FFFFFF');
			
			jQuery('#link_form_inputs').show('slow');
			jQuery('#link_form_text').css('color', '#000000');
			jQuery('#link_form_text').html(SYP_ACCOUNTS_INSTRUCTIONS);
		}
	});
});

