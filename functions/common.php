<?php

function sysadmin_contact_msg($code)
{
  return "Please contact the system administrator and include this ID: $code";
}

function hpcman_log($msg)
{
  $t = time();
  srand($t);
  $code = substr($t, -4) . '-' . rand(10, 99);

  error_log("HPCman: $msg ($code)");

  return $code;
}

function hpcman_warn($msg)
{
  return hpcman_log('WARN: ' . $msg);
}

function hpcman_error($msg)
{
  return hpcman_log('ERROR: ' . $msg);
}

?>
