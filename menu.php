<?php
// side Nav
    if(isset($_SESSION['snuuid']) && isset($_SESSION['vsid']))
	echo print_choose_vsite_action();
    if(isset($_SESSION['snuuid']) && !isset($_SESSION['vsid']))
	echo print_choose_site_action();
    if(!isset($_SESSION['snuuid']) && !isset($_SESSION['vsid'])) 
	echo print_choose_site();

?>
    <b>Project Tags</b>
    <a href="?action=print_add_edit_tag">Add Tag</a>
    <a href="?action=print_view_edit_tags">View/Edit Tags</a>
    <b>Project Tags in Site</b>
    <div id="tag-menu">
      <?php
        $result = db_get_tags($_SESSION['snuuid']);
        $count = pg_num_rows($result);
        if ($count == 0) {
          echo "(No tags defined.)";
        }

        echo "<a href='?action=print_projects_by_tag'>(untagged)</a>";

        while($tag = pg_fetch_assoc($result)) {
          echo "<a href='?action=print_projects_by_tag&amp;tag={$tag['tag']}' title='{$tag['description']}'>{$tag['tag']} ({$tag['count']})</a>";
        }
      ?>
    </div>
    <b>HPCman</b>
    <a href='?action=logout'>Clear Session</a>
    <a href='?action=manage_sites'>Manage Sites</a>
<?php 
  if(isset($_REQUEST['projid'])) {
    sscanf($_REQUEST['projid'], '%d', $projid);
    echo "    <a href='?action=print_site_projects&amp;projid=$projid'>Return to Project Management</a><br>\n"; 
  }
?>
