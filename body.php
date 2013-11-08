<?php

include_once('functions/common.php');
include_once('functions/request.php');
include_once('functions/body.php');

unset($_SESSION['error']);

switch($action) {
  case 'logout':
    echo '<a href=\'.\'>Choose a site</a>';
    break;

  case 'do_choose_site':
    break;

  case 'add_person':
    echo print_addedit_principal();
    break;

  case 'edit_principal':
    echo print_addedit_principal(get_request_field('puuid'));
    break;

  case 'choose_site':
    echo print_choose_site();
    break;

  case 'choose_vsite':
    echo print_choose_vsite($_SESSION['snuuid']);
    break;

  case 'choose_vsite_action':
    echo print_choose_vsite_action();
    break;

  case 'print_view_groups':
    echo print_view_groups($_SESSION['snuuid']);
    break;

  case 'print_edit_group_membership':
    echo print_edit_group_membership($_SESSION['snuuid'], get_request_field('groupname'));
    break;

  case 'print_add_group':
    echo print_add_group($_SESSION['snuuid'], get_request_field('groupname'), $_SESSION['sitename']);
    break;

  case 'view_people':
    echo print_site_people($_SESSION['snuuid']);
    break;

  case 'do_create_edit_principal':
    if($preload_success)
      echo "   <h3>Add/Edit Success</h3>\n";
    else
      echo "   <h3>Add/Edit Fail: ".pg_last_error()."</h3>\n";
    break;

  case 'do_add_user_account':
    if($preload_success)
      echo "   <h3>Add/Edit Success</h3>\n";
    else
      echo "   <h3>Add/Edit Fail: ".pg_last_error()."</h3>\n";
    break;

  case 'do_edit_user_account':
    if($preload_success)
      echo "   <h3>Add/Edit Success</h3>\n";
    else
      echo "   <h3>Add/Edit Fail: ".pg_last_error()."</h3>\n";
    break;

  case 'print_view_user_accounts':
    echo print_view_user_accounts($_SESSION['snuuid'], get_request_field('puuid'));
    break;

  case 'print_edit_user_account':
    $puuid = get_request_field('puuid');
    if(isset($puuid))
      echo print_edit_user_account($_SESSION['snuuid'], '', $puuid);
    else
      echo print_edit_user_account($_SESSION['snuuid'], get_request_field('username'));
    break;

  case 'print_change_password':
    echo print_change_password(get_request_field('puuid'));
    break;

  case 'do_change_password':
    echo print_change_password(get_request_field('puuid'));
    break;

  case 'view_vs_users':
    echo print_view_vs_users($_SESSION['snuuid'], $_SESSION['vsid']);
    break;

  case 'view_vs_groups':
    echo print_view_vs_groups($_SESSION['snuuid'], $_SESSION['vsid']);
    break;

  case 'manage_sites':
    echo print_manage_sites();
    break;

  case 'print_view_vsites':
    echo print_view_vsites($_SESSION['snuuid']);
    break;

  case 'print_vs_group_membership':
    $snuuid = get_request_field('snuuid');
    $vsid = get_request_field('vsid');
    echo print_vs_group_membership($snuuid, $vsid);
    break;

  case 'print_add_edit_vsite':
    echo print_add_edit_vsite($_SESSION['snuuid'], get_request_field('vsid'));
    break;

  case 'add_edit_site':
    echo print_add_edit_site(get_request_field('snuuid'));
    break;

  case 'do_add_edit_vsite':
    if($preload_success)
      echo "Add/Update success";
    else
      echo "Add/Update Fail";
    break;

  case 'print_edit_fs':
    echo print_edit_fs($_SESSION['snuuid'], get_request_field('fsname'));
    break;

  case 'print_manage_fs':
    echo print_manage_fs($_SESSION['snuuid']);
    break;

  case 'print_site_projects':
    echo print_site_projects($_SESSION['snuuid'], get_request_field('projid', -1));
    break;

  case 'print_edit_project':
    $proj = get_request_field('projid');
    if (!isset($proj)) {
      $proj = get_request_field('projname');
    }
    echo print_edit_project($_SESSION['snuuid'], $proj);
    break;

  case 'manage_project_membership':
    echo print_manage_project_membership($_SESSION['snuuid'], get_request_field('projid'));
    break;

  case 'view_cputime':
    echo view_cputime($_SESSION['snuuid'], get_request_field('username'));
    break;

  case 'view_cputime_csv':
    echo view_cputime_csv($_SESSION['snuuid'], get_request_field('username'));
    break;

  case 'print_select_project':
    echo print_select_project($_SESSION['snuuid'], get_request_field('target'));
    break;

  case 'print_manage_project_parent':
    $projid = get_request_field('projid');
    if($projid == get_request_field('select_project_argument')) {
      echo print_edit_project($_SESSION['snuuid'], $projid);
    } else {
      echo print_manage_project_parent($_SESSION['snuuid']);
    }
    break;

  case 'print_projects_by_tag':
    $tag = get_request_field('tag');
    print_projects_by_tag($_SESSION['snuuid'], $tag);
    break;

  case 'print_view_edit_tags':
    print_view_edit_tags();
    break;

  case 'print_add_edit_tag':
    $tag = get_request_field('tag');
    print_add_edit_tag($tag);
    break;

  case '':
    echo 'No action selected. Please use the menu to begin an action.';
    break;

  default:
    $code = hpcman_warn("Unknown action: $action");
    echo 'Unknown action! ' . sysadmin_contact_msg($code);
}
?>
