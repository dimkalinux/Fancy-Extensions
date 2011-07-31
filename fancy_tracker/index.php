<?php

if (!defined('FORUM_ROOT'))
	define('FORUM_ROOT', '../../');

require FORUM_ROOT.'include/common.php';
require FORUM_ROOT.'lang/'.$forum_user['language'].'/common.php';
require FORUM_ROOT.'lang/'.$forum_user['language'].'/post.php';

if ($forum_user['language'] != 'English' && file_exists(FORUM_ROOT.'extensions/fancy_tracker/lang/'.$forum_user['language'].'/fancy_tracker.php')) {
	require FORUM_ROOT.'extensions/fancy_tracker/lang/'.$forum_user['language'].'/fancy_tracker.php';
} else {
	require FORUM_ROOT.'extensions/fancy_tracker/lang/English/fancy_tracker.php';
}

$query = array(
	'SELECT'	=> 'COUNT(*) AS enabled',
	'FROM'		=> 'extensions',
	'WHERE'		=> 'id=\'fancy_tracker\' AND disabled=0'
);
$result = $forum_db->query_build($query) or Fancy_Tracker::benc_error('Unable to check for extension.');

if ($forum_db->result($result) != '1') {
	message($lang_common['Bad request']);
}

if ($forum_user['g_use_tracker'] == '0') {
	message($lang_common['No view']);
}

$action = isset($_GET['action']) ? forum_trim($_GET['action']) : FALSE;

if ($action == 'get') {
	$info_hash = isset($_GET['hash']) ? forum_trim($_GET['hash']) : '';

	if (!Fancy_Tracker::is_info_hash($info_hash)) {
		message($lang_common['Bad request']);
	}

	if (!file_exists(FORUM_ROOT.'extensions/fancy_tracker/torrents/'.$info_hash.'.torrent')) {
		message($lang_tracker['File not exists']);
	}

	$query = array(
		'SELECT'	=> 't.name',
		'FROM'		=> 'torrents AS t',
		'WHERE'		=> 'UPPER(t.info_hash) = UPPER(\''.$forum_db->escape($info_hash).'\')'
	);
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	$name = $forum_db->result($result);

	if (is_null($name) || $name === false) {
		message($lang_common['Bad request']);
	}

	if (strlen($forum_user['passkey']) != 32) {
		$forum_user['passkey'] = md5($forum_user['salt'].$forum_user['id'].time().$forum_user['username'].$forum_user['password']);

		$query = array(
			'UPDATE'	=> 'users',
			'SET'		=> 'passkey=\''.$forum_db->escape($forum_user['passkey']).'\'',
			'WHERE'		=> 'id='.$forum_user['id']
		);
		$forum_db->query_build($query) or error(__FILE__, __LINE__);
	}

	$torrent = Fancy_Tracker::benc_decode(file_get_contents(FORUM_ROOT.'extensions/fancy_tracker/torrents/'.$info_hash.'.torrent'));
	$torrent['announce'] = forum_link($forum_url['announce'], $forum_user['passkey']);

	// RETRACKER.LOCAL
	if ($forum_config['o_fancy_tracker_use_retracker'] == '1') {
		$torrent['announce-list'] = array(
			array(
				forum_link($forum_url['announce'], $forum_user['passkey']),
				'http://retracker.local/announce',
			),
		);
	}

	// End the transaction
	$forum_db->end_transaction();

	$forum_db->close();

	// SEND
	header('Content-Type: application/x-bittorrent');
	header("Pragma: public");
  	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header('Content-Disposition: attachment; filename="'.htmlspecialchars($name, ENT_QUOTES).'.torrent"');
	exit(Fancy_Tracker::benc_encode($torrent));
}

// If we end up here, the script was called with some wacky parameters
message($lang_common['Bad request']);

?>
