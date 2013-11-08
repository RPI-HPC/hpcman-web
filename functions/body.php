<?php

/*
 *  view_cputime_csv(int snuuid, string username)
 *  return a string with a text mime-type CSV page
 *
 */
function view_cputime_csv($snuuid, $username = '') {
  header('Content-type: text/csv');
  header('Content-Disposition: attachment; filename="cputime.csv"');
  $ret = "Job Name,User Name,Job Start,Job End,Units,CPU Time,Memory,Host,Record Time Stamp\n";

  if(!isset($_SESSION['startts'])) $_SESSION['startts'] = '2007-01-01';
  if(!isset($_SESSION['endts'])) $_SESSION['endts'] = '2038-01-01';

    if($username != '') {
      // view all records
      $sql = "SELECT * FROM cputime WHERE snuuid=$1 AND username like $2
	AND jobstart >= $3
        AND jobstart <= $4
	ORDER BY jobstart DESC";
    } else {
      $sql = "SELECT * FROM cputime WHERE snuuid=$1
	AND jobstart >= $2
        AND jobstart <= $3";
    }

  $res = ($username == '') ? pg_query_params($sql, array($snuuid, $_SESSION['startts'], $_SESSION['endts']))
       : pg_query_params($sql, array($snuuid, $username, $_SESSION['startts'], $_SESSION['endts']));

  while($row = pg_fetch_assoc($res)) {
      $ret .= "{$row['jobname']},{$row['username']},{$row['jobstart']},{$row['jobend']},{$row['units']},{$row['cputime']},{$row['memory']},{$row['machine']},{$row['timestamp']}
";
  }

  return $ret;
}

/*
 *  print_select_project(int snuuid, string traget_action, [string argument], [bool alloption])
 *  print a project selection form and send the user to given action
 *   with project* variables set
 */
function print_select_project($snuuid, $target, $argument = NULL, $all = true) {
  $ret = "  <form action='.' method='POST'>
   <input type='hidden' name='action' value='$target'>\n";

  if($argument)
   $ret .= "<input type='hidden' name='select_project_argument' value='$argument'>\n";
  $ret .= "
   <b>Select a Project:</b>
   <select name='projid'>
";

  $sql = "SELECT projid, projname FROM projects WHERE snuuid=$1 ";
  $sql .= ($all) ? "" : ' AND projname <> \'users\' ';
  $sql .= " ORDER BY projname";
  $res = pg_query_params($sql, array($snuuid));
  while($row = pg_fetch_assoc($res)) {
    $ret .= "    <option value='{$row['projid']}'>{$row['projname']}</option>\n";
  }

  $ret .= ($all) ? "<option value='-1'>ALL PROJECTS</option>" : "\n" ;
  $ret .= "
   </select>
   <input type='submit' value='Submit'>
  </form>
";

  return $ret;
}

/*
 * print_project_by_tag(int snuuid, [string tag])
 * Print all projects with the given tag.
 * If not tag is given print untagged projects.
 */
function print_projects_by_tag($snuuid, $tag=NULL)
{
  if (!$tag) {
    echo "Untagged projects in site '{$_SESSION['sitename']}':";
    $result = db_get_untagged_projects($snuuid);
  } else {
    echo "Projects tagged '$tag' in site '{$_SESSION['sitename']}':";
    $result = db_get_projects_by_tag($snuuid, $tag);
  }
  $count = pg_num_rows($result);
  if ($count == 0) {
    echo "No projects tagged '$tag' in this site.";
    return;
  }

  echo '<table><tr><th>Project</th><th>Description</th></tr>';
  while($project = pg_fetch_assoc($result)) {
    echo "<tr><td><a href='?action=print_edit_project&amp;projid={$project['projid']}' title='{$project['projshortdesc']}'>{$project['projname']}</a></td><td>{$project['projshortdesc']}</td></tr>";
  }
  echo '</table>';
}

function print_view_edit_tags()
{
  echo "<p><b>Note: Usage counts are across all sites, not the currently selected site.</b></p>";
  echo "<p><b>Note: Removing a tag will remove it from all projects in all sites.</b></p>";
  echo "<form>";
  $result = db_get_tags();
  $count = pg_num_rows($result);
  if ($count == 0) {
    echo "No tags exist.";
  }

  echo '<table><tr><th>Tag</th><th>Description</th><th>Use</th><th>Edit</th><th>Remove</th></tr>';
  while($tag = pg_fetch_assoc($result)) {
    echo "<tr><td>{$tag['tag']}</td><td>{$tag['description']}</td><td>{$tag['count']}</td><td><a href='?action=print_add_edit_tag&amp;tag={$tag['tag']}'>Edit</a></td><td><a href='?action=do_remove_tag&amp;tag={$tag['tag']}'>Remove</a></td></tr>";
  }
  echo '</table>';

  echo "</form>";
}

function print_add_edit_tag($tag)
{
  if ($tag) {
    echo "<p><b>Note: This will action will affect all site and project currently utilizing this tag.</b></p>";
    echo "Edit tag '$tag':";
    $description = db_get_tag_description($tag);
  } else {
    echo "Add tag:";
    $description = '';
  }


  echo "<form method='post' action='?action=do_add_edit_tag'>";
  if ($tag) {
    echo "<input type='hidden' name='original_tag' value='$tag'/>";
  }
  echo "<table><tr><td>Tag</td><td><input name='tag' type='text' value='$tag' /></td></tr><tr><td>Description</td><td><textarea name='description'>$description</textarea></td></tr></table><input type='submit' value='Add/Edit Tag' /></form>";
}

?>
