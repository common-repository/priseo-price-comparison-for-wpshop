<?php

/*	Check if file is include. No direct access possible with file url	*/
if ( !defined( 'ABSPATH' ) ) {
	die( 'Access is not allowed by this way' );
}

$syp_tpl_element = array();

/*	Product search by barcode input */
ob_start();
?>
<input type="text" placeholder="<?php _e('Enter your barcode', 'wp_syp') ?>" value="{SYP_PRODUCT_BARCODE}" name="syp_product_barcode" id='syp_product_barcode_id' {SYP_READ_ONLY} />
<div id="syp_search_product_by_barcode_spinner"><img alt="spinner" src="<?php echo WP_PLUGIN_URL  . '/' . SYP_PLUGIN_DIR; ?>/templates/ajaxSpinner.gif" /></div>
<?php
$syp_tpl_element['syp_search_product_barcode'] = ob_get_contents();
ob_end_clean();


/*	Product search by barcode btn, result field */
ob_start();
?>
<button class="button-secondary" id="syp_btn_search_product_by_barcode" type='button'>
	<?php _e('Search', 'wp_syp'); ?>
</button>
<div id="syp_search_product_result" ></div>
<?php
$syp_tpl_element['syp_search_product_search'] = ob_get_contents();
ob_end_clean();


/*	Product search updates btn, result field */
ob_start();
?>
<button id="syp_btn_search_product_updates" type='button' class='button-secondary'>
	<?php _e('Search updates', 'wp_syp'); ?>
</button>
<div id="syp_search_product_result" ></div>
<?php
$syp_tpl_element['syp_search_product_search_updates'] = ob_get_contents();
ob_end_clean();


/*	Product search by barcode import btn */
ob_start();
?>
<div id="syp_search_product_import" style='display:none;'>
	<button id='syp_import_btn' type='button' name='syp_import' class='button-secondary'><?php _e('Import', 'Priseo'); ?></button>
</div>
<?php
$syp_tpl_element['syp_search_product_import'] = ob_get_contents();
ob_end_clean();


/*	Product search parameters */
ob_start();
?>
<div id="syp_search_product_parameters" style='display:none;'>
	<input id='syp_enable_sync' type='checkbox' name='syp_enable_sync' {SYP_AUTO_UPDATE_DISABLED}/><label for='syp_enable_sync'><?php _e(' Enable automatic update.', 'Priseo'); ?></label>
</div>
<?php
$syp_tpl_element['syp_search_product_parameters'] = ob_get_contents();
ob_end_clean();


/*	Product search product prices*/
ob_start();
?>
<br />
<div id="syp_search_product_prices" style='display:none;'>
	<button id='syp_search_product_prices_btn' type='button' class='button-secondary'><?php _e('Search for prices', 'Priseo'); ?></button>
	<img id="syp_search_product_prices_spinner" alt="spinner" style="display:none" src="<?php echo WP_PLUGIN_URL  . '/' . SYP_PLUGIN_DIR; ?>/templates/ajaxSpinner.gif" />
</div>
<?php
$syp_tpl_element['syp_search_product_prices_btn'] = ob_get_contents();
ob_end_clean();

/*	Product others sellers prices */
ob_start();
?>
<div id="syp_search_product_prices_result" style='display:none;'>
	<div id="product_data_container">
		<h1 id="syp_search_import_product_title"><?php _e('Sellers prices', 'Priseo'); ?></h1>
		<div id="product_data_content">
			<table>
				<tr id='syp_prices_table_legend'>
					<th><?php _e('Sellers', 'Priseo'); ?></th>
					<th><?php _e('Prices', 'Priseo'); ?></th>
					<th><?php _e('Dates', 'Priseo'); ?></th>
				</tr>
			</table>
		</div>
	</div>
</div>
<?php
$syp_tpl_element['syp_search_product_prices_result'] = ob_get_contents();
ob_end_clean();
?>
