<?php
/**
 * functions-controller.php **************************************************
 * Common functions used in the controller for getting/setting current classes,
 * redirecting URLs, and working with the context.
 * @package core
 */

// force UTF-8 Ø



// Determines if this request used a query string (as opposed to mod_rewrite).
// A valid encoded URL is only allowed to have one question mark: for a query string.
function is_query_request() {
	return (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '?') !== false);
}


/**
 * Returns the URL of any main page (image/album/page#/etc.) in any form
 * desired (rewrite or query-string).
 * @param $with_rewrite boolean or null, whether the returned path should be in rewrite form.
 *   Defaults to null, meaning use the mod_rewrite configuration to decide.
 * @param $album : the Album object to use in the path. Defaults to the current album (if null).
 * @param $image : the Image object to use in the path. Defaults to the current image (if null).
 * @param $page : the page number to use in the path. Defaults to the current page (if null).
 */
function zpurl($with_rewrite=NULL, $album=NULL, $image=NULL, $page=NULL, $special='') {
	global $_zp_current_album, $_zp_current_image, $_zp_page;
	// Set defaults
	if ($with_rewrite === NULL)  $with_rewrite = getOption('mod_rewrite');
	if (!$album)  $album = $_zp_current_album;
	if (!$image)  $image = $_zp_current_image;
	if (!$page)   $page  = $_zp_page;

	$url = '';
	if ($with_rewrite) {
		if (in_context(ZP_IMAGE)) {
			$encoded_suffix = implode('/', array_map('rawurlencode', explode('/', im_suffix())));
			$url = pathurlencode($album->name) . '/' . rawurlencode($image->filename) . $encoded_suffix;
		} else if (in_context(ZP_ALBUM)) {
			$url = pathurlencode($album->name) . ($page > 1 ? '/page/'.$page : '');
		} else if (in_context(ZP_INDEX)) {
			$url = ($page > 1 ? 'page/' . $page : '');
		}
	} else {
		if (in_context(ZP_IMAGE)) {
			$url = 'index.php?album=' . pathurlencode($album->name) . '&image='. rawurlencode($image->filename);
		} else if (in_context(ZP_ALBUM)) {
			$url = 'index.php?album=' . pathurlencode($album->name) . ($page > 1 ? '&page='.$page : '');
		} else if (in_context(ZP_INDEX)) {
			$url = 'index.php' . ($page > 1 ? '?page='.$page : '');
		}
	}
	if ($url == im_suffix() || empty($url)) { $url = ''; }
	if (!empty($url) && !(empty($special))) {
		if ($page > 1) {
			$url .= "&$special";
		} else {
			$url .= "?$special";
		}
	}
	return $url;
}


/**
 * Checks to see if the current URL matches the correct one, redirects to the
 * corrected URL if not with a 301 Moved Permanently.
 */
function fix_path_redirect() {
	if (getOption('mod_rewrite')) {
		$sfx = im_suffix();
		$request_uri = urldecode($_SERVER['REQUEST_URI']);
		$i = strpos($request_uri, '?');
		if ($i !== false) {
			$params = substr($request_uri, $i+1);
			$request_uri = substr($request_uri, 0, $i);
		} else {
			$params = '';
		}
		if (strlen($sfx) > 0 && in_context(ZP_IMAGE) && substr($request_uri, -strlen($sfx)) != $sfx ) {
			$redirecturl = zpurl(true, NULL, NULL, NULL, $params);
			header("HTTP/1.0 301 Moved Permanently");
			header('Location: ' . FULLWEBPATH . '/' . $redirecturl);
			exit();
		}
	}
}


/******************************************************************************
 ***** Action Handling and context data loading functions *********************
 ******************************************************************************/

function zp_handle_comment() {
	global $_zp_current_image, $_zp_current_album, $_zp_comment_stored, $_zp_current_zenpage_news, $_zp_current_zenpage_page;
	$activeImage = false;
	$comment_error = 0;
	$cookie = zp_getCookie('zenphoto');
	if (isset($_POST['comment'])) {
		if ((in_context(ZP_ALBUM) || in_context(ZP_ZENPAGE_NEWS_ARTICLE) || in_context(ZP_ZENPAGE_PAGE))) {
			$p_name = sanitize($_POST['name'],3);
			if (isset($_POST['email'])) {
				$p_email = sanitize($_POST['email'], 3);
			} else {
				$p_email = "";
			}
			if (isset($_POST['website'])) {
				$p_website = sanitize($_POST['website'], 3);
			} else {
				$p_website = "";
			}
			$p_comment = sanitize($_POST['comment'], 1);
			$p_server = sanitize($_SERVER['REMOTE_ADDR'], 3);
			if (isset($_POST['code'])) {
				$code1 = sanitize($_POST['code'], 3);
				$code2 = sanitize($_POST['code_h'], 3);
			} else {
				$code1 = '';
				$code2 = '';
			}
			$p_private = isset($_POST['private']);
			$p_anon = isset($_POST['anon']);

			if (isset($_POST['imageid'])) {  //used (only?) by the tricasa hack to know which image the client is working with.
				$activeImage = zp_load_image_from_id(strip_tags($_POST['imageid']));
				if ($activeImage !== false) {
					$commentadded = $activeImage->addComment($p_name, $p_email,	$p_website, $p_comment,
																							$code1, $code2,	$p_server, $p_private, $p_anon);
	 				$redirectTo = $activeImage->getImageLink();
					}
			} else {
				// ZENPAGE: if else change
				if (in_context(ZP_IMAGE) AND in_context(ZP_ALBUM)) {
					$commentobject = $_zp_current_image;
					$redirectTo = $_zp_current_image->getImageLink();
				} else if (!in_context(ZP_IMAGE) AND in_context(ZP_ALBUM)){
					$commentobject = $_zp_current_album;
					$redirectTo = $_zp_current_album->getAlbumLink();
				} else 	if (in_context(ZP_ZENPAGE_NEWS_ARTICLE)) {
					$commentobject = $_zp_current_zenpage_news;
					$redirectTo = FULLWEBPATH . '/index.php?p='.ZENPAGE_NEWS.'&title='.$_zp_current_zenpage_news->getTitlelink();
				} else if (in_context(ZP_ZENPAGE_PAGE)) {
					$commentobject = $_zp_current_zenpage_page;
					$redirectTo = FULLWEBPATH . '/index.php?p='.ZENPAGE_PAGES.'&title='.$_zp_current_zenpage_page->getTitlelink();
				}
				$commentadded = $commentobject->addComment($p_name, $p_email, $p_website, $p_comment,
													$code1, $code2,	$p_server, $p_private, $p_anon);
			}
			if ($commentadded == 2) {
				$comment_error = 0;
				if (isset($_POST['remember'])) {
					// Should always re-cookie to update info in case it's changed...
					$info = array($p_name, $p_email, $p_website, '', false, $p_private, $p_anon);
					zp_setcookie('zenphoto', implode('|~*~|', $info), time()+COOKIE_PESISTENCE, '/');
				} else {
					zp_setcookie('zenphoto', '', time()-368000, '/');
				}
				//use $redirectTo to send users back to where they came from instead of booting them back to the gallery index. (default behaviour)
				//TODO: this does not work for IIS. How to detect IIS server and just fall through?
				// if you are running IIS, delete the next two lines
				header('Location: ' . $redirectTo);
				exit();
			} else {
				$_zp_comment_stored = array($p_name, $p_email, $p_website, $p_comment, false, $p_private, $p_anon);
				if (isset($_POST['remember'])) $_zp_comment_stored[4] = true;
				$comment_error = 1 + $commentadded;
				// ZENPAGE: if statements added
				if ($activeImage !== false AND !in_context(ZP_ZENPAGE_NEWS_ARTICLE) AND !in_context(ZP_ZENPAGE_PAGE)) { // tricasa hack? Set the context to the image on which the comment was posted
					$_zp_current_image = $activeImage;
					$_zp_current_album = $activeImage->getAlbum();
					set_context(ZP_IMAGE | ZP_ALBUM | ZP_INDEX);
				}
			}
		}
	} else  if (!empty($cookie)) {
		// Comment form was not submitted; get the saved info from the cookie.
		$_zp_comment_stored = explode('|~*~|', stripslashes($cookie));
		$_zp_comment_stored[4] = true;
		if (!isset($_zp_comment_stored[5])) $_zp_comment_stored[5] = false;
		if (!isset($_zp_comment_stored[6])) $_zp_comment_stored[6] = false;
	} else {
		$_zp_comment_stored = array('','','', '', false, false, false);
	}
return $comment_error;
}

/**
 * Handle AJAX editing in place
 *
 * @param string $context 	either 'image' or 'album', object to be updated
 * @param string $field		field of object to update (title, desc, etc...)
 * @param string $value		new edited value of object field
 * @since 1.3
 * @author Ozh
 **/
function editInPlace_handle_request($context = '', $field = '', $value = '', $orig_value = '') {
	// Cannot edit when context not set in current page (should happen only when editing in place from index.php page)
	if ( !in_context(ZP_IMAGE) && !in_context(ZP_ALBUM) )
	die ($orig_value.'<script type="text/javascript">alert("'.gettext('Oops.. Cannot edit from this page').'");</script>');

	// Make a copy of context object
	switch ($context) {
		case 'image':
			global $_zp_current_image;
			$object = $_zp_current_image;
			break;
		case 'album':
			global $_zp_current_album;
			$object = $_zp_current_album;
			break;
		default:
			die (gettext('Error: malformed Ajax POST'));
	}

	// Dates need to be handled before stored
	if ($field == 'date') {
		$value = date('Y-m-d H:i:s', strtotime($value));
	}

	// Sanitize new value
	switch ($field) {
		case 'desc':
			$level = 1;
			break;
		case 'title':
			$level = 2;
			break;
		default:
			$level = 3;
	}
	$value = str_replace("\n", '<br />', sanitize($value, $level)); // note: not using nl2br() here because it adds an extra "\n"

	// Write new value
	if ($field == '_update_tags') {
		$value = trim($value, ', ');
		$object->setTags($value);
	} else {
		$object->set($field, $value);
	}

	$result = $object->save();
	if ($result !== false) {
		echo $value;
	} else {
		echo ('<script type="text/javascript">alert("'.gettext('Could not save!').'");</script>'.$orig_value);
	}
	die();
}

/**
 *checks for album password posting
 */
function zp_handle_password() {
	global $_zp_loggedin, $_zp_login_error, $_zp_current_album;
	if (zp_loggedin()) { return; } // who cares, we don't need any authorization
	$cookiepath = WEBPATH;
	if (WEBPATH == '') { $cookiepath = '/'; }
	$check_auth = '';
	if (isset($_GET['z']) && $_GET['p'] == 'full-image' || isset($_GET['p']) && $_GET['p'] == '*full-image') {
		$authType = 'zp_image_auth';
		$check_auth = getOption('protected_image_password');
		$check_user = getOption('protected_image_user');
	} else if (in_context(ZP_SEARCH)) {  // search page
		$authType = 'zp_search_auth';
		$check_auth = getOption('search_password');
		$check_user = getOption('search_user');
	} else if (in_context(ZP_ALBUM)) { // album page
		$authType = "zp_album_auth_" . cookiecode($_zp_current_album->name);
		$check_auth = $_zp_current_album->getPassword();
		$check_user = $_zp_current_album->getUser();
		if (empty($check_auth)) {
			$parent = $_zp_current_album->getParent();
			while (!is_null($parent)) {
				$check_auth = $parent->getPassword();
				$check_user = $parent->getUser();
				$authType = "zp_album_auth_" . cookiecode($parent->name);
				if (!empty($check_auth)) { break; }
				$parent = $parent->getParent();
			}
		}
	}
	if (empty($check_auth)) { // anything else is controlled by the gallery credentials
		$authType = 'zp_gallery_auth';
		$check_auth = getOption('gallery_password');
		$check_user = getOption('gallery_user');
	}
	// Handle the login form.
	if (DEBUG_LOGIN) debugLog("zp_handle_password: \$authType=$authType; \$check_auth=$check_auth; \$check_user=$check_user; ");
	if (isset($_POST['password']) && isset($_POST['pass'])) {
		if (isset($_POST['user'])) {
			$post_user = $_POST['user'];
		} else {
			$post_user = '';
		}
		$post_pass = $_POST['pass'];
		$auth = md5($post_user . $post_pass);
		if (DEBUG_LOGIN) debugLog("zp_handle_password: \$post_user=$post_user; \$post_pass=$post_pass; \$auth=$auth; ");
		if ($_zp_loggedin = checkLogon($post_user, $post_pass)) {	// allow Admin user login
			zp_setcookie("zenphoto_auth", $auth, time()+COOKIE_PESISTENCE, $cookiepath);
			if (isset($_POST['redirect']) && !empty($_POST['redirect'])) {
				header("Location: " . FULLWEBPATH . "/" . sanitize($_POST['redirect'], 3));
				exit();
			}
		} else {
			if (($auth == $check_auth) && $post_user == $check_user) {
				// Correct auth info. Set the cookie.
				if (DEBUG_LOGIN) debugLog("zp_handle_password: valid credentials");
				zp_setcookie($authType, $auth, time()+COOKIE_PESISTENCE, $cookiepath);
				if (isset($_POST['redirect']) && !empty($_POST['redirect'])) {
					header("Location: " . FULLWEBPATH . "/" . sanitize($_POST['redirect'], 3));
					exit();
				}
			} else {
				// Clear the cookie, just in case
				if (DEBUG_LOGIN) debugLog("zp_handle_password: invalid credentials");
				zp_setcookie($authType, "", time()-368000, $cookiepath);
				$_zp_login_error = true;
			}
		}
		return;
	}
	if (empty($check_auth)) { //no password on record
		return;
	}
	if (($saved_auth = zp_getCookie($authType)) != '') {
		if ($saved_auth == $check_auth) {
			if (DEBUG_LOGIN) debugLog("zp_handle_password: valid cookie");
			return;
		} else {
			// Clear the cookie
			if (DEBUG_LOGIN) debugLog("zp_handle_password: invalid cookie");
			zp_setcookie($authType, "", time()-368000, $cookiepath);
		}
	}
}


function zp_load_page($pagenum=NULL) {
	global $_zp_page;
	if (!is_numeric($pagenum)) {
		$_zp_page = isset($_GET['page']) ? $_GET['page'] : 1;
	} else {
		$_zp_page = round($pagenum);
	}
}


/**
 * Loads the gallery if it hasn't already been loaded. This function doesn't
 * really do anything, since the gallery is always loaded in init...
 */
function zp_load_gallery() {
	global $_zp_gallery;
	if ($_zp_gallery == NULL)
	$_zp_gallery = new Gallery();
	set_context(ZP_INDEX);
	return $_zp_gallery;
}

/**
 * Loads the search object if it hasn't already been loaded.
 */
function zp_load_search() {
	global $_zp_current_search;
	if ($_zp_current_search == NULL)
		$_zp_current_search = new SearchEngine();
	set_context(ZP_INDEX | ZP_SEARCH);
	$cookiepath = WEBPATH;
	if (WEBPATH == '') { $cookiepath = '/'; }
	$params = $_zp_current_search->getSearchParams();
	zp_setcookie("zenphoto_image_search_params", $params, 0, $cookiepath);
	return $_zp_current_search;
}

/**
 * zp_load_album - loads the album given by the folder name $folder into the
 * global context, and sets the context appropriately.
 * @param $folder the folder name of the album to load. Ex: 'testalbum', 'test/subalbum', etc.
 * @param $force_cache whether to force the use of the global object cache.
 * @return the loaded album object on success, or (===false) on failure.
 */
function zp_load_album($folder, $force_nocache=false) {
	global $_zp_current_album, $_zp_gallery, $_zp_dynamic_album;
	$_zp_current_album = new Album($_zp_gallery, $folder, !$force_nocache);
	if (!$_zp_current_album->exists) return false;
	if ($_zp_current_album->isDynamic()) {
		$_zp_dynamic_album = $_zp_current_album;
	} else {
		$_zp_dynamic_album = null;
	}
	set_context(ZP_ALBUM | ZP_INDEX);
	return $_zp_current_album;
}

/**
 * zp_load_image - loads the image given by the $folder and $filename into the
 * global context, and sets the context appropriately.
 * @param $folder is the folder name of the album this image is in. Ex: 'testalbum'
 * @param $filename is the filename of the image to load.
 * @return the loaded album object on success, or (===false) on failure.
 */
function zp_load_image($folder, $filename) {
	global $_zp_current_image, $_zp_current_album, $_zp_current_search;
	if ($_zp_current_album == NULL || $_zp_current_album->name != $folder) {
		$album = zp_load_album($folder);
	} else {
		$album = $_zp_current_album;
	}
	if (!$_zp_current_album->exists) return false;
	$_zp_current_image = newImage($album, $filename);
	if (!$_zp_current_image->exists) return false;
	set_context(ZP_IMAGE | ZP_ALBUM | ZP_INDEX);
	return $_zp_current_image;
}

/**
 * Loads a zenpage pages page
 * Sets up $_zp_current_zenpage_page and returns it as the function result.
 * @param $titlelink the titlelink of a zenpage page to setup a page object directly. Meant to be used only for the zenpage homepage feature.
 * @return object
 */
function zenpage_load_page() {
	global $_zp_current_zenpage_page;
	$_zp_current_zenpage_page = NULL;
	if (isset($_GET['title'])) {
		$titlelink = sanitize($_GET['title']);
	} else {
		$titlelink = '';
	}
	$sql = 'SELECT `id` FROM '.prefix('zenpage_pages').' WHERE `titlelink`="'.$titlelink.'"';
	$result = query_single_row($sql);
	if (!empty($titlelink) && is_array($result)) {
		$_zp_current_zenpage_page = new ZenpagePage($titlelink);
		add_context(ZP_ZENPAGE_PAGE);
	} else {
		$_GET['p'] = strtoupper(ZENPAGE_PAGES).':'.$titlelink;
	}
	return $_zp_current_zenpage_page;
}

/**
 * Loads a zenpage news page
 * Sets up $_zp_current_zenpage_news and returns it as the function result.
 *
 * @return object
 */
function zenpage_load_news() {
	global $_zp_current_zenpage_news;
	$_zp_current_zenpage_news = NULL;
	if (isset($_GET['title'])) {
		$titlelink = sanitize($_GET['title']);
		$sql = 'SELECT `id` FROM '.prefix('zenpage_news').' WHERE `titlelink`="'.$titlelink.'"';
		$result = query_single_row($sql);
		if (is_array($result)) {
			add_context(ZP_ZENPAGE_NEWS_ARTICLE);
			$_zp_current_zenpage_news = new ZenpageNews($titlelink);
		} else {
			$_GET['p'] = strtoupper(ZENPAGE_NEWS).':'.$titlelink;
		}
		return $_zp_current_zenpage_news;
	}
	return true;
}

/**
 * zp_load_image_from_id - loads and returns the image "id" from the database, without
 * altering the global context or zp_current_image.
 * @param $id the database id-field of the image.
 * @return the loaded image object on success, or (===false) on failure.
 */
function zp_load_image_from_id($id){
	$sql = "SELECT `albumid`, `filename` FROM " .prefix('images') ." WHERE `id` = " . $id;
	$result = query_single_row($sql);
	$filename = $result['filename'];
	$albumid = $result['albumid'];

	$sql = "SELECT `folder` FROM ". prefix('albums') ." WHERE `id` = " . $albumid;
	$result = query_single_row($sql);
	$folder = $result['folder'];

	$album = zp_load_album($folder);
	$currentImage = newImage($album, $filename);
	if (!$currentImage->exists) return false;
	return $currentImage;
}

function zp_load_request() {
	list($album, $image) = rewrite_get_album_image('album','image');
	zp_load_page();
	$success = true;
	if (!empty($image)) {
		$success = zp_load_image($album, $image);
	} else if (!empty($album)) {
		$success = zp_load_album($album);
	}
	if (isset($_GET['p'])) {
		$page = str_replace(array('/','\\','.'), '', $_GET['p']);
		if ($page == "search") {
			$success = zp_load_search();
		}
		if (getOption('zp_plugin_zenpage')) {
			if ($page == ZENPAGE_PAGES) {
				$success = zenpage_load_page();
			} else if ($page == ZENPAGE_NEWS) {
				$success = zenpage_load_news();
			}
		}
	}
	if ($success) add_context(ZP_INDEX);
	return $success;
}

?>