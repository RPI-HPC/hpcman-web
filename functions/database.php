<?php

/*
 * invalid_id(int id)
 * Returns true if the id is set and negative
 *
 * This is not meant to handle unset IDs. 0 is acceptable for IDs (such as the
 * vsite universe).
 */
function invalid_id($id)
{
	return isset($id) && $id < 0;
}

/*
 * db_get_name_from_puuid(int puuid)
 * Returns the name of a principal for the given principal UUID
 */
function db_get_name_from_puuid($puuid)
{
	$s = "SELECT name FROM principals WHERE puuid=$1";
	$r = pg_query_params($s, array($puuid));
	if($r)
		return pg_fetch_result($r, 'name');
	else
		return -1;
}

/*
 * db_user_is_member_of_group(string username, string groupname, int snuuid)
 * Returns whether username is member of given group for given site
 */
function db_user_is_member_of_group($username, $groupname, $snuuid)
{
	$sql = "SELECT creation FROM group_members
		WHERE username=$1
		AND groupname=$2
		AND snuuid=$3";
	$res = pg_query_params($sql, array($username, $groupname, $snuuid));
	return pg_num_rows($res) != 0;
}

/*
 * db_get_gecos_from_username(string username, int snuuid)
 * Returns the gecos field for a given username and site
 */
function db_get_gecos_from_username($username, $snuuid)
{
	$sql = "SELECT name FROM principals, user_accounts
		WHERE principals.puuid=user_accounts.puuid
		AND user_accounts.snuuid=$1
		AND user_accounts.username=$2";
	$res = pg_query_params($sql, array($snuuid, $username));
	return pg_fetch_result($res, 'name');
}

/*
 * db_user_is_member_of_vsite(string username, int vsid, int snuuid)
 * Returns whether username is members of a given vsite for given site
 */
function db_user_is_member_of_vsite($username, $vsid, $snuuid)
{
	$sql = "SELECT username FROM virtual_site_members
		WHERE username=$1
		AND vsid=$2
		AND snuuid=$3";
	$res = pg_query_params($sql, array($username, $vsid, $snuuid));
	return pg_num_rows($res) != 0;
}

/*
 * db_get_fs_quota_project(int snuuid, int projid)
 * Returns the name, usage, quota, and block size of filesystems for a project
 */
function db_get_fs_quota_project($snuuid, $projid)
{
	$sql = "SELECT
		filesystem_project_quotas.fsname,
		blockusage,
		hardblockquota,
		filesystems.blocksize,
		to_char(filesystem_project_quotas.refreshed, 'YYYY-MM-DD HH24:MI:SS') as refreshed
		FROM
		filesystem_project_quotas, filesystems as filesets, filesystems
		WHERE
		filesystems.fstype='GPFS' and
		filesystems.snuuid=filesystem_project_quotas.snuuid and
		filesets.fsname=filesystem_project_quotas.fsname and
		filesets.snuuid=filesystems.snuuid and
		filesets.devid=filesystems.devid and
		filesystem_project_quotas.projid=$2 and
		filesystems.snuuid=$1
		ORDER BY fsname;";
	$result = pg_query_params($sql, array($snuuid, $projid));
	return $result;
}

/*
 * db_get_tags_project(int snuuid, int projid)
 * Returns names and descriptions for all tags, marking those that are selected for a given project
 */
function db_get_tags_project($snuuid, $projid)
{
	$sql = "SELECT
		project_tags.tag,
		description,
		COUNT(project_tagging.tag) as selected
		FROM
		project_tags
		LEFT JOIN project_tagging ON
		(project_tags.tag=project_tagging.tag
		AND projid=$2
		AND snuuid=$1)
		GROUP BY
		project_tags.tag,
		project_tags.description,
		project_tagging.tag
		ORDER BY project_tags.description;";
	$result = pg_query_params($sql, array($snuuid, $projid));
	return $result;
}

/*
 * db_get_tags([int snuuid])
 * Returns a list with names, descriptions, and project counts for all tags.
 * If snuuid is given, the tags returned will only be ones in use in the site.
 * The count for a call without a site will include all sites.
 */
function db_get_tags($snuuid=NULL)
{
  $sql = "SELECT project_tags.tag, description, COUNT(project_tagging.tag)
          FROM project_tags
          LEFT JOIN project_tagging ON project_tagging.tag=project_tags.tag ";
  if ($snuuid) {
    $sql .= "WHERE project_tagging.snuuid=$1 ";
  }
  $sql .= "GROUP BY project_tags.tag, description, project_tagging.tag
           ORDER BY project_tags.tag";
  if ($snuuid) {
    $result = pg_query_params($sql, array($snuuid));
  } else {
    $result = pg_query($sql);
  }
  return $result;
}

/*
 * db_get_tag_description(string tag)
 * Returns the tag description/
 */
function db_get_tag_description($tag)
{
  $sql = "SELECT description FROM project_tags where tag=$1";
  $res = pg_query_params($sql, array($tag));
  return pg_fetch_result($res, 'description');
}

/*
 * db_get_projects_by_tag(int snuuid, string tag)
 * Return a list of projects and descriptions for a given tag.
 */
function db_get_projects_by_tag($snuuid, $tag)
{
  $sql = "SELECT projname, projshortdesc, projects.projid
          FROM project_tagging, projects
          WHERE project_tagging.snuuid = projects.snuuid
          AND project_tagging.projid = projects.projid
          AND tag=$1 AND projects.snuuid=$2
          ORDER BY projname";
  $result = pg_query_params($sql, array($tag, $snuuid));
  return $result;
}

/*
 * db_replace_project_tags(int snuuid, int projid, array tags)
 * Returns true if tags are fully replaced. False if nothing was changed.
 */
function db_replace_project_tags($snuuid, $projid, $tags)
{
	pg_query("BEGIN TRANSACTION");
	$sql = 'DELETE FROM project_tagging where snuuid=$1 and projid=$2';
	pg_query_params($sql, array($snuuid, $projid));
	if (!isset($tags)) {
		pg_query("COMMIT");
		return true;
	}

	$sql = 'INSERT INTO project_tagging (snuuid,projid,tag) VALUES ($1, $2, $3)';
	foreach($tags as $tag) {
		if (!pg_query_params($sql, array($snuuid, $projid, $tag))) {
			pg_query("ROLLBACK");
			return false;
		}
	}

	pg_query("COMMIT");
	return true;
}

function db_get_projid_from_name($snuuid, $projname)
{
	$sql = 'SELECT projid from projects where snuuid=$1 AND projname=$2';
	$res = pg_query_params($sql, array($snuuid, $projname));
	$row = pg_fetch_assoc($res);
	return $row['projid'];
}

function db_update_useraccount_vs_membership($snuuid, $username, $vsids)
{
	if(!isset($vsids))
		return true;

	$sql = "SELECT vsid FROM virtual_sites WHERE snuuid=$1";
	$rr = pg_query_params($sql, array($snuuid));

	$vsites = pg_fetch_all_columns($rr);
	$intersection = array_intersect($vsites, $vsids);

	// transaction: drop old affiliations, add new ones, commit
	pg_query("BEGIN WORK");
	$sql = "DELETE FROM virtual_site_members
		WHERE snuuid=$1 AND username=$2 AND vsid <> 0";
	pg_query_params($sql, array($snuuid, $username));
	foreach($intersection as $vsid) {
		$sql = "INSERT INTO virtual_site_members
			(snuuid, vsid, username) VALUES ($1, $2, $3)";
		if(!pg_query_params($sql, array($snuuid, $vsid, $username))) {
			pg_query("ROLLBACK");
			return false;
		}
	}

	pg_query("COMMIT");
	return TRUE;
}

function db_add_user_account($snuuid, $username, $puuid, $groupname, $homedirectory, $quota, $shell, $projid)
{
	$sql = "INSERT INTO user_accounts (snuuid, username, puuid, groupname,
		homedirectory, quota, shell, projid)
		VALUES ($1, $2, $3, $4, $5, $6,	$7, $8)";
	$res = pg_query_params($sql, array($snuuid, $username, $puuid,
			       $groupname, $homedirectory, $quota, $shell,
		               $projid));
	return $res;
}

function db_get_project_cluster_access($snuuid, $projid)
{
  $sql = "SELECT cluster, parentnode, share from project_cluster_access where snuuid=$1 AND projid=$2";
  $res = pg_query_params($sql, array($snuuid, $projid));
  return $res;
}

?>
