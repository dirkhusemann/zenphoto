<?php
// table creation NB: if tables are added or deleted, the zenphoto expected_tables array needs to be modified
$db_schema[] = "CREATE TABLE IF NOT EXISTS ".prefix('zenpage_news')." (
      `id` int(11) unsigned NOT NULL auto_increment,
      `title` text NOT NULL,
      `content` text,
      `extracontent` text,
      `show` int(1) unsigned NOT NULL default '1',
      `date` datetime, 
      `titlelink` varchar(255) NOT NULL default '',
      `commentson` int(11) unsigned NOT NULL,
      `codeblock` text,
      `author` varchar(64) NOT NULL,
      `lastchange` datetime default NULL,
      `lastchangeauthor` varchar(64) NOT NULL,
      `hitcounter` int(11) unsigned default 0,
      `permalink` int(1) unsigned NOT NULL default 0,
      `locked` int(1) unsigned NOT NULL default 0,
      PRIMARY KEY  (`id`),
			UNIQUE (`titlelink`)
      ) $collation;";

$db_schema[] = "CREATE TABLE IF NOT EXISTS ".prefix('zenpage_news_categories')." (
      `id` int(11) unsigned NOT NULL auto_increment,
      `cat_name` text NOT NULL, 
      `cat_link` varchar(255) NOT NULL default '',
      `permalink` int(1) unsigned NOT NULL default 0,
      `hitcounter` int(11) unsigned default 0,
       PRIMARY KEY  (`id`),
			 UNIQUE (`cat_link`)
       ) $collation;";

$db_schema[] = "CREATE TABLE IF NOT EXISTS ".prefix('zenpage_news2cat')." (
      `id` int(11) unsigned NOT NULL auto_increment,
      `cat_id` int(11) unsigned NOT NULL, 
      `news_id` int(11) unsigned NOT NULL,
      PRIMARY KEY  (`id`)
      ) $collation;";

$db_schema[] = "CREATE TABLE IF NOT EXISTS ".prefix('zenpage_pages')." (
      `id` int(11) unsigned NOT NULL auto_increment,
      `parentid` int(11) unsigned default NULL,
      `title` text NOT NULL,
      `content` text,
      `extracontent` text,
      `sort_order`varchar(20) NOT NULL default '',
			`show` int(1) unsigned NOT NULL default '1',
      `titlelink` varchar(255) NOT NULL default '',
      `commentson` int(11) unsigned NOT NULL,
      `codeblock` text,
      `author` varchar(64) NOT NULL,
      `date` datetime default NULL,
      `lastchange` datetime default NULL,
      `lastchangeauthor` varchar(64) NOT NULL,
      `hitcounter` int(11) unsigned default 0,
      `permalink` int(1) unsigned NOT NULL default 0,
      `locked` int(1) unsigned NOT NULL default 0,
      PRIMARY KEY  (`id`),
      UNIQUE (`titlelink`)
      ) $collation;";

// updates
$sql_statements[] = 'ALTER TABLE '.prefix('zenpage_news_categories').' DROP INDEX `cat_link`;';
$sql_statements[] = 'ALTER TABLE '.prefix('zenpage_news_categories').' ADD UNIQUE (`cat_link`);';
$sql_statements[] = 'ALTER TABLE '.prefix('zenpage_news').' DROP INDEX `titlelink`;';
$sql_statements[] = 'ALTER TABLE '.prefix('zenpage_news').' ADD UNIQUE (`titlelink`);';
$sql_statements[] = 'ALTER TABLE '.prefix('zenpage_pages').' DROP INDEX `titlelink`;';
$sql_statements[] = 'ALTER TABLE '.prefix('zenpage_pages').' ADD UNIQUE (`titlelink`);';
      
?>