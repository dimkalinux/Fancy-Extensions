<?php

header("Content-type: text/plain");
header("Pragma: no-cache");

if (!defined('FORUM_ROOT')) {
	define('FORUM_ROOT', '../../');
}

if (!defined('FORUM_ESSENTIALS_LOADED')) {
	require FORUM_ROOT.'include/essentials.php';
}

// Turn off magic_quotes_runtime
if (get_magic_quotes_runtime()) {
	set_magic_quotes_runtime(0);
}

// Strip slashes from GET/POST/COOKIE (if magic_quotes_gpc is enabled)
if (get_magic_quotes_gpc()) {
	function stripslashes_array($array) {
		return is_array($array) ? array_map('stripslashes_array', $array) : stripslashes($array);
	}

	$_GET = stripslashes_array($_GET);
	$_POST = stripslashes_array($_POST);
	$_COOKIE = stripslashes_array($_COOKIE);
}

if (intval($forum_config['o_fancy_tracker_enable_scrape'], 10) === 0) {
	Fancy_Tracker::benc_error('The scrape interface is disabled.');
}

if (!isset($_GET["info_hash"])) {
	Fancy_Tracker::benc_error('Invalid info_hash.');
}

$info_hashes = array();
$querys = explode('&', $_SERVER['QUERY_STRING']);

foreach ($querys as $q) {
	if (substr($q, 0, 10) == 'info_hash=') {
		list(,$_hash) = explode('=', $q);
		//$_hash = forum_trim($_hash);

		if (strlen($_hash) === 20) {
			$_hash = bin2hex($_hash);
		}

		if (Fancy_Tracker::is_info_hash($_hash)) {
			array_push($info_hashes, $_hash);
			continue;
		}
	}
}

$response = array();
$response['flags'] = $response['files'] = array();
foreach ($info_hashes as $hash) {
	// GET TORRENT INFO
	$query = array(
		'SELECT'	=>	't.name, t.completed',
		'FROM'		=>	'torrents AS t',
		'WHERE'		=>	'UPPER(t.info_hash) = UPPER(\''.$forum_db->escape($hash).'\')'
	);
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	if (!$forum_db->num_rows($result)) {
		continue;
	}
	$torrent_info = $forum_db->fetch_assoc($result);
	$torrent_info['seeds'] = $torrent_info['leechers'] = 0;

	// GET TORRENT PEERS INFO
	$peersQuery = array(
		'SELECT'	=>	'p.*',
		'FROM'		=>	'peers AS p',
		'WHERE'		=>	'p.info_hash = \''.$forum_db->escape($hash).'\''
	);

	$peersResult = $forum_db->query_build($peersQuery) or error(__FILE__, __LINE__);
	while ($cur_peer = $forum_db->fetch_assoc($peersResult)) {
		if ($cur_peer['remaining'] == 0) {
			$torrent_info['seeds']++;
		} else {
			$torrent_info['leechers']++;
		}
	}

	$response['files'][Fancy_Tracker::hex2bin($hash)] = array(
		'complete'	=>	$torrent_info['seeds'],
		'downloaded'	=>	$torrent_info['completed'],
		'incomplete'	=>	$torrent_info['leechers'],
		'name'		=>	$torrent_info['name'],
	);

}

// ADD FLAGS
$response['flags']['min_request_interval'] = intval($forum_config['o_fancy_tracker_announce_interval'], 10)*2;

$forum_db->close();

exit(Fancy_Tracker::benc_encode($response));

?>
