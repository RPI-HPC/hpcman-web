<?php
// Password related utilities for HPCMan web agents

include_once('functions/common.php');

/*
 *  Generic Password generation, returns password
*/
function generate_password($length=8, $strength=9) {
    $vowels = 'aeuy';
    $consonants = 'bdghjmnpqrstvz';

    if ($strength & 1) {
        $consonants .= 'BDGHJLMNPQRSTVWXZ';
    }
    if ($strength & 2) {
        $vowels .= "AEUY";
    }
    if ($strength & 4) {
        $consonants .= '23456789';
    }
    if ($strength & 8) {
        $consonants .= '@#$%';
    }

    $password = '';
    $alt = time() % 2;
    for ($i = 0; $i < $length; $i++) {
        if ($alt == 1) {
            $password .= $consonants[(rand() % strlen($consonants))];
            $alt = 0;
        } else {
            $password .= $vowels[(rand() % strlen($vowels))];
            $alt = 1;
        }
    }
    // add a number, there has to be a number
    $password .= rand(1, 9);

    return $password;
}

/*
 * hpcman_hash(string algorithm, string strToHash)
 *  Hash the given string using the given algorithm and return it,
 *  return false on failure.
 */
function hpcman_hash($algo, $p) {
  switch($algo) {
  case'cleartext':
    return $p;
    break;
  case 'crypt':
    return base64_encode(crypt($p, $p));
    break;
  case 'md5':
    return md5($p);
    break;
  case 'ssha':
    $salt = "CCCC";
    return base64_encode(pack("H*", sha1($p.$salt)).$salt);
    break;
  }

  return false;
}

/*
 * match_current_passwd(int puuid, string password)
 * Return true if given password matches the stored hash of the given type
 */
function match_current_passwd($puuid, $given) {
  $sql = "SELECT passwordtype, password FROM passwords WHERE puuid=$1";
  $res = pg_query_params($sql, array($puuid));

  while($tuple = pg_fetch_assoc($res)) { 
    if(hpcman_hash($tuple['passwordtype'], $given) == $tuple['password'])
      return true;
  }
  
  return false;
}

/*
 * do_change_password(int puuid, resource& error)
 *  Given a puuid, read from $_REQUEST the current and new passwords,
 *   check password fitness, make the change
 */
function do_change_password($puuid, &$error, $set_force_reset=false) {
  global $_min_passwd_len;
  global $_passwd_numbers;
  global $_passwd_uperlower;
  global $_passwd_chars;

  if($puuid === '') {
    $error = "No PUUID given";
    return false;
  }

  if($_REQUEST['p1'] === $_REQUEST['p2']) {
    $temparr = array();
    $p = $_REQUEST['p1'];

    // do strength test here
    if(strlen($p) < $_min_passwd_len) {
      $error = "Password too short";
      return false;
    }
    if($_passwd_numbers && !preg_match('/[0-9]/', $p)) {
      $error = "Password must contain one or more numbers";
      return false;
    }
    if($_passwd_uperlower && 
    	(!preg_match('/[A-Z]/', $p) || !preg_match('/[a-z]/', $p))) {
      $error = "Password must contain both upper case and lower case";
      return false;
    }
    if($_passwd_chars && 
    	(preg_match_all('/[A-Za-z0-9]/', $p, &$temparr) == strlen($p))) {
      $error = "Password must contain non-alphanumeric characters";
      return false;
    }

    // we got here, so update password
    $s = "SELECT distinct passwordtype from password_types";
    $res = pg_query($s);
    if (!$res)  {
      $error = "DB Error";
      return false;
    }
    $hashes = pg_fetch_all($res);
    pg_free_result($res);

    hpcman_log("Updating ".count($hashes)." password hashes for puuid ".$puuid);

    if (!pg_query("BEGIN")) {
      $error = "Could not begin transaction";
      return false;
    }

    foreach($hashes as $hash) {
      $s = "UPDATE passwords SET password=$1 
	WHERE puuid=$2 AND passwordtype=$3";
      $passwd = hpcman_hash($hash['passwordtype'], $p);
      $result = pg_query_params($s, array($passwd, $puuid, $hash['passwordtype']));
      if (!$result) {
        $error = "DB Error";
        return false;
      }

      $acount = pg_affected_rows($result);

      if ($acount > 1) {
        $error = "Error: Too many rows";
        if(!pg_query("ROLLBACK")) {
          $error .= ", rollback failed";
        }
        return false;
      } else if ($acount < 1) {
        $error = "Error: Not enough rows";
        if(!pg_query("ROLLBACK")) {
          $error .= ", rollback failed";
        }
        return false;
      }
    }

    if (!pg_query("COMMIT")) {
      $error = "DB Error: Commit failed";
      return false;
    }

    if($set_force_reset) {
      $sql = "UPDATE authenticators SET mustchange='t' 
	WHERE puuid=$1 AND authid=0";
      if(!pg_query_params($sql, array($puuid))) {
	$error = "DB Error";
        return false;
      }
    } else {
      $sql = "UPDATE authenticators SET mustchange='f'
        WHERE puuid=$1 AND authid=0";
      if(!pg_query_params($sql, array($puuid))) {
        $error = "DB Error";
        return false;
      }
    }
  } else {
    $error = "Passwords do not match";
    return false;
  }
  return true;
}

?>
