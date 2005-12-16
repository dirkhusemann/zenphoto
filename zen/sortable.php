<?php

require_once("admin-functions.php");

// Load the Sortable lists now and set up the global sortable array
$_zp_sortable_list = new SLLists('scriptaculous');


/**
 * A utility function that can be used to insert all of the necessary script stuff
 * in the <head> of the page so that sortable lists are enabled.
 * 
 * TODO: Make this function generic. I.e. give it params.
 * 
 * @author Todd Papaioannou (toddp@acm.org)
 * @since  1.0.0
 */
function zenSortablesHeader()
{
  global $_zp_sortable_list;
  
  if (zp_loggedin()) {
      
    $_zp_sortable_list->addList('images','imageOrder','img',"overlap:'horizontal',constraint:false");
    $_zp_sortable_list->debug = false;
    $_zp_sortable_list->printTopJS();
  }
}

/**
 * Insert the final Sortable.create call in the footer of the page. 
 * This is required to finalize the sortable lists stuff.
 * 
 * @author Todd Papaioannou (toddp@acm.org)
 * @since  1.0.0
 */
function zenSortablesFooter()
{
  global $_zp_sortable_list;
  
  if (zp_loggedin()) {
    $_zp_sortable_list->printBottomJs();
  }
}

/**
 * Insert the Save button that will POST the sortable list to the page
 * indicated by $link.
 *
 * TODO: Make this function generic. I.e. give it params.
 * 
 * @param $link The destination of the POST operation.
 *
 * @author Todd Papaioannou (toddp@acm.org)
 * @since  1.0.0
 */
function zenSortablesSaveButton($link)
{
  global $_zp_sortable_list;
  
  echo "\n<div>\n";
  $_zp_sortable_list->printForm($link, 'POST', 'Save', 'button');
  echo "\n</div>";
}


/**
 * Insert the chunk that handles the POST operation of the sorted list.
 * 
 * TODO: Make this function generic. I.e. give it more params.
 * 
 * @param dbtable The database table that will be updated.
 * 
 * @author Todd Papaioannou (toddp@acm.org)
 * @since  1.0.0
 */
function zenSortablesPostHandler($dbtable) {
  if (isset($_POST['sortableListsSubmitted'])) {
  	$orderArray = SLLists::getOrderArray($_POST['imageOrder'], 'images');
  	foreach($orderArray as $item) {
  		saveSortOrder($dbtable, $item['element'], $item['order']);
  	}
  }
}


/**
 * Save the new sort order for an element.
 *
 * @param dbtable   The dbtable that will be updated.
 * @param imageid   The id of the image, as defined in the id column.
 * @param sortorder The new sort order for this image.
 * 
 * @author Todd Papaioannou (toddp@acm.org)
 * @since  1.0.0 
 */
function saveSortOrder($dbtable, $id, $sortorder) {
  
  // This is a nasty hack really
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
		<script src="<?=$this->jsPath;?>/prototype.js" type="text/javascript"></script>
		<script src="<?=$this->jsPath;?>/scriptaculous.js" type="text/javascript"></script>
		<script language="JavaScript" type="text/javascript"><!--
			function populateHiddenVars() {
				<?
				foreach($this->lists as $list) {
					?>
					document.getElementById('<?=$list['input'];?>').value = Sortable.serialize('<?=$list['list'];?>');
					<?
				}
				?>
				return true;
			}
			//-->
		</script>
		<?
	}
	
	function printBottomJs() {
		?>
		 <script type="text/javascript">
			// <![CDATA[
			<?
			foreach($this->lists as $list) {
				?>
				Sortable.create('<?=$list['list'];?>',{tag:'<?=$list['tag'];?>'<?=$list['additionalOptions'];?>});
				<?
			}
			?>
			// ]]>
		 </script>
		<?
	}
	
	function printHiddenInputs() {
		$inputType = ($this->debug) ? 'text' : 'hidden';

		foreach($this->lists as $list) {
			if ($this->debug) echo '<br>'.$list['input'].': ';
			?>
			<input type="<?=$inputType;?>" name="<?=$list['input'];?>" id="<?=$list['input'];?>" size="60">
			<?
		}
		if ($this->debug) echo '<br>';
	}
	
	function printForm($action, $method = 'POST', $submitText = 'Submit', $submitClass = '',$formName = 'sortableListForm') {
		?>
		<form action="<?=$action;?>" method="<?=$method;?>" onSubmit="populateHiddenVars();" name="<?=$formName;?>" id="<?=$formName;?>">
			<? $this->printHiddenInputs();?>
			<input type="hidden" name="sortableListsSubmitted" value="true">
			<?
			if ($this->debug) {
				?><input type="button" value="View Serialized Lists" class="<?=$submitClass;?>" onClick="populateHiddenVars();"><br><?
			}
			?>
			<input type="submit" value="<?=$submitText;?>" class="<?=$submitClass;?>">
		</form>
		<?
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