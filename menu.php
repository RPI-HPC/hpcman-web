<?php
// side Nav
    if(isset($_SESSION['snuuid']) && isset($_SESSION['vsid']))
	echo print_choose_vsite_action();
    if(isset($_SESSION['snuuid']) && !isset($_SESSION['vsid']))
	echo print_choose_site_action();
    if(!isset($_SESSION['snuuid']) && !isset($_SESSION['vsid'])) 
	echo print_choose_site();

?>
    <b>HPCman</b>
    <a href='?action=logout'>Clear Session</a>
    <a href='?action=manage_sites'>Manage Sites</a>
<?php 
  if(isset($_REQUEST['projid'])) {
    sscanf($_REQUEST['projid'], '%d', $projid);
    echo "    <a href='?action=print_site_projects&amp;projid=$projid'>Return to Project Management</a><br>\n"; 
  }
?>
