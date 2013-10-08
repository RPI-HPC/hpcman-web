<?php
  require_once('preload.php');

  if(isset($_REQUEST['hideui'])) {
    require_once('body.php');
  } else {
    require_once('functions/template.php');
    require_once('template.php');
  }
?>
