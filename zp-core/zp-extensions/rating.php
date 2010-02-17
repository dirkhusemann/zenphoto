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
 * @package plugins
 */
if (!defined('OFFSET_PATH')) define('OFFSET_PATH', 3);
$plugin_is_filter = -5;
$plugin_description = gettext("Adds several theme functions to enable images, album, news, or pages to be rated by users.");
$plugin_author = "Stephen Billard (sbillard)and Malte Müller (acrylian)";
$plugin_version = '1.2.9'; 
$plugin_URL = "http://www.zenphoto.org/documentation/plugins/_".PLUGIN_FOLDER."---rating.php.html";

require_once(dirname(dirname(__FILE__)).'/functions.php');
if (isset($_GET['action']) && $_GET['action']=='clear_rating') {
	if (!(zp_loggedin(ADMIN_RIGHTS | MANAGE_ALL_ALBUM_RIGHTS))) { // prevent nefarious access to this page.
		header('Location: ' . FULLWEBPATH . '/' . ZENFOLDER . '/admin.php?from=' . currentRelativeURL(__FILE__));
		exit();
	}
	query('UPDATE '.prefix('images').' SET total_value=0, total_votes=0, rating=0, used_ips="" ');
	query('UPDATE '.prefix('albums').' SET total_value=0, total_votes=0, rating=0, used_ips="" ');
	query('UPDATE '.prefix('zenpage_news').' SET total_value=0, total_votes=0, rating=0, used_ips="" ');
	query('UPDATE '.prefix('zenpage_pages').' SET total_value=0, total_votes=0, rating=0, used_ips="" ');
	header('Location: ' . FULLWEBPATH . '/' . ZENFOLDER . '/admin.php?action=external&msg='.gettext('All ratings have been set to <em>unrated</em>.'));
	exit();
}

$option_interface = new jquery_rating();

zp_register_filter('edit_album_utilities', 'optionVoteStatus');
zp_register_filter('save_album_utilities_data', 'optionVoteStatusSave');
zp_register_filter('admin_utilities_buttons', 'rating_purgebutton');

if (getOption('rating_image_individual_control')) {
	zp_register_filter('edit_image_utilities', 'optionVoteStatus');
	zp_register_filter('save_image_utilities_data', 'optionVoteStatusSave');
}

$ME = substr(basename(__FILE__),0,-4);
// register the scripts needed
if (isset($_zp_gallery) && !is_null($_zp_gallery)) {
	addPluginScript('<script type="text/javascript" src="'.WEBPATH.'/'.ZENFOLDER.'/'.PLUGIN_FOLDER.'/'.$ME.'/jquery.MetaData.js"></script>');
	addPluginScript('<script type="text/javascript" src="'.WEBPATH.'/'.ZENFOLDER.'/'.PLUGIN_FOLDER.'/'.$ME.'/jquery.rating.js"></script>');
	$theme = getCurrentTheme();
	$css = SERVERPATH.'/'.THEMEFOLDER. '/'.internalToFilesystem($theme).'/jquery.rating.css';
	if (file_exists($css)) {
		$css = WEBPATH.'/'.THEMEFOLDER.'/'.$theme.'/jquery.rating.css';
	} else {
		$css = WEBPATH.'/'.ZENFOLDER.'/'.PLUGIN_FOLDER.'/'.substr(basename(__FILE__),0,-4).'/jquery.rating.css';
	}
	addPluginScript('<link rel="stylesheet" href="'.$css.'" type="text/css" />');
	addPluginScript('<script type="text/javascript">'.
										"$.fn.rating.options = { 
											cancel: '".gettext('retract')."'   // advisory title for the 'cancel' link
									 	}; 
						 			</script>");
}

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
		setOptionDefault('rating_split_stars', 1);
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
		return array(	gettext('Voting state') => array('key' => 'rating_status', 'type' => OPTION_TYPE_RADIO,
										'buttons' => $this->ratingstate,
										'desc' => gettext('<em>Enable</em> state of voting.')),
									gettext('Split stars') =>array('key' => 'rating_split_stars', 'type' => OPTION_TYPE_CHECKBOX,
										'desc' => gettext('Enable to allow rating stars to show half stars for fractional rating values. May cause performance problems for pages with large numbers of rating elements.')),
									gettext('Individual image control') =>array('key' => 'rating_image_individual_control', 'type' => OPTION_TYPE_CHECKBOX,
										'desc' => gettext('Enable to allow voting status control on individual images.')),
									gettext('Recast vote') =>array('key' => 'rating_recast', 'type' => OPTION_TYPE_RADIO,
										'buttons' => array(gettext('No') => 0, gettext('Show rating') => 1, gettext('Show previous vote') => 2),
										'desc' => gettext('Allow users to change their vote. If Show previous vote is chosen, the stars will reflect the last vote of the viewer. Otherwise they will reflect the current rating.'))
								);
	}

	function handleOption($option, $currentValue) {
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
 * @param bool $text if false, no annotation text is displayed
 */
function printRating($vote=3, $object=NULL, $text=true) {
	global $_zp_gallery_page, $_rating_css_loaded;
	if (is_null($object)) {
		$object = getCurrentPageObject();
	}
	$table = $object->table;
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
				case ZENPAGE_PAGES.'.php':
				case ZENPAGE_NEWS.'.php':
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

	$rating = $object->get('rating');
  $votes = $object->get('total_votes');
	$id = $object->get('id');
	$unique = '_'.$table.'_'.$id;
	$ip = getUserIP();
	$recast = getOption('rating_recast');
  $split_stars = getOption('rating_split_stars')+1;  
	$oldrating = getRatingByIP($ip,$object->get('used_ips'), $object->get('rating'));
	if ($vote && $recast==2 && $oldrating) {
		$starselector = round($oldrating*$split_stars);
	} else {
		$starselector = round($rating*$split_stars);
	}
	$disable = !$vote || ($oldrating && !$recast);
  if ($rating > 0) {
  	$msg = sprintf(ngettext('Rating %2$.1f (%1$u vote)', 'Rating %2$.1f (%1$u votes)', $votes), $votes, $rating);
  } else {
  	$msg = gettext('Not yet rated');
  }
	if ($split_stars>1) {
		$split = ' {split:2}';
	} else {
		$split = '';
	}
  ?>
	<script type="text/javascript">
		$(function() {
			$('#star_rating<?php echo $unique; ?> :radio.star').rating('select','<?php echo $starselector; ?>');
			<?php
			if ($disable) {
				?>
				$('#star_rating<?php echo $unique; ?> :radio.star').rating('disable');
				<?php
			}
		?>
		});
	</script>
		<form name="star_rating<?php echo $unique; ?>" id="star_rating<?php echo $unique; ?>" action="submit">
		  <input type="radio" class="star<?php echo $split; ?>" name="star_rating-value<?php echo $unique; ?>" value="1" title="<?php echo gettext('1 star'); ?>" />
		  <input type="radio" class="star<?php echo $split; ?>" name="star_rating-value<?php echo $unique; ?>" value="2" title="<?php echo gettext('1 star'); ?>" />
		  <input type="radio" class="star<?php echo $split; ?>" name="star_rating-value<?php echo $unique; ?>" value="3" title="<?php echo gettext('2 stars'); ?>" />
		  <input type="radio" class="star<?php echo $split; ?>" name="star_rating-value<?php echo $unique; ?>" value="4" title="<?php echo gettext('2 stars'); ?>" />
		  <input type="radio" class="star<?php echo $split; ?>" name="star_rating-value<?php echo $unique; ?>" value="5" title="<?php echo gettext('3 stars'); ?>" />
		  <?php
		  if ($split_stars>1) {
		  	?>
			  <input type="radio" class="star {split:2}" name="star_rating-value<?php echo $unique; ?>" value="6" title="<?php echo gettext('3 stars'); ?>" />
			  <input type="radio" class="star {split:2}" name="star_rating-value<?php echo $unique; ?>" value="7" title="<?php echo gettext('4 stars'); ?>" />
			  <input type="radio" class="star {split:2}" name="star_rating-value<?php echo $unique; ?>" value="8" title="<?php echo gettext('4 stars'); ?>" />
			  <input type="radio" class="star {split:2}" name="star_rating-value<?php echo $unique; ?>" value="9" title="<?php echo gettext('5 stars'); ?>" />
			  <input type="radio" class="star {split:2}" name="star_rating-value<?php echo $unique; ?>" value="10" title="<?php echo gettext('5 stars'); ?>" />
		  	<?php
		  }
		  if (!$disable) {
			  ?>
			  <span id="submit_button<?php echo $unique; ?>">
			  	<input type="button" value="<?php echo gettext('Submit &raquo;'); ?>" onclick="javascript:
						var dataString = $(this.form).serialize();
						if (dataString || <?php printf('%u',$recast && $oldrating); ?>) {
							<?php
							if ($recast) {
								?>
								if (!dataString) {
									dataString = 'star_rating-value<?php echo $unique; ?>=0';
								}
								<?php
							} else {
								?>
								$('#star_rating<?php echo $unique; ?> :radio.star').rating('disable');
								$('#submit_button<?php echo $unique; ?>').hide();
								<?php
							}
							?>
							$.ajax({   
								type: 'POST',   
								url: '<?php echo WEBPATH.'/'.ZENFOLDER.'/'.PLUGIN_FOLDER.'/'.substr(basename(__FILE__),0,-4); ?>/update.php',   
								data: dataString+'&amp;id=<?php echo $id; ?>&amp;table=<?php echo $table; ?>'
							});
							$('#vote<?php echo $unique; ?>').html('<?php echo gettext('Vote Submitted'); ?>');
						} else {
							$('#vote<?php echo $unique; ?>').html('<?php echo gettext('nothing to submit'); ?>');
						}
			  		"/>
			  </span>
			  <?php
		  }
		  ?>
	  </form>
		<span style="line-height: 0em;"><br clear="all" /></span>
	  <span class="vote" id="vote<?php echo $unique; ?>" <?php if (!$text) echo 'style="display:none;"'; ?>>
	  	<?php echo $msg; ?>
	  </span>
	<?php
}


/**
 * Returns the current rating of an object
 *
 * @param object $object optional ratings target. If not supplied, the current script object is used
 * @return float
 */
function getRating($object=NULL) {
	if (is_null($object)) {
		$object = getCurrentPageObject();
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
	$output = gettext('Vote Status:').'<br />'."\n";
	foreach($me->ratingstate as $text=>$value) {
		if($value == $currentvalue) {
			$checked = 'checked="checked"';
		} else {
			$checked = '';
		} 
		$output .= "<label style='padding-right: .5em'><span style='white-space:nowrap'>\n<input type='radio' name='".$prefix."rating_status' id='rating_status".$value."-".$prefix."' value='".$value."' ".$checked."/> ".$text."</span>\n</label>"."\n";
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

function rating_purgebutton($buttons) {
	$buttons[] = array(
								'button_text'=>gettext('Reset all ratings'),
								'formname'=>'clearrating_button',
								'action'=>PLUGIN_FOLDER.'/rating.php?action=clear_rating',
								'icon'=>'images/reset1.png', 
								'title'=>gettext('Sets all ratings to unrated.'),
								'alt'=>'',
								'hidden'=> '<input type="hidden" name="action" value="clear_rating" />',
								'rights'=> ADMIN_RIGHTS
								);
	return $buttons;
}


?>