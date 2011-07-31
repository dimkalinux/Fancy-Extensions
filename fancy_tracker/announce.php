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


// Error: no web browsers allowed
if (!isset($_GET["info_hash"]) || !isset($_GET["peer_id"])) {
	header("HTTP/1.0 400 Bad Request");
	die("This file is for BitTorrent clients.\n");
}


$query = array(
	'SELECT'	=> 'COUNT(*) AS enabled',
	'FROM'		=> 'extensions',
	'WHERE'		=> 'id=\'fancy_tracker\' AND disabled=0'
);
$result = $forum_db->query_build($query) or Fancy_Tracker::benc_error('Unable to check for extension.');

if ($forum_db->result($result) != '1') {
	Fancy_Tracker::benc_error('The tracker extension is not installed.');
}

$now = time();

$fields = array(
	'passkey'	=> array(
		'default'	=> 'FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF'
	),
	'info_hash'	=> array(
		'default'	=> FALSE
	),
	'peer_id'	=> array(
		'default'	=> FALSE
	),
	'event'		=> array(
		'default'	=> 'announce'
	),
	'ip'		=> array(
		'default'	=> $_SERVER['REMOTE_ADDR'],
		'alternatives'	=> array('ipv4', 'localip')
	),
	'port'		=> array(
		'default'	=> FALSE
	),
	'downloaded'	=> array(
		'default'	=> FALSE
	),
	'uploaded'	=> array(
		'default'	=> FALSE
	),
	'left'		=> array(
		'default'	=> FALSE
	),
	'num_want'	=> array(
		'default'	=> intval($forum_config['o_fancy_tracker_peer_request_count'], 10),
		'alternatives'	=> array('num want', 'numwant')
	),
);

$length = count($fields);
$keys = array_keys($fields);

for ($i = 0; $i < $length; $i++) {
	$key = $keys[$i];
	if (!isset($_GET[$keys[$i]]) && isset($fields[$keys[$i]]['alternatives'])) {
		foreach ($fields[$keys[$i]]['alternatives'] as $key) {
			if (isset($_GET[$key])) {
				break;
			}
		}
	}

	if (!isset($_GET[$key])) {
		$fields[$keys[$i]] = $fields[$keys[$i]]['default'];
		continue;
	}

	$fields[$keys[$i]] = $_GET[$key];
}


$fields['info_hash'] = bin2hex($fields['info_hash']);
$fields['peer_id'] = bin2hex($fields['peer_id']);

if ($forum_config['o_fancy_tracker_allow_submit_ip'] == '0') { // OR the IP is a local IP
	$fields['ip'] = forum_trim($_SERVER['REMOTE_ADDR']);
}

$fields['agent'] = forum_trim($_SERVER['HTTP_USER_AGENT']);

foreach ($fields as $key => $value) {
	if ($value !== FALSE) {
		continue;
	}
	Fancy_Tracker::benc_error('Required field \''.$key.'\' is empty.');
}


if (strlen($fields['passkey']) != 32) {
	Fancy_Tracker::benc_error('Invalid passkey length of '.strlen($fields['passkey']).', should be 32: '.forum_htmlencode($fields['passkey']));
}

foreach (array('info_hash', 'peer_id') as $key) {
	if (strlen($fields[$key]) != 40) {
		Fancy_Tracker::benc_error('Invalid '.$key.' length of '.strlen($fields[$key]).', should be 40: '.forum_htmlencode($fields[$key]));
	}
}

foreach (array('uploaded', 'downloaded', 'left', 'port', 'num_want') as $key) {
	if (!is_numeric($fields[$key])) {
		Fancy_Tracker::benc_error('Invalid key, '.$key.' must be numeric (not \''.forum_htmlencode($fields[$key]).'\').');
	}
}

if ($fields['port'] < 0 || $fields['port'] > 0xFFFF) {
	Fancy_Tracker::benc_error('Invalid port '.$fields['port'].'.');
}

if ($fields['num_want'] > intval($forum_config['o_fancy_tracker_peer_request_count'], 10)) {
	$fields['num_want'] = intval($forum_config['o_fancy_tracker_peer_request_count'], 10);
}

if (!preg_match('/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/', $fields['ip']) && !preg_match('/^((([0-9A-Fa-f]{1,4}:){7}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}:[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){5}:([0-9A-Fa-f]{1,4}:)?[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){4}:([0-9A-Fa-f]{1,4}:){0,2}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){3}:([0-9A-Fa-f]{1,4}:){0,3}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){2}:([0-9A-Fa-f]{1,4}:){0,4}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(([0-9A-Fa-f]{1,4}:){0,5}:((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(::([0-9A-Fa-f]{1,4}:){0,5}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|([0-9A-Fa-f]{1,4}::([0-9A-Fa-f]{1,4}:){0,5}[0-9A-Fa-f]{1,4})|(::([0-9A-Fa-f]{1,4}:){0,6}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){1,7}:))$/', $fields['ip'])) {
	Fancy_Tracker::benc_error('Invalid IP address \''.forum_htmlencode($fields['ip']).'\'.');
}

$query = array(
	'SELECT'	=> 'u.*, g.*',
	'FROM'		=> 'users AS u',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'groups AS g',
			'ON'			=> 'g.g_id=u.group_id'
		)
	),
	'WHERE'		=> 'UPPER(u.passkey) = UPPER(\''.$forum_db->escape($fields['passkey']).'\') AND g.g_use_tracker = 1'
);

$result = $forum_db->query_build($query) or Fancy_Tracker::benc_error('Unable to fetch user information.');
$forum_user = $forum_db->fetch_assoc($result);

if (!$forum_user) {
	Fancy_Tracker::benc_error('Invalid passkey.');
}

$query = array(
	'SELECT'	=> 't.*',
	'FROM'		=> 'torrents AS t',
	'WHERE'		=> 'UPPER(t.info_hash) = UPPER(\''.$forum_db->escape($fields['info_hash']).'\')'
);

$result = $forum_db->query_build($query) or Fancy_Tracker::benc_error('Unable to fetch torrent information.');
$torrent = $forum_db->fetch_assoc($result);

if (!$torrent) {
	Fancy_Tracker::benc_error('Torrent not registered with this tracker.');
}


$query = array(
	'SELECT'	=> 'p.*',
	'FROM'		=> 'peers AS p',
	'WHERE'		=> 'UPPER(p.info_hash) = UPPER(\''.$forum_db->escape($fields['info_hash']).'\') AND UPPER(p.peer_id) = UPPER(\''.$forum_db->escape($fields['peer_id']).'\')'
);

$result = $forum_db->query_build($query) or Fancy_Tracker::benc_error('Unable to fetch peer information.');
$peer = $forum_db->fetch_assoc($result);

if ($peer) {
	$uploaded = max(0, $fields['uploaded'] - $peer['uploaded']);
	$downloaded = max(0, $fields['downloaded'] - $peer['downloaded']);

	$query = array(
		'UPDATE'	=> 'users',
		'SET'		=> 'uploaded = uploaded + '.$uploaded.', downloaded = downloaded + '.$downloaded,
		'WHERE'		=> 'id = '.$forum_user['id']
	);

	$forum_db->query_build($query) or Fancy_Tracker::benc_error('Unable to update users ratio.');

	$query = array(
		'UPDATE'	=> 'torrents',
		'SET'		=> 'uploaded = uploaded + '.$uploaded.', downloaded = downloaded + '.$downloaded,
		'WHERE'		=> 'UPPER(info_hash) = UPPER(\''.$forum_db->escape($fields['info_hash']).'\')'
	);

	$forum_db->query_build($query) or Fancy_Tracker::benc_error('Unable to update torrents ratio.');
}

if ($fields['event'] == 'stopped') {
	if (isset($peer)) {
		$query = array(
			'DELETE'	=> 'peers',
			'WHERE'		=> 'UPPER(info_hash) = UPPER(\''.$forum_db->escape($fields['info_hash']).'\') AND UPPER(peer_id) = UPPER(\''.$forum_db->escape($fields['peer_id']).'\')'
		);

		$forum_db->query_build($query) or Fancy_Tracker::benc_error('Unable to delete finished peer.');
	}
} else {
	if (isset($peer)) {
		$query = array(
			'UPDATE'	=> 'peers',
			'SET'		=> 'uploaded = \''.$fields['uploaded'].'\', downloaded = \''.$fields['downloaded'].'\', remaining = \''.$fields['left'].'\', last_action = \''.$now.'\'',
			'WHERE'		=> 'UPPER(info_hash) = UPPER(\''.$forum_db->escape($fields['info_hash']).'\') AND UPPER(peer_id) = UPPER(\''.$forum_db->escape($fields['peer_id']).'\')'
		);

		$forum_db->query_build($query) or Fancy_Tracker::benc_error('Unable to update peer information.');
	} else {
		$connectable = FALSE;
		if ($handle = @fsockopen($fields['ip'], $fields['port'], $errno, $errstr, 5)) {
			fclose($handle);
			$connectable = TRUE;
		}

		$query = array(
			'INSERT'	=> 'info_hash, user_id, peer_id, ip, port, remaining, started, last_action, agent, connectable',
			'INTO'		=> 'peers',
			'VALUES'	=> '\''.$forum_db->escape($fields['info_hash']).'\', \''.$forum_user['id'].'\', \''.$forum_db->escape($fields['peer_id']).'\', \''.$fields['ip'].'\', \''.$fields['port'].'\', \''.$fields['left'].'\', \''.$now.'\', \''.$now.'\', \''.$forum_db->escape($fields['agent']).'\', \''.($connectable ? '1' : '0').'\''
		);

		$forum_db->query_build($query) or Fancy_Tracker::benc_error('Unable to add new peer.');
	}
}

$completed = '';
if ($fields['event'] == 'completed') {
	$query = array(
		'INSERT'	=> 'info_hash, peer_id, user_id, time, ip',
		'INTO'		=> 'snatches',
		'VALUES'	=> '\''.$forum_db->escape($fields['info_hash']).'\', \''.$forum_db->escape($fields['peer_id']).'\', \''.$forum_user['id'].'\', \''.$now.'\', \''.$fields['ip'].'\''
	);

	$forum_db->query_build($query) or Fancy_Tracker::benc_error('Unable to update snatch list.');
	$completed = ', completed = completed + 1';
}

$query = array(
	'UPDATE'	=> 'torrents',
	'SET'		=> 'last_action = \''.$now.'\''.$completed,
	'WHERE'		=> 'UPPER(info_hash) = UPPER(\''.$forum_db->escape($torrent['info_hash']).'\')'
);

$forum_db->query_build($query) or Fancy_Tracker::benc_error('Unable to update torrent status.');

// SWITCH RANDOM FUNCTIONS
switch ($db_type) {
	case 'mysql':
	case 'mysqli':
	case 'mysql_innodb':
	case 'mysqli_innodb':
	case 'pgsql':
		$random_fn = 'RAND()';
		break;

	case 'sqlite':
	case 'sqlite3':
		$random_fn = 'random()';
		break;

	default:
		$random_fn = 'RAND()';
}

$query = array(
	'SELECT'	=> 'p.peer_id, p.ip, p.port, p.remaining',
	'FROM'		=> 'peers AS p',
	'WHERE'		=> 'UPPER(p.info_hash) = UPPER(\''.$forum_db->escape($fields['info_hash']).'\') AND UPPER(p.peer_id) != UPPER(\''.$forum_db->escape($fields['peer_id']).'\')',
	'ORDER BY'	=> $random_fn,
	'LIMIT'		=> $fields['num_want']
);

$result = $forum_db->query_build($query) or Fancy_Tracker::benc_error('Unable to fetch list of peers.');

$peers = array();
$seeders = $leechers = 0;
while ($cur_peer = $forum_db->fetch_assoc($result)) {
	$peers[] = array(
		'ip'		=> $cur_peer['ip'],
		'peer id'	=> str_pad(Fancy_Tracker::hex2bin($cur_peer['peer_id']), 20),
		'port'		=> intval($cur_peer['port'], 10),
	);

	// GET num SEED and LEECHERS
	if ($cur_peer['remaining'] == 0) {
		$seeders++;
	} else {
		$leechers++;
	}
}

// End the transaction
$forum_db->end_transaction();

// LAST QUERY
$forum_db->close();


$response = Fancy_Tracker::benc_encode(array(
	'complete'		=> $seeders,
	'incomplete'	=> $leechers,
	'interval'		=> intval($forum_config['o_fancy_tracker_announce_interval'], 10),
	'peers'			=> $peers,
));

exit($response);
