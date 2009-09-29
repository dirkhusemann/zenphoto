<?php
define('OFFSET_PATH', 5);
$const_webpath = dirname(dirname(dirname(dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))))));
$host = "http://".htmlentities($_SERVER["HTTP_HOST"], ENT_QUOTES, 'UTF-8');
require_once("../../../../functions.php"); 
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>TinyZenpage</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<script type="text/javascript" src="../../../../js/jquery.js"></script>
<script type="text/javascript" src="../../../flowplayer/flashembed-0.34.pack.js"></script>
</head>
<body>
<div style="text-align: center; width 450px;">
<?php 
$imagename = sanitize($_GET['image']); 
$albumname = sanitize($_GET['album']); 
// getting the webpath manually since the offset does not work here
$partialpath = strpos(FULLWEBPATH, '/'.ZENFOLDER);
$webpath = substr(FULLWEBPATH,0,$partialpath);
$ext = strtolower(strrchr($imagename, "."));
if (($ext == ".flv") || ($ext == ".mp3") || ($ext == ".mp4") ||  ($ext == ".3gp") ||  ($ext == ".mov")) {
	echo '
			<p id="playerContainer"><a href="http://www.adobe.com/go/getflashplayer">'.gettext('Get Flash').'</a> '.gettext('to see this player.').'</p>
			<script>
			$("#playerContainer").flashembed({
      	src:\'../../../flowplayer/FlowPlayerLight.swf\',
      	width:\'450\', 
      	height:\'338\'
    	},
    		{config: {  
      		autoPlay: \'false\',
    			loop: false,
					controlsOverVideo: \'ease\',
      		videoFile: \''.$host.getAlbumFolder(WEBPATH).$albumname.'/'.$imagename.'\',
      		initialScale: \'fit\',
      		backgroundColor: \'black\',
      		controlBarBackgroundColor: \'black\',
      		controlsAreaBorderColor: \'black\'
    		}} 
  		);
			
  		</script>';
} else {
?>
<img src="<?php echo $host.WEBPATH.'/'.ZENFOLDER; ?>/i.php?a=<?php echo $albumname; ?>&amp;i=<?php echo $imagename; ?>&amp;s=440&amp;t=true" />
<?php } ?>
<div><!-- main div -->
</body>
</html>
