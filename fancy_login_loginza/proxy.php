<?php

define('FORUM_QUIET_VISIT', 1);

if (!defined('FORUM_ROOT')) {
	define('FORUM_ROOT', './../../');
}

if (!defined('FORUM_SKIP_CSRF_CONFIRM')) {
	define('FORUM_SKIP_CSRF_CONFIRM', 1);
}

require FORUM_ROOT.'include/common.php';

$fancy_login_loginza = new Fancy_login_loginza;
$fancy_login_loginza->do_answer_from_server();

?>
