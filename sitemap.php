<?php
require_once(dirname(__FILE__).'/zp-core/folder-definitions.php');
define('OFFSET_PATH', 0);
require_once(ZENFOLDER . "/template-functions.php");
require_once(ZENFOLDER . "/zp-extensions/sitemap-extended.php");
if(!getOption('zp_plugin_sitemap-extended')) {
	header("HTTP/1.0 404 Not Found");
	header("Status: 404 Not Found");
	include(ZENFOLDER. '/404.php');
	exit();
} 
startSitemapCache();
// Output content type and charset
header('Content-Type: text/xml;charset=utf-8');
// Output XML file headers, and plug the plugin :)
sitemap_echonl('<?xml version="1.0" encoding="UTF-8"?>');
sitemap_echonl('<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">');
printSitemapIndexLinks();
printSitemapAlbumsAndImages();

// Optional Zenpage stuff
if(getOption('zp_plugin_zenpage')) {
	printSitemapZenpagePages();
	printSitemapZenpageNewsIndex();
	printSitemapZenpageNewsArticles();
	printSitemapZenpageNewsCategories();
}
sitemap_echonl('</urlset>');// End off the <urlset> tag
endSitemapCache();