<?php
/**
 * search class
 * @package classes
 */

// force UTF-8 Ø


//*************************************************************
//*ZENPHOTO SEARCH ENGINE CLASS *******************************
//*************************************************************

class SearchEngine
{
	var $words;
	var $dates;
	var $whichdates = 'date'; // for zenpage date searches, which date field to search
	var $fieldList;
	var $page;
	var $images;
	var $albums;
	var $album_list; // list of albums to search
	var $dynalbumname;
	var $search_structure;		// relates translatable names to search fields
	var $lastimagesort = NULL;  // remember the order for the last album/image sorts
	var $lastsubalbumsort = NULL;

	/**
	 * Constuctor
	 *
	 * @param bool $dynamic_album set true for dynamic albums (limits the search fields)
	 * @return SearchEngine
	 */
	function SearchEngine($dynamic_album = false) {
		global $_zp_exifvars;
		//image/album fields
		$this->search_structure['title']							= gettext('Title');
		$this->search_structure['desc']								= gettext('Description');
		$this->search_structure['tags']								= gettext('Tags');
		$this->search_structure['filename']						= gettext('File/Folder name');
		$this->search_structure['date']								= gettext('Date');
		$this->search_structure['custom_data']				= gettext('Custom data');
		$this->search_structure['location']						= gettext('Location/Place');
		$this->search_structure['city']								= gettext('City');
		$this->search_structure['state']							= gettext('State');
		$this->search_structure['country']						= gettext('Country');
		$this->search_structure['copyright']					= gettext('Copyright');
		if (getOption('zp_plugin_zenpage') && !$dynamic_album) {//zenpage fields
			$this->search_structure['content']					= gettext('Content');
			$this->search_structure['extracontent']			= gettext('ExtraContent');
			$this->search_structure['author']						= gettext('Author');
			$this->search_structure['lastchangeauthor']	= gettext('Last Editor');
			$this->search_structure['titlelink']				= gettext('TitleLink');
		}
		//metadata fields
		foreach ($_zp_exifvars as $field=>$row) {
			$this->search_structure[$field]							= $row[2];
		}

		if (isset($_REQUEST['words'])) {
			$this->words = $_REQUEST['words'];
		} else {
			$this->words = '';
			if (isset($_REQUEST['date'])) {  // words & dates are mutually exclusive
				$this->dates = sanitize($_REQUEST['date'], 3);
				if (isset($_REQUEST['whichdate'])) {
					$this->whichdates = sanitize($_REQUEST['whichdate']);
				}
			} else {
				$this->dates = '';
			}
		}
		$this->fieldList = $this->parseQueryFields();
		$this->album_list = NULL;
		if (isset($_REQUEST['inalbums'])) {
			$list = trim(sanitize($_REQUEST['inalbums'], 3));
			if (!empty($list)) {
				$this->album_list = explode(',',$list);
			}
		}
		$this->images = NULL;
		$this->albums = NULL;
	}

	/**
	 * Returns a list of search fields display names indexed by the search mask
	 *
	 * @return array
	 */
	function getSearchFieldList() {
		$list = array();
		foreach ($this->search_structure as $key=>$display) {
			$list[$key] = $display;
		}
		return $list;
	}

	/**
	 * Returns an array of the enabled search fields
	 *
	 * @return array
	 */
	function allowedSearchFields() {
		$setlist = array();
		$fields = getOption('search_fields');
		if (is_numeric($fields)) {
			$setlist = $this->numericFields($fields);
		} else {
			$list = explode(',',$fields);
			foreach ($this->search_structure as $key=>$display) {
				if (in_array($key,$list)) {
					$setlist[$key] = $display;
				}
			}
		}
		return $setlist;
	}

	/**
	 * converts old style bitmask field spec into field list array
	 *
	 * @param bit $fields
	 * @return array
	 */
	function numericFields($fields) {
		if ($fields==0) $fields = 0x0fff;
		if ($fields & 0x01) $list['title'] = $this->search_structure['title'];
		if ($fields & 0x02) $list['desc'] = $this->search_structure['desc'];
		if ($fields & 0x04) $list['tags'] = $this->search_structure['tags'];
		if ($fields & 0x08) $list['filename'] = $this->search_structure['filename'];
		return $list;
	}

	/**
	 * creates a search query from the search words
	 *
	 * @param bool $long set to false to omit albumname and page parts
	 *
	 * @return string
	 */
	function getSearchParams($long=true) {
		global $_zp_page;
		$r = '';
		$w = urlencode(trim($this->codifySearchString()));
		if (!empty($w)) { $r .= '&words=' . $w; }
		$d = trim($this->dates);
		if (!empty($d)) {
			$r .= '&date=' . $d;
			$d = trim($this->whichdates);
			if ($d != 'date') {
				$r.= '&whichdates=' . $d;
			}
		}
		$r .= $this->getSearchFieldsText($this->fieldList);
		if ($long) {
			$a = $this->dynalbumname;
			if ($a) { $r .= '&albumname=' . $a; }
			if (!empty($this->album_list)) {
				$r .= '&inalbums='.implode(',', $this->album_list);
			}
			if ($_zp_page != 1) {
				$this->page = $_zp_page;
				$r .= '&page=' . $_zp_page;
			}
		}
		return $r;
	}

	/**
	 * Returns the "searchstring" element of a query parameter set
	 *
	 * @param array $fields the fields required
	 * @param string $param the query parameter (possibly with the intro character
	 * @return string
	 */
	function getSearchFieldsText($fields, $param='&searchfields=') {
		$default = array_keys($this->allowedSearchFields());
		$diff = array_diff($default, $fields);
		if (count($diff)>0) {
			foreach ($fields as $field) {
				$param .= $field.',';
			}
			return substr($param,0,-1);
		}
		return '';
	}

	/**
	 * Parses and stores a search string
	 * NOTE!! this function assumes that the 'words' part of the list has been urlencoded!!!
	 *
	 * @param string $paramstr the string containing the search words
	 */
	function setSearchParams($paramstr) {
		$params = explode('&', $paramstr);
		foreach ($params as $param) {
			$e = strpos($param, '=');
			$p = substr($param, 0, $e);
			$v = substr($param, $e + 1);
			switch($p) {
				case 'words':
					$this->words = urldecode($v);
					break;
				case 'date':
					$this->dates = $v;
					break;
				case 'whichdates':
					$this->whichdates = $v;
					break;
				case 'searchfields':
					if (is_numeric($v)) {
						$this->fieldList = array_flip($this->numericFields($v));
					} else {
						$this->fieldList = array();
						$list = explode(',',$v);
						foreach ($this->search_structure as $key=>$row) {
							if (in_array($key,$list)) {
								$this->fieldList[] = $key;
							}
						}
					}
					break;
				case 'page':
					$this->page = $v;
					break;
				case 'albumname':
					$this->dynalbumname = $v;
					break;
				case 'inalbums':
					$this->album_list = explode(',', $v);
					break;
			}
		}
		if (!empty($this->words)) {
			$this->dates = ''; // words and dates are mutually exclusive
		}
	}

	/**
	 * Returns the search words variable
	 *
	 * @return string
	 */
	function getSearchWords() {
		return $this->words;
	}

	/**
	 * Returns the search dates variable
	 *
	 * @return string
	 */
	function getSearchDate() {
		return $this->dates;
	}

	/**
	 * Returns the search fields variable
	 *
	 * @param bool $array set to true to return the fields as array elements. Otherwise
	 * a comma delimited string is returned
	 *
	 * @return mixed
	 */
	function getSearchFields($array=false) {
		if ($array) return $this->fieldList;
		return implode(',',$this->fieldList);
	}

	/**
	 * Parses a search string
	 * Items within quotations are treated as atomic
	 * AND, OR and NOT are converted to &, |, and !
	 *
	 * Returns an array of search elements
	 *
	 * @return array
	 */
	function getSearchString() {
		$searchstring = trim($this->words);
		$space_is_OR = getOption('search_space_is_or');
		$opChars = array ('&'=>1, '|'=>1, '!'=>1, ','=>1, '('=>2);
		if ($space_is_OR) {
			$opChars[' '] = 1;
		}
		$c1 = ' ';
		$result = array();
		$target = "";
		$i = 0;
		do {
			$c = substr($searchstring, $i, 1);
			switch ($c) {
				case "'":
				case '"':
				case '`':
					$j = strpos($searchstring, $c, $i+1);
					if ($j !== false) {
						$target .= substr($searchstring, $i+1, $j-$i-1);
						$i = $j;
					} else {
						$target .= $c;
					}
					$c1 = $c;
					break;
				case ' ':
					if (!$space_is_OR) {
						$c1 = $c;
						$target .= $c;
						break;
					}
				case ',':
					if (!empty($target)) {
						$r = trim($target);
						if (!empty($r)) {
							switch ($r) {
								case 'AND':
									$r = '&';
									break;
								case 'OR':
									$r = '|';
									break;
								case 'NOT':
									$r = '!';
									break;
							}
							$last = $result[] = $r;
							$target = '';
						}
					}
					$c2 = substr($searchstring, $i+1, 1);
					switch ($c2) {
						case 'A':
							if (substr($searchstring, $i+1, 4) == 'AND ') $c2 = '&';
							break;
						case 'O':
							if (substr($searchstring, $i+1, 3) == 'OR ') $c2 = '|';
							break;
						case 'N':
							if (substr($searchstring, $i+1, 4) == 'NOT ') $c2 = '!';
							break;
					}
					if (!((isset($opChars[$c2])&&$opChars[$c2]==1) || (isset($opChars[$last])&&$opChars[$last]==1))) {
						$last = $result[] = '|';
						$c1 = $c;
					}
					break;
				case '&':
				case '|':
				case '!':
				case '(':
				case ')':
					if (!empty($target)) {
						$r = trim($target);
						if (!empty($r)) {
							$last = $result[] = $r;
							$target = '';
						}
					}
					$c1 = $c;
					$target = '';
					$last = $result[] = $c;
					break;
				case 'A':
					if (substr($searchstring, $i, 4) == 'AND ') {
						$searchstring = substr($searchstring, 3);
						if (!empty($target)) {
							$r = trim($target);
							if (!empty($r)) {
								$last = $result[] = $r;
								$target = '';
							}
						}
						$c1 = $c;
						$target = '';
						$last = $result[] = '&';
						break;
					}
				case 'O':
					if (substr($searchstring, $i, 3) == 'OR ') {
						$searchstring = substr($searchstring, 2);
						if (!empty($target)) {
							$r = trim($target);
							if (!empty($r)) {
								$last = $result[] = $r;
								$target = '';
							}
						}
						$c1 = $c;
						$target = '';
						$last = $result[] = '|';
						break;
					}
				case 'N':
					if (substr($searchstring, $i, 4) == 'NOT ') {
						$searchstring = substr($searchstring, 3);
						if (!empty($target)) {
							$r = trim($target);
							if (!empty($r)) {
								$last = $result[] = $r;
								$target = '';
							}
						}
						$c1 = $c;
						$target = '';
						$last = $result[] = '!';
						break;
					}
					default:
					$c1 = $c;
					$target .= $c;
					break;
			}
		} while ($i++ < strlen($searchstring));
		if (!empty($target)) { $last = $result[] = trim($target); }
		$lasttoken = '';
		foreach ($result as $key=>$token) {
			if ($token=='|' && $lasttoken=='|') { // remove redundant OR ops
				unset($result[$key]);
			}
			$lasttoken = $token;
		}
		if ($lasttoken == '|') array_pop($result);
		return $result;
	}

	/**
	 * recodes the search words replacing the boolean operators with text versions
	 *
	 * @param string $quote how to represent quoted strings
	 *
	 * @return string
	 *
	 */
	function codifySearchString($quote='"') {
		$opChars = array ('('=>2, '&'=>1, '|'=>1, '!'=>1, ','=>1);
		$searchstring = $this->getSearchString();
		$sanitizedwords = '';
		if (is_array($searchstring)) {
			foreach($searchstring as $singlesearchstring){
				switch ($singlesearchstring) {
					case '&':
						$sanitizedwords .= " AND ";
						break;
					case '|':
						$sanitizedwords .= " OR ";
						break;
					case '!':
						$sanitizedwords .= " NOT ";
						break;
					case '(':
					case ')':
						$sanitizedwords .= " $singlesearchstring ";
						break;
					default:
						$sanitizedword = sanitize($singlesearchstring, 3);
						$setQuote = $sanitizedword != $singlesearchstring;
						if (!$setQuote) {
							foreach ($opChars as $char => $value) {
								if ((strpos($singlesearchstring, $char) !== false)) {
									$setQuote = true;
									break;
								}
							}
						}
						if ($setQuote) {
							$sanitizedwords .= $quote.$singlesearchstring.$quote;
						} else {
							$sanitizedwords .= ' '.sanitize($singlesearchstring, 3).' ';
						}
				}
			}
		}
		return trim(str_replace(array('   ','  '),' ', $sanitizedwords));
	}

	/**
	 * Returns the number of albums found in a search
	 *
	 * @return int
	 */
	function getNumAlbums() {
		if (is_null($this->albums)) {
			$this->getAlbums(0, NULL, NULL, false);
		}
		return count($this->albums);
	}

	/**
	 * Returns the set of fields from the url query/post
	 * @return int
	 * @since 1.1.3
	 */
	function parseQueryFields() {
		$fields = array();
		if (isset($_REQUEST['searchfields'])) {
			$fs = sanitize($_REQUEST['searchfields']);
			if (is_numeric($fs)) {
				$fields = array_flip($this->numericFields($fs));
			} else {
				$fields = explode(',',$fs);
			}
		} else {
			foreach ($_REQUEST as $key=>$value) {
				if (strpos($key, '_SEARCH_') !== false) {
					$fields[] = $value;
				}
			}
		}
		return $fields;
	}

	/**
	 * returns the results of a date search
	 * @param string $searchstring the search target
	 * @param string $searchdate the date target
	 * @param string $tbl the database table to search
	 * @param string $sorttype what to sort on
	 * @param string $sortdirection what direction
	 * @return string
	 * @since 1.1.3
	 */
	function searchDate($searchstring, $searchdate, $tbl, $sorttype, $sortdirection, $whichdate='date') {
		global $_zp_current_album;
		$sql = 'SELECT DISTINCT `id`, `show`,`title`';
		switch ($tbl) {
			case 'zenpage_pages':
			case 'zenpage_news':
				$sql .= ',`titlelink` ';
				break;
			case 'albums':
				$sql .= ",`desc`,`folder` ";
				break;
			default:
				$sql .= ",`desc`,`albumid`,`filename`,`location`,`city`,`state`,`country` ";
				break;
		}
		$sql .= "FROM ".prefix($tbl)." WHERE ";
		if(!zp_loggedin()) { $sql .= "`show` = 1 AND ("; }

		if (!empty($searchdate)) {
			if ($searchdate == "0000-00") {
				$sql .= "`date`=\"0000-00-00 00:00:00\"";
			} else {
				$d1 = $searchdate."-01 00:00:00";
				$d = strtotime($d1);
				$d = strtotime('+ 1 month', $d);
				$d2 = substr(date('Y-m-d H:m:s', $d), 0, 7) . "-01 00:00:00";
				$sql .= "`$whichdate` >= \"$d1\" AND `date` < \"$d2\"";
			}
		}
		if(!zp_loggedin()) { $sql .= ")"; }

		switch ($tbl) {
			case 'zenpage_news':
				$key = '`id`';
				break;
			case 'zenpage_pages':
				$key = '`sort_order`';
				break;
			case 'albums':
				if (is_null($sorttype)) {
					if (empty($this->dynalbumname)) {
						$key = lookupSortKey(getOption('gallery_sorttype'), 'sort_order', 'folder');
						if ($key != '`sort_order`') {
							if (getOption('gallery_sortdirection')) {
								$key .= " DESC";
							}
						}
					} else {
						$gallery = new Gallery();
						$album = new Album($gallery, $this->dynalbumname);
						$key = $album->getAlbumSortKey();
						if ($key != '`sort_order`' && $key != 'RAND()') {
							if ($album->getSortDirection('album')) {
								$key .= " DESC";
							}
						}
					}
				} else {
					$sorttype = lookupSortKey($sorttype, 'sort_order', 'folder');
					$key = trim($sorttype.' '.$sortdirection);
				}
				break;
			default:
				$hidealbums = getNotViewableAlbums();
				if (!is_null($hidealbums)) {
					foreach ($hidealbums as $id) {
						$sql .= ' AND `albumid`!='.$id;
					}
				}
				if (is_null($sorttype)) {
					if (empty($this->dynalbumname)) {
						$key = lookupSortKey(getOption('image_sorttype'), 'filename', 'filename');
						if ($key != '`sort_order`') {
							if (getOption('image_sortdirection')) {
								$key .= " DESC";
							}
						}
					} else {
						$gallery = new Gallery();
						$album = new Album($gallery, $this->dynalbumname);
						$key = $album->getImageSortKey();
						if ($key != '`sort_order`' && $key != 'RAND()') {
							if ($album->getSortDirection('image')) {
								$key .= " DESC";
							}
						}
					}
				} else {
					$sorttype = lookupSortKey($sorttype, 'filename', 'filename');
					$key = trim($sorttype.' '.$sortdirection);
				}
				break;
		}
		$sql .= " ORDER BY ".$key;
		$result = query_full_array($sql);
		if (!$result) return array();
		return $result;
	}

	/**
	 * Searches the table for tags
	 * Returns an array of database records.
	 *
	 * @param string $searchstring
	 * @param string $tbl set to 'albums' or 'images'
	 * @param string $sorttype what to sort on
	 * @param string $sortdirection what direction
	 * @return array
	 */
	function searchFieldsAndTags($searchstring, $tbl, $sorttype, $sortdirection) {
		$allIDs = null;
		$idlist = array();
		$exact = getOption('exact_tag_match');

		// create an array of [tag, objectid] pairs for tags
		$tag_objects = array();
		$fields = $this->fieldList;
		if (count($fields)==0) { // then use the default ones
			$fields = array_keys($this->allowedSearchFields());
		}
		$t = array_search('tags',$fields);
		if ($t!==false) {
			unset($fields[$t]);
			$tagsql = 'SELECT t.`name`, o.`objectid` FROM '.prefix('tags').' AS t, '.prefix('obj_to_tag').' AS o WHERE t.`id`=o.`tagid` AND o.`type`="'.$tbl.'" AND (';
			foreach($searchstring as $singlesearchstring){
				switch ($singlesearchstring) {
					case '&':
					case '!':
					case '|':
					case '(':
					case ')':
						break;
					default:
						$targetfound = true;
						if ($exact) {
							$tagsql .= '`name` = "'.zp_escape_string($singlesearchstring).'" OR ';
						} else {
							$tagsql .= '`name` LIKE "%'.zp_escape_string($singlesearchstring).'%" OR ';
						}
				}
			}
			$tagsql = substr($tagsql, 0, strlen($tagsql)-4).') ORDER BY t.`id`';
			$objects = query_full_array($tagsql, true);
			if (is_array($objects)) {
				$tag_objects = $objects;
			}
		}

		// create an array of [name, objectid] pairs for the search fields.
		$field_objects = array();
		if (count($fields)>0) {
			$columns = array();
			$sql = 'SHOW COLUMNS FROM '.prefix($tbl);
			$result = query_full_array($sql);
			if (is_array($result)) {
				foreach ($result as $row) {
					$columns[] = strtolower($row['Field']);
				}
			}
			foreach($searchstring as $singlesearchstring){
				switch ($singlesearchstring) {
					case '&':
					case '!':
					case '|':
					case '(':
					case ')':
						break;
					default:
						$targetfound = true;
						query('SET @serachtarget="'.zp_escape_string($singlesearchstring).'"');
						$fieldsql = 'SELECT @serachtarget AS name, `id` AS `objectid` FROM '.prefix($tbl).' WHERE (';

						foreach ($fields as $fieldname) {
							if ($tbl=='albums' && $fieldname='filename') {
								$fieldname = 'folder';
							}
							if ($fieldname && in_array($fieldname, $columns)) {
								$fieldsql .= ' `'.$fieldname.'` LIKE "%'.zp_escape_string($singlesearchstring).'%" OR ';
							}
						}
						$fieldsql = substr($fieldsql, 0, strlen($fieldsql)-4).') ORDER BY `id`';
						$objects = query_full_array($fieldsql, true);
						if (is_array($objects)) {
							$field_objects = array_merge($field_objects, $objects);
						}
				}
			}
		}

		$objects = array_merge($tag_objects, $field_objects);
		if (count($objects) != 0) {
			$tagid = '';
			$taglist = array();

			foreach ($objects as $object) {
				$tagid = strtolower($object['name']);
				if (!isset($taglist[$tagid]) || !is_array($taglist[$tagid])) {
					$taglist[$tagid] = array();
				}
				$taglist[$tagid][] = $object['objectid'];
			}
			$op = '';
			$idstack = array();
			$opstack = array();
			while (count($searchstring) > 0) {
				$singlesearchstring = array_shift($searchstring);
				switch ($singlesearchstring) {
					case '&':
					case '!':
					case '|':
						$op = $op.$singlesearchstring;
						break;
					case '(':
						array_push($idstack, $idlist);
						array_push($opstack, $op);
						$idlist = array();
						$op = '';
						break;
					case ')':
						$objectid = $idlist;
						$idlist = array_pop($idstack);
						$op = array_pop($opstack);
						switch ($op) {
							case '&':
								if (is_array($objectid)) {
									$idlist = array_intersect($idlist, $objectid);
								} else {
									$idlist = array();
								}
								break;
							case '!':
								break; // Paren followed by NOT is nonsensical?
							case '&!':
								if (is_array($objectid)) {
									$idlist = array_diff($idlist, $objectid);
								}
								break;
							case '';
							case '|':
								if (is_array($objectid)) {
									$idlist = array_merge($idlist, $objectid);
								}
								break;
						}
						$op = '';
						break;
							default:
								$lookfor = strtolower($singlesearchstring);
								$objectid = NULL;
								foreach ($taglist as $key => $objlist) {
									if (($exact && $lookfor == $key) || (!$exact && preg_match('%'.$lookfor.'%', $key))) {
										if (is_array($objectid)) {
											$objectid = array_merge($objectid, $objlist);
										} else {
											$objectid = $objlist;
										}
									}
								}
								switch ($op) {
									case '&':
										if (is_array($objectid)) {
											$idlist = array_intersect($idlist, $objectid);
										} else {
											$idlist = array();
										}
										break;
									case '!':
										if (is_null($allIDs)) {
											$allIDs = array();
											$result = query_full_array("SELECT `id` FROM ".prefix($tbl));
											if (is_array($result)) {
												foreach ($result as $row) {
													$allIDs[] = $row['id'];
												}
											}
										}
										if (is_array($objectid)) {
											$idlist = array_merge($idlist, array_diff($allIDs, $objectid));
										}
										break;
									case '&!':
										if (is_array($objectid)) {
											$idlist = array_diff($idlist, $objectid);
										}
										break;
									case '';
									case '|':
										if (is_array($objectid)) {
											$idlist = array_merge($idlist, $objectid);
										}
										break;
								}
								$idlist = array_unique($idlist);
								$op = '';
								break;
				}
				$idlist = array_unique($idlist);
			}
		}
		if (count($idlist)==0) {return NULL; }

		$sql = 'SELECT DISTINCT `id`,`show`,`title`,';
		switch ($tbl) {
			case 'zenpage_pages':
			case 'zenpage_news':
				$sql .= '`titlelink` ';
				break;
			case 'albums':
				$sql .= "`desc`,`folder` ";
				break;
			default:
				$sql .= "`desc`,`albumid`,`filename`,`location`,`city`,`state`,`country` ";
				break;
		}
		$sql .= "FROM ".prefix($tbl)." WHERE ";
		if(!zp_loggedin()) { $sql .= "`show` = 1 AND "; }
		$sql .= "(";
		asort($idlist);
		$last = $base = array_shift($idlist);
		foreach ($idlist as $object) {
			if ($last < $object-1) {
				if ($base == $last) {
					$sql .= '(`id`='.$base.') OR ';
				} else {
					$sql .= '(`id` BETWEEN '.$base.' AND '.$last.') OR ';
				}
				$base = $object;
			}
			$last = $object;
		}
		if ($base == $last) {
			$sql .= '(`id`='.$base.'))';
		} else {
			$sql .= '(`id` BETWEEN '.$base.' AND '.$last.'))';
		}

		switch ($tbl) {
			case 'zenpage_news':
				$key = '`id`';
				break;
			case 'zenpage_pages':
				$key = '`sort_order`';
				break;
			case 'albums':
				if (is_null($sorttype)) {
					if (empty($this->dynalbumname)) {
						$key = lookupSortKey(getOption('gallery_sorttype'), 'sort_order', 'folder');
						if (getOption('gallery_sortdirection')) { $key .= " DESC"; }
					} else {
						$gallery = new Gallery();
						$album = new Album($gallery, $this->dynalbumname);
						$key = $album->getAlbumSortKey();
						if ($key != '`sort_order`' && $key != 'RAND()') {
							if ($album->getSortDirection('album')) {
								$key .= " DESC";
							}
						}
					}
				} else {
					$sorttype = lookupSortKey($sorttype, 'sort_order', 'folder');
					$key = trim($sorttype.' '.$sortdirection);
				}
				break;
			default:
				if (is_null($sorttype)) {
					if (empty($this->dynalbumname)) {
						$key = lookupSortKey(getOption('image_sorttype'), 'filename', 'filename');
						if (getOption('image_sortdirection')) { $key .= " DESC"; }
					} else {
						$gallery = new Gallery();
						$album = new Album($gallery, $this->dynalbumname);
						$key = $album->getImageSortKey();
						if ($key != '`sort_order`') {
							if ($album->getSortDirection('image')) {
								$key .= " DESC";
							}
						}
					}
				} else {
					$sorttype = lookupSortKey($sorttype, 'filename', 'filename');
					$key = trim($sorttype.' '.$sortdirection);
				}
				break;
		}

		$sql .= " ORDER BY ".$key;
		$result = query_full_array($sql);
		return $result;
	}

	/**
	 * Returns an array of albums found in the search
	 * @param string $sorttype what to sort on
	 * @param string $sortdirection what direction
	 *
	 * @return array
	 */
	function getSearchAlbums($sorttype, $sortdirection) {
		if (getOption('search_no_albums')) return array();
		$albums = array();
		$searchstring = $this->getSearchString();
		$albumfolder = getAlbumFolder();
		if (empty($searchstring)) { return $albums; } // nothing to find
		$search_results = $this->searchFieldsAndTags($searchstring, 'albums', $sorttype, $sortdirection);
		if (isset($search_results) && is_array($search_results)) {
			foreach ($search_results as $row) {
				$albumname = $row['folder'];
				if ($albumname != $this->dynalbumname) {
					if (file_exists($albumfolder . internalToFilesystem($albumname))) {
						if (isMyAlbum($albumname, ALL_RIGHTS) || checkAlbumPassword($albumname, $hint) && $row['show']) {
							if (empty($this->album_list) || in_array($albumname, $this->album_list)) {
								$albums[] = $albumname;
							}
						}
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
	 * @param string $sorttype what to sort on
	 * @param string $sortdirection what direction
	 * @param bool $care set to false if the order of the albums does not matter
	 *
	 * @return array
	 */
	function getAlbums($page=0, $sorttype=NULL, $sortdirection=NULL, $care=false) {
		if (is_null($this->albums) || $care && $sorttype.$sortdirection !== $this->lastsubalbumsort) {
			$this->albums = $this->getSearchAlbums($sorttype, $sortdirection);
			$this->lastsubalbumsort = $sorttype.$sortdirection;
		}
		if ($page == 0) {
			return $this->albums;
		} else {
			$albums_per_page = max(1, getOption('albums_per_page'));
			return array_slice($this->albums, $albums_per_page*($page-1), $albums_per_page);
		}
	}

	/**
	 * Returns the index of the album within the search albums
	 *
	 * @param string $curalbum The album sought
	 * @return int
	 */
	function getAlbumIndex($curalbum) {
		$albums = $this->getAlbums(0);
		return array_search($curalbum, $albums);
	}

	/**
	 * Returns the album following the current one
	 *
	 * @param string $curalbum the name of the current album
	 * @return object
	 */
	function getNextAlbum($curalbum) {
		$albums = $this->getAlbums(0);
		$inx = array_search($curalbum, $albums)+1;
		if ($inx >= 0 && $inx < count($albums)) {
			$gallery = new Gallery();
			return new Album($gallery, $albums[$inx]);
		}
		return null;
	}

	/**
	 * Returns the album preceding the current one
	 *
	 * @param string $curalbum the name of the current album
	 * @return object
	 */
	function getPrevAlbum($curalbum) {
		$albums = $this->getAlbums(0);
		$inx = array_search($curalbum, $albums)-1;
		if ($inx >= 0 && $inx < count($albums)) {
			$gallery = new Gallery();
			return new Album($gallery, $albums[$inx]);
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
	function getSearchImages($sorttype, $sortdirection) {
		if (getOption('search_no_images')) return array();
		$hint = '';
		$images = array();
		$searchstring = $this->getSearchString();
		$searchdate = $this->dates;
		if (empty($searchstring) && empty($searchdate)) { return $images; } // nothing to find
		$albumfolder = getAlbumFolder();
		if (empty($searchdate)) {
			$search_results = $this->searchFieldsAndTags($searchstring, 'images', $sorttype, $sortdirection);
		} else {
			$search_results = $this->SearchDate($searchstring, $searchdate, 'images', $sorttype, $sortdirection);
		}
		if (isset($search_results) && is_array($search_results)) {
			foreach ($search_results as $row) {
				$albumid = $row['albumid'];
				$query = "SELECT id, title, folder,`show` FROM ".prefix('albums')." WHERE id = $albumid";
				$row2 = query_single_row($query); // id is unique
				$albumname = $row2['folder'];
				if (file_exists($albumfolder . internalToFilesystem($albumname) . '/' . internalToFilesystem($row['filename']))) {
					if (isMyAlbum($albumname, ALL_RIGHTS) || checkAlbumPassword($albumname, $hint) && $row2['show']) {
						if (empty($this->album_list) || in_array($albumname, $this->album_list)) {
							$images[] = array('filename' => $row['filename'], 'folder' => $albumname);
						}
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
	 * @param string $sorttype what to sort on
	 * @param string $sortdirection what direction
	 * @return array
	 */
	function getImages($page=0, $firstPageCount=0, $sorttype=NULL, $sortdirection=NULL) {
		if (is_null($this->images) || $sorttype.$sortdirection !== $this->lastimagesort) {
			$this->images = $this->getSearchImages($sorttype, $sortdirection);
			$this->lastimagesort = $sorttype.$sortdirection;
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
				$images_per_page = max(1, getOption('images_per_page'));
				$pageStart = $firstPageCount + $images_per_page * $fetchPage;
			}
			$slice = array_slice($this->images, $pageStart, $images_per_page);
			return $slice;
		}
	}

	/**
	 * Returns the index of this image in the search images
	 *
	 * @param string $album The folder name of the image
	 * @param string $filename the filename of the image
	 * @return int
	 */
	function getImageIndex($album, $filename) {
		if (is_null($this->images)) {
			$this->images = $this->getSearchImages(NULL, NULL);
		}
		$images = $this->getImages();
		$c = 0;
		foreach($images as $image) {
			if (($album == $image['folder']) && ($filename == $image['filename'])) {
				return $c;
			}
			$c++;
		}
		return false;
	}
	/**
	 * Returns a specific image
	 *
	 * @param int $index the index of the image desired
	 * @return object
	 */
	function getImage($index) {
		global $_zp_gallery;
		if (!is_null($this->images)) {
			$this->getImages();
		}
		if ($index >= 0 && $index < $this->getNumImages()) {
			$img = $this->images[$index];
			return newImage(new Album($_zp_gallery, $img['folder']), $img['filename']);
		}
		return false;
	}

	/**
	 * Returns a list of Pages IDs found in the search
	 *
	 * @return array
	 */
	function getSearchPages() {
		if (getOption('zp_plugin_zenpage')) {
			if (getOption('search_no_pages')) return array();
			$searchstring = $this->getSearchString();
			$searchdate = $this->dates;
			if (empty($searchstring) && empty($searchdate)) { return array(); } // nothing to find
			if (empty($searchdate)) {
				$search_results = $this->searchFieldsAndTags($searchstring, 'zenpage_pages', false, false);
			} else {
				$search_results = $this->SearchDate($searchstring, $searchdate, 'zenpage_pages', false, false);
			}
			return $search_results;
			}
		return false;
	}

	/**
	 * Returns a list of News IDs found in the search
	 *
	 * @return array
	 */
	function getSearchNews() {
		if (getOption('zp_plugin_zenpage')) {
			if (getOption('search_no_news')) return array();
			$searchstring = $this->getSearchString();
			$searchdate = $this->dates;
			if (empty($searchstring) && empty($searchdate)) { return array(); } // nothing to find
			if (empty($searchdate)) {
				$search_results = $this->searchFieldsAndTags($searchstring, 'zenpage_news', false, false);
			} else {
				$search_results = $this->SearchDate($searchstring, $searchdate, 'zenpage_news', false, false,$this->whichdates);
			}
			return $search_results;
		}
		return false;
	}

} // search class end

?>