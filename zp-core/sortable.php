<?php

require_once("admin-functions.php");

// Load the Sortable lists now and set up the global sortable array
$_zp_sortable_list = new SLLists('js');


/**
 * A utility function that can be used to insert all of the necessary script stuff
 * in the <head> of the page so that sortable lists are enabled. The container types
 * support are limited to those supported by scriptaculous: currently div and ul.
 * 
 * @param $sortContainerID The id of container that will contain the sortable elements.
 * @param $orderedList     The array that will contain the ordered elements.
 * @param $sortableElement The element type that will be sorted.
 * @param $options         Additional options to be passed to scriptaculous.
 * 
 * @author Todd Papaioannou (lucky@luckyspin.org)
 * @since  1.0.0
 */
function zenSortablesHeader($sortContainerID, $orderedList, $sortableElement, $options="") {
	global $_zp_sortable_list;
	
	if (zp_loggedin()) {
			
		$_zp_sortable_list->addList($sortContainerID, $orderedList, $sortableElement, $options);
		$_zp_sortable_list->debug = false;
		$_zp_sortable_list->printTopJS();
	}
}

/**
 * Insert the final Sortable.create call in the footer of the page. 
 * This is required to finalize the sortable lists stuff.
 * 
 * @author Todd Papaioannou (lucky@luckyspin.org)
 * @since  1.0.0
 */
function zenSortablesFooter() {
	global $_zp_sortable_list;
	
	if (zp_loggedin()) {
		$_zp_sortable_list->printBottomJs();
	}
}

/**
 * Insert the Save button that will POST the sortable list to the page
 * indicated by $link.
 *
 * @param $link  The destination of the POST operation.
 * @param $label The label for the button.
 *
 * @author Todd Papaioannou (lucky@luckyspin.org)
 * @since  1.0.0
 */
function zenSortablesSaveButton($link, $label="Save") {
	global $_zp_sortable_list;
	
	$_zp_sortable_list->printForm($link, 'POST', $label, 'button');
}


/**
 * Insert the chunk that handles the POST operation of the sorted list.
 * 
 * @param $orderedList     The list of ordered elements to be saved.
 * @param $sortContainerID The parent container for the sortable elements.
 * @param $dbtable         The database table that will be updated.
 * 
 * @author Todd Papaioannou (lucky@luckyspin.org)
 * @since  1.0.0
 */
function zenSortablesPostHandler($orderedList, $sortContainerID, $dbtable) {
	if (isset($_POST['sortableListsSubmitted'])) {
		$orderArray = SLLists::getOrderArray($_POST[$orderedList], $sortContainerID);
		foreach($orderArray as $item) {
			saveSortOrder($dbtable, $item['element'], $item['order']);
		}
	}
}


/**
 * Save the new sort order for a sortable item.
 *
 * @param dbtable   The dababase table that will be updated.
 * @param id        The id of the sortable item, as defined in the id column.
 * @param sortorder The new sort order for this item.
 * 
 * @author Todd Papaioannou (lucky@luckyspin.org)
 * @since  1.0.0 
 */
function saveSortOrder($dbtable, $id, $sortorder) {
	
	// This is a nasty hack really, but it works.. The hack being we need id_XX in the element id.
	$real_id = substr($id, 0, 3);
	
	// TODO: Only issue the update when the order has changed. How do determine this?
	query("UPDATE ".prefix($dbtable)." SET `sort_order`='" . mysql_escape_string($sortorder) .
				"' WHERE `id`=".$id);
}


/*
 * This class implements a PHP wrapper around the scriptaculous javascript libraries created by
 * Thomas Fuchs (http://script.aculo.us/).
 *
 * SLLists was created by Greg Neustaetter in 2005 and may be used for free by anyone for any purpose.  
 * Just keep my name in here please and give me credit if you like, but give Thomas all the real credit!
 */
class SLLists {

	var $lists = array();
	var $jsPath;
	var $debug = false;
	
	function SLLists($jsPath) {
		$this->jsPath = $jsPath;
	}
	
	function addList($list, $input, $tag = 'li', $additionalOptions = '') {
		if ($additionalOptions != '') $additionalOptions = ','.$additionalOptions;
		$this->lists[] = array("list" => $list, "input" => $input, "tag" => $tag, "additionalOptions" => $additionalOptions);
	}
	
	function printTopJS() {
		?>
		<script src="<?php echo $this->jsPath;?>/prototype.js" type="text/javascript"></script>
		<script src="<?php echo $this->jsPath;?>/scriptaculous/scriptaculous.js" type="text/javascript"></script>
		<script language="JavaScript" type="text/javascript"><!--
			function populateHiddenVars() {
				<?php foreach($this->lists as $list) { ?>
					document.getElementById('<?php echo $list['input'];?>').value = Sortable.serialize('<?php echo $list['list'];?>');
				<?php } ?>
				return true;
			}
			//-->
		</script>
		<?php
	}
	
	function printBottomJs() {
		?>
		 <script type="text/javascript">
			// <![CDATA[
			<?php foreach($this->lists as $list) { ?>
				Sortable.create('<?php echo $list['list'];?>',{tag:'<?php echo $list['tag'];?>'<?php echo $list['additionalOptions'];?>});
			<?php } ?>
			// ]]>
		 </script>
		<?php
	}
	
	function printHiddenInputs() {
		$inputType = ($this->debug) ? 'text' : 'hidden';

		foreach($this->lists as $list) {
			if ($this->debug) echo '<br>'.$list['input'].': ';
			?>
			<input type="<?php echo $inputType;?>" name="<?php echo $list['input'];?>" id="<?php echo $list['input'];?>" size="60">
			<?php
		}
		if ($this->debug) echo '<br>';
	}
	
	function printForm($action, $method = 'POST', $submitText = 'Submit', $submitClass = '',$formName = 'sortableListForm') {
		?>
		<form action="<?php echo $action;?>" method="<?php echo $method;?>" onSubmit="populateHiddenVars();" name="<?php echo $formName;?>" id="<?php echo $formName;?>">
			<?php $this->printHiddenInputs();?>
			<input type="hidden" name="sortableListsSubmitted" value="true">
			<?php
			if ($this->debug) {
				?><input type="button" value="View Serialized Lists" class="<?php echo $submitClass;?>" onClick="populateHiddenVars();"><br><?php
			}
			?>
			<input type="submit" value="<?php echo $submitText;?>" class="<?php echo $submitClass;?>">
		</form>
		<?php
	}
	
	function getOrderArray($input,$listname,$itemKeyName = 'element',$orderKeyName = 'order') {
		parse_str($input,$inputArray);
		$inputArray = $inputArray[$listname];
		$orderArray = array();
		for($i=0;$i<count($inputArray);$i++) {
			$orderArray[] = array($itemKeyName => $inputArray[$i], $orderKeyName => $i +1);
		}
		return $orderArray;
	}

}