CLEARLY THIS FILE WILL DO NOTHING FOR YOU. IT'S A DUMP FOR ALL THE SCHEMA STUFF WE NEED TO UPDATE EVENTUALLY.

These are the sql statements we'll need to issue:

ALTER TABLE zp_images ADD COLUMN sort_order INTEGER AFTER title;
alter table zp_albums add column sort_type varchar(20) after place;


Also, don't forget to update the DDL in setup.php too.