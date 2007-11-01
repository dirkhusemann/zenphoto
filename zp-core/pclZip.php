<?php
// Unzip function; ignores ZIP directory structure.
// Requires zziplib

function unzip($file, $dir) {
   $zip = zip_open($file);
   if ($zip) {
     while ($zip_entry = zip_read($zip)) {
       // Skip non-images in the zip file.
       if (!is_image(zip_entry_name($zip_entry))) continue;
       
       if (zip_entry_open($zip, $zip_entry, "r")) {
         $buf = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
         $path_file = str_replace("/",DIRECTORY_SEPARATOR, $dir . '/' . zip_entry_name($zip_entry));
         $fp = fopen($path_file, "w");
         fwrite($fp, $buf);
         fclose($fp);
         zip_entry_close($zip_entry);
       }
     }
     zip_close($zip);
   }
}

// zip_open fix starts here
function ShellFix($s) {
  return "'".str_replace("'", "'''", $s)."'";
}
function zip_open($s) {
  $fp = @fopen($s, 'rb');
  if(!$fp) return false;
  $lines = Array();
  $cmd = 'unzip -v '.shellfix($s);
  exec($cmd, $lines);
  $contents = Array();
  $ok=false;
  foreach($lines as $line) {
    if($line[0]=='-') { $ok=!$ok; continue; }
    if(!$ok) continue;
    $length = (int)$line;
    $fn = trim(substr($line,58));
    $contents[] = Array('name' => $fn, 'length' => $length);
  }
  return Array('fp' => $fp, 'name' => $s, 'contents' => $contents, 'pointer' => -1);
}
function zip_read(&$fp) { 
  if(!$fp) return false;
  $next = $fp['pointer'] + 1;
  if($next >= count($fp['contents'])) return false;
  $fp['pointer'] = $next;
  return $fp['contents'][$next];
}
function zip_entry_name(&$res) {
  if(!$res) return false;
  return $res['name'];
}
function zip_entry_filesize(&$res) {
  if(!$res) return false;
  return $res['length'];
}
function zip_entry_open(&$fp, &$res) {
  if(!$res) return false;
  $cmd = 'unzip -p '.shellfix($fp['name']).' '.shellfix($res['name']);
  $res['fp'] = popen($cmd, 'r');
  return !!$res['fp'];
}
function zip_entry_read(&$res, $nbytes) {
  while ($s = fgets($res['fp'],1024)) {
    $data .= $s;
  }
  return $data;
}
function zip_entry_close(&$res) {
  fclose($res['fp']);
  unset($res['fp']);
}
function zip_close(&$fp) {
  fclose($fp['fp']);
}
?>
