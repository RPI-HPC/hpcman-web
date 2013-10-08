<?php

$_dbconnstr = 'host=localhost port=5432 dbname=hpcman user=todora password=405jordan';

$__year = (date('n') >= 7) ? date('Y') + 1 : date('Y');
$_default_account_expiration = "{$__year}0630 23:59:59"; 

// uncomment to enforce default site
//$_default_snuuid = 1; // always assume that we're dealing with this site

$_min_passwd_len = 7; // minimum password length
$_passwd_numbers = true; // passwords must contain numbers
$_passwd_uperlower = true; // passwords must contain upper case and lower case
$_passwd_chars = true; // passwords must contain special characters

$_hirearchy_cardinality=1; // maximum number of parents a project may have

$_default_user_quota = 5; // default quota in G for users

$_default_home_directory_path = "/gpfs/small/PROJECT/home/USER";

$__debug = false;  // toggle session debugging

?>
