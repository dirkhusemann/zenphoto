<?php
/*
   Copyright (c) 2005 Steven Armstrong <sa at c-area dot ch>
   
   Drop in replacement for native gettext.
   
   This file is part of PHP-gettext.

   PHP-gettext is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.

   PHP-gettext is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with PHP-gettext; if not, write to the Free Software
   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/
/*
LC_CTYPE		0
LC_NUMERIC	1
LC_TIME			2
LC_COLLATE	3
LC_MONETARY	4
LC_MESSAGES	5
LC_ALL			6
*/

//require('streams.php');
//require('gettext.php');


// Variables

/* global $text_domains, $default_domain, $LC_CATEGORIES, $EMULATEGETTEXT, $CURRENTLOCALE;
$text_domains = array();
$default_domain = 'messages';
$LC_CATEGORIES = array('LC_CTYPE', 'LC_NUMERIC', 'LC_TIME', 'LC_COLLATE', 'LC_MONETARY', 'LC_MESSAGES', 'LC_ALL');
$EMULATEGETTEXT = 0;
$CURRENTLOCALE = '';*/


// Utility functions

/**
 * Utility function to get a StreamReader for the given text domain.
 */
function _get_reader($domain=null, $category=5, $enable_cache=true) {
	
}

/**
 * Returns whether we are using our emulated gettext API or PHP built-in one.
 */
function locale_emulation() {
    
}

/**
 * Checks if the current locale is supported on this system.
 */
function _check_locale() {
   
}

/**
 * Get the codeset for the given domain.
 */
function _get_codeset($domain=null) {
	
}

/**
 * Convert the given string to the encoding set by bind_textdomain_codeset.
 */
function _encode($text) {
	
}




// Custom implementation of the standard gettext related functions

/**
 * Sets a requested locale, if needed emulates it.
 */
function _setlocale($category, $locale) {
    
}

/**
 * Sets the path for a domain.
 */
function _bindtextdomain($domain, $path) {

}

/**
 * Specify the character encoding in which the messages from the DOMAIN message catalog will be returned.
 */
function _bind_textdomain_codeset($domain, $codeset) {

}

/**
 * Sets the default domain.
 */
function _textdomain($domain) {

}

/**
 * Lookup a message in the current domain.
 */
function _gettext($msgid) {

}
/**
 * Alias for gettext.
 */
function __($msgid) {
	
}
/**
 * Plural version of gettext.
 */
function _ngettext($single, $plural, $number) {

}

/**
 * Override the current domain.
 */
function _dgettext($domain, $msgid) {
	
}
/**
 * Plural version of dgettext.
 */
function _dngettext($domain, $single, $plural, $number) {

}

/**
 * Overrides the domain and category for a single lookup.
 */
function _dcgettext($domain, $msgid, $category) {

}
/**
 * Plural version of dcgettext.
 */
function _dcngettext($domain, $single, $plural, $number, $category) {

}



// Wrappers to use if the standard gettext functions are available, but the current locale is not supported by the system.
// Use the standard impl if the current locale is supported, use the custom impl otherwise.

function T_setlocale($category, $locale) {
 
}

function T_bindtextdomain($domain, $path) {

}
function T_bind_textdomain_codeset($domain, $codeset) {
  
}
function T_textdomain($domain) {

}
function T_gettext($msgid) {

}
function T_($msgid) {

}
function T_ngettext($single, $plural, $number) {
	
}
function T_dgettext($domain, $msgid) {

}
function T_dngettext($domain, $single, $plural, $number) {
	
}
function T_dcgettext($domain, $msgid, $category) {

}
function T_dcngettext($domain, $single, $plural, $number, $category) {
	
}



// Wrappers used as a drop in replacement for the standard gettext functions

if (!function_exists('gettext')) {
	function bindtextdomain($domain, $path) {
		//return _bindtextdomain($domain, $path);
	}
	function bind_textdomain_codeset($domain, $codeset) {
		//return _bind_textdomain_codeset($domain, $codeset);
	}
	function textdomain($domain) {
		//return _textdomain($domain);
	}
	function gettext($msgid) {
		return $msgid;
	}
	function _($msgid) {
		//return __($msgid);
	}
	function ngettext($single, $plural, $number) {
		//return _ngettext($single, $plural, $number);
	}
	function dgettext($domain, $msgid) {
		//return _dgettext($domain, $msgid);
	}
	function dngettext($domain, $single, $plural, $number) {
		//return _dngettext($domain, $single, $plural, $number);
	}
	function dcgettext($domain, $msgid, $category) {
		//return _dcgettext($domain, $msgid, $category);
	}
	function dcngettext($domain, $single, $plural, $number, $category) {
		//return _dcngettext($domain, $single, $plural, $number, $category);
	}
}

?>