<?php
/**
 * rating -- Supports an rating system for images, albums, pages, and news articles
 * 
 * uses Star Rating Plugin by Fyneworks.com
 * 
 * An option exists to allow viewers to recast their votes. If not set, a viewer may
 * vote only one time and not change his mind.
 * 
 * Customize the stars by placing a modified copy of jquery.rating.css in your theme folder
 *  
 * @author Stephen Billard (sbillard)and Malte Müller (acrylian)
 * @version 2.0.0
 * @package plugins
 */
require_once(dirname(dirname(__FILE__)).'/functions.php');
$plugin_is_filter = 5;
$plugin_description = gettext("Adds several theme functions to enable images, album, news, or pages to be rating by users.");
$plugin_author = "Stephen Billard (sbillard)and Malte Müller (acrylian)";
$plugin_version = '2.0.0';
$plugin_URL = "http://www.zenphoto.org/documentation/plugins/_plugins---rating.php.html";
$option_interface = new jquery_rating();

register_filter('edit_album_utilities', 'optionVoteStatus', 3);
register_filter('save_album_utilities_data', 'optionVoteStatusSave', 2);

if (getOption('rating_image_individual_control')) {
	register_filter('edit_image_utilities', 'optionVoteStatus', 3);
	register_filter('save_image_utilities_data', 'optionVoteStatusSave', 2);
}

$ME = substr(basename(__FILE__),0,-4);
// register the scripts needed
addPluginScript('<script type="text/javascript" src="'.WEBPATH.'/'.ZENFOLDER.PLUGIN_FOLDER.$ME.'/jquery.MetaData.js"></script>');
addPluginScript('<script type="text/javascript" src="'.WEBPATH.'/'.ZENFOLDER.PLUGIN_FOLDER.$ME.'/jquery.rating.js"></script>');
require_once($ME.'/functions-rating.php');

/**
 * Option handler class
 *
 */
class jquery_rating {
	var $ratingstate;
	/**
	 * class instantiation function
	 *
	 * @return jquery_rating
	 */
	function jquery_rating() {
		setOptionDefault('rating_recast', 1);
		setOptionDefault('rating_status', 3);
		setOptionDefault('rating_image_individual_control', 0);
		$this->ratingstate = array(gettext('open') => 3, gettext('members &amp; guests') => 2, gettext('members only') => 1, gettext('closed') => 0);
	}


	/**
	 * Reports the supported options
	 *
	 * @return array
	 */
	function getOptionsSupported() {
		return array(	gettext('Clear ratings') => array('key' => 'clear_rating', 'type' => 2,
										'desc' => gettext('Sets all images and albums to unrated.')),
									gettext('Voting state') => array('key' => 'rating_status', 'type' => 4,
										'buttons' => $this->ratingstate,
										'desc' => gettext('<em>Enable</em> state of voting.')),
									gettext('Individual image control') =>array('key' => 'rating_image_individual_control', 'type' => 1,
										'desc' => gettext('Enable to allow voting status control on individual images.')),
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

$_rating_css_loaded = false;
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
function printRating($vote=3, $object=NULL) {
	global $_zp_gallery_page, $_rating_css_loaded;
	if (is_null($object)) {
		getCurrentPageObject($object, $table);
	}
	$vote = min($vote, getOption('rating_status'), $object->get('rating_status'));
	switch ($vote) {
		case 1: // members only
			if (!zp_loggedin()) {
				$vote = 0;
			}
			break;
		case 2: // members & guests
			switch ($_zp_gallery_page) {
				case 'album.php':
					$album = $object;
					$hint = '';
					if (!(zp_loggedin() || checkAlbumPassword($album->name, $hint))) {
						$vote = 0;
					}
					break;
				case 'pages.php':
				case 'news.php':
					if (!zp_loggedin()) { // no guest password
						$vote = 0;
					}
					break;
				default:
					$album = $object->getAlbum();
					$hint = '';
					if (!(zp_loggedin() || checkAlbumPassword($album->name, $hint))) {
						$vote = 0;
					}
					break;
			}
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
  if (!$_rating_css_loaded) {
	  $theme = getCurrentTheme();
	  $css = SERVERPATH.'/'.THEMEFOLDER. '/'.internalToFilesystem($theme).'/jquery.rating.css';
		if (file_exists($css)) {
			$css = WEBPATH.'/'.THEMEFOLDER.'/'.$theme.'/jquery.rating.css';
		} else {
			$css = WEBPATH.'/'.ZENFOLDER.PLUGIN_FOLDER.substr(basename(__FILE__),0,-4).'/jquery.rating.css';
		}
	  ?>
		<link rel="stylesheet" href="<?php echo $css; ?>" type="text/css" />
		<script type="text/javascript">
			$.fn.rating.options = { 
				cancel: '<?php echo gettext('retract'); ?>'   // advisory title for the 'cancel' link
		 	}; 
 		</script>
		<?php
		$_rating_css_loaded = true;
  }
  ?>
	<span class="rating">
		<form name="star_rating">
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
					if (dataString || <?php printf('%u',$recast && $oldrating); ?>) {
						<?php
						if ($recast) {
							?>
							if (!dataString) {
								dataString = 'star_rating-value=0';
							}
							<?php
						} else {
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
	<span style="line-height: 0em;"><br clear=all /></span>
  <span class="vote" id="vote<?php echo $unique; ?>">
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

/**
 * Option filter handler for images and albums
 *
 * @param string $before HTML from prior filters
 * @param object $object object being rated
 * @param string $prefix indicator if admin is processing multiple objects
 * @return string Combined HTML
 */
function optionVoteStatus($before, $object, $prefix) {
	$me = new jquery_rating();
	$currentvalue = $object->get('rating_status');
	$output = 'Vote Status:<br />'."\n";
	foreach($me->ratingstate as $text=>$value) {
		if($value == $currentvalue) {
			$checked = "checked='checked' ";
		} else {
			$checked = '';
		} 
		$output .= "<label style='padding-right: .5em'><span style='white-space:nowrap'>\n<input type='radio' name='".$prefix."rating_status' id='".$value."-".$prefix."rating_status' value='".$value."' ".$checked."/> ".$text."</span>\n</label>"."\n";
	}
	$output = $before.'<hr />'."\n".$output;
	return $output;
}

/**
 * Option save handler for the filter
 *
 * @param object $object object being rated
 * @param string $prefix indicator if admin is processing multiple objects
 */
function optionVoteStatusSave($object, $prefix) {
	$object->set('rating_status', sanitize($_POST[$prefix.'rating_status']));
}

?>