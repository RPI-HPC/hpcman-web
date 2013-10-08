<?php

include_once("common.php");
include_once('database.php');

function set_error($msg)
{
	if (!isset($_SESSION['error']))
		$_SESSION['error'] = $msg;
	else
		$_SESSION['error'] = implode(' ', array($_SESSION['error'], $msg));
}

function handle_db_error()
{
	$code = hpcman_error(pg_last_error());
	set_error('A database error has been logged and the current action may have failed. ' . sysadmin_contact_msg($code));
}

/*
 *  Add or edit file systems given the site id.
 *  Put error in $err
 */
function do_add_edit_fs($snuuid, &$err) {
	if(isset($_REQUEST['update'])) {
		$sql = "UPDATE filesystems SET
			fsname = $2,
			devid = $3,
			description = $4,
			fstype = $5,
			mountpoint = $6
			WHERE fsname=$2 AND snuuid=$1";
	} else {
		$sql = "INSERT INTO filesystems (
			fsname,
			devid,
			description,
			snuuid,
			fstype,
			mountpoint
			) VALUES (
			$2, $3, $4, $1,	$5, $6)";
	}

	$res = pg_query_params($sql, array(
		$snuuid,
		$_REQUEST['fsname'],
		$_REQUEST['devid'],
		$_REQUEST['description'],
		$_REQUEST['fstype'],
		$_REQUEST['mountpoint']
	));

	if($res) {
		hpcman_log("Added filesystem {$_REQUEST['fsname']}");
		return true;
	} else {
		$err = pg_last_error();
		return false;
	}
}

function do_manage_project_parent($snuuid) {
  sscanf($_REQUEST['projid'], '%d', $projid);
  sscanf($_REQUEST['projparentid'], '%d', $projparentid);
  sscanf($_REQUEST['parentshare'], '%f', $parentshare);

  if($_REQUEST['isnew'] === "true") {
    $sql = "INSERT INTO project_parents VALUES ($1, $2, $3, $4)";
    return pg_query_params($sql, array(
        $snuuid,
	$projid,
	$projparentid,
	$parentshare
        ));
  } else if(isset($_REQUEST['delete'])) {
    $sql = 'DELETE FROM project_parents
        WHERE snuuid=$1
	AND projid=$2
	AND projparentid=$3';
    return pg_query_params($sql, array(
            $snuuid,
            $projid,
            $projparentid
	    ));
  } else {
    $sql = 'UPDATE project_parents SET
        parentshare=$1,
	modified=now()
	WHERE snuuid=$2
	AND projid=$3
	AND projparentid=$4';
    return pg_query_params($sql, array(
        $parentshare,
	$snuuid,
	$projid,
	$projparentid
	));
  }
}

function do_edit_group_membership($snuuid, $groupname) {
  // check sanity of form data
  if(!isset($_REQUEST['available']) && !isset($_REQUEST['members'])) {
    $_SESSION['error'] = "No users selected";
    return false;
  }
  if(isset($_REQUEST['members']) && $_REQUEST['submit'] == "->") {
    $_SESSION['error'] = "Invalid group membership move.";
    return false;
  }
  if(isset($_REQUEST['available']) && $_REQUEST['submit'] == "<-") {
    $_SESSION['error'] = "Invalid group membership move.";
    return false;
  }

  // OK, make the changes
  if(isset($_REQUEST['available'])) {
    $sql = "INSERT INTO group_members (
	snuuid,
	groupname,
	username
	) VALUES (
	$1,
	$2,
	$3
	)";
    foreach($_REQUEST['available'] as $username) {
      $res = pg_query_params($sql, array($snuuid, $groupname, $username));
      if(!$res) return false;
    }
  }
  if(isset($_REQUEST['members'])) {
    $sql = "DELETE FROM group_members WHERE snuuid=$1 AND groupname=$2 AND username=$3";
    foreach( $_REQUEST['members'] as $username) {
      $res = pg_query_params($sql, array($snuuid, $groupname, $username));
      if(!$res) return false;
    }
  }

  return true;
}

/*
 * add or edit a site, read all data from HTTP REQUEST vars
 */
function do_add_edit_site() {
  if($_REQUEST['snuuid'] !== "") {
    $sql = "UPDATE sites SET
	sitename=$2,
	startuid=$3,
	startgid=$4,
	uvuser=$5,
	description=$6,
	defaultshell=$7
	WHERE snuuid=$1
";
    $res = pg_query_params($sql, array(
	($_REQUEST['snuuid']),
	($_REQUEST['sitename']),
	($_REQUEST['startuid']),
	($_REQUEST['startgid']),
	($_REQUEST['uvuser']),
	($_REQUEST['description']),
	($_REQUEST['defaultshell'])
    ));
  } else {
    $sql = "INSERT INTO sites (
	sitename,
	startuid,
	startgid,
	uvuser,
	description,
	defaultshell
	) VALUES ( $1, $2, $3, $4, $5, $6 )";
    $res = pg_query_params($sql, array(
	($_REQUEST['sitename']),
        ($_REQUEST['startuid']),
        ($_REQUEST['startgid']),
        ($_REQUEST['uvuser']),
        ($_REQUEST['description']),
        ($_REQUEST['defaultshell'])
    ));
  }
  if(!$res) {
    $_SESSION['error'] = "Add/Update Site FAILED";
    return false;
  }
  return true;

}

function do_add_edit_project($snuuid, $projid, $cpuquot) {
  if ($_REQUEST['projfield'] == '') {
    $projfield = NULL;
  } else {
    $projfield = $_REQUEST['projfield'];
  }
  if(isset($projid) && $projid != "") {
    hpcman_log("Preparing to update project " . $projid . " to SNUUID " . $snuuid);
    $sql = "UPDATE projects SET
	projname=$3,
	projdesc=$4,
	projcpuquota=$5,
	projusername=$6,
	groupname=$7,
	projnotes=$8,
	projsector=$9,
	projshortdesc=$10,
  projstate=$11,
  projfield=$12
	WHERE snuuid=$1 AND projid=$2";
    $params = array(
	$snuuid,
	$projid,
	($_REQUEST['projname']),
	($_REQUEST['projdesc']),
	$cpuquot,
	($_REQUEST['projusername']),
	($_REQUEST['groupname']),
	($_REQUEST['projnotes']),
	($_REQUEST['projsector']),
	($_REQUEST['projshortdesc']),
  ($_REQUEST['projstate']),
  $projfield);
  } else {
    hpcman_log("Preparing to add project " . $_REQUEST['projname'] . " to SNUUID " . $snuuid);
    $sql = "INSERT INTO projects (
	snuuid,
	projname,
	projdesc,
	projcpuquota,
	projusername,
	groupname,
	projnotes,
	projsector,
	projshortdesc,
  projstate,
  projfield
	) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11)";
    $params = array(
	$snuuid,
	($_REQUEST['projname']),
	($_REQUEST['projdesc']),
	$cpuquot,
	($_REQUEST['projusername']),
	($_REQUEST['groupname']),
	($_REQUEST['projnotes']),
	($_REQUEST['projsector']),
	($_REQUEST['projshortdesc']),
  ($_REQUEST['projstate']),
  $projfield);
  }
  $res = pg_query_params($sql, $params);
  if(!$res) {
    handle_db_error();
    return false;
  }
  $projid = db_get_projid_from_name($snuuid, $_REQUEST['projname']);

  // take care of project ownership
  // subtractions
  // owners
  $sq = "SELECT puuid FROM project_owners
         WHERE snuuid=$1 AND projid=$2";
  $proj_res = pg_query_params($sq, array($snuuid, $projid));
  while($row = pg_fetch_assoc($proj_res)) {
    $puuid = $row['puuid'];
    if(isset($_REQUEST["remove-projowner-$puuid"])) {
      $sq = "DELETE FROM project_owners
             WHERE snuuid=$1 AND projid=$2 AND puuid=$3";
      $res = pg_query_params($sq, array($snuuid, $projid, $puuid));
    }
  }

  // sponsors
  $sq = "SELECT puuid FROM project_sponsors
         WHERE snuuid=$1 AND projid=$2";
  $proj_res = pg_query_params($sq, array($snuuid, $projid));
  while($row = pg_fetch_assoc($proj_res)) {
    $puuid = $row['puuid'];
    if(isset($_REQUEST["remove-projsponsor-$puuid"])) {
      $sq = "DELETE FROM project_sponsors
             WHERE snuuid=$1 AND projid=$2 AND puuid=$3";
      $res = pg_query_params($sq, array($snuuid, $projid, $puuid));
    }
  }

  // additions
  // owners
  if(isset($_REQUEST['addprojowner'])) {
    foreach($_REQUEST['addprojowner'] as $owner) {
      $sq = "INSERT INTO project_owners
             (snuuid, projid, puuid) VALUES ($1, $2, $3)";
      pg_query_params($sq, array($snuuid, $projid, $owner));
    }
  }

  // sponsors
  if(isset($_REQUEST['addprojsponsor'])) {
    foreach($_REQUEST['addprojsponsor'] as $owner) {
      $sq = "INSERT INTO project_sponsors
             (snuuid, projid, puuid) VALUES ($1, $2, $3)";
      pg_query_params($sq, array($snuuid, $projid, $owner));
    }
  }

  //  save project tagging
  if (isset($_REQUEST['project_tags'])) {
    $tags = $_REQUEST['project_tags'];
    if (!db_replace_project_tags($snuuid, $projid, $tags))
      $_SESSION['error'] = 'Error updating tags' . pg_last_error();
  }
  return true;
}

/*
 * do_add_user_account(int snuuid, int puuid)
 * Add the user account in the POSTed form to the given site and person
 *
 */
function do_add_user_account($snuuid, $username, $puuid, $groupname, $homedirectory, $quota, $shell, $projid, $vsids) {
  if(!db_add_user_account($snuuid, $username, $puuid, $groupname, $homedirectory, $quota, $shell, $projid)) {
    handle_db_error();
    return false;
  }

  if(!db_update_useraccount_vs_membership($snuuid, $username, $vsids)) {
    handle_db_error();
    return false;
  }

  return true;
}

function do_edit_user_account($snuuid, $username) {
  $sql = "
	UPDATE user_accounts
	SET
		homedirectory=$3,
		quota=$4,
		shell=$5,
		useraccountstate=$6
	WHERE snuuid=$1 AND username=$2
";

  $quota = ($_REQUEST['quota'] == "") ? 0 : ($_REQUEST['quota']);

  $ret = pg_query_params($sql, array(
	$snuuid,
	$username,
	($_REQUEST['homedirectory']),
	$quota,
	($_REQUEST['shell']),
	($_REQUEST['useraccountstate'])
   ));

  if(!$ret) {
    handle_db_error();
    return false;
  }

  return db_update_useraccount_vs_membership($snuuid, $username);
}

function do_create_edit_principal() {
  $isvo = (isset($_REQUEST['isvo'])) ? 't' : 'f';
  $expires = ($_REQUEST['expires'] == '') ? 'epoch' : ($_REQUEST['expires']);

  if(isset($_REQUEST['puuid'])) {
    $sql = "
	UPDATE principals SET
		isvo=$1,
		expires=$2,
		name=$3,
		contactinfo=$4,
		defaultusername=$5,
		emailAddress=$7,
		principalstate=$8
	WHERE puuid=$6
";
    $ret = pg_query_params($sql, array(
	$isvo,
	$expires,
	($_REQUEST['name']),
	($_REQUEST['contactinfo']),
	($_REQUEST['defaultusername']),
	$_REQUEST['puuid'],
	($_REQUEST['emailAddress']),
	($_REQUEST['principalstate'])
      ));
  } else {
    $sql = "
	INSERT INTO principals (
		isvo,
		expires,
		name,
		contactinfo,
		defaultusername,
		emailAddress
	) VALUES (
		$1,
		$2,
		$3,
		$4,
		$5,
		$6
	)
";
    pg_query("BEGIN TRANSACTION");
    $ret = pg_query_params($sql, array(
        $isvo,
        $expires,
        ($_REQUEST['name']),
        ($_REQUEST['contactinfo']),
        ($_REQUEST['defaultusername']),
	($_REQUEST['emailAddress'])
      ));
    if($ret) {
      pg_query('INSERT INTO passwords (puuid, passwordtype)
                SELECT (SELECT last_value FROM principals_puuid_seq)
		, passwordtype FROM password_types');
      pg_query('COMMIT');
    } else {
      handle_db_error();
      pg_query('ROLLBACK');
      return false;
    }
  }

  return $ret;
}

/*
 *do_choose_vsite(int snuuid)
 * associate the given vsite with the current user's session
 */
function do_choose_vsite($snuuid) {
  sscanf($_REQUEST['choose_vsite'], "%d", $_SESSION['vsid']);
  if(sscanf($_REQUEST['choose_vsite'], "%d", $vsid)) {
    $sql = "SELECT vsname FROM virtual_sites WHERE vsid=$1 AND snuuid=$2";
    $res = pg_query_params($sql, array($vsid, $snuuid));
    $_SESSION['vsname'] = pg_fetch_result($res, 'vsname');
    $_SESSION['vsid'] = $vsid;
  }
}

/*
 * do_choose_site()
 * associate the given site with the current user's session
 */
function do_choose_site() {
  global $_default_snuuid;

  $sql = "SELECT sitename FROM sites WHERE snuuid = $1";
  $snuuid = (isset($_default_snuuid)) ? $_default_snuuid : $_REQUEST['snuuid'];
  $res = pg_query_params($sql, array($snuuid))
    or die(pg_last_error($res));
  if(pg_num_rows($res) != 1) return FALSE;
  $_SESSION['sitename'] = pg_fetch_result($res, 'sitename');
  $_SESSION['snuuid'] = $snuuid;
  if(isset($_SESSION['vsid'])) {
    unset($_SESSION['vsid']);
    unset($_SESSION['vsname']);
  }
  return TRUE;
}

function do_add_group($snuuid, $groupname) {
	$sql = 'INSERT INTO groups (snuuid, groupname) VALUES ($1, $2)';
	$res = pg_query_params($sql, array($snuuid, $groupname));
	// TODO: check result
}

function do_add_edit_vsite($snuuid, $vsid, $dbuser, $vsname, $description) {
  if (invalid_id($snuuid))
    return false;

  if (invalid_id($vsid))
    return false;

  $s = "SELECT vsid FROM virtual_sites WHERE snuuid=$1 AND vsid=$2";
  $r = pg_query_params($s, array($snuuid, $vsid));
  if(pg_num_rows($r) == 0) {
    $sql = "INSERT INTO virtual_sites (
            snuuid, dbuser, vsname, description
            ) VALUES ($1, $2, $3, $4)";
    $params = array($snuuid, $dbuser, $vsname, $description);
  } else {
    $sql = "UPDATE virtual_sites SET dbuser=$3, vsname=$4, description=$5
            WHERE snuuid=$1 AND vsid=$2";
    $params = array($snuuid, $vsid, $dbuser, $vsname, $description);
  }
  return pg_query_params($sql, $params) !== false;
}

/*
 * process membership changes between virtual sites and groups
 */
function do_vs_group_membership($snuuid, $vsid) {
  if(isset($_REQUEST['available']) && ($_REQUEST['submit'] === '->')) {
    foreach($_REQUEST['available'] as $group) {
      // TODO check that group exists before attempting to add it!!
      $sql = "INSERT INTO virtual_site_group_members VALUES ($1, $2, $3)";
      pg_query_params($sql, array($snuuid, $vsid, $group));
    }
  }
  if(isset($_REQUEST['members']) && ($_REQUEST['submit'] === '<-')) {
    foreach($_REQUEST['members'] as $group) {
      // TODO check that group exists before attempting to add it!!
      $sql = "DELETE FROM virtual_site_group_members
              WHERE snuuid=$1
	      AND vsid=$2
	      AND groupname=$3";
      pg_query_params($sql, array($snuuid, $vsid, $group));
    }
  }
}

?>
