<?php

/*******************************************************************/
/*ZENPHOTO SEARCH ENGINE CLASS ********************************/
/*******************************************************************/
define('SEARCH_TITLE', 1);
define('SEARCH_DESC', 2);
define('SEARCH_TAGS', 4);
define('SEARCH_FILENAME', 8);
define('SEARCH_LOCATION', 16);
define('SEARCH_CITY', 32);
define('SEARCH_STATE', 64);
define('SEARCH_COUNTRY', 128);
define('SEARCH_FOLDER', 256);

/*******************************************************************/
class SearchEngine
{
var $words;
var $dates;
var $fields;
var $page;
var $images;
var $albums;

/**
 * Constuctor
 *
 * @return SearchEngine
 */
function SearchEngine() {
  $this->words = $this->getSearchWords();
  $this->dates = $this->getSearchDate();
  $this->fields = $this->getQueryFields();
  $this->images = null;  
  $this->albums = null;
  }

  /**
   * creates a search query from the search words
   *
   * @return string
   */
function getSearchParams() {
  global $_zp_page;
  $r = '';
  $w = $this->words;
  if (!empty($w)) { $r .= '&words=' . $w; }
  $d = $this->dates;
  if (!empty($d)) { $r .= '&date=' . $d; }
  $f = $this->fields;
  if (!empty($f)) { $r .= '&searchfields=' . $f; }
  if ($_zp_page != 1) { 
    $this->page = $_zp_page;
    $r .= '&page=' . $_zp_page; 
  }
  return $r;
}
/**
 * Parses and stores a search string
 *
 * @param string $paramstr the string containing the search words
 */
function setSearchParams($paramstr) {
  $params = explode('&', $paramstr);
  foreach ($params as $param) {
    $e = strpos($param, '=');
	$p = substr($param, 0, $e);
	$v = sanitize(substr($param, $e + 1), true);
    switch($p) {
	  case 'words':
	  $this->words = $v;
	  break;
	  case 'date':
	  $this->dates = $v;
	  break;
	  case 'searchfields':
	  $this->fields = $v;
	  break;
	  case 'page':
	  $this->page = $v;
	  break;
    }
  }
}

/**
 * Captures and returns the query search string
 *
 * @return string
 */
function getSearchWords() {
  $this->words = sanitize($_REQUEST['words']);
  return $this->words;
}

/**
 * Captures and returns the date string of a search
 *
 * @return string
 */
function getSearchDate() {
  $this->dates = sanitize($_REQUEST['date']);
  return $this->dates;
}

/**
 * Parses a search string
 * Items in peck marks are treated as atomic
 * Returns an array of search elements
 *
 * @return array
 */
function getSearchString() {
  $searchstring = $this->words;
  $result = array();
  $target = "";
  $i = 0;
  do {
    $c = substr($searchstring, $i, 1);
    switch ($c) {
      case '`':
        $j = strpos($searchstring, '`', $i + 1);
        $target .= substr($searchstring, $i+1, $i-$j);
        $i = $j;
        break;
      case '&':
      case '|':
      case '!':
      case ',':
      case '(':
      case ')':
        if (!empty($target)) { 
          $r = trim($target);
          if (!empty($r)) {
            $result[] = sanitize($r, true); 
          }
        }
        $target = '';
        $result[] = $c;
        break;
      default:
        $target .= $c;
        break;
    }
  } while ($i++ < strlen($searchstring));
  if (!empty($target)) { $result[] = sanitize(trim($target)); } 

  return $result;
}

/**
 * Returns the number of albums found in a search
 *
 * @return int
 */
function getNumAlbums() {
 if (is_null($this->albums)) { 
    $this->getAlbums(0);
  }
  return count($this->albums);
}

/**
 * Returns the set of fields from the url query/post
 *@return int 
 *@since 1.1.3
 */
function getQueryFields() {
  if (isset($_REQUEST['searchfields'])) {
    $fields = 0+strip($_GET['searchfields']); 
  } else {
    $fields = 0;
  }
  if (isset($_REQUEST['sf_title'])) { $fields |= SEARCH_TITLE; }
  if (isset($_REQUEST['sf_desc']))  { $fields |= SEARCH_DESC; }
  if (isset($_REQUEST['sf_tags']))  { $fields |= SEARCH_TAGS; }
  if (isset($_REQUEST['sf_filename'])) { $fields |= SEARCH_FILENAME; }
  if (isset($_REQUEST['sf_location'])) { $fields |= SEARCH_LOCATION; }
  if (isset($_REQUEST['sf_city'])) { $fields |= SEARCH_CITY; }
  if (isset($_REQUEST['sf_state'])) { $fields |= SEARCH_STATE; }
  if (isset($_REQUEST['sf_country'])) { $fields |= SEARCH_COUNTRY; }
  
  if ($fields == 0) { $fields = SEARCH_TITLE | SEARCH_DESC | SEARCH_TAGS | SEARCH_FILENAME | SEARCH_LOCATION | SEARCH_CITY | SEARCH_STATE | SEARCH_COUNTRY; }

  $fields = $fields & getOption('search_fields');
  return $fields;
}

/**
  * returns the sql string for a search
  * @param string $searchstring the search target
  * @param string $searchdate the date target
  * @param string $tbl the database table to search
  * @param int $fields which fields to perform the search on
  * @return string
  * @since 1.1.3
 */
function getSearchSQL($searchstring, $searchdate, $tbl, $fields) {
  $sql = 'SELECT `show`,`title`,`desc`,`tags`';
  if ($tbl=='albums') {
    if ($fields & SEARCH_FILENAME) { $fields = $fields + SEARCH_FOLDER; } // for searching these are really the same thing, just named differently in the different tables
	$fields = $fields & (SEARCH_TITLE + SEARCH_DESC + SEARCH_TAGS + SEARCH_FOLDER); // these are all albums have
	$sql .= ",`folder`";
  } else {
    $sql .= ",`albumid`,`filename`,`location`,`city`,`state`,`country`";
  }
  $sql .= " FROM ".prefix($tbl)." WHERE ";
  if(!zp_loggedin()) { $sql .= "`show` = 1 AND ("; }
  $join = "";
  $nrt = 0;
  foreach($searchstring as $singlesearchstring){
    switch ($singlesearchstring) {
      case '&':
        $join .= " AND ";
        break;
      case '!':
        $join .= " NOT ";
        break;
      case ',':
      case '|':
        $join .= " OR ";
        break;
      case '(':
      case ')':
        $join .= $singlesearchstring;
        break;
      default:
        $subsql = "";
        $nr = 0;
        if (SEARCH_TITLE & $fields) {
          $nr++;
          if ($nr > 1) { $subsql .= " OR "; } // add OR for more searchstrings
          $subsql .= " `title` LIKE '%$singlesearchstring%'";
        }
        if (SEARCH_DESC & $fields) {
          $nr++;
          if ($nr > 1) { $subsql .= " OR "; } // add OR for more searchstrings
          $subsql .= " `desc` LIKE '%$singlesearchstring%'";
        }
        if (SEARCH_TAGS & $fields) {
          $nr++;
          if ($nr > 1) { $subsql .= " OR "; } // add OR for more searchstrings
          $subsql .= " `tags` LIKE '%$singlesearchstring%'";
        }
        if (SEARCH_FOLDER & $fields) {
          $nr++;
          if ($nr > 1) { $subsql .= " OR "; } // add OR for more searchstrings
          $subsql .= " `folder` LIKE '%$singlesearchstring%'";
        }
        if (SEARCH_FILENAME & $fields) {
          $nr++;
          if ($nr > 1) { $subsql .= " OR "; } // add OR for more searchstrings
          $subsql .= " `filename` LIKE '%$singlesearchstring%'";
        }
        if (SEARCH_LOCATION & $fields) {
          $nr++;
          if ($nr > 1) { $subsql .= " OR "; } // add OR for more searchstrings
          $subsql .= " `location` LIKE '%$singlesearchstring%'";
        }
        if (SEARCH_CITY & $fields) {
          $nr++;
          if ($nr > 1) { $subsql .= " OR "; } // add OR for more searchstrings
          $subsql .= " `city` LIKE '%$singlesearchstring%'";
        }
        if (SEARCH_STATE & $fields) {
          $nr++;
          if ($nr > 1) { $subsql .= " OR "; } // add OR for more searchstrings
          $subsql .= " `state` LIKE '%$singlesearchstring%'";
        }
        if (SEARCH_COUNTRY & $fields) {
          $nr++;
          if ($nr > 1) { $subsql .= " OR "; } // add OR for more searchstrings
          $subsql .= " `country` LIKE '%$singlesearchstring%'";
        }
        if ($nr > 0) {
          $nrt++;
          $sql .= $join; 
          $join = "";
          $sql .= "($subsql)";
        }
    }
  }
  $sql .= $join;
  
  if (!empty($searchdate)) {
    if ($nrt > 1) { $sql = $sql." AND "; }
	$nrt++;
	if ($searchdate == "0000-00") {
	  $sql .= "`date`=\"0000-00-00 00:00:00\"";
	} else {
  	  $d1 = $searchdate."-01 00:00:00";
	  $d = strtotime($d1);
	  $d = strtotime('+ 1 month', $d);
      $d2 = substr(date('Y-m-d H:m:s', $d), 0, 7) . "-01 00:00:00";
      $sql .= "`date` >= \"$d1\" AND `date` < \"$d2\""; 
	}
  }
  if(!zp_loggedin()) { $sql .= ")"; }
  if ($nrt == 0) { return NULL; } // no valid fields
  return $sql;
}

/**
 * Returns an array of albums found in the search
 *
 * @return array
 */
function getSearchAlbums() {
  $albums = array();
  $searchstring = $this->getSearchString(); 
  if (empty($searchstring)) { return $albums; } // nothing to find
  $sql = $this->getSearchSQL($searchstring, '', 'albums', $this->fields);
  if (empty($sql)) { return $albums; } // no valid fields
  $albumfolder = getAlbumFolder();
  $search_results = query_full_array($sql, true);
  if (is_array($search_results)) {
    foreach ($search_results as $row) {
      $albumname = $row['folder'];
      if (file_exists($albumfolder . $albumname)) {
        if (checkAlbumPassword($albumname)) {
          $albums[] = $row['folder'];
        }
      }
    }
  }

return $albums;

}

/**
 * Returns an array of album names found in the search. 
 * If $page is not zero, it returns the current page's albums
 *
 * @param int $page the page number we are on
 * @return array
 */
function getAlbums($page=0) {
 if (is_null($this->albums)) {
    $this->albums = $this->getSearchAlbums();
  }
    if ($page == 0) { 
      return $this->albums;
    } else {
      $albums_per_page = getOption('albums_per_page');
      return array_slice($this->albums, $albums_per_page*($page-1), $albums_per_page);
    }  
  }
  
  /**
   * Returns the album following the current one
   *
   * @return object
   */
  function getNextAlbum() {
    $albums = $this->getAlbums(0);
    $inx = array_search($this->name, $albums)+1;
    if ($inx >= 0 && $inx < count($albums)) {
      return new Album($parent, $albums[$inx]);
    } 
    return null;
  }
  
  /**
   * Returns the album preceding the current one
   *
   * @return object
   */
  function getPrevAlbum() {
     $albums = $this->getAlbums(0);
    $inx = array_search($this->name, $albums)-1;
    if ($inx >= 0 && $inx < count($albums)) {
      return new Album($paraent, $albums[$inx]);
    } 
    return null;
  }


  /**
   * Returns the number of images found in the search
   *
   * @return int
   */
  function getNumImages() {
  if (is_null($this->images)) { 
    $this->getImages(0);
  }
  return count($this->images);
}

/**
 * Returns an array of image names found in the search
 *
 * @return array
 */
function getSearchImages() {
  global $_zp_current_gallery;
  $images = array();
  $searchstring = $this->getSearchString();
  $searchdate = $this->dates;
  if (empty($searchstring) && empty($searchdate)) { return $images; } // nothing to find
  $sql = $this->getSearchSQL($searchstring, $searchdate, 'images', $this->fields);
  if (empty($sql)) { return $images; } // no valid fields
  $albumfolder = getAlbumFolder();
  $search_results = query_full_array($sql, true); 
  if (is_array($search_results)) {
    foreach ($search_results as $row) {
      $albumid = $row['albumid'];
      $query = "SELECT id, title, folder,`show` FROM ".prefix('albums')." WHERE id = $albumid";
      $row2 = query_single_row($query); // id is unique
      $albumname = $row2['folder'];
      if (file_exists($albumfolder . $albumname . '/' . $row['filename'])) {
        if (checkAlbumPassword($albumname)) {
          $images[] = array('filename' => $row['filename'], 'folder' => $row2['folder']);
        }
      }
    }
  }

  return $images;

}

/**
 * Returns an array of images found in the search
 * It will return a "page's worth" if $page is non zero
 *
 * @param int $page the page number desired
 * @param int $firstPageCount count of images that go on the album/image transition page
 * @return array
 */
function getImages($page=0, $firstPageCount=0) {
  if (is_null($this->images)) {
    $this->images = $this->getSearchImages();
  }
  if ($page == 0) {
    return $this->images;
  } else {
    // Only return $firstPageCount images if we are on the first page and $firstPageCount > 0
      if (($page==1) && ($firstPageCount>0)) {
        $pageStart = 0;
        $images_per_page = $firstPageCount;
      } else {
        if ($firstPageCount>0) { 
          $fetchPage = $page - 2; 
        } else {
          $fetchPage = $page - 1;
        } 
        $images_per_page = getOption('images_per_page');
        $pageStart = $firstPageCount + $images_per_page * $fetchPage;
      }  
    $slice = array_slice($this->images, $pageStart , $images_per_page);
    return $slice;
    }
  }
  /**
   * Returns a specific image
   *
   * @param int $index the index of the image desired
   * @return object
   */
  function getImage($index) {
    global $_zp_gallery;
    if ($index >= 0 && $index < $this->getNumImages()) {
      if (!is_null($this->images)) {
		$img = $this->images[$index];
        return new Image(new Album($_zp_gallery, $img['folder']), $img['filename']);
      }
    }
    return false;
  }

} // search class end

?>