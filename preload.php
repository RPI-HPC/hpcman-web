<?php
/*
 *  Simple web ui to HPCMan user manager - 2007 AAT
 *  This is NOT meant to be used in any kind of permanent installation
 *   or meant to be even remotely secure!
 */
require_once('globals.inc.php');
require_once('hpcpasswd.inc.php');
require_once('functions.inc.php');
require_once('functions/request.php');
require_once('functions/preload.php');

$dbh = pg_connect($_dbconnstr) or die('Could not connect to DB, giving up.');

session_start();

//
//  follow very simple action -> content model
//
/*
if(!isset($_SESSION['snuuid']) && $_REQUEST['action'] !== "do_choose_site")
  $action = 'choose_site';
elseif(!isset($_REQUEST['action']))
  $action = 'choose_site_action';
else
*/

  $action = get_request_field('action');

  global $_default_snuuid;
  if(!isset($_SESSION['snuuid']) && isset($_default_snuuid))
    $action = 'do_choose_site';

  if (!isset($action)) {
    $action = '';
  }

$preload_success = true;

switch($action) {
  case 'logout':
    if (isset($_COOKIE[session_name()])) {
      setcookie(session_name(), '', time()-42000, '/');
    }
    session_destroy();
    break;

  case 'do_choose_site':
    if(!do_choose_site()) echo "Internal error, bailing out";
    break;

  case 'edit_group_membership':
    // do some kind of group name validation here
    if(!do_edit_group_membership($_SESSION['snuuid'], get_request_field('groupname'))) {
      $action = 'print_edit_group_membership';
    } else {
      $action = 'print_edit_group_membership';
    }
    break;

  case 'do_vs_group_membership':
    // do some kind of name validation here
    do_vs_group_membership(get_request_field('snuuid'), get_request_field('vsid'));
    $action = 'print_vs_group_membership';
    break;

  case 'add_group':
    // TODO: groupname validation/syntax check
    do_add_group($_SESSION['snuuid'], get_request_field('groupname'));
    break;

  case 'do_choose_vsite';
    do_choose_vsite($_SESSION['snuuid']);
    $action = '';
    break;

  case 'do_add_edit_site':
    if(do_add_edit_site())
      $action = 'manage_sites';
    else
      $action = 'add_edit_site';
    break;

  case 'choose_site_action':
    break;

  case 'do_create_edit_principal':
    $preload_success = do_create_edit_principal();
    break;

  case 'do_add_edit_fs':
    if(!do_add_edit_fs($_SESSION['snuuid'], $_SESSION['error']))
      $action='print_edit_fs';
    else
      $action='print_manage_fs';
    break;

  case 'do_add_edit_project':
    if(do_add_edit_project($_SESSION['snuuid'],
		           get_request_field('projid'),
		           get_request_field('projcpuquota'))) {
      $_SESSION['error'] = "Project Updated";
      $action = 'print_site_projects';
    } else $action = 'print_edit_project';
    break;

  case 'do_manage_project_parent':
    $share = get_request_field('parentshare');
    if($share > 1 || $share <= 0) {
      $_SESSION['error'] = "Parent share must be > 0 and <= 1";
      $action = 'print_edit_project';
      break;
    }
    if(do_manage_project_parent($_SESSION['snuuid'])) {
      $_SESSION['error'] = "Project parent updated or added";
      $action = 'print_edit_project';
    } else {
      $_SESSION['error'] = "Update failed: ".pg_last_error();
      if(get_request_field('projid') == get_request_field('select_project_argument')) {
          $_SESSION['error'] = 'Cannot set project owner to self';
      }
      $action = 'print_manage_project_parent';
    }
    break;

  case 'do_change_password':
    $preload_success = do_change_password(get_request_field('puuid'), $err, true);
    if($preload_success) {
      $_SESSION['error'] = "Password Updated";
    } else {
      $_SESSION['error'] = $err;
    }
    break;

  case 'do_edit_user_account':
    $preload_success = do_edit_user_account(get_request_field('snuuid'), get_request_field('username'));
    break;

  case 'do_add_user_account':
    $preload_success = do_add_user_account($_SESSION['snuuid'],
                                           get_request_field('username'),
                                           get_request_field('puuid'),
                                           get_request_field('groupname'),
                                           get_request_field('homedirectory'),
                                           get_request_field('quota'),
                                           get_request_field('shell'),
                                           get_request_field('projid'),
                                           get_request_field('vsids'));
    break;

  case 'do_add_edit_vsite':
    $preload_success = do_add_edit_vsite($_SESSION['snuuid'],
                                         get_request_field('vsid'),
                                         get_request_field('dbuser'),
                                         get_request_field('vsname'),
                                         get_request_field('description'));
    break;

  case 'do_add_edit_tag':
    $preload_success = do_add_edit_tag(get_request_field('tag'),
                                       get_request_field('description'),
                                       get_request_field('original_tag'));
    if ($preload_success) {
      $_SESSION['error'] = "Tag added/updated successfully.";
      $action = 'print_view_edit_tags';
    } else {
      $_SESSION['error'] = "Tag add/update failed.";
    }
    break;

  case 'do_remove_tag':
    $preload_success = do_remove_tag(get_request_field('tag'));
    if ($preload_success) {
      $action = 'print_view_edit_tags';
      $_SESSION['error'] = "Tag removed successfully.";
    } else {
      $_SESSION['error'] = "Unable to remove tag.";
    }
}
?>
