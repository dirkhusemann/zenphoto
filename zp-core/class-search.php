<?php

/*******************************************************************/
/*ZENPHOTO SEARCH ENGINE CLASS *************************************/
/*******************************************************************/
class SearchEngine
{
var $words;
var $dates;
var $images;
var $albums;

// constructor
function SearchEngine() {
  $this->words = $this->getSearchWords();
  $this->dates = $this->getSearchDate();
  $this->images = null;  
  $this->albums = null;
  }

/******************************************************************/
function getSearchWords() {
  $this->words = $_REQUEST['words'];
  return $this->words;
}
/******************************************************************/
function getSearchDate() {
  $this->dates = $_REQUEST['date'];
  return $this->dates;
}
/******************************************************************/
function getSearchString() {
  $searchstring = $this->words;
  $result = array();
  $unquoted = "";
  do {
    $i = strpos($searchstring, "`");  // use peck marks to quote search elements
    if (!($i === false)) {
      $unquoted = trim($unquoted." ".trim(substr($searchstring, 0, $i)));
	  $searchstring = substr($searchstring, $i+1);
     $j = strpos($searchstring, "`");
	  if (!($j === false)) {
	    $result[] = substr($searchstring, 0, $j);
        $searchstring = substr($searchstring, $j+1);
	  }
    }
  } while (!($i === false));
  $unquoted = trim($unquoted." ".trim($searchstring));
  $searchstring = explode(",",$unquoted); // separating several search words

  foreach ($searchstring as $item) {
    $item = trim($item);
    if (!empty($item)) {
      $result[] = mysql_real_escape_string($item);
	}
  }
  return $result;
}
/******************************************************************/
function getNumAlbums() {
 if (is_null($this->albums)) { 
    $this->getAlbums(0);
  }
  return count($this->albums);
}
/******************************************************************/   
function getSearchAlbums() {
  $albums = array();
  $searchstring = $this->getSearchString(); 
  if (empty($searchstring)) { return $albums; } // nothing to find
  $sql="SELECT `title`, `desc`, `tags`, `folder`, `show` FROM ".prefix('albums')." WHERE ";
  if(!zp_loggedin()) { $sql = $sql."`show` = 1 AND"; }
  $nr = 0;
  foreach($searchstring as $singlesearchstring){
    $nr++;
    if ($nr > 1) { $sql = $sql." OR "; } // add OR for more searchstrings
    $sql = $sql." `title` LIKE '%".$singlesearchstring."%' 
                  OR `desc` LIKE '%".$singlesearchstring."%'
                  OR `tags` LIKE '%".$singlesearchstring."%'
                  OR `folder` LIKE '%".$singlesearchstring."%'"; 
  }  
  $albumfolder = getAlbumFolder();
  $search_results = query_full_array($sql); 
  foreach ($search_results as $row) {  
    if (file_exists($albumfolder . $row['folder'])) {  
      $albums[] = $row['folder']; 
    }	
  }

return $albums;

}
/******************************************************************/
function getAlbums($page=0) {
 if (is_null($this->albums)) {
    $this->albums = $this->getSearchAlbums();
  }
    if ($page == 0) { 
      return $this->albums;
    } else {
      $albums_per_page = zp_conf('albums_per_page');
      return array_slice($this->albums, $albums_per_page*($page-1), $albums_per_page);
    }  
  }

/******************************************************************/
function getNumImages() {
  if (is_null($this->images)) { 
    $this->getImages(0);
  }
  return count($this->images);
}
/******************************************************************/   
function getSearchImages() {
  global $_zp_current_gallery;
  $images = array();
  $searchstring = $this->getSearchString();
  $searchdate = $this->getSearchDate();
  if (empty($searchstring) && empty($searchdate)) { return $images; } // nothing to find
  $sql="SELECT `title`, `desc`, `tags`, `albumid`, `filename`, `show`, `date`, `location`, `city`, `state`, `country` FROM ".prefix('images')." WHERE ";
  if(!zp_loggedin()) { $sql = $sql."`show` = 1 AND"; }
  $nr = 0;
  foreach($searchstring as $singlesearchstring){
    $nr++;
    if ($nr > 1) { $sql = $sql." OR "; } // add OR for more searchstrings
    $sql = $sql." `title` LIKE '%".$singlesearchstring."%' 
                  OR `desc` LIKE '%".$singlesearchstring."%'
                  OR `tags` LIKE '%".$singlesearchstring."%'
                  OR `filename` LIKE '%".$singlesearchstring."%'
                  OR `location` LIKE '%".$singlesearchstring."%'
                  OR `city` LIKE '%".$singlesearchstring."%'
                  OR `state` LIKE '%".$singlesearchstring."%'
                  OR `country` LIKE '%".$singlesearchstring."%'"; 
  }  
  if (!empty($searchdate)) { 
    if ($nr > 1) { $sql = $sql." AND "; } // add OR for more searchstrings
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
  $albumfolder = getAlbumFolder();
  $search_results = query_full_array($sql); 
  foreach ($search_results as $row) { 
    $albumid = $row['albumid'];
   	$query = "SELECT id, title, folder,`show` FROM ".prefix('albums')." WHERE id = $albumid"; 
   	$row2 = query_single_row($query); // id is unique
	if (file_exists($albumfolder . $row2['folder'] . '/' . $row['filename'])) {
      $images[] = array('filename' => $row['filename'], 'folder' => $row2['folder']);
	}
  } 

  return $images;

}
/******************************************************************/
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
        $images_per_page = zp_conf('images_per_page');
        $pageStart = $firstPageCount + $images_per_page * $fetchPage;
      }  
    $slice = array_slice($this->images, $pageStart , $images_per_page);
    return $slice;
    }
  }


} // search class end



?>