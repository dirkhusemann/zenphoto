<?php
/** 
 * zenPaypal -- PayPal ordering support
 * 
 * Provides a PayPal ordering form for image print ordering.
 * 
 * Plugin option 'zenPaypal_userid' allows setting the PayPal user email.
 * Plugin option 'zenPaypal_pricelist' provides the default pricelist. 
 * 
 * Price lists can also be passed as a parameter to the zenPaypal() function. See also 
 * zenPaypalPricelistFromString() for parsing a string into the pricelist array. This could be used, 
 * for instance, by storing a pricelist string in the 'customdata' field of your images and then parsing and 
 * passing it in the zenPaypal() call. This would give you individual pricing by image.
 *  
 */

$plugin_description =   "<a href =\"http://blog.qelix.com/2008/04/07/paypal-integraion-for-zenphoto-zenpaypal/\">".
	"zenPayPal</a> -- ".gettext("Paypal Integration for Zenphoto.");
$plugin_author = 'Ebrahim Ezzy (Nimbuz) '.gettext("made into a plugin by ").'Stephen Billard (sbillard)';
$plugin_version = '1.0.0';
$plugin_URL = "http://www.zenphoto.org/documentation/zenphoto/_plugins---zenPaypal.php.html";
$option_interface = new zenPaypalOptions();
addPluginScript('<link rel="stylesheet" href="'.FULLWEBPATH."/".ZENFOLDER.'/plugins/zenPaypal/zenPaypal.css" type="text/css" />');

class zenPaypalOptions {

	function zenPaypalOptions() {

		$pricelist = array("4x6:".gettext("Matte") => '5.75', "4x6:".gettext("Glossy") => '10.00', "4x6:".gettext("Paper") => '8.45', 
								"8x10:".gettext("Matte") => '15.00', "8x10:".gettext("Glossy") => '20.00', "8x10:".gettext("Paper") => '8.60', 
								"11x14:".gettext("Matte") => '25.65', "11x14:".gettext("Glossy") => '26.75', "11x14:".gettext("Paper") => '15.35', );
		setOptionDefault('zenPaypal_userid', "");
		$pricelistoption = '';
		foreach ($pricelist as $item => $price) {
			$pricelistoption .= $item.'='.$price.' ';
		}
		setOptionDefault('zenPaypal_pricelist', $pricelistoption);
	}
	
	
	function getOptionsSupported() {
		return array(	gettext('PayPal User ID') => array('key' => 'zenPaypal_userid', 'type' => 0, 
										'desc' => gettext("Your PayPal User ID.")),
									gettext('Price list') => array('key' => 'zenPaypal_pricelist', 'type' => 2,
										'desc' => gettext("Your pricelist by size and media. The format of this option is <em>price elements</em> separated by spaces.<br/>".
																			"A <em>price element</em> has the form: <em>size</em>:<em>media</em>=<em>price</em><br/>".
																			"example: <code>4x6:Matte=5.75 8x10:Glossy=20.00 11x14:Paper=15.35</code>."))
		);
	}
 	function handleOption($option, $currentValue) {
 		if ($option=='zenPaypal_pricelist') {
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
function zenPaypalPricelistFromString($prices) {
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
function zenPaypal($pricelist=NULL) {
	if (!is_array($pricelist)) {
		$pricelist = zenPaypalPricelistFromString(getOption('zenPaypal_pricelist'));
	}
?>
<script language="javascript">

function CalculateOrder(form) {
	<?php 
	$sizes = array();
	$media = array();
	foreach ($pricelist as $key=>$price) {
		$itemparts = explode(':', $key);
		$media[] = $itemparts[1];
		$sizes[] = $itemparts[0];
		echo 'if (document.myform.os0.value == "'.$itemparts[0].'" && document.myform.os1.value == "'.$itemparts[1].'") {'."\n";
		echo 'document.myform.amount.value = '.$price.';'."\n";
		echo 'document.myform.item_name.value = "'.getImageTitle().' - Photo Size '.$itemparts[0].' - '.$itemparts[1].'";'."\n";
		echo '}'."\n";
	}
	?>        
}

</script>

<form target="paypal" action="https://www.paypal.com/cgi-bin/webscr" method="post" name="myform">
<input type="hidden" name="on0"	value="Size"> <label>Size</label> 
	<select name="os0">
	<?php
	$media = array_unique($media);
	$sizes = array_unique($sizes);
	foreach ($sizes as $size) {
		echo '<option value="'.$size.'" selected>'.$size."\n";
	}
	 ?>
</select> 
<input type="hidden" name="on1" value="Color"> <label>Color</label>
<select name="os1">
	<?php
	foreach ($media as $paper) {
		echo '<option value="'.$paper.'" selected>'.$paper."\n";
	}
	 ?>
</select> 
<input type="image" src="https://www.paypal.com/en_US/i/btn/x-click-butcc.gif" border="0"
	name="submit" onClick="CalculateOrder(this.form)"
	alt="Make payments with PayPal - it's fast, free and secure!"
	class="buynow_button"> <input type="hidden" name="cmd" value="_xclick">
<input type="hidden" name="business" value="<?php echo getOption('zenPaypal_userid'); ?>">
<input type="hidden" name="item_name" value="Options Change Amount"> 
<input type="hidden" name="amount" value="1.00"> 
<input type="hidden" name="shipping" value="0.00"> <input type="hidden" name="no_note" value="1"> 
<input type="hidden" name="currency_code" value="USD"> 
<input type="hidden" name="return" value="<?php echo 'http://'. $_SERVER['SERVER_NAME']. getNextImageURL();?>">
<input type="hidden" name="cancel_return" value="<?php echo 'http://'. $_SERVER['SERVER_NAME'].getImageLinkURL();?>">
</form>
<?php
}
?>