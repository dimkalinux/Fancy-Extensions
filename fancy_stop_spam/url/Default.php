<?php

// Make sure no one attempts to run this script "directly"
if (!defined('FORUM')) {
	exit;
}

$forum_url['fancy_stop_spam_profile_section']           = 'profile.php?section=fancy_stop_spam_profile_section&amp;id=$1';

$forum_url['fancy_stop_spam_admin_section']             = 'extensions/fancy_stop_spam/admin.php';
$forum_url['fancy_stop_spam_admin_logs']                = 'extensions/fancy_stop_spam/admin.php?section=logs';
$forum_url['fancy_stop_spam_admin_new_users']           = 'extensions/fancy_stop_spam/admin.php?section=new_users';
$forum_url['fancy_stop_spam_admin_suspicious_users']    = 'extensions/fancy_stop_spam/admin.php?section=suspicious_users';
