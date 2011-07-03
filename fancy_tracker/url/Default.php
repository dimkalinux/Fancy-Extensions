<?php

// Make sure no one attempts to run this script "directly"
if (!defined('FORUM')) {
	exit;
}

$forum_url['torrents-upload']		= 'extensions/'.$ext_info['id'].'/index.php?action=upload&amp;fid=$1';
$forum_url['torrents-get']			= 'extensions/'.$ext_info['id'].'/index.php?action=get&amp;hash=$1';
$forum_url['announce']				= 'extensions/'.$ext_info['id'].'/announce.php?passkey=$1';
$forum_url['user-torrents-search']	= 'search.php?action=show_user_torrents&amp;user_id=$1';
