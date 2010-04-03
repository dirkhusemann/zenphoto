<?php
require_once(dirname(__FILE__).'/zp-core/folder-definitions.php');
define('OFFSET_PATH', 0);
require_once(ZENFOLDER . "/template-functions.php");
//require_once(ZENFOLDER . "/functions-rss.php");
if(!getOption('zp_plugin_zenphoto-sitemap-extended')) {
	header("HTTP/1.0 404 Not Found");
	header("Status: 404 Not Found");
	include(ZENFOLDER. '/404.php');
	exit();
} 
startSitemapCache(true);
// Output content type and charset
header('Content-Type: text/xml;charset=utf-8');
// Output XML file headers, and plug the plugin :)
echonl('<?xml version="1.0" encoding="UTF-8"?>');
echonl('<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">');

// Add the front page of the gallery to the URL list
echonl("\t<url>\n\t\t<loc>".FULLWEBPATH."</loc>\n\t\t<lastmod>".date('Y-m-d')."</lastmod>\n\t\t<changefreq>daily</changefreq>\n\t</url>");
// getting index pages
if(galleryAlbumsPerPage() != 0) {
	$toplevelpages = ceil($_zp_gallery->getNumAlbums() / galleryAlbumsPerPage());
} else {
	$toplevelpages = false;
}
// print further index pages if avaiable
if($toplevelpages) {
	for($x = 2;$x <= $toplevelpages; $x++) {
		$url = FULLWEBPATH.'/'.rewrite_path('page/'.$x,'index.php?page='.$x,false);
		echonl("\t<url>\n\t\t<loc>".$url."</loc>\n\t\t<lastmod>".date('Y-m-d')."</lastmod>\n\t\t<changefreq>daily</changefreq>\n\t</url>");
	}
}
// password check required because subalbums inherit passwords from parents!
$passwordcheck = '';
$albumscheck = query_full_array("SELECT * FROM " . prefix('albums'). " ORDER BY title");
foreach($albumscheck as $albumcheck) {
	if(!checkAlbumPassword($albumcheck['folder'], $hint)) { //TODO If done as a single file this throws an fatal error because the internally used zp_loggedin() is still not defined
		$albumpasswordcheck= " AND id != ".$albumcheck['id'];
		$passwordcheck = $passwordcheck.$albumpasswordcheck;
	}
}
$albumWhere = "WHERE `dynamic`=0 AND `show`=1".$passwordcheck;
// Find public albums
$albums = query_full_array('SELECT `folder`,`date` FROM ' . prefix('albums') . $albumWhere);
foreach($albums as $album) {
	$albumobj = new Album($_zp_gallery,$album['folder']);
	//getting the album pages
	set_context(ZP_ALBUM);
  makeAlbumCurrent($albumobj);
  $pageCount = getTotalPages();
	$date = substr($albumobj->getDatetime(),0,10);
	$url = FULLWEBPATH.'/'.rewrite_path(pathurlencode($albumobj->name),'?album='.pathurlencode($albumobj->name),false);
	echonl("\t<url>\n\t\t<loc>".$url."</loc>\n\t\t<lastmod>".$date."</lastmod>\n\t\t<changefreq>daily</changefreq>\n\t</url>");
	// print album pages if avaiable
	if($pageCount > 1) {
		for($x = 2;$x <= $pageCount; $x++) {
			$url = FULLWEBPATH.'/'.rewrite_path(pathurlencode($albumobj->name).'/page/'.$x,'?album='.pathurlencode($albumobj->name).'&amp;page='.$x,false);
			echonl("\t<url>\n\t\t<loc>".$url."</loc>\n\t\t<lastmod>".$date."</lastmod>\n\t\t<changefreq>daily</changefreq>\n\t</url>");
		}
	}
	$images = $albumobj->getImages();
	foreach($images as $image) {
		$date = substr($album['date'],0,10);
		$path = FULLWEBPATH.'/'.rewrite_path(pathurlencode($album['folder']).'/'.urlencode($image),'?album='.pathurlencode($album['folder']).'&amp;image='.urlencode($image),false);
		echonl("\t<url>\n\t\t<loc>".$path."</loc>\n\t\t<lastmod>".$date."</lastmod>\n\t\t<changefreq>daily</changefreq>\n\t</url>");
	}
}
restore_context();
// Optional Zenpage stuff
if(getOption('zp_plugin_zenpage')) {
	//Zenpage pages
	$pages = getPages(true);
	foreach($pages as $page) {
		$pageobj = new ZenpagePage($page['titlelink']);
		$date = substr($pageobj->getDatetime(),0,10);
		$url = FULLWEBPATH.'/'.rewrite_path(ZENPAGE_PAGES.'/'.urlencode($page['titlelink']),'?p='.ZENPAGE_PAGES.'&amp;title='.urlencode($page['titlelink']),false);
		echonl("\t<url>\n\t\t<loc>".$url."</loc>\n\t\t<lastmod>".$date."</lastmod>\n\t\t<changefreq>weekly</changefreq>\n\t</url>");
	}
	// zenpage news index
	$url = FULLWEBPATH.'/'.rewrite_path(ZENPAGE_NEWS,'?p='.ZENPAGE_NEWS,false);
	echonl("\t<url>\n\t\t<loc>".$url."</loc>\n\t\t<lastmod>".date('Y-m-d')."</lastmod>\n\t\t<changefreq>daily</changefreq>\n\t</url>");
	// getting pages for the main news loop
	$newspages = ceil(getTotalArticles() / getOption("zenpage_articles_per_page"));
	if($newspages > 1) {
		for($x = 2;$x <= $newspages; $x++) {
			$url = FULLWEBPATH.'/'.rewrite_path(ZENPAGE_NEWS.'/page/'.$x,'?p='.ZENPAGE_NEWS.'&amp;page='.$x,false);
			echonl("\t<url>\n\t\t<loc>".$url."</loc>\n\t\t<lastmod>".date('Y-m-d')."</lastmod>\n\t\t<changefreq>daily</changefreq>\n\t</url>");
		}
	}
	//Zenpage news articles
	$articles = getNewsArticles('','','published',true,"date","desc"); //query_full_array("SELECT titlelink, `date` FROM ".prefix('zenpage_news'));// normally getNewsArticles() should be user but has currently a bug in 1.2.9 regarding getting all articles...
	foreach($articles as $article) {
		$articleobj = new ZenpageNews($article['titlelink']);
		$date = substr($articleobj->getDatetime(),0,10);
		$url = FULLWEBPATH.'/'.rewrite_path(ZENPAGE_NEWS.'/'.urlencode($articleobj->getTitlelink()),'?p='.ZENPAGE_NEWS.'&amp;title=' . urlencode($articleobj->getTitlelink()),false);
		echonl("\t<url>\n\t\t<loc>".$url."</loc>\n\t\t<lastmod>".$date."</lastmod>\n\t\t<changefreq>daily</changefreq>\n\t</url>");
	}
	//Zenpage news categories
	$newscats = getAllCategories();
	// Add the correct URLs to the URL list
	foreach($newscats as $newscat) {
		$url = FULLWEBPATH.'/'.rewrite_path(ZENPAGE_NEWS.'/category/'.urlencode($newscat['cat_link']),'?p='.ZENPAGE_NEWS.'&amp;category=' . urlencode($newscat['cat_link']),false);
		echonl("\t<url>\n\t\t<loc>".$url."</loc>\n\t\t<changefreq>weekly</changefreq>\n\t</url>");
	}
}
echonl('</urlset>');// End off the <urlset> tag
endSitemapCache(true);