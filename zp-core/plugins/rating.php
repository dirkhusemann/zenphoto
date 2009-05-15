<?php
/**
 * rating -- Supports an rating system for images, albums, pages, and news articles
 * 
 * uses Star Rating Plugin by Fyneworks.com
 * 
 * An option exists to allow viewers to recast their votes. If not set, a viewer may
 * vote only one time and not change his mind.
 *  
 * @author Stephen Billard (sbillard)and Malte Müller (acrylian)
 * @version 2.0.0
 * @package plugins
 */
require_once(dirname(dirname(__FILE__)).'/functions.php');

$plugin_description = gettext("Adds several theme functions to enable images, album, news, or pages to be rating by users.");
$plugin_author = "Stephen Billard (sbillard)and Malte Müller (acrylian)";
$plugin_version = '2.0.0';
$plugin_URL = "http://www.zenphoto.org/documentation/plugins/_plugins---rating.php.html";
$option_interface = new jquery_rating();

// register the scripts needed
$ME = substr(basename(__FILE__),0,-4);
addPluginScript('<script type="text/javascript" src="'.WEBPATH.'/'.ZENFOLDER.PLUGIN_FOLDER.$ME.'/jquery.MetaData.js"></script>');
addPluginScript('<script type="text/javascript" src="'.WEBPATH.'/'.ZENFOLDER.PLUGIN_FOLDER.$ME.'/jquery.rating.js"></script>');
addPluginScript('<link rel="stylesheet" href="'.WEBPATH.'/'.ZENFOLDER.PLUGIN_FOLDER.$ME.'/jquery.rating.css" type="text/css" />');

require_once($ME.'/functions-rating.php');

/**
 * Option handler class
 *
 */
class jquery_rating {
	/**
	 * class instantiation function
	 *
	 * @return jquery_rating
	 */
	function jquery_rating() {
		setOptionDefault('rating_recast', 1);
	}

	/**
	 * Reports the supported options
	 *
	 * @return array
	 */
	function getOptionsSupported() {
		return array(	gettext('Clear ratings') => array('key' => 'clear_rating', 'type' => 2,
										'desc' => gettext("Sets all images and albums to unrated.")),
									gettext('Recast vote') =>array('key' => 'rating_recast', 'type' => 1,
										'desc' => gettext('Allow users to change their vote.'))
								);
	}

	/**
	 * Custom opton handler--creates the clear ratings button
	 *
	 * @param string $option
	 * @param string $currentValue
	 */
	function handleOption($option, $currentValue) {
		if($option=="clear_rating") {
			?>
			<div class='buttons'>
				<a href="<?php echo WEBPATH.'/'.ZENFOLDER.PLUGIN_FOLDER.substr(basename(__FILE__),0,-4); ?>/update.php?clear_rating&height=100&width=250" class="thickbox" title="<?php echo gettext("Clear ratings"); ?>">
					<img src='images/edit-delete.png' alt='' />
					<?php echo gettext("Clear ratings"); ?>
				</a>
			</div>
			<?php
		}
	}

}

/**
 * Prints the rating star form and the current rating
 * Insert this function call in the page script where you 
 * want the star ratings to appear.
 *
 * NOTE:
 * If $vote is false or the rating_recast option is false then
 * the stars shown will be the rating. Otherwise the stars will
 * show the value of the viewer's last vote.
 * 
 * @param bool $vote set to false to disable voting
 * @param object $object optional object for the ratings target. If not set, the current page object is used
 */
function printRating($vote=true, $object=NULL) {
	if (is_null($object)) {
		getCurrentPageObject($object, $table);
	}
	$rating = round($object->get('rating'));
  $votes = $object->get('total_votes');
	$id = $object->get('id');
	$unique = '_'.get_class($object).'_'.$id;
	$ip = sanitize($_SERVER['REMOTE_ADDR'], 0);
	$recast = getOption('rating_recast');
	$oldrating = round(checkForIP($ip, $id, $table));
	if ($vote && $recast && $oldrating) {
		$starselector = round($oldrating*2);
	} else {
		$starselector = round($rating*2);
	}
	$disable = !$vote || ($oldrating && !$recast);
  if ($rating > 0) {
  	$msg = sprintf(ngettext('Rating %2$d (%1$u vote)', 'Rating %2$d (%1$u votes)', $votes), $votes, $object->get('rating'));
  } else {
  	$msg = gettext('Not yet rated');
  }
	?>
	<span class="rating">
		<form name="star_rating">
		<script type="text/javascript">
			$.fn.rating.options = { 
				cancel: '<?php echo gettext('reset'); ?>'   // advisory title for the 'cancel' link
		 	}; 
 		</script>
			<?php
			if ($rating > 0) {
				?>
				<script type="text/javascript">
					$(function() {
					$('input',this.form).rating('select','<?php echo $starselector; ?>');
				});
				</script>
				<?php
			}
			if ($disable) {
				?>
				<script type="text/javascript">
					$(function() {
						$('input',this.form).rating('disable');
						$('#submit_button<?php echo $unique; ?>').hide();
						$('#vote<?php echo $unique; ?>').html('<?php echo $msg; ?>');
					});
				</script>
				<?php
			}
			?>
		  <input type="radio" class="star {split:2}" name="star_rating-value" value="1" title="<?php echo gettext('1 star'); ?>" />
		  <input type="radio" class="star {split:2}" name="star_rating-value" value="2" title="<?php echo gettext('1 star'); ?>"/>
		  <input type="radio" class="star {split:2}" name="star_rating-value" value="3" title="<?php echo gettext('2 stars'); ?>"/>
		  <input type="radio" class="star {split:2}" name="star_rating-value" value="4" title="<?php echo gettext('2 stars'); ?>"/>
		  <input type="radio" class="star {split:2}" name="star_rating-value" value="5" title="<?php echo gettext('3 stars'); ?>"/>
		  <input type="radio" class="star {split:2}" name="star_rating-value" value="6" title="<?php echo gettext('3 stars'); ?>"/>
		  <input type="radio" class="star {split:2}" name="star_rating-value" value="7" title="<?php echo gettext('4 stars'); ?>"/>
		  <input type="radio" class="star {split:2}" name="star_rating-value" value="8" title="<?php echo gettext('4 stars'); ?>"/>
		  <input type="radio" class="star {split:2}" name="star_rating-value" value="9" title="<?php echo gettext('5 stars'); ?>"/>
		  <input type="radio" class="star {split:2}" name="star_rating-value" value="10" title="<?php echo gettext('5 stars'); ?>"/>
		  <span id="submit_button<?php echo $unique; ?>">
		  <input type="button" value="<?php echo gettext('Submit &raquo;'); ?>" onClick="javascript:
					var dataString = $(this.form).serialize();   
					if (dataString) {
						<?php
						if (!$recast) {
							?>
							$('input',this.form).rating('disable');
							$('#submit_button<?php echo $unique; ?>').hide();
							<?php
						}
						?>
						$.ajax({   
							type: 'POST',   
							url: '<?php echo WEBPATH.'/'.ZENFOLDER.PLUGIN_FOLDER.substr(basename(__FILE__),0,-4); ?>/update.php',   
							data: dataString+'&id=<?php echo $id; ?>&table=<?php echo $table; ?>'
						});
						$('#vote<?php echo $unique; ?>').html('<?php echo gettext('Vote Submitted'); ?>');
					} else {
						$('#vote<?php echo $unique; ?>').html('<?php echo gettext('nothing to submit'); ?>');
					}
		  		"/>
		  </span>
	  </form>
	</span>
  <span class="vote" id="vote<?php echo $unique; ?>" style="clear:all">
  	<?php echo $msg; ?>
  </span>
	<?php
}

/**
 * Prints the image rating information for the current image
 * Deprecated:
 * Included for forward compatibility--use printRating() directly
 *
 */
function printImageRating() {
	printRating();
}

/**
 * Prints the album rating information for the current image
 * Deprecated:
 * Included for forward compatibility--use printRating() directly
 *
 */
function printAlbumRating() {
	printRating();
}


/**
 * Returns the current rating of an object
 *
 * @param object $object optional ratings target. If not supplied, the current script object is used
 * @return float
 */
function getRating($object=NULL) {
	if (is_null($object)) {
		getCurrentPageObject($object, $table);
	}
	return $object->get('rating');
}

?>