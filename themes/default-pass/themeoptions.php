<?php
class ThemeOptions {

  var $iSupport = array('Allow_comments' => array('type' => 1, 'desc' => 'Set to enable comment section.'));

  function ThemeOptions() {
    /* put any setup code needed here */
    $gallery = new Gallery();
    $gallery->setOptionDefault('Allow_comments', true);
  }
  
  function getOptionsSupported() {return $this->iSupport;}
  function handleOption($option, $currentValue) {}
}
?>
