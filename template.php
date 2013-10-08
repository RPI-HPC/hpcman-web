<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>
  <head>
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <script src="js/sorttable.js" type="text/javascript"></script>
    <script src="/static/js/jquery.js" type="text/javascript"></script>
    <script src="/static/js/jquery-ui.js" type="text/javascript"></script>
    <script src="js/hpcman.js" type="text/javascript"></script>
    <link rel="stylesheet" type="text/css" href="/static/css/smoothness/jquery-ui.css">

    <title>HPC-MANager Web UI</title>
  </head>
  <body>
    <div id="header">
      <h1>HPC-MANager Web UI</h1>
      <div id="site-selector">
        <div>
          <h3>Site: <?php hpcman_print($_SESSION['sitename'], '(not selected)'); ?></h3>
          <sup><a href='?action=choose_site'>change</a></sup>
        </div>

        <div>
          <h3>Project: <?php hpcman_print($_SESSION['projname'], '(not selected)'); ?></h3>
          <sup><a href='?action=choose_project'>change</a></sup>
        </div>

        <div>
          <h3>Virtual site: <?php hpcman_print($_SESSION['vsname'], '(not selected)'); ?></h3>
          <sup><a href='?action=choose_vsite'>change</a></sup>
        </div>
      </div>
      <?php if($__debug) { print_r($_SESSION); print_r($_REQUEST); } ?>
      <div id="notification-box">
        <div id="notification">
          <?php hpcman_print($_SESSION['error'], ''); ?>
        </div>
      </div>
    </div>
    <table>
      <tr>
        <td id="menu">
          <!-- begin menu -->
          <?php require_once('menu.php'); ?>
          <!-- end menu -->
        </td>
        <td>
          <table align='center'>
            <tr>
              <td>
                <!-- begin body -->
                <?php require_once('body.php'); ?>
                <!-- end body -->
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </body>
</html>
