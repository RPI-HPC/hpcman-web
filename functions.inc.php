<?php
//
//  All print_ functions return a string with their output, 
//	preferably in a <div>
//

include_once('functions/common.php');
include_once('functions/format.php');
include_once('functions/database.php');

/*
 * print_choose_project(int snuuid)
 * Return string with project chooser form for given site
 */
function print_choose_project($snuuid) {
  if(!is_numeric($snuuid)) return 'Invalid SNUUID';

  $ret = '<div class=\'\'>Select a project site to work with:';
  $sql = "SELECT projname, projid FROM projects WHERE snuuid=$1";

  $res = pg_query_params($sql, array($snuuid));
  if($res) {
    $ret .= '<form action=\'.\' >
	<input type=\'hidden\' name=\'action\' value=\'do_choose_project\'>
	<select name=\'choose_project\'>
	';
    while($row = pg_fetch_assoc($res)) {
      $ret .= '<option value=\''.$row['projid'].'\'>';
      $ret .= $row['projname'].'</option>';
    }
    $ret .= "</select>\n";
    $ret .= "<input type='submit' value='Choose Project'></form>\n";
  } else {
    $ret .= 'Error loading projects.';
  }

  return $ret.'</div>';
}

/*
 * print_choose_vsite(int snuuid)
 * Return string with vsite chooser form for given site
 */
function print_choose_vsite($snuuid) {
  if(invalid_id($snuuid))
    return 'Invalid SNUUID';

  $ret = '<div class=\'\'>Select a virtual site to work with:';
  $sql = "SELECT vsname, vsid FROM virtual_sites WHERE snuuid=$1";

  $res = pg_query_params($sql, array($snuuid));
  if($res) {
    $ret .= '<form action=\'.\' >
	<input type=\'hidden\' name=\'action\' value=\'do_choose_vsite\'>
	<select name=\'choose_vsite\'>
	';
    while($row = pg_fetch_assoc($res)) {
      $ret .= '<option value=\''.$row['vsid'].'\'>';
      $ret .= $row['vsname'].'</option>';
    }
    $ret .= "</select>\n";
    $ret .= "<input type='submit' value='Choose VSite'></form>\n";
  } else {
    $ret .= 'Error loading vsites.';
  }

  return $ret.'</div>';
}

/*
 * print_choose_site()
 * returns string with form to choose from available sites for management
 */
function print_choose_site() {
  $ret = '<div class=\'\'>Select a site to work with:';
  $sql = 'SELECT sitename, snuuid FROM sites';

  $res = pg_query($sql);
  if($res) {
    $ret .= '<form action=\'.\' >
	<input type=\'hidden\' name=\'action\' value=\'do_choose_site\'>
	<select name=\'snuuid\'>
	';
    while($row = pg_fetch_assoc($res)) {
      $ret .= '<option value=\''.$row['snuuid'].'\'>';
      $ret .= $row['sitename'].'</option>';
    }
    $ret .= "</select>\n";
    $ret .= "<input type='submit' value='Choose Site'></form>\n";
  } else {
    $ret .= 'Error loading sites.';
  }

  return $ret.'</div>';
}

/*
 * print_choose_site_action()
 * Display a menu for working with a site
 */
function print_choose_site_action() {
  $ret = "
  <div id='search-box'>
    <span id='search-box-title'>Search</span>
    <form id='search' action='.' method='post'>
      <input type='hidden' name='action' value='print_view_user_accounts'>
      <input type='hidden' name='type' value=''>
      <span class='search-title'>Principal</span><input class='searchkey_principal' type='text' name='searchkey_principal'>
      <span class='search-title'>Username</span><input class='searchkey_account' type='text' name='searchkey_account'>
    </form>
    <form id='search2' action='.' method='post'>
      <input type='hidden' name='action' value='print_edit_project'>
      <span class='search-title'>Project</span><input class='searchkey_project' type='text' name='projname'>
    </form>
  </div>

  <b>People/VOs</b>
  <a href='?action=add_person'>Add a Person/VO</a>
  <a href='?action=view_people'>View/Edit People and VOs</a>

  <b>Projects</b>
  <a href='?action=print_edit_project'>Add a Project</a>
  <a href='?action=print_select_project&amp;target=print_site_projects'>View/Edit Projects</a>

  <b>Groups (POSIX)</b>
  <a href='?action=print_add_group'>Add a Group</a>
  <a href='?action=print_view_groups'>View/Edit Groups</a>

  <b>User Accounts (all projects)</b>
  <a href='?action=print_view_user_accounts'>View User Accounts</a>

  <b>Usage Data</b>
  <a href='?action=print_select_project&amp;target=view_cputime'>Compute Time</a>

  <b>File Systems</b>
  <a href='?action=print_manage_fs'>Manage File Systems</a>

  <b>Virtual Sites</b>
  <a href='?action=print_add_edit_vsite'>Add a Virtual Site</a>
  <a href='?action=print_view_vsites'>View/Edit Virtual Sites</a>
";

  return $ret;
}

/*
 * print_view_vsites(int snuuid)
 * Return a string with a <div> of all of the vsites in the given site
 */
function print_view_vsites($snuuid) {
  $sql = "SELECT * FROM virtual_sites WHERE snuuid=$1";
  $res = pg_query_params($sql, array($snuuid));

  $ret = "  <div class=''><table>
   <tr>
    <th>Name</th>
    <th>Description</th>
    <th>Created</th>
    <th>Last Modified</th>
    <th>Edit</th>
    <th>Work with VSite</th>
   </tr>
";
 
  while($row = pg_fetch_assoc($res)) {
    $ret .= "   <tr>
    <td>".$row['vsname']."</td>
    <td>".$row['description']."</td>
    <td>".$row['created']."</td>
    <td>".$row['modified']."</td>
    <td><a href='?action=print_add_edit_vsite&amp;vsid=".$row['vsid']."'>Edit</a></td>
    <td><a href='?action=do_choose_vsite&amp;choose_vsite=".$row['vsid']."'>Work with VSite</a></td>
";
  }

  return $ret."  </table></div>\n";
}

/*
 * print_view_vs_users(int snuuid, int vsid)
 * return a string showing all of the users associated with a given vsite
 */
function print_view_vs_users($snuuid, $vsid) {
  $sql = "SELECT 
	virtual_site_members.username 
	FROM 
	virtual_site_members 
	WHERE 
	 virtual_site_members.snuuid=$1
	AND virtual_site_members.vsid=$2";
  $res = pg_query_params($sql, array($snuuid, $vsid));
 
  $ret = "  <div class=''><table>
   <tr>
    <th>User Account</th>
    <th>Details</th>
   </tr>
";
  while($row = pg_fetch_assoc($res)) {
    $ret .= "    <tr><td>".$row['username']." (".
	db_get_gecos_from_username($row['username'], $snuuid)
	.")</td><td><a href='?action=print_edit_user_account&amp;username="
	.$row['username']."&amp;snuuid=$snuuid'>Details</a></td></tr>\n";
  }

  $ret .= "   </table><div>\n";

  return $ret;
}

function print_choose_vsite_action() {
  $ret = print_choose_site_action();

  $ret .= "<b>This Virtual Site</b>";
  $ret .= "<a href='?action=view_vs_users'>View/Edit Virtual Site Users</a>\n";
  $ret .= "<a href='?action=view_vs_groups'>View/Edit Virtual Site Groups</a>\n";

  return $ret;
}

function print_edit_user_account($snuuid, $username="", $puuid=-1) {

  if($username === "" && $puuid !== -1) {
    sscanf($_REQUEST['projid'], '%d', $projid);
    $sql = "SELECT defaultusername, projusername, projname
	FROM principals, projects 
	WHERE puuid=$1 AND projid=$2 AND snuuid=$3";
    $res = pg_query_params($sql, array($puuid, $projid, $snuuid));
    $defaultusername = pg_fetch_result($res, 'projusername');
    $defaultusername .= pg_fetch_result($res, 'defaultusername');

    $ret = "   <div class=''><table><form action='?action=do_add_user_account' method='post'>\n";
    $ret .= "    <input type='hidden' name='puuid' value='$puuid'>\n";
    $ret .= "    <tr><th colspan='2'>Adding user for ".db_get_name_from_puuid($puuid)."</th></tr>\n";
    $ret .= "    <tr><td>User Name:</td><td><input type='text' name='username' value='$defaultusername'> (max 8 characters)</td></tr>\n";
  }

  $existing = false;

  if($snuuid !== "" && $username !== "") {
    $existing = true;
    $sql = "SELECT 
		username,
		puuid,
		projid,
		uid,
		groupname,
		homedirectory,
		quota,
		shell,
		useraccountstate
	FROM user_accounts
	WHERE
		snuuid=$1
	AND
		username=$2
   ";

    $ret = "   <div class=''><table><form action='?action=do_edit_user_account' method='post'>\n";

    $res = pg_query_params($sql, array($snuuid, $username));

    if(!$res) return "Error in DB query!";
    $row = pg_fetch_assoc($res);

    // editing existing user, so set project id
    $projid = $row['projid'];

    $ret .= "   <input type='hidden' name='snuuid' value='$snuuid'>\n";
    $ret .= "   <input type='hidden' name='username' value='$username'>\n";
  } else {
    $sql = "SELECT defaultshell AS shell FROM sites WHERE snuuid=$1";
    $res = pg_query_params($sql, array($snuuid));
    if(!$res) return "Error in DB query!";
    $row = pg_fetch_assoc($res);
  }

  $ret .= "    <tr><th colspan='2'>Editing $username</th></tr>\n";

  $ret .= "   <tr>
    <td>Project:</td>
    <td>
     <select name='projid'";

  if($existing) $ret .= " disabled";

  $ret .= ">\n";

  $sql = "SELECT projid, projname, groupname FROM projects WHERE snuuid=$1";
  $r = pg_query_params($sql, array($snuuid));
  while($p = pg_fetch_assoc($r)) {
    $ret .= "      <option value='{$p['projid']}'";
    if($p['projid'] == $projid) {
	$ret .= " selected ";
	$projgroupname = $p['groupname'];
	$projname = $p['projname'];
    }
    $ret .= ">{$p['projname']}</option>\n";
  }
  $ret .= "
     </select>
    </td>
   </tr>
";

  $ret .= "    <tr><td>Primary group:</td><td>\n";
  $ret .= "    <select name='groupname'";
  if($existing) $ret .= " disabled ";
  $ret .= ">\n";
  $sql = "SELECT groupname FROM groups WHERE snuuid=$1 AND groupstate='A'";
  $re = pg_query_params($sql, array($snuuid));
  while($r = pg_fetch_assoc($re)) {
    $ret .= "	<option value='".$r['groupname']."'";
    if($projgroupname == $r['groupname']) $ret .= " selected ";
    $ret .= ">{$r['groupname']}</option>\n";
  }
  $ret .= "    </select></td></tr>\n";

  $ret .= "    <tr>
     <td>Home Directory:</td>
     <td>\n";
  /*
      <select name='fsname'>\n";

  $sql = "SELECT fsname, fstype, mountpoint FROM filesystems
	WHERE snuuid = $1";
  $res = pg_query_params($sql, array($snuuid));

  while($fs = pg_fetch_assoc($res)) {
    $ret .= "       <option value='{$fs['fsname']}'>{$fs['fsname']} ({$fs['mountpoint']})</option>\n";
  }

      </select>{$projname}/home/{$username}
  */
  global $_default_home_directory_path;
  $hdp = preg_replace('/PROJECT/', $projname, $_default_home_directory_path);
  $hdp = preg_replace('/USER/', $defaultusername, $hdp);
  $homedir = (isset($row['homedirectory']) && strlen($row['homedirectory']) > 0) ? $row['homedirectory'] : $hdp;

  $ret .= "
      <input type='text' name='homedirectory' value='$homedir'> (a file system path)</td>
    </tr>\n";
  
  global $_default_user_quota;
  $quot = (isset($row['quota']) && strlen($row['quota']) > 0) ? $row['quota'] : $_default_user_quota;
  $ret .= "    <tr><td>Quota:</td><td><input type='text' name='quota' value='$quot'> (GB, a positive integer or zero)</td></tr>\n";

  $ret .= "    <tr><td>Shell:</td><td><input type='text' name='shell' value='".$row['shell']."'> (fully qualified path to a valid shell)</td></tr>\n";

  $ret .= "    <tr><td>State:</td><td>
      <select name='useraccountstate'>\n";
  $s = "SELECT * FROM user_accounts_states";
  $stres = pg_query($s);
  while($state = pg_fetch_assoc($stres)) {
    $ret .= "       <option value='{$state['useraccountstate']}' ";
    if(isset($row['useraccountstate']) && $row['useraccountstate'] == $state['useraccountstate'])
      $ret .= "selected='SELECTED'";
    $ret .= " >({$state['useraccountstate']}) {$state['useraccountstatedesc']}</option>\n";
  }
  $ret .= "
      </select>
     </td></tr>\n";

  $ret .= "    <tr><td valign='top'>Virtual Site Membership</td>
    <td>
     <select name='vsids[]' size='5' multiple>\n";

  $sql = "SELECT vsid, vsname FROM virtual_sites WHERE snuuid=$1";
  $rr = pg_query_params($sql, array($snuuid));

  while($vs = pg_fetch_assoc($rr)) {
    $ret .= "	<option value='".$vs['vsid']."'";
    if(db_user_is_member_of_vsite($username, $vs['vsid'], $snuuid))
      $ret .= " SELECTED='selected' ";
    if($vs['vsid'] == 0) $ret .= ' DISABLED ';
    $ret .= ">".$vs['vsname']."</option>\n";
  }

  $ret .= "     </select>
    </td></tr>\n";

  $ret .= "    <tr><td><input type='submit' value='Update or Create User'></td></tr>\n";

  return $ret."   </form></table></div>\n";
}

function print_change_password($puuid) {
  if($puuid == '' || $puuid == -1)
    return "<h3>No PUUID given</h3>\n";

  $name = db_get_name_from_puuid($puuid);

  $ret = "   <div class=''><table><form action='?action=do_change_password' method='post'>
    <tr><th colspan='2'>Changing Password for $name</th></tr>
    <tr><td colspan='2'><i>Note that user must change password before they may log in.</i></td></tr>
    <input type='hidden' name='puuid' value='$puuid'>\n";
  $ret .= "    <tr><td>New Password:</td>
    <td><input type='password' name='p1'></td></tr>
    <tr><td>Confirm:</td><td><input type='password' name='p2'></td></tr>
    <tr><td colspan='2'><input type='submit' value='Change Password'></td></tr>\n";
  return $ret."   </form></table></div>";
}

function print_view_user_accounts($snuuid, $puuid) {
 $ret = "  <div class=''>\n";
 if(!isset($puuid) || $puuid == "")
  $ret .= "  <center>
   <form id='view_user_accounts' action='' method='post'>
   <input type='hidden' name='action' value='print_view_user_accounts'>
   <input type='hidden' name='type' value=''>
   Search user accounts by owner's name: <input class='searchkey_principal' type='text' name='searchkey_principal'><br/>
   Search user accounts by account name: <input class='searchkey_account' type='text' name='searchkey_account'>
   </form>
";
 if(isset($_REQUEST['searchkey_principal']) || isset($_REQUEST['searchkey_account']) || $puuid !== "") {
  $sql = "SELECT
		username,
		name,
		user_accounts.puuid,
		useraccountstatedesc,
		user_accounts.created,
		user_accounts.modified,
		projname,
		projects.projid,
		user_accounts.projid,
		mustchange
	FROM
		user_accounts,
		principals,
		user_accounts_states,
		projects,
		authenticators
	WHERE
		user_accounts.snuuid=$1
	AND
		principals.puuid=user_accounts.puuid
	AND
		user_accounts_states.useraccountstate=user_accounts.useraccountstate
	AND
		user_accounts.projid = projects.projid
	AND
		projects.snuuid = $1
	AND
		authenticators.puuid = user_accounts.puuid
";

  if($puuid != "") {
    hpcman_log("Searching for accounts in site $snuuid linked to puuid $puuid");
    $sql .= "AND user_accounts.puuid=$2";
    $res = pg_query_params($sql, array($snuuid, $puuid));
  } else if($_REQUEST['type'] == 'principal' || $_REQUEST['searchkey_principal'] != "") {
    hpcman_log("Searching for accounts in site $snuuid with principal name like ${_REQUEST['searchkey_principal']}");
    $sql .= "AND lower(principals.name) LIKE $2";
    $res = pg_query_params($sql, array($snuuid, '%'.(strtolower($_REQUEST['searchkey_principal'])).'%'));
  } else if($_REQUEST['type'] != 'account' || $_REQUEST['searchkey_account'] != "") {
    hpcman_log("Searching for accounts in site $snuuid with account name like ${_REQUEST['searchkey_account']}");
    $sql .= "AND lower(user_accounts.username) LIKE $2";
    $res = pg_query_params($sql, array($snuuid, '%'.(strtolower($_REQUEST['searchkey_account'])).'%'));
  } else {
    hpcman_log("Searching for all accounts in site $snuuid");
    $res = pg_query_params($sql, array($snuuid));
  }

  if(pg_num_rows($res) > 0) {

  $ret .= "  
  <table class=\"sortable\" border='1' >
   <thead>
   <tr>
	<th>User Name</th>
	<th>Principal</th>
	<th>Project</th>
	<th>State</th>
	<th>Edit</th>
	<th>Reset Password</th>
	<th>Created</th>
	<th>Modified</th>
   </tr>
   </thead>\n";

  while($row = pg_fetch_assoc($res)) {
    $ret .= "    <tr><td><a href='?action=print_edit_user_account&amp;username={$row['username']}'>{$row['username']}</a></td>
    <td><a href='?action=edit_principal&amp;puuid=".$row['puuid']."'>".$row['name']."</a></td>\n";
    $ret .= "    <td><a href='?action=print_edit_project&amp;projid={$row['projid']}'>{$row['projname']}</a></td>\n";
    $ret .= "    <td>".$row['useraccountstatedesc']."</td>\n";
    $ret .= "    <td><a href='?action=print_edit_user_account&amp;username=".$row['username']."&amp;snuuid=$snuuid'>Edit</a></td>\n";
    $ret .= "    <td><a href='?action=print_change_password&amp;puuid={$row['puuid']}'>Reset</a>&nbsp;";
    $ret .= ($row['mustchange'] == 't') ? "&nbsp;<font color='red'>*</font>" : "";
    $ret .= "</td>\n";
    $ret .= "    <td>".$row['created']."</td>\n";
    $ret .= "    <td>".$row['modified']."</td></tr>\n";
  }
 
  $ret .= "
   <tfoot>
   <tr>
    <td colspan='8'><center><font color='red'>*</font> = User must change password before logging in.
      <br >
      Click a column header to sort it.
     </center>
    </td>
   </tr>
   </tfoot>\n";
 
  $ret .= "</table></div>\n";
 } else {// end check on numrows
  $ret .= "<h3>No records found.</h3>\n";
 }
 } // end if() on search or puuid
/*
  if($puuid !== "") {
    $ret .= "    <center><a href='?action=print_edit_user_account&amp;puuid=$puuid&amp;snuuid=$snuuid'>Add User Account</a></center>";
  }
*/
  return $ret;
}

function print_addedit_principal($puuid=-1) {
  global $_default_account_expiration;

  $ret = "<div class=''>\n   <form action='./?action=do_create_edit_principal' method='POST'>
    <table class=''>\n";

  $name = "";
  $defaultusername = "";
  $emailaddress = "";
  $contactinfo = "";

  if(is_numeric($puuid) && $puuid > 0) {
    $sql = "SELECT 
	puuid, 
	principalstate, 
	isvo, 
	expires, 
	name, 
	contactinfo,
	emailAddress,
	defaultusername, 
	iuuid 
    FROM principals 
    WHERE puuid=$1";

    $res = pg_query_params($sql, array($puuid));
    if(!$res) return "Error in DB query!";
    $pr = pg_fetch_assoc($res);
    $ret .= "    <input type='hidden' name='puuid' value='$puuid'>\n";
    if($pr['isvo'] == 't') $isvo = true;

    $name = $pr['name'];
    $defaultusername = $pr['defaultusername'];
    $emailaddress = $pr['emailaddress'];
    $contactinfo = $pr['contactinfo'];
  }

  $expires = (isset($pr)) ? $pr['expires'] : $_default_account_expiration;

  $ret .= "    <tr><td>Name:</td><td><input type='text' name='name' value='";
  $ret .= $name."'>&nbsp;(free form)</td></tr>\n";
 
  $ret .= "    <tr><td>Expires:</td><td><input type='text' name='expires' value='$expires'>&nbsp;(YYYYMMDD HH:MM:SS)</td></tr>\n";

  $ret .= "    <tr><td>Default user portion of user name:</td><td><input id='defaultusername' type='text' name='defaultusername' maxlength='4' value='".$defaultusername."'>&nbsp;(4 characters, no vowels)</td></tr>\n";

  $ret .= "<tr><td>Contact Email:</td><td><input id='emailaddress' type='text' name='emailAddress' value='".$emailaddress."'>&nbsp;(all automated correspondence will go here)</td></tr>\n";

  $ret .= "    <tr><td>Contact information:</td><td><textarea rows=10 cols=30 name='contactinfo'>".$contactinfo."</textarea></td></tr>\n";

  $ret .= "   <tr><td>State:</td>
    <td>
     <select name='principalstate'>\n";
  $s = "SELECT principalstate, principalstatename FROM principalstates";
  $r = pg_query($s);
  while($st = pg_fetch_assoc($r)) {
    $ret .= "     <option value='{$st['principalstate']}'";
    if(isset($pr['principalstate']) && $pr['principalstate'] == $st['principalstate'] &&
	$puuid != -1) 
      $ret .= " selected ";
    if($puuid == -1)
      if($st['principalstate'] != 'C')
        $ret .= "disabled ";
    $ret .= ">{$st['principalstatename']}</option>\n";
  }
  $ret .= "
     </select>
    </td></tr>\n";

  $ret .= "<tr><td>Is Virtual Organization?</td><td><input type='checkbox' name='isvo' ";
  $ret .= (isset($isvo)) ? "CHECKED='checked'" : '';
  $ret .= "></td></tr>\n";

  $ret .= "    <tr><td><input type='submit' value='Create or Edit Principal'></td></tr>\n";

  return $ret."  </table></form></div>";
}

function print_site_people() {
  $ret = "<div class=''><center>\n";

  $ret .= "<a href='./?action=view_people&amp;all'>Show all principals</a> 
	&nbsp;||&nbsp;<a href='./?action=view_people&amp;active'>Show all active</a>
	&nbsp;||&nbsp;<a href='./?action=view_people&amp;unapproved'>Show all awaiting approval</a>
  <br>
";
  $ret .= "
  <form action='./' method='get'>
   <input type='hidden' name='action' value='view_people'>
   Display only people whose names contain: <input name='search'><br>
   <input type='submit' value='Search'>
  </form></center>
";

  $ret .= "   <table border='1' class=\"sortable\">
    <thead>
    <tr>
	<th>PUUID</th>
	<th>Name</th>
	<th>State</th>
	<th>Edit</th>
	<th>User Accounts</th>
	<th>Created</th>
	<th>Modified</th>
    </tr>
    </thead>
";

  $sql = "SELECT 
		name,
		principals.principalstate,
		principalstatename, 
		puuid,
		created,
		modified
	FROM 
		principals, principalstates 
	WHERE isvo='F' 
	AND principalstates.principalstate=principals.principalstate";

  if(isset($_REQUEST['unapproved']))
    $sql .= " AND principals.principalstate='C'";

  if(isset($_REQUEST['active']))
    $sql .= " AND principals.principalstate='A'";

  if(isset($_REQUEST['search'])) {
    $sql .= " AND lower(principals.name) LIKE $1";
    $sql .= " ORDER BY name ASC";
    $res = pg_query_params($sql, array('%'.(strtolower($_REQUEST['search'])).'%'));
    if(!$res) return 'DB Query Error!';
  } else if(isset($_REQUEST['all']) || isset($_REQUEST['active']) 
	|| isset($_REQUEST['unapproved'])) { 
    $sql .= " ORDER BY name ASC";
    $res = pg_query($sql);
    if(!$res) return 'DB Query Error!';
  } else {
    $res = pg_query('SELECT puuid FROM principals WHERE puuid=-1');
  }

  while($row = pg_fetch_assoc($res)) {
    $ret .= "    <tr><td>".$row['puuid']."
    </td><td>".$row['name']."
    </td><td>".$row['principalstatename']."
    </td><td><a href='./?action=edit_principal&amp;puuid=".$row['puuid']."'>Edit</a>\n";
    if(strncmp($row['principalstate'], "A", 1) === 0)
	$ret .= "
    </td><td><a href='./?action=print_view_user_accounts&amp;puuid=".$row['puuid']."'>View/Modify</a>\n";
    else       $ret .= "   </td><td><i>Not Applicable</i>\n";
    $ret .= "
    </td><td>".$row['created']."
    </td><td>".$row['modified']."
    </td></tr>\n";
  }

  $ret .= "
   <tfoot>
   <tr>
    <td colspan='7'><center><a href='?action=add_person'>Add a Person</a>
     <br >
     Click a column header to sort it.
    </td>
   </tr>
   </tfoot>
  </table>
";

  return $ret."</div>\n";
}

function print_view_groups($snuuid) {
  $sql = "SELECT groupname, gid, groupstatedesc, created, modified
	FROM groups, group_states
	WHERE groups.snuuid=$1 AND group_states.groupstate=groups.groupstate";
  $res = pg_query_params($sql, array($snuuid));

  $ret = "   <div class=''>
   <table border='1' class=\"sortable\">
    <thead>
    <tr>
     <th>Name</th>
     <th>GID</th>
     <th>State</th>
     <th>Membership</th>
     <th>Created</th>
     <th>Modified</th>
    </tr>
    </thead>
";

  while($row = pg_fetch_assoc($res)) {
    $ret .= "    <tr>
     <td>".$row['groupname']."</td>
     <td>".$row['gid']."</td>
     <td>".$row['groupstatedesc']."</td>
     <td><a href='?action=print_edit_group_membership&amp;groupname=".$row['groupname']."'>Membership</a></td>
     <td>".$row['created']."</td>
     <td>".$row['modified']."</td>
    </tr>
";
  }

  $ret .= "   </table></div>\n";

  return $ret;
}

function print_add_group($snuuid, $groupname, $sitename) {
  $ret = "<h3>Add Group to $sitename</h3>
          <div class=''><table><form action='?action=add_group' method='post'>
          <tr><td>Group name:</td>
          <td><input type='text' name='groupname'></td></tr>
          <tr><td colspan='2'><input type='submit' value='Create Group'></td>
          </tr>\n";
  $ret .= "</form></table>\n";

  return $ret;
}

function print_edit_group_membership($snuuid, $groupname) {
  if (!isset($groupname) || $groupname == '')
    return "<h3>No group selected!</h3>\n";

  $sql = "SELECT name, user_accounts.username, principals.puuid
	FROM group_members, principals, user_accounts
	WHERE group_members.snuuid=$1
	AND group_members.groupname=$2
	AND principals.puuid=user_accounts.puuid
	AND user_accounts.snuuid=$1
	AND user_accounts.useraccountstate='A'
	AND principals.puuid=user_accounts.puuid 
	AND user_accounts.username=group_members.username
	ORDER BY name ASC";

  $members_res = pg_query_params($sql, array($snuuid, $groupname));

  $sql = "SELECT name, user_accounts.username, principals.puuid
	FROM principals, user_accounts
	WHERE user_accounts.snuuid=$1
	AND principals.puuid=user_accounts.puuid
	AND user_accounts.useraccountstate='A'
	AND user_accounts.username NOT IN (
		SELECT user_accounts.username
        	FROM group_members, principals, user_accounts
	        WHERE group_members.snuuid=$1
        	AND group_members.groupname=$2
	        AND principals.puuid=user_accounts.puuid
        	AND user_accounts.snuuid=$1
	        AND user_accounts.useraccountstate='A'
        	AND principals.puuid=user_accounts.puuid
	        AND user_accounts.username=group_members.username
	)
        ORDER BY name ASC";

  $res = pg_query_params($sql, array($snuuid, $groupname));

  $ret .= "  <b>Edit Group Membership</b><br>
   <i>Note that users whose primary group is $groupname will not appear to be members.</i>
   <form action='?action=edit_group_membership' method='post'>
   <table>
    <tr><th>Active Users in Site</th><th></th><th>Members of Group</th></tr>
    <tr>
     <td>
      <input type='hidden' name='groupname' value='$groupname'>
      <select name='available[]' size='10' multiple>\n";

  while($row = pg_fetch_assoc($res)) {
    $ret .= "	<option value='".$row['username']."'>".$row['name']." (".$row['username'].")</option>\n";
  }

  $ret .= "
      </select>
     </td>
     <td>
	<input type='submit' name='submit' value='<-'><br>
	<input type='submit' name='submit' value='->'>
     </td>
     <td>
      <select name='members[]' size='10' multiple>\n";

  while($row = pg_fetch_assoc($members_res)) {
    $ret .= "	<option value='".$row['username']."'>".$row['name']." (".$row['username'].")</option>\n";
  }

  $ret .= "
      </select>
     </td>
    </tr>
   </table>
   </form>
";

  return $ret;
}

function print_add_edit_vsite($snuuid, $vsid) {
  if (invalid_id($snuuid))
    return "Bad SNUUID.";

  if (invalid_id($vsid))
    return "Bad VSID.";

  $ret = "  <div class=''><table>\n";
  $ret .= "   <form action='?action=do_add_edit_vsite' method='post'>\n";

  $vsname = '';
  $dbuser = '';
  $description = '';

  if(isset($vsid)) {
    $sql = "SELECT * FROM virtual_sites WHERE snuuid=$1 AND vsid=$2";
    $res = pg_query_params($sql, array($snuuid, $vsid));
    $vs = pg_fetch_assoc($res);

    if(!$vs) {
      $ret .= "   <tr><th colspan='2'>Virtual site not found!</th></tr>\n";;
    } else {
      $vsname = $vs['vsname'];
      $dbuser = $vs['dbuser'];
      $description = $vs['description'];

      $ret .= "   <tr><th colspan='2'>Editing {$vs['vsname']}</th></tr>\n";
      $ret .= "   <input type='hidden' name='vsid' value='$vsid'>\n";
    }
  }
  
  $ret .= "   <tr>
    <td>Name:</td>
    <td><input type='text' name='vsname' value='$vsname'></td>
   </tr><tr>
    <td>DB User:</td>
    <td><input type='text' name='dbuser' value='$dbuser'></td>
   </tr><tr>
    <td>Description:</td>
    <td><textarea name='description' rows='8' cols='30'>$description</textarea></td>
   </tr><tr>
    <td colspan='2'><input type='submit' value='Add or Update VSite'></td>
   </tr>";

  return $ret."  </form></table></div>\n";
}

function print_view_vs_groups($snuuid, $vsid) {
  $ret = "  <div class=''><table>
   <tr>
    <th>Group Name</th>
    <th>Status</th>
    <th>Modify</th>
   </tr>
";

  $sql = "SELECT groupname, groupstatedesc 
	FROM vs_groups, group_states
	WHERE vs_groups.snuuid=$1
	AND vs_groups.vsid=$2
	AND group_states.groupstate=vs_groups.groupstate";

  $res = pg_query_params($sql, array($snuuid, $vsid));
  while($row = pg_fetch_assoc($res)) {
    $ret .= "   <tr><td>".$row['groupname']."
    </td><td>".$row['groupstatedesc']."
    </td><td><a href='?action=&amp;groupname=".$row['groupname']."'>Modify</a>
   </tr>
";
  }

  return $ret."  <tr><td colspan='3'><a href='?action=print_vs_group_membership&amp;vsid=$vsid&amp;snuuid=$snuuid'>Associate Groups with VSite</a></td>
  </tr></table></div>\n";
}

function print_manage_sites() {
  $sql = "SELECT snuuid, sitename, description, created, modified
          FROM sites ORDER BY sitename ASC";
  $res = pg_query($sql);
  if (!$res) {
    return "Unable to load sites.";
  }

  $ret = "  <div class=''><table>
   <tr>
    <th>Site Name</th>
    <th>Modify</th>
    <th>Description</th>
    <th>Created</th>
    <th>Last Modified</th>
   </tr>
";

  while ($row = pg_fetch_assoc($res)) {
    $ret .= "   <tr>
    <td><a href='?action=do_choose_site&amp;snuuid={$row['snuuid']}'>{$row['sitename']}</a></td>
    <td><a href='?action=add_edit_site&amp;snuuid={$row['snuuid']}'>Modify</a></td>
    <td>{$row['description']}</td>
    <td>{$row['created']}</td>
    <td>{$row['modified']}</td>
   </tr>
";
  }
  $ret .= "   <tr><td colspan='5'><a href='?action=add_edit_site'>Add a Site</a></td></tr>\n";

  return $ret."  </table></div>\n";
}

function print_add_edit_site($snuuid) {
  if(isset($snuuid)) {
    $sql = "SELECT 
	sitename, description, startuid, startgid, uvuser, defaultshell
	FROM sites WHERE snuuid=$1";
    $res = pg_query_params($sql, array($snuuid));
    $s = pg_fetch_assoc($res);
  }

  $ret = "  <div class=''><table>
  <form action='?action=do_add_edit_site' method='post'>
  <input type='hidden' name='snuuid' value='{$snuuid}'>
   <tr>
    <td>Site Name</td>
    <td><input type='text' name='sitename' value='{$s['sitename']}'></td>
   </tr>
   <tr>
    <td>Starting UID Number:</td>
    <td><input type='text' name='startuid' value='{$s['startuid']}'></td>
   </tr>
   <tr>
    <td>Starting GID Number:</td>
    <td><input type='text' name='startgid' value='{$s['startgid']}'></td>
   </tr>
   <tr>
    <td>UV User:</td>
    <td><input type='text' name='uvuser' value='{$s['uvuser']}'></td>
   </tr>
   <tr>
    <td>Default Shell:</td>
    <td><input type='text' name='defaultshell' value='{$s['defaultshell']}'></td>
   </tr>
   <tr>
    <td>Description:</td>
    <td>
     <textarea name='description' rows='10' cols='30'>{$s['description']}</textarea>
    </td>
   </tr>
   <tr>
    <td colspan='2'><input type='submit' value='Add or Update Site'></td>
   </tr>
";

  return $ret."  </form></table></div>\n";
}

/*
 * return string with interface for managing vs group membership
 */
function print_vs_group_membership($snuuid, $vsid) {
  // get available non-member groups
  $sql = "SELECT groupname FROM groups 
	WHERE snuuid=$1 
	AND groupname NOT IN (
	SELECT groupname FROM virtual_site_group_members
		WHERE snuuid=$1 AND vsid=$2
  )";
  $avres = pg_query_params($sql, array($snuuid, $vsid));

  // get member groups
  $sql = "SELECT groupname FROM virtual_site_group_members
	WHERE snuuid=$1 AND vsid=$2";
  $memres = pg_query_params($sql, array($snuuid, $vsid));

  $ret = "  <div class=''><table>
   <tr>
    <th>Available groups:</th>
    <th></th>
    <th>Groups associated with VSite:</th>
   </tr>
  <form action='?action=do_vs_group_membership' method='post'>
   <input type='hidden' name='snuuid' value='$snuuid'>
   <input type='hidden' name='vsid' value='$vsid'>\n";

  $ret .= "   <tr>
    <td>
     <select name='available[]' size='8' multiple>\n";

  while($av = pg_fetch_assoc($avres)) {
    $ret .= "     <option value='{$av['groupname']}'>{$av['groupname']}</option>\n";
  }

  $ret .= "    </select>
    </td>
    <td>
     <input type='submit' name='submit' value='->'>
     <br>
     <input type='submit' name='submit' value='<-'>
    </td>\n";

  $ret .= "    <td>
     <select name='members[]' size='8' multiple>
";
  
  while($mem = pg_fetch_assoc($memres)) {
    $ret .= "     <option value='{$mem['groupname']}'>{$mem['groupname']}</option>\n";
  }

  $ret .= "     </select>
    </td>
   </tr>
  </form></table></div>\n";

  return $ret;
}

/*
 * print_manage_fs(int snuuid)
 * print a list and management links for all defined file systems for site
 */
function print_manage_fs($snuuid) {
  $ret = "  <div class=''><table>
   <tr>
    <th>Name</th>
    <th>Device Node</th>
    <th>Creation Date</th>
    <th>Description</th>
   </tr>
";

  $sql = "SELECT * FROM filesystems WHERE snuuid=$1 order by fsname;";
  $res = pg_query_params($sql, array($snuuid));

  while($row = pg_fetch_assoc($res)) {
    $ret .= "  <tr>
    <td><a href='?action=print_edit_fs&amp;fsname={$row['fsname']}'>{$row['fsname']}</a></td>
    <td>{$row['devid']}</td>
    <td>{$row['created']}</td>
    <td>{$row['description']}</td>
   </tr>
";
  }

  $ret .= "   <tr><td colspan='4'><a href='?action=print_edit_fs'>Add File System</a></td></tr>
  </table>
  </div>
";

  return $ret;
}

/*
 * print_edit_fs(int snuuid, [string fsname])
 * Print a form for adding or updating a file system for given site
 */
function print_edit_fs($snuuid, $fsname = '') {
  $ret = "  <div class=''><table>
  <form action='?action=do_add_edit_fs' method='POST'>
";

  if($fsname != '') {
    $sql = "SELECT * FROM filesystems WHERE snuuid=$1 AND fsname=$2";
    $res = pg_query_params($sql, array($snuuid, $fsname));
    $fs = pg_fetch_assoc($res);
    if(pg_num_rows($res) > 0)
    $ret .= "   <input type='hidden' name='update' value='true'>";
  }

  $ret .="
   <tr>
    <td>File system name:</td>
    <td><input type='text' name='fsname' value='{$fs['fsname']}'></td>
   </tr>
   <tr>
    <td>File system Device ID:</td>
    <td><input type='text' name='devid' value='{$fs['devid']}'></td>
   </tr>
   <tr>
    <td>File system default mount point:</td>
    <td><input type='text' name='mountpoint' value='{$fs['mountpoint']}'></td>
   </tr>
   <tr>
    <td>Type:</td>
    <td>
     <select name='fstype'>";
  $sql = "SELECT * FROM filesystem_types ORDER BY fstype ASC";
  $r = pg_query($sql);
  while($fxs = pg_fetch_assoc($r)) {
    $ret .= "      <option value='{$fxs['fstype']}'";
    if($fs['fstype'] == $fxs['fstype']) 
      $ret .= " selected ";
    $ret .= ">{$fxs['fstype']}</option>\n";
  }
  $ret .= "
     </select>
    </td>
   </tr>
   <tr>
    <td>Description:</td>
    <td><textarea name='description' rows='4' cols='30'>{$fs['description']}</textarea></td>
   </tr>

   <tr><td colspan='2'><input type='submit' value='Add/Edit File System'></td></tr>
  </form>
  </table>
  </div>
    ";

  return $ret;
}

function print_site_projects($snuuid, $projid) {
  $ret = "  <div class=''>
  <table border='1' width='800px' class=\"sortable\">
   <thead>
   <tr>
    <th>Project Name</th>
    <th>Project Description</th>
    <th>Manage</th>
    <th>Created</th>
    <th>Last Modified</th>
   </tr>
   </thead>
";

  $sql = "SELECT * FROM projects WHERE snuuid=$1 ";
  if($projid >= 0)
    $sql .= " AND projid = $2 ";

  $sql .= "ORDER by projname ASC";

  $res = ($projid >= 0) ? pg_query_params($sql, array($snuuid, $projid))
	: pg_query_params($sql, array($snuuid));

  while($row = pg_fetch_assoc($res)) {
    $ret .= "   <tr>
    <td><a href='?action=print_edit_project&amp;projid={$row['projid']}'>{$row['projname']}</a></td>
    <td>{$row['projdesc']}</td>
    <td><a href='?action=manage_project_membership&amp;projid={$row['projid']}'>Membership</a></td>
    <td>{$row['created']}</td>
    <td>{$row['modified']}</td>
   </tr>
";
  }

  $ret .= "
   <tfoot>
   <tr>
    <td colspan='5'><center>Click a column header to sort it.</center></td>
   </tr>
   </tfoot>\n";

  return $ret."  </table></div>\n";
}

function print_fs_quota_project($snuuid, $projid) {
  $result = db_get_fs_quota_project($snuuid, $projid);
  $count = pg_num_rows($result);

  if ($count == 0) {
    return "(none)";
  }

  $ret = "<div><span><span class='count'>$count</span> quotas:</span><table><thead><tr><th>Filesystem</th><th>Used</th><th>Quota</th><th>Usage</th><th>Usage Refreshed</th></tr></thead><tbody>";
  while($fs = pg_fetch_assoc($result)) {
    $quota = $fs['hardblockquota'] * $fs['blocksize'];
    $size = $fs['blockusage'] * $fs['blocksize'];
    $usage = ($size / $quota) * 100;
    $quota_nice = nice_size($quota, 2);
    $size_nice = nice_size($size, 2);
    $usage_nice = number_format($usage,2,'.','');
    $ret .= "<tr><td><a href='?action=print_edit_fs&amp;fsname={$fs['fsname']}'>{$fs['fsname']}</a></td><td>$size_nice</td><td>$quota_nice</td><td>$usage_nice%</td><td>{$fs['refreshed']}</td></tr>";
  }
  $ret .= "</tbody></table></div>";
  return $ret;
}

function print_project_cluster_access($snuuid, $projid) {
  $result = db_get_project_cluster_access($snuuid, $projid);
  $count = pg_num_rows($result);
  if ($count == 0) {
    return "(none)";
  }

  $ret = "<div><span>Access to <span class='count'>$count</span> clusters:</span><table><thead><tr><th>Cluster</th><th>Parent</th><th>Share</th><th>Root Share</th></thead><tbody>";
  while($cluster = pg_fetch_assoc($result)) {
    $parent = $cluster['parentnode'];
    if ($parent == '') {
      $parent = '(root)';
    }
    $share = $cluster['share'];
    if ($share == '') {
      $share = '*';
    }
    $root_share = $cluster['root_share'];
    if ($root_share == '') {
      $root_share = 'n/a';
    } else {
      $root_share = round(100 * $root_share, 2) . '&nbsp;%';
    }
    $ret .= "<tr><td>{$cluster['cluster']}</td><td>$parent</td><td>$share</td><td>$root_share</td></tr>";
  }
  $ret .= "</tbody></table></div>";

  return $ret;
}

function print_tags_project($snuuid, $projid) {
  $ret = "<select name='project_tags[]' multiple='multiple'>";
  $result = db_get_tags_project($snuuid, $projid);
  $count = pg_num_rows($result);
  if ($count == 0) {
    return "(No tags defined.)";
  }

  while($tag = pg_fetch_assoc($result)) {
    $ret .= "<option value='{$tag['tag']}'".($tag['selected']?" selected='selected'":"").">{$tag['description']}</option>";
  }
  $ret .= "</select>";
  return $ret;
}

function print_simple_principal_multiselect($snuuid, $projid, $title, $table, $name) {
   $ret = "
   <tr>
    <td>$title:</td>
    <td>
     <table>
      <tr><th>Name</th><th>Remove?</th></tr>
";

  $s = "SELECT principals.puuid, name FROM principals, $table
	WHERE snuuid=$1 AND projid=$2
	AND principals.puuid = $table.puuid";
  $r = pg_query_params($s, array($snuuid, $projid));

  while($o = pg_fetch_assoc($r)) {
    $ret .= "     <tr><td><a href='?action=edit_principal&puuid={$o['puuid']}'>{$o['name']}</a></td>
       <td><input type='checkbox' name='remove-{$name}-{$o['puuid']}'></td></tr>
";
  }
  if(pg_num_rows($r) == 0)
    $ret .= "   <tr><td colspan='2'><i>No principals defined.</i></td></tr>\n";

  $ret .= "     </table></td></tr>";

  $ret .= "
   <tr><td>Add $title:</td><td>
     <select name='add{$name}[]' multiple>
";

  $s = "SELECT puuid, name FROM principals WHERE puuid NOT IN
	(
		SELECT puuid FROM $table WHERE
		snuuid=$1 AND projid=$2
	)
	ORDER BY name ASC";
  $r = pg_query_params($s, array($snuuid, $projid));

  while($princ = pg_fetch_assoc($r)) {
    $ret .= "      <option value='{$princ['puuid']}'>{$princ['name']}</option>\n";
  }

  $ret .= "
     </select>
    </td>
   </tr>";

   return $ret;
}

function print_project_owners ($snuuid, $projid) {
  return print_simple_principal_multiselect($snuuid, $projid, "Project Administrators", "project_owners", "projowner");
}

function print_project_sponsors ($snuuid, $projid) {
  return print_simple_principal_multiselect($snuuid, $projid, "Project Sponsors (PIs)", "project_sponsors", "projsponsor");
}

function print_project_fields ($selected_field) {
  $ret = "<select name='projfield'><option value=''".(($selected_field == '')?" selected='selected'":"").">(none)</option>";

  $sql = "SELECT field, description FROM project_fields ORDER BY field;";
  $res = pg_query($sql);
  while ($field = pg_fetch_assoc($res)) {
    $ret .= "<option value='{$field['field']}'".(($selected_field == $field['field'])?" selected='selected'":"").">{$field['description']} ({$field['field']})</option>";
  }

  $ret .= "</select>";
  return $ret;
}

function print_edit_project($snuuid, $projid) {
  global $_hirearchy_cardinality;
  $parent_count = 0;

  if(is_numeric($projid)) {
    $sql = 'SELECT projname, projdesc, projcpuquota, projusername, groupname, projnotes, projsector, projshortdesc, projstate, projfield
	FROM projects
	WHERE
	projects.snuuid=$1 AND projects.projid=$2';
    $res = pg_query_params($sql, array($snuuid, $projid));
    $proj = pg_fetch_assoc($res, 0);

    $projname = $proj['projname'];
    $projusername = $proj['projusername'];
    $groupname = $proj['groupname'];
    $projshortdesc = $proj['projshortdesc'];
    $projdesc = $proj['projdesc'];
    $projfield = $proj['projfield'];

    $quota = $proj['projcpuquota'];
    $projsector = $proj['projsector'];
    $projnotes = $proj['projnotes'];
    $projstate = $proj['projstate'];
  } elseif(is_string($projid)) {
     $sql = 'SELECT projid, projdesc, projcpuquota, projusername, groupname, projnotes, projsector, projshortdesc, projstate, projfield
	FROM projects
	WHERE
	projects.snuuid=$1 AND projects.projname=$2';
    $res = pg_query_params($sql, array($snuuid, $projid));
    $proj = pg_fetch_assoc($res, 0);

    $projname = $projid;
    $projid = $proj['projid'];
    $projusername = $proj['projusername'];
    $groupname = $proj['groupname'];
    $projshortdesc = $proj['projshortdesc'];
    $projdesc = $proj['projdesc'];
    $projfield = $proj['projfield'];

    $quota = $proj['projcpuquota'];
    $projsector = $proj['projsector'];
    $projnotes = $proj['projnotes'];
    $projstate = $proj['projstate'];

  } else {
     // defaults
     $projname='';
     $projusername='';
     $groupname='';
     $projshortdesc='';
     $projdesc='';
     $quota = 1;
     $projsector = 'A';
     $projnotes='';
     $projstate = 'E';
     $projfield = '';
  }

  $ret = "<div class=''><table>
  <form action='.' method='post'>
  <input type='hidden' name='action' value='do_add_edit_project'>
  <input type='hidden' name='projid' value='$projid'>
  <input id='snuuid' type='hidden' name='snuuid' value='$snuuid'>
   <tr>
    <td>Project Name:</td>
    <td><input id='projname' type='text' name='projname' value='{$projname}'>&nbsp;(typcially 4 characters, max 16, no punctuation)</td>
   </tr>
   <tr>
    <td>Project Default user name portion:</td>
    <td><input type='text' name='projusername' maxlength='4' value='{$projusername}'>&nbsp;(4 characters, no vowels)</td>
   </tr>
   <tr>
    <td>Project Default Group:</td>
    <td><input type='text' name='groupname' value='{$groupname}'>&nbsp;(legal POSIX group name, must not already exist)</td>
   </tr>
   <tr>
    <td>Project Short Description:</td>
    <td><input type='text' name='projshortdesc' value='{$projshortdesc}'></td>
   </tr>
   <tr>
    <td>Project Description:</td>
    <td><textarea name='projdesc' rows='10' cols='30'>{$projdesc}</textarea></td>
   </tr>";

   $ret .= print_project_sponsors($snuuid, $projid);

   $ret .= "<tr>
     <td>Project Sector:</td>
     <td>
       <input type='radio' name='projsector' value='A'".(($projsector == 'A')?" checked='checked'":"").">Academic&nbsp;
       <input type='radio' name='projsector' value='C'".(($projsector == 'C')?" checked='checked'":"").">Commercial&nbsp;
       <input type='radio' name='projsector' value='G'".(($projsector == 'G')?" checked='checked'":"").">Government
       <br>(One must be selected)
     </td>
   </tr>
   <tr>
    <td>Project Field:</td>
     <td>
       ".print_project_fields($projfield)."
       <br>(One must be selected)
     </td>
   </tr>
   <tr>
    <td>Project Notes:</td>
    <td><textarea name='projnotes' rows='10' cols='30'>{$projnotes}</textarea></td>
   </tr>
   <tr>
    <td>Project CPU Time Quota:</td>
    <td><input type='text' name='projcpuquota' value='{$quota}'>&nbsp;(CPU-Minutes, integer) MAY NOT BE BLANK!</td>
   </tr>\n";

  if(is_numeric($projid)) {
   $ret .= print_project_owners($snuuid, $projid);
   $ret .= "
   <tr>
    <td>
     Project Parents:
    </td>
    <td>".print_project_parents($snuuid,$projid,&$parent_count)."</td>
   </tr>
   ";
  } // end if(proj exists)

  $ret .= "
   <tr>
    <td>
      Filesystem Quotas
    </td>
    <td>
      ".print_fs_quota_project($snuuid,$projid)."
    </td>
   </tr>
   <tr>
    <td>
      Project State
    </td>
    <td>
       <input type='radio' name='projstate' value='E'".(($projstate == 'E')?" checked='checked'":"").">Enabled&nbsp;
       <input type='radio' name='projstate' value='D'".(($projstate == 'D')?" checked='checked'":"").">Disabled&nbsp;
    </td>
   </tr>
   <tr>
    <td>
      Cluster Access
    </td>
    <td>
      ".print_project_cluster_access($snuuid,$projid)."
    </td>
   </tr>
   <tr><td>Project Tags</td><td>".print_tags_project($snuuid,$projid)."</td></tr>
   <tr>
    <td colspan='2'>
     <input type='submit' value='Add or Update Project'>
    </td>
   </tr>
   </form>";

  if(is_numeric($projid)) {
    $ret .= "
    <tr>
     <td colspan='2'><a href='?action=manage_project_membership&amp;projid=$projid'>Manage Membership</a></td>
    </tr><tr><td colspan='2'><a target='_blank' href='/admin/stats/all/{$projname}/all/?grp=usr&ed=".date('Y-m-d')."&sd=2011-01-01'>Project Stats</a></td></tr>\n";
    if($parent_count < $_hirearchy_cardinality) {
      $ret .= "
    <tr>
     <td>Add Project Parent:</td>
     <td>".print_select_project($snuuid, 'print_manage_project_parent', $projid, true)."</td>
    </tr>
      ";
    }
  }

  return $ret."  </table></div>";
}

function print_project_parents($snuuid, $projid, $count) {
  $ret = '';

  $sql = 'SELECT projects.projid, projects.projname, project_parents.parentshare
  	FROM projects, project_parents
  	WHERE projects.snuuid=project_parents.snuuid
	AND projects.projid=project_parents.projparentid
	AND project_parents.snuuid=$1
	AND project_parents.projid=$2';
  $res = pg_query_params($sql, array($snuuid, $projid));

  if(pg_num_rows($res) > 0) {
    $ret .= '
    <table>
     <tr>
      <th>Parent</th>
      <th>Share</th>
      <th>Modify</th>
     </tr>
    ';
    while($row = pg_fetch_assoc($res)) {
      $ret .= "     <tr>
      <td>{$row['projname']}</td>
      <td>{$row['parentshare']}</td>
      <td><a href='?action=print_manage_project_parent&amp;projid=$projid&amp;projparentid={$row['projid']}'>Modify</a></td>
     </tr>\n";
     $count++;
    }
    $ret .= '
    </table>
    ';
  } else {
    $ret .= '<i>No parent projects defined.</i>';
  }

  return $ret;
}

function print_manage_project_parent($snuuid) {
  if(isset($_REQUEST['select_project_argument'])) {
    sscanf($_REQUEST['projid'], '%d', $projparentid);
    sscanf($_REQUEST['select_project_argument'], '%d', $projid);
  } else {
    sscanf($_REQUEST['projid'], '%d', $projid);
    sscanf($_REQUEST['projparentid'], '%d', $projparentid);
  }

  $sql = 'SELECT projname FROM projects WHERE snuuid=$1 AND projid=$2';
  $res = pg_query_params($sql, array($snuuid, $projid));
  $projname = pg_fetch_result($res, 'projname');

  $sql = 'SELECT projname FROM projects WHERE snuuid=$1 AND projid=$2';
  $res = pg_query_params($sql, array($snuuid, $projparentid));
  $projparentname = pg_fetch_result($res, 'projname');

  $ret = "
    <form action='./?action=do_manage_project_parent' method='POST'>
    <input type='hidden' name='projid' value='$projid'>
    <input type='hidden' name='projparentid' value='$projparentid'>\n";

  $ret .="
    <table>
     <tr>
      <th>Project</th>
      <th>Parent</th>
      <th>Share</th>
      <th>Delete?</th>
     </tr>
     <tr>
      <td>$projname</td>
      <td>$projparentname</td>\n";

    $sql = 'SELECT parentshare FROM project_parents 
    	WHERE snuuid=$1
	AND projid=$2
	AND projparentid=$3';
    $res = pg_query_params($sql, array($snuuid, $projid, $projparentid));
    $parentshare = pg_fetch_result($res, 'parentshare');

    if(!is_numeric($parentshare))
      $ret .= "<input type='hidden' name='isnew' value='true'>\n";

    $ret .= "
     <td><input name='parentshare' type='text' size='3' value='$parentshare'></td>
     <td><input name='delete' type='checkbox' ";

  if(!is_numeric($parentshare)) 
    $ret .= " disabled='disabled' ";

   $ret .="></td>
     </tr>
     <tr><td colspan='4'><input type='submit' value='Manage or Delete Parent'></td></tr>
    </table>
    </form>  
  ";

  return $ret;
}

function print_manage_project_membership($snuuid, $projid) {
  $s = "SELECT projname, projcpuquota 
	FROM projects 
	WHERE snuuid = $1 AND projid = $2";
  $r = pg_query_params($s, array($snuuid, $projid));
  $projname = pg_fetch_result($r, 'projname');
  $projcpuquota = pg_fetch_result($r, 'projcpuquota');

  // get all cput time, this will probably be replaced with a link to a
  //  reporting tool at some point
  $s = "select sum(cputime) as cpuall from cputime where username in
	(select username from user_accounts where snuuid=$1 and projid=$2)";
  $r = pg_query_params($s, array($snuuid, $projid));
  $cpuall = pg_fetch_result($r, 'cpuall');

  $ret = "  <div class=''><table>
   <tr><th colspan='2'>Managing Project $projname</th></tr>
   <tr>
    <td>CPU Time Quota: </td><td>$projcpuquota</td>
   </tr>
   <tr>
    <td>CPU Time Used: </td><td>$cpuall</td>
   </tr>\n";

  // 3 cases; no view, view=%search%, or view=all
    $ret .= "   <tr>
    <td><a href='?action=manage_project_membership&amp;projid=$projid&amp;view=all'>View All Members</a></td>
    <td>
     <form action='' method='get'>
	<input type='hidden' name='action' value='manage_project_membership'>
	<input type='hidden' name='projid' value='$projid'>
	<input type='hidden' name='view' value='search'>
	View Members Whose Name Contains:&nbsp;<input type='text' name='search'>
	<input type='submit' value='Search'></form>
    </td>
   </tr></table>";
  if(isset($_REQUEST['view'])) {
    $sql = "SELECT 
	principals.name, principals.puuid, username, user_accounts.created,
	mustchange
	FROM principals, user_accounts, authenticators
	WHERE user_accounts.snuuid=$1
	AND user_accounts.projid=$2
	AND principals.puuid = user_accounts.puuid
	AND authenticators.puuid = user_accounts.puuid
	AND authenticators.authid = 0 AND user_accounts.useraccountstate != 'R'";
    if(isset($_REQUEST['search'])) {
	$sql .= " AND LOWER(principals.name) LIKE $3";
	$res = pg_query_params($sql, array(
		$snuuid, 
		$projid, 
		'%'.(strtolower($_REQUEST['search'])).'%'));
    } else {
	$res = pg_query_params($sql, array($snuuid, $projid));
    }
    $ret .= "  <table border='1' class='sortable'>
   <thead>
   <tr>
    <th>Member</th>
    <th>User Name</th>
    <th>CPU Time</th>
    <th>Reset Password</th>
    <th>Added</th>
   </tr>
   </thead>
";
    while($member = pg_fetch_assoc($res)) {
      $ret .= "   <tr>
    <td><a href='?action=edit_principal&amp;puuid={$member['puuid']}'>{$member['name']}</a></td>
    <td><a href='?action=print_edit_user_account&amp;username={$member['username']}'>{$member['username']}</a></td>
    <td><a href='?action=view_cputime&amp;username={$member['username']}'>View</a></td>
    <td><a href='?action=print_change_password&amp;puuid={$member['puuid']}'>Reset</a>";
    $ret .= ($member['mustchange'] == 't') ? "&nbsp;<font color='red'>*</font>" : "";
    $ret .= "    </td>
    <td>{$member['created']}</td>
    <!--<td></td>-->
   </tr>
";
    }
    $ret .= "
   <tfoot>
   <tr>
    <td colspan='5'>
     <center><font color='red'>*</font> = User must change password before logging in.<br >Click a column header to sort it.</center>
    </td>
   </tr>
   </tfoot>
";
  }

  $ret .= "
  </table>
  <center>
  <table>
   <tr><td colspan='2'><hr></td></tr>
   <tr>
    <td>Add a member:</td>
    <td>
    <form action='./' method='post'>
     <input type='hidden' name='action' value='print_edit_user_account'>
     <input type='hidden' name='projid' value='$projid'>
     <input type='hidden' name='projname' value='$projname'>
     <select name='puuid'>\n";
  $sql = "SELECT name, puuid FROM principals WHERE principalstate='A'
	AND puuid NOT IN (SELECT puuid FROM user_accounts 
		WHERE snuuid=$1 AND projid=$2) ORDER BY name";
  $res = pg_query_params($sql, array($snuuid, $projid));
  while($pri = pg_fetch_assoc($res)) {
    $ret .= "      <option value='{$pri['puuid']}'>{$pri['name']}</option>\n";
  }
  $ret .= "
     </select>
     <input type='submit' value='Setup Account'>
    </form>
    </td>
</tr>
";

  return $ret."  </table></center></div>";
}

function view_cputime($snuuid, $username = '') {
  $all = isset($_REQUEST['all']);
  $host = isset($_REQUEST['host']);

  $sql = "SELECT max(jobstart) AS max FROM cputime";
  $max = pg_fetch_result(pg_query($sql), 'max');
  sscanf($max, '%s %s', $max, $t);
  $sql = "SELECT min(jobstart) AS min FROM cputime";
  $min = pg_fetch_result(pg_query($sql), 'min');
  sscanf($min, '%s %s', $min, $t);

  if(!isset($_SESSION['startts'])) $_SESSION['startts'] = $min;
  if(!isset($_SESSION['endts'])) $_SESSION['endts'] = $max;

  $startts = (isset($_REQUEST['startyear'])) ? 
	sprintf('%s-%s-%s', $_REQUEST['startyear'], $_REQUEST['startmonth'], $_REQUEST['startday']) 
	: $_SESSION['startts'];
  $endts = (isset($_REQUEST['endyear'])) ? 
	sprintf('%s-%s-%s', $_REQUEST['endyear'], $_REQUEST['endmonth'], $_REQUEST['endday'])
	: $_SESSION['endts'];

  $_SESSION['startts'] = $startts;
  $_SESSION['endts'] = $endts;

  // work-around for modularization of project selection
  if(isset($_REQUEST['projid']) && $_REQUEST['projid'] != -1) {
    sscanf($_REQUEST['projid'], '%s', $projid);
    $sql = "SELECT projusername FROM projects WHERE snuuid=$1 AND projid=$2";
    $res = pg_query_params($sql, array($snuuid, $projid));
    $username = pg_fetch_result($res, 'projusername').'%';
    if(!$username) {
      return;
    }
  }

  $urlusername = htmlspecialchars($username);

  $years = range(substr($min, 0, 4), substr($max, 0, 4));
  // TODO make valid dates...
  $months = range(1, 12);
  $days = range(1, 31);

  $ret = "  <div class=''>
  <center><a href='?action=view_cputime&amp;username=$urlusername'>View Summary</a>
  &nbsp;||&nbsp;<a href='?action=view_cputime&amp;username=$urlusername&amp;all'>View All Records</a>
  &nbsp;<a href='?action=view_cputime_csv&amp;username=$urlusername&amp;all&amp;csv&amp;hideui' target=\"_blank\" >(CSV)</a>
  &nbsp;||&nbsp;<a href='?action=view_cputime&amp;username=$urlusername&amp;host'>View Host Summaries</a>
  <hr>
  <form method='GET' action='' >
   <input type='hidden' name='action' value='view_cputime'>
   <input type='hidden' name='username' value='$urlusername'>
";

  if($all) $ret .= "   <input type='hidden' name='all'>\n";
  if($host) $ret .= "   <input type='hidden' name='host'>\n";

  $ret .= "
  <center>Time Range (inclusive):
   <select name='startyear'>
";
  sscanf($_SESSION['startts'], '%d-%d-%d', $startyear, $t, $t);
  foreach($years as $year) {
    $ret .= "    <option value='{$year}'";
    if($startyear == $year) $ret .= " SELECTED ";
    $ret .= ">{$year}</option>\n";
  }
  $ret .= "
   </select>
   <select name='startmonth'>
";
  sscanf($_SESSION['startts'], '%d-%d-%d', $t, $startmonth, $t);
  foreach($months as $month) {
    $ret .= "    <option value='{$month}'";
    if($startmonth == $month) $ret .= " SELECTED ";
    $ret .= ">{$month}</option>\n";
  }
  $ret .= "
   </select>
   <select name='startday'>
";
  sscanf($_SESSION['startts'], '%d-%d-%d', $t, $t, $startday);
  foreach($days as $day) {
    $ret .= "    <option value='{$day}'";
    if($startday == $day) $ret .= " SELECTED ";
    $ret .= ">{$day}</option>\n";
  }

  $ret .= "
   </select>
  To:
   <select name='endyear'>
";
  sscanf($_SESSION['endts'], '%d-%d-%d', $endyear, $t, $t);
  foreach($years as $year) {
    $ret .= "    <option value='{$year}'";
    if($endyear == $year) $ret .= " SELECTED ";
    $ret .= ">{$year}</option>\n";
  }
  $ret .= "
   </select>
   <select name='endmonth'>
";
  sscanf($_SESSION['endts'], '%d-%d-%d', $t, $endmonth, $t);
  foreach($months as $month) {
    $ret .= "    <option value='{$month}'";
    if($endmonth == $month) $ret .= " SELECTED ";
    $ret .= ">{$month}</option>\n";
  }
  $ret .= "
   </select>
   <select name='endday'>
";
  sscanf($_SESSION['endts'], '%d-%d-%d', $t, $t, $endday);
  foreach($days as $day) { 
    $ret .= "    <option value='{$day}'";
    if($endday == $day) $ret .= " SELECTED ";
    $ret .= ">{$day}</option>\n";
  }

  $ret .= "
   </select>
   <input type='submit' value='Submit'>
</center>
  </form>
  <hr>
  <table class='sortable' border='1'>
   <thead>
";

  if($all) {
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
    $ret .= "   <tr>
    <th>Job Identifier</th>
    <th>Job Owner</th>
    <th>Job Start</th>
    <th>Job End</th>
    <th>Resources</th>
    <th>CPU Time</th>
    <th>Machine</th>
    <th>Time Stamp</th>
   </tr>
";
  } else if($host) {
    if($username != '') {
      // view summary
      $sql = "SELECT sum(cputime) as cputime,
	sum(memory) AS memory,
	machine,
	username
	FROM cputime where
	snuuid=$1 AND username like $2
	AND jobstart >= $3
        AND jobstart <= $4
	GROUP BY machine, username";
    } else {
      $sql = "SELECT sum(cputime) as cputime,
        sum(memory) AS memory,
        machine,
	username
        FROM cputime where
        snuuid=$1
	AND jobstart >= $2
	AND jobstart <= $3
	GROUP BY machine, username";
    }
    $ret .= "   <tr>
    <th>Machine</th>
    <th>CPU Time</th>
    <th>Memory</th>
    <th>Job Owner</th>
   </tr>
";
  } else {
    $sql = ($username == '') ? 
	"SELECT sum(cputime) as cputime, sum(memory) as memory
	from cputime where snuuid=$1
	AND jobstart >= $2
        AND jobstart <= $3"
	: "SELECT sum(cputime) as cputime, sum(memory) as memory
	from cputime where snuuid=$1 and username like $2 
	AND jobstart >= $3
        AND jobstart <= $4";
    $ret .= "   <tr><th>CPU Time</th><th>Memory</th></tr>
";
  }

  $res = ($username == '') ? pg_query_params($sql, array($snuuid, $startts, $endts))
	: pg_query_params($sql, array($snuuid, $username, $startts, $endts));
  $ret .= "   </thead>\n";

  if($res && $all) {
    while($row = pg_fetch_assoc($res)) {
      $ret .= "   <tr>
    <td>{$row['jobname']}</td>
    <td>{$row['username']}</td>
    <td>{$row['jobstart']}</td>
    <td>{$row['jobend']}</td>
    <td>{$row['units']}</td>
    <td>{$row['cputime']}</td>
    <td>{$row['machine']}</td>
    <td>{$row['timestamp']}</td>
   </tr>
";
    }
  } else if($res && $host) {
    while($row = pg_fetch_assoc($res)) {
      $ret .= "   <tr>
    <td>{$row['machine']}</td>
    <td>{$row['cputime']}</td>
    <td>{$row['memory']}</td>
    <td>{$row['username']}</td>
   </tr>
";
    }
  } else if($res) {
    $row =  pg_fetch_assoc($res);
    $ret .= "   <tr>
    <td>{$row['cputime']}</td>
    <td>{$row['memory']}</td>
   </tr>
";
  } else {
    return $ret;
  }

  $ret .= "
   <tfoot>
   <tr>
    <td colspan='8'><center>Click a column header to sort it.</center></td>
   </tr>
   </tfoot>
  </table>
  </center>
  </div>
";

  return $ret;
}

?>
