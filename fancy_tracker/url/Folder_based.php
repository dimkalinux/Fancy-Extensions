<?php

// Make sure no one attempts to run this script "directly"
if (!defined('FORUM')) {
	exit;
}

$forum_url['torrents-upload']	= 'new/torrent/$1/';
$forum_url['torrents-get']		= 'get/torrent/$1/';
$forum_url['announce']			= 'announce/torrent/$1/';
$forum_url['user-torrents-search']	= 'search/torrents/user/$1/';
