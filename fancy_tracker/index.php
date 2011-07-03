<?php

if (!defined('FORUM_ROOT'))
	define('FORUM_ROOT', '../../');

require_once FORUM_ROOT.'include/common.php';
require_once FORUM_ROOT.'lang/'.$forum_user['language'].'/common.php';

$query = array(
	'SELECT'	=>	'1',
	'FROM'		=>	'extensions',
	'WHERE'		=>	'id = \'fancy_tracker\' AND disabled = 0'
);
$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

if (!$forum_db->num_rows($result)) {
	message($lang_common['Bad request']);
}

if ($forum_user['g_use_tracker'] == '0') {
	message($lang_common['No view']);
}

// Load the post.php language file
require_once FORUM_ROOT.'lang/'.$forum_user['language'].'/post.php';

if (file_exists(FORUM_ROOT.'extensions/fancy_tracker/lang/'.$forum_user['language'].'/fancy_tracker.php')) {
	require FORUM_ROOT.'extensions/fancy_tracker/lang/'.$forum_user['language'].'/fancy_tracker.php';
} else {
	require FORUM_ROOT.'extensions/fancy_tracker/lang/English/fancy_tracker.php';
}

$action = isset($_GET['action']) ? forum_trim($_GET['action']) : FALSE;

if ($action == 'get') {
	$info_hash = isset($_GET['hash']) ? forum_trim($_GET['hash']) : '';

	if (!Fancy_Tracker::is_info_hash($info_hash) || !file_exists(FORUM_ROOT.'extensions/fancy_tracker/torrents/'.$info_hash.'.torrent')) {
		message($lang_common['Bad request']);
	}

	$query = array(
		'SELECT'	=>	't.name',
		'FROM'	=>	'torrents AS t',
		'WHERE'	=>	'UPPER(t.info_hash) = UPPER(\''.$forum_db->escape($info_hash).'\')'
	);
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	if (!$forum_db->num_rows($result)) {
		message($lang_common['Bad request']);
	}

	$name = $forum_db->result($result);

	if (utf8_strlen($forum_user['passkey']) != 32) {
		$forum_user['passkey'] = md5($forum_user['salt'].$forum_user['id'].time().$forum_user['username'].$forum_user['password']);

		$query = array(
			'UPDATE'	=>	'users',
			'SET'	=>	'passkey = \''.$forum_db->escape($forum_user['passkey']).'\'',
			'WHERE'	=>	'id = '.$forum_user['id']
		);
		$forum_db->query_build($query) or error(__FILE__, __LINE__);
	}

	$torrent = Fancy_Tracker::benc_decode(file_get_contents(FORUM_ROOT.'extensions/fancy_tracker/torrents/'.$info_hash.'.torrent'));
	$torrent['announce'] = forum_link($forum_url['announce'], $forum_user['passkey']);

	// RETRACKER.LOCAL
	if (intval($forum_config['o_fancy_tracker_use_retracker'], 10) === 1) {
		$torrent['announce-list'] = array(
			array(
				forum_link($forum_url['announce'], $forum_user['passkey']),
				'http://retracker.local/announce',
			),
		);
	}

	$forum_db->close();

	// SEND
	header('Content-Type: application/x-bittorrent');
	header("Pragma: public");
  	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header('Content-Disposition: attachment; filename="'.htmlspecialchars($name, ENT_QUOTES).'.torrent"');
	exit(Fancy_Tracker::benc_encode($torrent));
} else if ($action == 'upload') {
	($hook = get_hook('po_start')) ? eval($hook) : null;

	if ($forum_user['g_upload_torrents'] == '0') {
		message($lang_common['Bad request']);
	}

	$tid = isset($_GET['tid']) ? intval($_GET['tid']) : 0;
	$fid = isset($_GET['fid']) ? intval($_GET['fid']) : 0;
	if ($fid < 1) {
		message($lang_common['Bad request']);
	}

	// GET INFO ABOUT FORUM
	$query = array(
		'SELECT'	=> 'f.id, f.forum_name, f.moderators, f.redirect_url, fp.post_replies, fp.post_topics',
		'FROM'		=> 'forums AS f',
		'JOINS'		=> array(
			array(
				'LEFT JOIN'		=> 'forum_perms AS fp',
				'ON'			=> '(fp.forum_id=f.id AND fp.group_id='.$forum_user['g_id'].')'
			)
		),
		'WHERE'		=> '(fp.read_forum IS NULL OR fp.read_forum=1) AND f.id='.$fid
	);

	($hook = get_hook('po_qr_get_forum_info')) ? eval($hook) : null;

	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	if (!$forum_db->num_rows($result)) {
		message($lang_common['Bad request']);
	}

	$cur_posting = $forum_db->fetch_assoc($result);
	$is_subscribed = FALSE;

	// Is someone trying to post into a redirect forum?
	if ($cur_posting['redirect_url'] != '') {
		message($lang_common['Bad request']);
	}

	// Sort out who the moderators are and if we are currently a moderator (or an admin)
	$mods_array = ($cur_posting['moderators'] != '') ? unserialize($cur_posting['moderators']) : array();
	$forum_page['is_admmod'] = ($forum_user['g_id'] == FORUM_ADMIN || ($forum_user['g_moderator'] == '1' && array_key_exists($forum_user['username'], $mods_array))) ? true : false;

	($hook = get_hook('po_pre_permission_check')) ? eval($hook) : null;

	// Do we have permission to post?
	if ((($fid && (($cur_posting['post_topics'] == '' && $forum_user['g_post_topics'] == '0') || $cur_posting['post_topics'] == '0')) ||
		(isset($cur_posting['closed']) && $cur_posting['closed'] == '1')) && !$forum_page['is_admmod']) {
		message($lang_common['No permission']);
	}

	($hook = get_hook('po_posting_location_selected')) ? eval($hook) : null;

	// Start with a clean slate
	$errors = array();

	if (isset($_POST['form_sent'])) {
		($hook = get_hook('po_form_submitted')) ? eval($hook) : null;

		// Make sure form_user is correct
		if (($forum_user['is_guest'] && $_POST['form_user'] != 'Guest') || (!$forum_user['is_guest'] && $_POST['form_user'] != $forum_user['username'])) {
			message($lang_common['Bad request']);
		}

		// Flood protection
		if (!isset($_POST['preview']) && $forum_user['last_post'] != '' && (time() - $forum_user['last_post']) < $forum_user['g_post_flood'] && (time() - $forum_user['last_post']) >= 0) {
			$errors[] = sprintf($lang_post['Flood'], $forum_user['g_post_flood']);
		}

		$subject = forum_trim($_POST['req_subject']);

		if ($subject == '')
			$errors[] = $lang_post['No subject'];
		else if (utf8_strlen($subject) > 70)
			$errors[] = $lang_post['Too long subject'];
		else if ($forum_config['p_subject_all_caps'] == '0' && utf8_strtoupper($subject) == $subject && !$forum_page['is_admmod'])
			$errors[] = $lang_post['All caps subject'];

		// If the user is logged in we get the username and e-mail from $forum_user
		if (!$forum_user['is_guest']) {
			$username = $forum_user['username'];
			$email = $forum_user['email'];
		} else {
			$errors[] = $lang_common['No permission'];
		}

		// If the user is logged in we get the username and e-mail from $forum_user
		if (!$forum_user['is_guest'])
		{
			$username = $forum_user['username'];
			$email = $forum_user['email'];
		}
		// Otherwise it should be in $_POST
		else
		{
			$username = forum_trim($_POST['req_username']);
			$email = strtolower(forum_trim(($forum_config['p_force_guest_email'] == '1') ? $_POST['req_email'] : $_POST['email']));

			// Load the profile.php language file
			require FORUM_ROOT.'lang/'.$forum_user['language'].'/profile.php';

			// It's a guest, so we have to validate the username
			$errors = array_merge($errors, validate_username($username));

			if ($forum_config['p_force_guest_email'] == '1' || $email != '')
			{
				if (!defined('FORUM_EMAIL_FUNCTIONS_LOADED'))
					require FORUM_ROOT.'include/email.php';

				if (!is_valid_email($email))
					$errors[] = $lang_post['Invalid e-mail'];

				if (is_banned_email($email))
					$errors[] = $lang_profile['Banned e-mail'];
			}
		}

		// If we're an administrator or moderator, make sure the CSRF token in $_POST is valid
		if ($forum_user['is_admmod'] && (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== generate_form_token(get_current_url()))) {
			$errors[] = $lang_post['CSRF token mismatch'];
		}


		// Clean up message from POST
		$message = forum_linebreaks(forum_trim($_POST['req_message']));

		if (strlen($message) > FORUM_MAX_POSTSIZE_BYTES)
			$errors[] = sprintf($lang_post['Too long message'], forum_number_format(strlen($message)), forum_number_format(FORUM_MAX_POSTSIZE_BYTES));
		else if ($forum_config['p_message_all_caps'] == '0' && utf8_strtoupper($message) == $message && !$forum_page['is_admmod'])
			$errors[] = $lang_post['All caps message'];

		// Validate BBCode syntax
		if ($forum_config['p_message_bbcode'] == '1' || $forum_config['o_make_links'] == '1')
		{
			if (!defined('FORUM_PARSER_LOADED'))
				require FORUM_ROOT.'include/parser.php';

			$message = preparse_bbcode($message, $errors);
		}

		if ($message == '')
			$errors[] = $lang_post['No message'];

		$hide_smilies = isset($_POST['hide_smilies']) ? 1 : 0;
		$subscribe = isset($_POST['subscribe']) ? 1 : 0;

		$now = time();

		if (!isset($_FILES['req_torrent']) || $_FILES['req_torrent']['error'] || !is_uploaded_file($_FILES['req_torrent']['tmp_name'])) {
			$errors[] = $lang_tracker['Error uploading torrent'];
		}

		($hook = get_hook('po_end_validation')) ? eval($hook) : null;

		if (empty($errors)) {
			set_time_limit(180);
			$data = Fancy_Tracker::benc_decode(file_get_contents($_FILES['req_torrent']['tmp_name']));
			@unlink($_FILES['req_torrent']['tmp_name']);

			if (!$data) {
				message($lang_tracker['Bad encoding']);
			}

			foreach (array('piece length', 'pieces', 'name') as $key) {
				if (!isset($data['info'][$key])) {
					message($lang_tracker['Malformed torrent']);
				}
			}

			if ((!isset($data['info']['files']) && !isset($data['info']['length']))) {
				message($lang_tracker['Malformed torrent']);
			}

			$torrent = array(
				'announce'	=>	forum_link($forum_url['announce']),
				'creation date'	=>	time(),
		      		'info'		=>	array(
			      		'piece length'	=>	$data['info']['piece length'],
			      		'pieces'		=>	$data['info']['pieces'],
			      		'private'		=>	'1',
			      		'name'		=>	$data['info']['name'],
		      		),
		      		'comment'		=>	'',
		      	);

	      	if (isset($data['created by'])) {
	      		$torrent['created by'] = forum_trim($data['created by']);
	      	}

	      	if (isset($data['info']['files'])) {
	      		$torrent['info']['files'] = $data['info']['files'];

	      		$size = 0;
	      		foreach ($torrent['info']['files'] as $file) {
	      			$size += $file['length'];
	      		}
	      	} else {
	      		$torrent['info']['length'] = $data['info']['length'];

	      		if (isset($data['info']['md5sum'])) {
	      			$torrent['info']['md5sum'] = $data['info']['md5sum'];
	      		}

	      		$size = $torrent['info']['length'];
	      	}

	      	unset($data);

	      	$info_hash = sha1(Fancy_Tracker::benc_encode($torrent['info']));

	      	$query = array(
	      		'SELECT'	=>	'1',
	      		'FROM'		=>	'torrents',
	      		'WHERE'		=>	'UPPER(info_hash) = UPPER(\''.$info_hash.'\')'
	      	);

	      	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	      	if ($forum_db->num_rows($result)) {
	      		message($lang_tracker['Dupe torrent']);
	      	}

	      	$handle = @fopen(FORUM_ROOT.'extensions/fancy_tracker/torrents/'.$info_hash.'.torrent', 'wb');
	      	if ($handle === FALSE) {
	      		message($lang_tracker['Unable to write']);
	      	}

			fwrite($handle, Fancy_Tracker::benc_encode($torrent));
	      	fclose($handle);

	      	$query = array(
	      		'INSERT'	=>	'info_hash, announce_url, date_added, poster_id, poster_ip, name, num_files, size',
	      		'INTO'		=>	'torrents',
	      		'VALUES'	=>	'\''.$info_hash.'\', \''.$torrent['announce'].'\', \''.$torrent['creation date'].'\', \''.$forum_user['id'].'\', \''.get_remote_address().'\', \''.$forum_db->escape($subject).'\', \''.(isset($torrent['info']['files']) ? count($torrent['info']['files']) : 1).'\', \''.$size.'\''
	      	);

	      	$forum_db->query_build($query) or error(__FILE__, __LINE__);
			$torrent_id = $forum_db->insert_id();

			$post_info = array(
				'is_guest'		=> $forum_user['is_guest'],
				'poster'		=> $username,
				'poster_id'		=> $forum_user['id'],	// Always 1 for guest posts
				'poster_email'	=> ($forum_user['is_guest'] && $email != '') ? $email : null,	// Always null for non-guest posts
				'subject'		=> $subject,
				'message'		=> $message,
				'hide_smilies'	=> $hide_smilies,
				'posted'		=> $now,
				'subscribe'		=> ($forum_config['o_subscriptions'] == '1' && (isset($_POST['subscribe']) && $_POST['subscribe'] == '1')),
				'forum_id'		=> $fid,
				'update_user'	=> TRUE,
				'update_unread'	=> TRUE,
				'torrent_id'	=> $torrent_id
			);

			($hook = get_hook('po_pre_add_topic')) ? eval($hook) : null;
			add_topic($post_info, $new_tid, $new_pid);

			($hook = get_hook('po_pre_redirect')) ? eval($hook) : null;
			redirect(forum_link($forum_url['post'], $new_pid), $lang_post['Post redirect']);
      	}
	}


	// Setup form
	$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;
	$forum_page['form_action'] = forum_link($forum_url['torrents-upload'], $fid);
	$forum_page['hidden_fields'] = array(
		'form_sent'		=> '<input type="hidden" name="form_sent" value="1" />',
		'form_user'		=> '<input type="hidden" name="form_user" value="'.((!$forum_user['is_guest']) ? forum_htmlencode($forum_user['username']) : 'Guest').'" />',
		'csrf_token'	=> '<input type="hidden" name="csrf_token" value="'.generate_form_token($forum_page['form_action']).'" />'
	);

	// Setup help
	$forum_page['text_options'] = array();
	if ($forum_config['p_message_bbcode'] == '1') {
		$forum_page['text_options']['bbcode'] = '<span'.(empty($forum_page['text_options']) ? ' class="first-item"' : '').'><a class="exthelp" href="'.forum_link($forum_url['help'], 'bbcode').'" title="'.sprintf($lang_common['Help page'], $lang_common['BBCode']).'">'.$lang_common['BBCode'].'</a></span>';
	}
	if ($forum_config['p_message_img_tag'] == '1') {
		$forum_page['text_options']['img'] = '<span'.(empty($forum_page['text_options']) ? ' class="first-item"' : '').'><a class="exthelp" href="'.forum_link($forum_url['help'], 'img').'" title="'.sprintf($lang_common['Help page'], $lang_common['Images']).'">'.$lang_common['Images'].'</a></span>';
	}
	if ($forum_config['o_smilies'] == '1') {
		$forum_page['text_options']['smilies'] = '<span'.(empty($forum_page['text_options']) ? ' class="first-item"' : '').'><a class="exthelp" href="'.forum_link($forum_url['help'], 'smilies').'" title="'.sprintf($lang_common['Help page'], $lang_common['Smilies']).'">'.$lang_common['Smilies'].'</a></span>';
	}

	// Setup breadcrumbs
	$forum_page['crumbs'][] = array($lang_tracker['Torrents'], forum_link($forum_url['index']));
	$forum_page['crumbs'][] = array($cur_posting['forum_name'], forum_link($forum_url['forum'], array($cur_posting['id'], sef_friendly($cur_posting['forum_name']))));
	$forum_page['crumbs'][] = $lang_tracker['Upload torrent'];

	($hook = get_hook('po_pre_header_load')) ? eval($hook) : null;

	define('FORUM_PAGE', 'post');
    require FORUM_ROOT.'header.php';

    // START SUBST - <!-- forum_main -->
    ob_start();

    ($hook = get_hook('po_main_output_start')) ? eval($hook) : null;

?>
	<div class="main-head">
		<h2 class="hn"><span><?php echo $lang_tracker['Upload torrent'] ?></span></h2>
	</div>
	<div class="main-subhead">
		<h2 class="hn"><span><?php echo $lang_tracker['Upload new torrent'] ?></span></h2>
	</div>
	<div id="post-form" class="main-content main-frm">
<?php
	if (!empty($forum_page['text_options']))
		echo "\t\t".'<p class="ct-options options">'.sprintf($lang_common['You may use'], implode(' ', $forum_page['text_options'])).'</p>'."\n";

	// If there were any errors, show them
	if (!empty($errors)) {
		$forum_page['errors'] = array();
		foreach ($errors as $cur_error) {
			$forum_page['errors'][] = '<li class="warn"><span>'.$cur_error.'</span></li>';
		}

		($hook = get_hook('po_pre_post_errors')) ? eval($hook) : null;
?>
		<div class="ct-box error-box">
			<h2 class="warn hn"><?php echo $lang_post['Post errors'] ?></h2>
			<ul class="error-list">
				<?php echo implode("\n\t\t\t\t", $forum_page['errors'])."\n" ?>
			</ul>
		</div>
<?php
	}
?>
		<div id="req-msg" class="req-warn ct-box error-box">
			<p><?php printf($lang_common['Required warn'], '<em>'.$lang_common['Required'].'</em>') ?></p>
		</div>
		<form id="afocus" class="frm-form" method="post" accept-charset="utf-8" enctype="multipart/form-data" action="<?php echo $forum_page['form_action'] ?>"<?php if (!empty($forum_page['form_attributes'])) echo ' '.implode(' ', $forum_page['form_attributes']) ?>>
			<div class="hidden">
				<?php echo implode("\n\t\t\t\t", $forum_page['hidden_fields'])."\n" ?>
			</div>
<?php

if ($forum_user['is_guest'])
{
	$forum_page['email_form_name'] = ($forum_config['p_force_guest_email'] == '1') ? 'req_email' : 'email';

	($hook = get_hook('po_pre_guest_info_fieldset')) ? eval($hook) : null;

?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_post['Guest post legend'] ?></strong></legend>
<?php ($hook = get_hook('po_pre_guest_username')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text required">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_post['Guest name'] ?> <em><?php echo $lang_common['Required'] ?></em></span></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="req_username" value="<?php if (isset($_POST['req_username'])) echo forum_htmlencode($username); ?>" size="35" maxlength="25" /></span>
					</div>
				</div>
<?php ($hook = get_hook('po_pre_guest_email')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text<?php if ($forum_config['p_force_guest_email'] == '1') echo ' required' ?>">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_post['Guest e-mail'] ?><?php if ($forum_config['p_force_guest_email'] == '1') echo ' <em>'.$lang_common['Required'].'</em>' ?></span></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="<?php echo $forum_page['email_form_name'] ?>" value="<?php if (isset($_POST[$forum_page['email_form_name']])) echo forum_htmlencode($email); ?>" size="35" maxlength="80" /></span>
					</div>
				</div>
<?php ($hook = get_hook('po_pre_guest_info_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php

	($hook = get_hook('po_guest_info_fieldset_end')) ? eval($hook) : null;

	// Reset counters
	$forum_page['group_count'] = $forum_page['item_count'] = 0;
}

	($hook = get_hook('po_pre_req_info_fieldset')) ? eval($hook) : null;

?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_common['Required information'] ?></strong></legend>
<?php
	($hook = get_hook('po_pre_req_subject')) ? eval($hook) : null;

?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text required longtext">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_post['Topic subject'] ?> <em><?php echo $lang_common['Required'] ?></em></span></label><br />
						<span class="fld-input"><input id="fld<?php echo $forum_page['fld_count'] ?>" type="text" name="req_subject" value="<?php if (isset($_POST['req_subject'])) echo forum_htmlencode($subject); ?>" size="70" maxlength="70" /></span>
					</div>
				</div>
<?php

	($hook = get_hook('po_pre_post_contents')) ? eval($hook) : null;

?>
				<div class="txt-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="txt-box textarea required">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_post['Write message'] ?> <em><?php echo $lang_common['Required'] ?></em></span></label>
						<div class="txt-input"><span class="fld-input"><textarea id="fld<?php echo $forum_page['fld_count'] ?>" name="req_message" rows="14" cols="95"><?php echo isset($_POST['req_message']) ? forum_htmlencode($message) : (isset($forum_page['quote']) ? forum_htmlencode($forum_page['quote']) : ''); ?></textarea></span></div>
					</div>
				</div>

				<div class="sf-set group-item<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text required">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_tracker['Torrent file'] ?></span></label><br />
						<span class="fld-input"><input type="file" id="fld<?php echo $forum_page['fld_count'] ?>" name="req_torrent" size="30" value="" /></span>
					</div>
				</div>
<?php
	$forum_page['checkboxes'] = array();
	if ($forum_config['o_smilies'] == '1') {
		$forum_page['checkboxes']['hide_smilies'] = '<div class="mf-item"><span class="fld-input"><input type="checkbox" id="fld'.(++$forum_page['fld_count']).'" name="hide_smilies" value="1"'.(isset($_POST['hide_smilies']) ? ' checked="checked"' : '').' /></span> <label for="fld'.$forum_page['fld_count'].'">'.$lang_post['Hide smilies'].'</label></div>';
	}

	// Check/uncheck the checkbox for subscriptions depending on scenario
	if (!$forum_user['is_guest'] && $forum_config['o_subscriptions'] == '1')
	{
		$subscr_checked = false;

		// If it's a preview
		if (isset($_POST['preview']))
			$subscr_checked = isset($_POST['subscribe']) ? true : false;
		// If auto subscribed
		else if ($forum_user['auto_notify'])
			$subscr_checked = true;
		// If already subscribed to the topic
		else if ($is_subscribed)
			$subscr_checked = true;

		$forum_page['checkboxes']['subscribe'] = '<div class="mf-item"><span class="fld-input"><input type="checkbox" id="fld'.(++$forum_page['fld_count']).'" name="subscribe" value="1"'.($subscr_checked ? ' checked="checked"' : '').' /></span> <label for="fld'.$forum_page['fld_count'].'">'.($is_subscribed ? $lang_post['Stay subscribed'] : $lang_post['Subscribe']).'</label></div>';
	}

	($hook = get_hook('po_pre_optional_fieldset')) ? eval($hook) : null;

	if (!empty($forum_page['checkboxes']))
	{

?>
				<fieldset class="mf-set set<?php echo ++$forum_page['item_count'] ?>">
					<legend><span><?php echo $lang_post['Post settings'] ?></span></legend>
					<div class="mf-box checkbox">
						<?php echo implode("\n\t\t\t\t\t", $forum_page['checkboxes'])."\n"; ?>
					</div>
<?php ($hook = get_hook('po_pre_checkbox_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
<?php
	}

	($hook = get_hook('po_pre_req_info_fieldset_end')) ? eval($hook) : null;

?>
			</fieldset>
<?php

($hook = get_hook('po_req_info_fieldset_end')) ? eval($hook) : null;

?>
			<div class="frm-buttons">
				<span class="submit"><input type="submit" name="submit" value="<?php echo $lang_tracker['Submit torrent'] ?>" /></span>
			</div>
		</form>
	</div>

<?php

	$forum_id = $cur_posting['id'];

	($hook = get_hook('po_end')) ? eval($hook) : null;

	$tpl_temp = forum_trim(ob_get_contents());
	$tpl_main = str_replace('<!-- forum_main -->', $tpl_temp, $tpl_main);
	ob_end_clean();
	// END SUBST - <!-- forum_main -->

	require FORUM_ROOT.'footer.php';
} else {
	// If we end up here, the script was called with some wacky parameters
	message($lang_common['Bad request']);
}


?>
