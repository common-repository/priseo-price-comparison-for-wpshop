<?php
/**
 * Utilities for manage display in the plugin
 *
 * @author Eoxia <dev@eoxia.com>
 * @version 1
 * @package syp
 * @subpackage librairies
 */

/*	Check if file is include. No direct access possible with file url	*/
if ( !defined( 'ABSPATH' ) ) {
	die( 'Access is not allowed by this way' );
}

/**
 * Utilities for manage display in the plugin
 *
 * @author Eoxia <dev@eoxia.com>
 * @version 1
 * @package syp
 * @subpackage librairies
 */
class syp_display {

	/**
	 * Build output for a given element of template
	 *
	 * @param string $template_part The element identifier to display
	 * @param array $template_part_component The different element to put into the template
	 * @param array $extras_args Not used for the moment. Could be used for make differanciation between element
	 * @param string $default_template_dir The directory where to look for the template file into theme
	 * @param string $template_elements_file The file name containing all template element
	 *
	 * @return string The template to output
	 */
	function display_template_element($template_part, $template_part_component, $extras_args = array(), $default_template_dir = 'syp', $template_elements_file = 'template_elements.tpl.php') {
		/*
		 * Directory containing custom templates
		 */
		$custom_template_part = get_stylesheet_directory() . '/' . $default_template_dir . '/';

		/*
		 * Get the default template in all cases
		 */
		require(WP_PLUGIN_DIR . '/' .  SYP_PLUGIN_DIR . '/templates/' . $template_elements_file);
		$tpl_element_default = $syp_tpl_element; unset($syp_tpl_element);

		/*
		 * Set the template element to return by default before checking if custom exists in order to be sure to return something
		 */
		$tpl_element_to_return = $tpl_element_default[$template_part];

		/*
		 * Check if the file have been duplicated into theme directory for customization
		 */
		if ( is_file($custom_template_part . $template_elements_file) ) {
			$file_path = $custom_template_part . $template_elements_file;

			require($file_path);
			if ( !empty($syp_tpl_element) && !empty($syp_tpl_element[$template_part]) ) {
				$tpl_element_to_return = $syp_tpl_element[$template_part];
			}
		}

		return self::feed_template($tpl_element_to_return, $template_part_component);
	}

	/**
	 * Fill a template with given element. Replace some code by content before output the html
	 *
	 * @param string $template_to_fill The complete html code we want to display with element to change
	 * @param array $feed The different element to put in place of the code into the tempalte part
	 *
	 * @return string The html code to display
	 */
	function feed_template($template_to_fill, $feed) {
		foreach ($feed as $element => $value) {
			$template_to_fill = str_replace('{SYP_'.$element.'}', $value, $template_to_fill);
		}

		return $template_to_fill;
	}

	/**
	 * Build output for plugin dashboard page
	 */
	function syp_dashboard()
	{
		$syp_meta = get_user_meta(get_current_user_id(), '_syp_login', true);
		if(!empty($syp_meta))
		{
			$html = '<div id="syp_dashboard_contener">';
			$disable = 'disabled="disabled"';
			$syp_abo_state = get_user_meta(get_current_user_id(), '_syp_abo_state', true);

			if(empty($syp_abo_state))
			{
				$user_state = "non-registered";
			}

			if(strtolower($syp_abo_state) == 'premium')
			{
				$user_state = 'Premium';
				$disable = '';
			}
			else
			{
				$user_state = 'Free';
			}

			if($user_state != 'non-registered')
			{
				$html .= '<div id="syp_dashboard">
					<h1 id="syp_search_import_product_title"> '. __("Priseo Dashboard", "Priseo") .'</h1>
					<div class="widget">';
				if($user_state != 'Premium')
				{
					$html .= '<a href="">';
				}
				$html .= '<div id="premium_pub">
					<img alt="get_premium" src="'. plugins_url() . '/' .  SYP_PLUGIN_DIR . '/templates/icon_premium.png" />
					<div id="premium_title">
						<h3>' . __("You are a ", "Priseo") . "<b>" . $user_state . "</b>" . __(" user.", "Priseo") . '</h3>';
						if($user_state != 'Premium')
						{
							$html .= '<H1>' . __("Get PREMIUM account !", "Priseo") .'</H1>
							<h3>' . __("Unlock products auto update, multiple  parallels synchronization and more !","Priseo") .'</h3>';
						}
						else
						{
							$html .= '<button id="synchronize_all" type="button" class="button-secondary" '.$disable.'>'. __('Synchronize all', 'Priseo') .'</button>
							<button id="search_all_updates" type="button" class="button-secondary" '.$disable.'>'. __('Search all updates', 'Priseo') .'</button>';
						}
				$html .= '</div>
						</div>';
				if($user_state != 'Premium')
				{
					$html .= '</a>';
				}
				$html .= '</div>
				</div>';


				$request = new syp_sender();
				$products = wpshop_products::product_list();

				$i = 0;
				$html .=
				'<div id="syp_control_panel">
					<h1 id="syp_search_import_product_title"> '. __("All products", "Priseo") .'</h1>
					<ul id="syp_dashboard_data_content">
							<li id="list-header">
								<div class="list-title">' . __("Product", "Priseo") .'</div>
								<div class="list-title">' . __("Barcode", "Priseo") . '</div>
								<div class="list-title">' . __("State", "Priseo") . '</div>
								<div class="list-title">' . __("Last Update", "Priseo") . '</div>
								<div class="list-title">' . __("Action", "Priseo") . '</div>
								<div class="list-title">' . __("Auto Update", "Priseo") . '</div>
							</li>
							 <link rel="stylesheet" href="http://code.jquery.com/ui/1.10.1/themes/base/jquery-ui.css" />
							<script src="http://code.jquery.com/jquery-1.9.1.js"></script>
							<script src="http://code.jquery.com/ui/1.10.1/jquery-ui.js"></script>
				<div id="accordion">';
				foreach($products as $product)
				{
					$barcode = syp_core::get_product_barcode($product->ID);
					$products[$i]->barcode = $barcode;

					$user_product = $request->get_user_product($product->barcode);
					if($user_product == false)
					{
						$products[$i]->is_sync = false;
						$products[$i]->last_update_date = "Unknown";
					}
					else
					{
						$products[$i]->is_sync = true;
						$last_update = get_post_meta($product->ID, '_syp_last_update', true);
						if(empty($last_update))
						{
							$last_update = __('Unknown', 'Priseo');
						}
						$products[$i]->last_update_date = $last_update;
					}

					$product_barcode = $product->barcode;
					if(empty($product->barcode))
					{
						$product_barcode = 'Unknown';
					}
					$html .=
					'<h3>
						<span class="list-element dashboard_product_id" value="'.$product->ID.'">'.$product->post_title.'</span>
						<span class="list-element dashboard_product_barcode">'. $product_barcode .'</span>
						<span class="list-element dashboard_state">';
						if($product->is_sync)
						{
							$html .= __('Synchronized', 'Priseo');
						} else {
							$html .= __('Local', 'Priseo');
						}

					$html .= '</span>
						<span class="list-element dashboard_last_update_date">'.$product->last_update_date.'</span>
						<span class="list-element">';

					if($product->is_sync)
					{
						$html .= '<button class="syp_btn_search_product_updates button-secondary" type="button" name="search_update" value="'.$product->barcode.'">' . __("Search update", "wp_syp") . '</button>';
					} else {
						$html .= '<button class="syp_btn_search_product_updates button-secondary" type="button" name="synchronize" value="'.$product->ID.'">' . __("Synchronize", "wp_syp") . '</button>';
					}

					$auto_update = get_post_meta($product->ID, '_auto_update', true);
					$checked = (empty($auto_update) || $auto_update == 'false' ? '' : 'checked="checked"');

					$html .='
							<span class="syp_spinner" id="syp_search_product_by_barcode_spinner_'.$product->barcode.'"><img alt="spinner" src="'. WP_PLUGIN_URL  . '/' . SYP_PLUGIN_DIR . '/templates/ajaxSpinner.gif" /></span></span>
							<span class="list-element">
								    <div class="checkbox">
									    <input type="checkbox" name="auto_update" class="checkbox-checkbox" value='.$product->ID.' id="mycheckbox_'.$product->ID.'" '.$disable.' '.$checked.' />
									    <label class="checkbox-label" for="mycheckbox_'.$product->ID.'">
									    <div class="checkbox-inner"></div>
									    <div class="checkbox-switch"></div>
									    </label>
								    </div>
							</span>
					</h3>

					<div id="product_accordion_content' . $product->barcode . '"></div>';
					$i++;
				}
			}
			echo $html . "</div>";
			wp_enqueue_script('syp_dashboard.js', WP_PLUGIN_URL  . '/' . SYP_PLUGIN_DIR . '/templates/syp_dashboard.js' );
		}
		else
		{
			echo '<script type="text/javascript">window.location="'.admin_url('options-general.php?page=syp-admin').'"</script>';
		}
	}
}
