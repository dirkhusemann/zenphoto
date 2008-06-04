<?php
/** 
 * GoogleCheckout -- Google ordering support [experimental]
 * 
 * Provides a Google Checkout ordering form for image print ordering.
 * 
 * Plugin option 'GoogleCheckout_merchantID' allows setting the PayPal user email.
 * Plugin option 'GoogleCheckout_pricelist' provides the default pricelist. 
 * Plugin option 'GoogleCheckout_pricelist'
 * Plugin option 'google_checkout_currency'
 * Plugin option 'google_checkout_tax'
 * Plugin option 'google_checkout_state'
 * Plugin option 'google_checkout_ship_method'
 * Plugin option 'google_checkout_ship_cost'
 *  
 * Price lists can also be passed as a parameter to the GoogleCheckout() function. See also 
 * GoogleCheckoutPricelistFromString() for parsing a string into the pricelist array. This could be used, 
 * for instance, by storing a pricelist string in the 'customdata' field of your images and then parsing and 
 * passing it in the GoogleCheckout() call. This would give you individual pricing by image.
 *  
 */

$plugin_description = gettext("GoogleCheckout Integration for Zenphoto. [experimental]");
$plugin_author = 'Stephen Billard (sbillard)';
$plugin_version = '1.0.0';
$plugin_URL = "http://www.zenphoto.org/documentation/zenphoto/_plugins---GoogleCheckout.php.html";
$option_interface = new GoogleCheckoutOptions();
addPluginScript('<link rel="stylesheet" href="'.FULLWEBPATH."/".ZENFOLDER.'/plugins/GoogleCheckout/GoogleCheckout.css" type="text/css" />');

/**
 * Plugin option handling class
 *
 */
class GoogleCheckoutOptions {

	function GoogleCheckoutOptions() {

		$pricelist = array("4x6:".gettext("Matte") => '5.75', "4x6:".gettext("Glossy") => '10.00', "4x6:".gettext("Paper") => '8.45', 
								"8x10:".gettext("Matte") => '15.00', "8x10:".gettext("Glossy") => '20.00', "8x10:".gettext("Paper") => '8.60', 
								"11x14:".gettext("Matte") => '25.65', "11x14:".gettext("Glossy") => '26.75', "11x14:".gettext("Paper") => '15.35', );
		setOptionDefault('GoogleCheckout_merchantID', "");
		$pricelistoption = '';
		foreach ($pricelist as $item => $price) {
			$pricelistoption .= $item.'='.$price.' ';
		}
		setOptionDefault('GoogleCheckout_pricelist', $pricelistoption);
		setOptionDefault('google_checkout_currency', 'USD');
		setOptionDefault('google_checkout_tax', 0);
		setOptionDefault('google_checkout_state', '');
		setOptionDefault('google_checkout_ship_method', 'UPS Ground');
		setOptionDefault('google_checkout_ship_cost', 0);
		}
	
	
	function getOptionsSupported() {
		return array(	gettext('Google Merchant ID') => array('key' => 'GoogleCheckout_merchantID', 'type' => 0, 
										'desc' => gettext("Your Google Merchant ID.")),
									gettext('Currency') => array('key' => 'google_checkout_currency', 'type' => 0, 
										'desc' => gettext("The currency for your transactions.")),
									gettext('Tax') => array('key' => 'google_checkout_tax', 'type' => 0, 
										'desc' => gettext("Your state tax rate.")),
									gettext('State') => array('key' => 'google_checkout_state', 'type' => 0, 
										'desc' => gettext("The state in which your business resides.")),
									gettext('Ship method') => array('key' => 'google_checkout_ship_method', 'type' => 0, 
										'desc' => gettext("How you ship the product.")),
									gettext('Shipping cost') => array('key' => 'google_checkout_ship_cost', 'type' => 0, 
										'desc' => gettext("What you charge for shipping.")),
									gettext('Price list') => array('key' => 'GoogleCheckout_pricelist', 'type' => 2,
										'desc' => gettext("Your pricelist by size and media. The format of this option is <em>price elements</em> separated by spaces.<br/>".
																			"A <em>price element</em> has the form: <em>size</em>:<em>media</em>=<em>price</em><br/>".
																			"example: <code>4x6:Matte=5.75 8x10:Glossy=20.00 11x14:Paper=15.35</code>."))
		);
	}
 	function handleOption($option, $currentValue) {
 		if ($option=='GoogleCheckout_pricelist') {
	 		echo '<textarea name="' . $option . '" cols="42" rows="3">' . $currentValue . "</textarea>\n";
	 }
	}
}

/**
 * Parses a price list element string and returns a pricelist array
 *
 * @param string $prices A text string of price list elements in the form <size>:<media>=<price> <size>:<media>=<price> ...
 * @return array
 */
function GoogleCheckoutPricelistFromString($prices) {
	$pricelist = array();
	$pricelistelements = explode(' ', $prices);
		foreach ($pricelistelements as $element) {
			if (!empty($element)) {
				$elementparts = explode('=', $element);
				$pricelist[$elementparts[0]] = $elementparts[1];
			}
		}
	return $pricelist;
}

/**
 * Places a Paypal button on your form
 * 
 * @param array $pricelist optional array of specific pricing for the image.
 */
function googleCheckout($pricelist=NULL) {
	if (!is_array($pricelist)) {
		$pricelist = GoogleCheckoutPricelistFromString(getOption('GoogleCheckout_pricelist'));
	}
?>
<script language="javascript">

function googleCalculateOrder(form) {
	<?php 
	$sizes = array();
	$media = array();
	foreach ($pricelist as $key=>$price) {
		$itemparts = explode(':', $key);
		$media[] = $itemparts[1];
		$sizes[] = $itemparts[0];
		echo 'if (document.myform.os0.value == "'.$itemparts[0].'" && document.myform.os1.value == "'.$itemparts[1].'") {'."\n";
		echo 'document.myform.item_price_1.value = '.$price.';'."\n";
		echo 'document.myform.item_name_1.value = "'.getImageTitle().' - Photo Size '.$itemparts[0].' - '.$itemparts[1].'";'."\n";
		echo 'document.myform.item_description_1.value = "'.getImageTitle().' - Photo Size '.$itemparts[0].' - '.$itemparts[1].'";'."\n";
		echo '}'."\n";
	}
	?>        
}

<?php
$locale = getOption('locale');
if (empty($locale)) { $locale = 'en_US'; }
?>

</script>

<form method="POST"
  action="https://checkout.google.com/api/checkout/v2/checkoutForm/Merchant/<?php echo $id = getOption('google_checkout_id'); ?>"
  accept-charset="utf-8" name="myform">
<input type="hidden" name="on0"	value="Size"> <label>Size</label> 
	<select name="os0" >
	<?php
	$media = array_unique($media);
	$sizes = array_unique($sizes);
	foreach ($sizes as $size) {
		echo '<option value="'.$size.'" selected>'.$size."\n";
	}
	 ?>
</select> 
<input type="hidden" name="on1" value="Color"> <label><?php echo gettext("Stock"); ?></label>
<select name="os1" >
	<?php
	foreach ($media as $paper) {
		echo '<option value="'.$paper.'" selected>'.$paper."\n";
	}
	 ?>
</select> 

  <input type="hidden" name="item_name_1" value="<?php echo $item; ?>"/>
  <input type="hidden" name="item_description_1" value="<?php echo $item_description; ?>"/>
  <input type="hidden" name="item_quantity_1" value="1"/>
  <input type="hidden" name="item_price_1" value="<?php echo $price ?>"/>
  <input type="hidden" name="item_currency_1" value="<?php echo getOption('google_checkout_currency'); ?>"/>

  <input type="hidden" name="ship_method_name_1" value="<?php echo getOption('google_checkout_ship_method'); ?> "/>
  <input type="hidden" name="ship_method_price_1" value="<?php echo getOption('google_checkout_ship_cost'); ?>"/>

  <input type="hidden" name="tax_rate" value="<?php echo getOption('google_checkout_tax'); ?>"/>
  <input type="hidden" name="tax_us_state" value="<?php echo getOption('google_checkout_state'); ?>"/>

  <input type="hidden" name="_charset_"/>

  <input type="image" name="Google Checkout" alt="<?php echo gettext("Fast checkout through Google"); ?>" class="checkout_button"
		src="http://checkout.google.com/buttons/checkout.gif?merchant_id=<?php echo $id; ?>
		&w=180&h=46&style=white&variant=text&loc=<?php echo $locale; ?>"
		onClick="googleCalculateOrder(this.form)"
		/>

</form>
<?php
}

/**
 * Prints a link that will expose the GoogleCheckout Price list table
 *
 * @param array $pricelist the GoogleCheckout price list
 * @param string $text The text to place for the link (defaults to "Price List")
 * @param string $textTag HTML tag for the link text. E.g. h3, ...  
 * @param string $id the division ID for the price list. (NB: a div named $id appended with "_data" is
 * 										created for the hidden table.
 * 
 * CSS entries for the following should be created for proper formatting. 
 *			#GoogleCheckoutPricelist_data table {
 *			#GoogleCheckoutPricelist_data th {
 *			#GoogleCheckoutPricelist_data td {
 *			#GoogleCheckoutPricelist_data table .price {
 *			#GoogleCheckoutPricelist_data table .size {
 *			#GoogleCheckoutPricelist_data table .media {
 * 
 */
function GoogleCheckoutPrintPricelist($pricelist=NULL, $text=NULL, $textTag='', $id='GoogleCheckoutPricelist'){
	if (!is_array($pricelist)) {
		$pricelist = GoogleCheckoutFromString(getOption('GoogleCheckout_pricelist'));
	}
	if (is_null($text)) $text = gettext("Price List");
	$dataid = $id . '_data';
	if (!empty($textTag)) {
		$textTagStart = '<'.$textTag.'>';
		$textTagEnd = '</'.$textTag.'>';
	}
	echo '<div id="' .$id. '">'."\n".'<a href="javascript: toggle('. "'" .$dataid."'".');">'.$textTagStart.$text.'</a>'.$textTagEnd."\n"."\n</div>";
	echo '<div id="' .$dataid. '" style="display: none;">'."\n";
	echo '<table>'."\n";
	echo '<table>'."\n";
	echo '<tr>'."\n";
	echo '<th>'.gettext("size").'</th>'."\n";
	echo '<th>'.gettext("media").'</th>'."\n";
	echo '<th>'.gettext("price").'</th>'."\n";
	echo '</tr>'."\n";
	$sizes = array();
	$media = array();
	foreach ($pricelist as $key=>$price) {
		$itemparts = explode(':', $key);
		echo '<tr>'."\n";
		echo '<td class="size">'.$itemparts[0].'</td>'."\n";
		echo '<td class="media">'.$itemparts[1].'</td>'."\n";
		echo '<td class="price">'.$price.'</td>'."\n";
		echo '</tr>'."\n";
	}
	echo '</table>'."\n";
	echo '</div>'."\n";
	echo "</div>\n";
}

?>