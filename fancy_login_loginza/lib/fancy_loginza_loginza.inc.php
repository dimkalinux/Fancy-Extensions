<?php

include 'LoginzaAPI.class.php';
include 'LoginzaUserProfile.class.php';

class Fancy_login_loginza {
	//
	public function do_answer_from_server() {
		global $forum_user, $lang_common, $lang_fancy_login_loginza, $forum_config;

		if (!empty($_POST['token'])) {
			$widget_id = intval($forum_config['o_fancy_login_loginza_widget_id'], 10);
			$secret_key = forum_trim($forum_config['o_fancy_login_loginza_secret_key']);

			// Получаем профиль авторизованного пользователя
			$LoginzaAPI = new LoginzaAPI();
			$UserProfile = $LoginzaAPI->getAuthInfo($_POST['token'], $widget_id, md5($_POST['token'].$secret_key));
			$prev_url = isset($_GET['return_url']) ? forum_trim($_GET['return_url']) : '';

			// проверка на ошибки
			if (is_object($UserProfile) && !empty($UserProfile->error_type)) {
				message(sprintf($lang_fancy_login_loginza['Error server'], forum_htmlencode($UserProfile->error_message)));
			} else if (!empty($UserProfile) && $this->check_profile($UserProfile)) {
				$this->process($UserProfile, $prev_url);
			}
		}

		// Show ERROR
		message($lang_fancy_login_loginza['Error loginza']);
	}


	//
	public function get_loginza_url() {
		global $forum_url, $forum_user, $forum_config;

		$query = array();
		$query['token_url'] = forum_link($forum_url['fancy_login_loginza_return'], forum_htmlencode($forum_user['prev_url']));

		// Loginza lang
		if ($forum_config['o_fancy_login_loginza_lang_from_forum'] == '1') {
			$query['lang'] = $this->get_lang_for_loginza();
		}

		// Providers
		$providers = $this->parse_providers($forum_config['o_fancy_login_loginza_providers']);
		if (is_array($providers) && count($providers) > 0) {
			$query['providers_set'] = implode(',', $providers);
		}

		$url = 'http://loginza.ru/api/widget?'.http_build_query($query);

		return $url;
	}


	// Return TRUE if fancy_login_loginza have all settings for work
	public function setuped() {
		global $forum_config;

		$result = FALSE;

		if (!empty($forum_config['o_fancy_login_loginza_secret_key']) && intval($forum_config['o_fancy_login_loginza_widget_id'], 10) > 0) {
			$result = TRUE;
		}

		return $result;
	}


	// Return TRUE if user have Loginza OpenID identity
	public function user_have_loginza($user_id) {
		global $forum_db;

		$have_loginza = FALSE;
		$user_id = intval($user_id, 10);

		if ($user_id > 1) {
			$query = array(
				'SELECT'	=> 'u.loginza_identity',
				'FROM'		=> 'users AS u',
				'WHERE'		=> 'u.id=\''.$forum_db->escape($user_id).'\''
			);
			$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
			$identity = $forum_db->result($result);

			if (!is_null($identity) && !empty($identity)) {
				$have_loginza = TRUE;
			}
		}

		return $have_loginza;
	}


	// Return Loginza identity for logged user or empty string
	public function get_user_identity() {
		global $forum_db, $forum_user;

		$identity = '';

		if (!$forum_user['is_guest']) {
			$query = array(
				'SELECT'	=> 'u.loginza_identity',
				'FROM'		=> 'users AS u',
				'WHERE'		=> 'u.id=\''.$forum_db->escape($forum_user['id']).'\''
			);
			$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
			$tmp_identity = $forum_db->result($result);

			if (!is_null($tmp_identity) && !empty($tmp_identity)) {
				$identity = $tmp_identity;
			}
		}

		return $identity;
	}


	// Remove Loginza OpenID identiry form DB->users
	public function user_remove_identity($user_id) {
		global $forum_db, $forum_user, $forum_url, $lang_fancy_login_loginza;

		// Allow only own profile
		if ($forum_user['is_guest'] || ($user_id != $forum_user['id'])) {
			message($lang_fancy_login_loginza['Error mismatch userid']);
		}

		$query = array(
			'UPDATE'	=> 'users',
			'SET'		=> 'loginza_identity=\'\', loginza_uid=\'\', loginza_provider=\'\'',
			'WHERE'		=> 'id='.$user_id
		);
		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		redirect(forum_link($forum_url['profile_about'], $user_id), $lang_fancy_login_loginza['Redirect identity off']);
	}


	//
	private function process($profile, $prev_url) {
		global $forum_user, $forum_url, $lang_fancy_login_loginza;

		if ($forum_user['is_guest']) {
			$user_id = $this->get_user_id_by_identity($profile->identity);
			if (FALSE !== $user_id) {
				$this->user_login($user_id, $prev_url);
			} else {
				$this->user_register($profile, $prev_url);
			}
		} else {
			$this->user_add_identity($forum_user['id'], $profile, FALSE);
			redirect(forum_link($forum_url['profile_about'], $forum_user['id']), $lang_fancy_login_loginza['Redirect identity on']);
		}
	}


	// Login user into FORUM
	private function user_login($user_id, $prev_url='') {
		global $forum_config, $lang_fancy_login_loginza, $forum_user, $forum_db, $cookie_name, $forum_url;

		// Load the login language file
		if (!isset($lang_login)) {
			require FORUM_ROOT.'lang/'.$forum_user['language'].'/login.php';
		}

		// Get user info matching login attempt
		$query = array(
			'SELECT'	=> 'u.id, u.group_id, u.password, u.salt, u.activate_key',
			'FROM'		=> 'users AS u',
			'WHERE' 	=> 'u.id=\''.$forum_db->escape($user_id).'\''
		);
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
		list($user_id, $group_id, $db_password_hash, $salt, $activate_key) = $forum_db->fetch_row($result);

		//
		if ($group_id == FORUM_UNVERIFIED && !empty($activate_key)) {
			message($lang_fancy_login_loginza['Activate first']);
		}

		// Remove this user's guest entry from the online list
		$query = array(
			'DELETE'	=> 'online',
			'WHERE'		=> 'ident=\''.$forum_db->escape(get_remote_address()).'\''
		);
		($hook = get_hook('li_login_qr_delete_online_user')) ? eval($hook) : null;
		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		$expire = time() + 1209600;
		forum_setcookie($cookie_name, base64_encode($user_id.'|'.$db_password_hash.'|'.$expire.'|'.sha1($salt.$db_password_hash.forum_hash($expire, $salt))), $expire);

		($hook = get_hook('li_login_pre_redirect')) ? eval($hook) : null;

		//if (empty($prev_url)) {
			$prev_url = forum_link($forum_url['index']);
		//}

		redirect(forum_htmlencode($prev_url).((substr_count($prev_url, '?') == 1) ? '&amp;' : '?').'login=1', $lang_login['Login redirect']);
	}


	// Prepare to register user use Loginza profile
	private function user_register($profile, $prev_url) {
		global $forum_config, $lang_fancy_login_loginza, $forum_user, $forum_db, $forum_url;

		// Load the profile language file
		if (!isset($lang_profile)) {
			require FORUM_ROOT.'lang/'.$forum_user['language'].'/profile.php';
		}

		// We allowed register new users?
		if ($forum_config['o_regs_allow'] == '0') {
			message($lang_profile['No new regs']);
		}

		// Check that someone from this IP didn't register a user within the last hour (DoS prevention)
		$query = array(
			'SELECT'	=> 'COUNT(u.id)',
			'FROM'		=> 'users AS u',
			'WHERE'		=> 'u.registration_ip=\''.$forum_db->escape(get_remote_address()).'\' AND u.registered>'.(time() - 3600)
		);
		($hook = get_hook('rg_register_qr_check_register_flood')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
		if ($forum_db->result($result) > 0) {
			message($lang_profile['Registration flood']);
		}

		// Get user info from Loginza Profile
		$username = $this->get_username_for_new_user($profile);
		$loginza_identity = isset($profile->identity) ? forum_trim($profile->identity) : FALSE;
		$lup = new LoginzaUserProfile($profile);
		$email = $lup->get_email();

		if (!$username) {
			message($lang_fancy_login_loginza['Error empty username']);
		}

		if (!$loginza_identity) {
			message($lang_fancy_login_loginza['Error empty identity']);
		}

		// Check e-mail address
		$banned_email = FALSE;
		$dupe_list = array();
		if ($email) {
			$error = $this->check_email($email, $banned_email, $dupe_list);
			if (TRUE !== $error) {
				message($error);
			}
		}

		// Clean old unverified registrators - delete older than 72 hours
		$query = array(
			'DELETE'	=> 'users',
			'WHERE'		=> 'group_id='.FORUM_UNVERIFIED.' AND activate_key IS NOT NULL AND registered < '.(time() - 259200)
		);
		($hook = get_hook('rg_register_qr_delete_unverified')) ? eval($hook) : null;
		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		($hook = get_hook('rg_register_end_validation')) ? eval($hook) : null;

		// User default info
		$language = $forum_config['o_default_lang'];
		$password = random_key(12, TRUE);
		$salt = random_key(12);
		$password_hash = forum_hash($password, $salt);
		$initial_group_id = ($forum_config['o_regs_verify'] == '0') ? $forum_config['o_default_user_group'] : FORUM_UNVERIFIED;

		// Timezone & DST
		$this->get_timezone_and_dst($timezone, $dst);

		// Insert the new user into the database.
		// We do this now to get the last inserted id for later use.
		$user_info = array(
			'username'				=> $username,
			'group_id'				=> $initial_group_id,
			'salt'					=> $salt,
			'password'				=> $password,
			'password_hash'			=> $password_hash,
			'email'					=> $email,
			'email_setting'			=> $forum_config['o_default_email_setting'],
			'timezone'				=> $timezone,
			'dst'					=> $dst,
			'language'				=> $forum_config['o_default_lang'],
			'style'					=> $forum_config['o_default_style'],
			'registered'			=> time(),
			'registration_ip'		=> get_remote_address(),
			'activate_key'			=> ($forum_config['o_regs_verify'] == '1') ? '\''.random_key(8, TRUE).'\'' : 'NULL',
			'require_verification'	=> ($forum_config['o_regs_verify'] == '1'),
			'notify_admins'			=> ($forum_config['o_regs_report'] == '1'),
			'loginza_profile'		=> $profile,
			'loginza_return_url'	=> $prev_url,
			'loginza_banned_email'	=> $banned_email,
			'loginza_dupe_list'		=> $dupe_list,
		);

		($hook = get_hook('rg_register_pre_add_user')) ? eval($hook) : null;

		// If we dont have email — save userdata to session and show form
		if (!$email) {
			if(!isset($_SESSION)) {
				session_start();
			}
			$session_id = 'fancy_login_loginza_'.random_key(12, TRUE, TRUE);
			$_SESSION[$session_id] = $user_info;
			$this->form_end_reg($session_id);
		} else {
			if ($forum_config['o_regs_verify'] == '1' && $forum_config['o_fancy_login_loginza_trust_openid_emails'] == '1') {
				// Skip activate email from OpenID
				$user_info['activate_key'] = 'NULL';
				$user_info['require_verification'] = FALSE;
				$user_info['group_id'] = $forum_config['o_default_user_group'];
			}

			$this->register($user_info);
		}
	}


	// Return Timezone and DST
	private function get_timezone_and_dst(&$timezone, &$dst) {
		global $forum_config;

		// Timezone & DST
		$timezone = date('Z') / 3600;
		$dst = date('I');

		if ($forum_config['o_default_timezone'] == $timezone || $forum_config['o_default_timezone'] == ($timezone - 1)) {
			$timezone = ($dst) ? $timezone - 1 : $timezone;
		} else {
			$dst = $forum_config['o_default_dst'];
			$timezone = $forum_config['o_default_timezone'];
		}
	}


	// Register user
	private function register($user_info) {
		global $forum_config, $cookie_name, $forum_url, $lang_profile, $lang_profile, $forum_user;

		if (!isset($lang_profile)) {
			require FORUM_ROOT.'lang/'.$forum_user['language'].'/profile.php';
		}

		$new_id = 0;
		add_user($user_info, $new_uid);

		// Fill new user profile
		if ($new_uid > 1) {
			$this->user_update_profile($new_uid, $user_info['loginza_profile']);
		}

		// Add Loginza identity
		$this->user_add_identity($new_uid, $user_info['loginza_profile'], TRUE);

		// If we previously found out that the e-mail was banned
		if ($user_info['loginza_banned_email'] && $forum_config['o_mailing_list'] != '') {
			$mail_subject = 'Alert - Banned e-mail detected';
			$mail_message = 'User \''.$user_info['username'].'\' registered with banned e-mail address: '.$user_info['email']."\n\n".'User profile: '.forum_link($forum_url['user'], $new_uid)."\n\n".'-- '."\n".'Forum Mailer'."\n".'(Do not reply to this message)';

			($hook = get_hook('rg_register_banned_email')) ? eval($hook) : null;
			forum_mail($forum_config['o_mailing_list'], $mail_subject, $mail_message);
		}

		// If we previously found out that the e-mail was a dupe
		$dupe_list = $user_info['loginza_dupe_list'];
		if (!empty($dupe_list) && $forum_config['o_mailing_list'] != '') {
			$mail_subject = 'Alert - Duplicate e-mail detected';
			$mail_message = 'User \''.$user_info['username'].'\' registered with an e-mail address that also belongs to: '.implode(', ', $dupe_list)."\n\n".'User profile: '.forum_link($forum_url['user'], $new_uid)."\n\n".'-- '."\n".'Forum Mailer'."\n".'(Do not reply to this message)';

			($hook = get_hook('rg_register_dupe_email')) ? eval($hook) : null;
			forum_mail($forum_config['o_mailing_list'], $mail_subject, $mail_message);
		}
		($hook = get_hook('rg_register_pre_login_redirect')) ? eval($hook) : null;

		// Must the user verify the registration or do we log him/her in right now?
		if ($user_info['require_verification'] == TRUE) {
			message(sprintf($lang_profile['Reg e-mail'], '<a href="mailto:'.forum_htmlencode($forum_config['o_admin_email']).'">'.forum_htmlencode($forum_config['o_admin_email']).'</a>'));
		}

		// Login and redirect
		$this->user_login($new_uid);
	}


	// Add Loginza identity to user
	private function user_add_identity($user_id, $profile, $skip_user_check=FALSE) {
		global $lang_fancy_login_loginza, $forum_user, $forum_db;

		// Allow only own profile
		if (($skip_user_check === FALSE) && $user_id != $forum_user['id']) {
			message($lang_fancy_login_loginza['Error mismatch userid']);
		}

		$loginza_identity = isset($profile->identity) ? forum_trim($profile->identity) : FALSE;
		$loginza_uid = isset($profile->uid) ? forum_trim($profile->uid) : '';
		$loginza_provider = isset($profile->provider) ? forum_trim($profile->provider) : '';

		if (!$loginza_identity) {
			message($lang_fancy_login_loginza['Error setup register']);
		}

		// Check if any user already have this identity
		if (FALSE !== $this->get_user_id_by_identity($loginza_identity)) {
			message($lang_fancy_login_loginza['Error dup identity']);
		}

		$query = array(
			'UPDATE'	=> 'users',
			'SET'		=> 'loginza_identity=\''.$forum_db->escape($loginza_identity).'\', loginza_uid=\''.$forum_db->escape($loginza_uid).'\', loginza_provider=\''.$forum_db->escape($loginza_provider).'\'',
			'WHERE'		=> 'id='.$user_id
		);
		$forum_db->query_build($query) or error(__FILE__, __LINE__);
	}


	// Fill user profile from Loginza profile
	private function user_update_profile($user_id, $profile) {
		global $forum_db;

		$query = array(
			'UPDATE'	=> 'users',
			'SET'		=> '',
			'WHERE'		=> 'id='.$user_id
		);

		$lup = new LoginzaUserProfile($profile);
		$realname = $lup->genFullName();
		$website = $lup->genUserSite();
		$icq = $lup->get_icq();

		// Realname
		if (!empty($realname)) {
			$query['SET'] .= 'realname=\''.$forum_db->escape($realname).'\'';
		}

		// Web
		if (!empty($website)) {
			if (!empty($query['SET'])) {
				$query['SET'] .= ', ';
			}
			$query['SET'] .= 'url=\''.$forum_db->escape($website).'\'';
		}

		// ICQ
		if (!empty($icq)) {
			if (!empty($query['SET'])) {
				$query['SET'] .= ', ';
			}
			$query['SET'] .= 'icq=\''.$forum_db->escape($icq).'\'';
		}

		// Run Query only if non empty
		if (!empty($query['SET'])) {
			$forum_db->query_build($query) or error(__FILE__, __LINE__);
		}
	}


	//
	private function get_username_for_new_user($profile) {
		$lup = new LoginzaUserProfile($profile);
		$username = forum_trim($lup->genNickname());

		if (empty($username)) {
			return FALSE;
		}

		// Check username
		$errors = validate_username($username);
		if (empty($errors)) {
			return $username;
		}

		// Try a fix username
		$i = 1;
		while ($i < 5) {
			$username .= '_'.$i;
			$errors = validate_username($username);
			if (empty($errors)) {
				return $username;
			}

			$i += 2;
		}

		return FALSE;
	}


	//
	private function check_profile($profile) {
		$identity = isset($profile->identity) ? forum_trim($profile->identity) : FALSE;

		if (!$identity) {
			return FALSE;
		}

		return TRUE;
	}


	// SHow form for email or other fields that not found in Loginza Profile
	public function form_end_reg($session_id='') {
		global $forum_db, $forum_url, $lang_common, $forum_config, $base_url, $forum_start, $tpl_main, $forum_user, $forum_page, $forum_updates, $ext_info, $lang_profile, $forum_head, $forum_loader, $forum_flash, $lang_fancy_login_loginza;

		// Load the profile.php language file
		require FORUM_ROOT.'lang/'.$forum_user['language'].'/profile.php';

		$errors = array();

		//
		if (isset($_POST['form_sent'])) {
			// Cancel?
			if (isset($_POST['cancel'])) {
				redirect(forum_link($forum_url['index']), $lang_common['Cancel redirect']);
			}

			if (!isset($_SESSION)) {
				session_start();
			}
			$session_id = isset($_POST['session_id']) ? forum_trim($_POST['session_id']) : FALSE;
			if (!$session_id || empty($_SESSION[$session_id])) {
				message($lang_fancy_login_loginza['Error session']);
			}

			$email = strtolower(forum_trim($_POST['req_email']));

			$banned_email = FALSE;
			$dupe_list = array();
			$error = $this->check_email($email, $banned_email, $dupe_list);
			if (TRUE !== $error) {
				$errors[] = $error;
			}

			if (empty($errors)) {
				$user_info = $_SESSION[$session_id];
				// Update user_info
				$user_info['email'] = $email;
				$user_info['loginza_banned_email'] = $banned_email;

				// Finally, destroy the session.
				unset($_SESSION[$session_id]);
				session_destroy();

				// Register user
				$this->register($user_info);
			}
		}

		// Setup form
		$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;
		$forum_page['form_action'] = forum_link($forum_url['fancy_login_loginza_end_reg']);

		// Setup breadcrumbs
		$forum_page['crumbs'] = array(
			array($forum_config['o_board_title'], forum_link($forum_url['index'])),
			array(sprintf($lang_profile['Register at'], $forum_config['o_board_title']), forum_link($forum_url['register'])),
		);


		define('FORUM_PAGE', 'fancy_login-loginza');
		require FORUM_ROOT.'header.php';

		// START SUBST - <!-- forum_main -->
		ob_start();
?>
	<div class="main-head">
		<h2 class="hn"><span><?php echo $lang_fancy_login_loginza['Setup account'] ?></span></h2>
	</div>
	<div class="main-content main-frm">
<?php
			// If there were any errors, show them
			if (!empty($errors))
			{
				$forum_page['errors'] = array();
				foreach ($errors as $cur_error) {
					$forum_page['errors'][] = '<li class="warn"><span>'.$cur_error.'</span></li>';
				}
?>
		<div class="ct-box error-box">
			<h2 class="warn hn"><?php echo $lang_profile['Register errors'] ?></h2>
			<ul class="error-list">
				<?php echo implode("\n\t\t\t\t", $forum_page['errors'])."\n" ?>
			</ul>
		</div>
<?php
			}
?>
		<div id="req-msg" class="req-warn ct-box error-box">
			<p class="important"><?php printf($lang_common['Required warn'], '<em>'.$lang_common['Required'].'</em>') ?></p>
		</div>
		<form id="afocus" class="frm-form" method="post" accept-charset="utf-8" action="<?php echo $forum_page['form_action'] ?>" autocomplete="off">
			<div class="hidden">
				<input type="hidden" name="form_sent" value="1" />
				<input type="hidden" name="session_id" value="<?php echo forum_htmlencode($session_id); ?>" />
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token($forum_page['form_action']) ?>" />
			</div>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_common['Required information'] ?></strong></legend>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text required">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_fancy_login_loginza['Email'] ?></span> <small><?php echo $lang_fancy_login_loginza['Email help'] ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="req_email" size="35" value="<?php echo(isset($_POST['req_email']) ? forum_htmlencode($_POST['req_email']) : ''); ?>" required /></span><br />
					</div>
				</div>
			</fieldset>
			<div class="frm-buttons">
				<span class="submit primary"><input type="submit" name="update" value="<?php echo $lang_common['Submit'] ?>" /></span>
				<span class="cancel"><input type="submit" name="cancel" value="<?php echo $lang_common['Cancel'] ?>" formnovalidate /></span>
			</div>
		</form>
	</div>
<?php
		$tpl_temp = forum_trim(ob_get_contents());
		$tpl_main = str_replace('<!-- forum_main -->', $tpl_temp, $tpl_main);
		ob_end_clean();
		// END SUBST - <!-- forum_main -->

		require FORUM_ROOT.'footer.php';
	}


	//
	private function check_email($email, &$banned_email, &$dupe_list) {
		global $lang_profile, $forum_db, $forum_config, $forum_user;

		$result = FALSE;

		// Load the profile language file
		if (!isset($lang_profile)) {
			require FORUM_ROOT.'lang/'.$forum_user['language'].'/profile.php';
		}


		if (!defined('FORUM_EMAIL_FUNCTIONS_LOADED')) {
			require FORUM_ROOT.'include/email.php';
		}

		if (!is_valid_email($email)) {
			return $lang_profile['Invalid e-mail'];
		}

		// Check if it's a banned e-mail address
		$banned_email = is_banned_email($email);
		if ($banned_email && $forum_config['p_allow_banned_email'] == '0') {
			return $lang_profile['Banned e-mail'];
		}

		// Check if someone else already has registered with that e-mail address
		$dupe_list = array();

		$query = array(
			'SELECT'	=> 'u.username',
			'FROM'		=> 'users AS u',
			'WHERE'		=> 'u.email=\''.$forum_db->escape($email).'\''
		);

		($hook = get_hook('rg_register_qr_check_email_dupe')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		while ($cur_dupe = $forum_db->fetch_assoc($result)) {
			$dupe_list[] = $cur_dupe['username'];
		}

		if (!empty($dupe_list)) {
			if ($forum_config['p_allow_dupe_email'] == '0') {
				return $lang_profile['Dupe e-mail'];
			}
		}

		return TRUE;
	}


	//
	private function get_user_id_by_identity($identity) {
		global $forum_db;

		$user_id = FALSE;

		if (!empty($identity)) {
			$query = array(
				'SELECT'	=> 'u.id',
				'FROM'		=> 'users AS u',
				'WHERE'		=> 'u.loginza_identity=\''.$forum_db->escape($identity).'\''
			);
			$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
			$user_id = (int) $forum_db->result($result);

			if ($user_id < 2) {
				$user_id = FALSE;
			}
		}

		return $user_id;
	}


	//
	public function parse_providers($providers_string) {
		$valid_providers = array('google', 'yandex', 'mailruapi', 'mailru', 'vkontakte',
								'facebook', 'twitter', 'loginza', 'myopenid', 'webmoney', 'rambler',
								'flickr', 'lastfm', 'verisign', 'aol', 'steam', 'openid');

		$providers = $a_p = array();

		if (!empty($providers_string)) {
			$a_p = explode(',', $providers_string);
		}

		if (count($a_p) > 0) {
			foreach ($a_p as $prov) {
				$prov = forum_trim($prov);
				if (in_array($prov, $valid_providers)) {
					array_push($providers, $prov);
				}
			}
		}

		return (empty($providers)) ? '' : $providers;
	}


	//
	private function get_lang_for_loginza() {
		global $forum_user;

		$loginza_lang = 'en';

		$langs = array('ru', 'uk', 'be', 'fr', 'en');

		if (!empty($forum_user['language'])) {
			switch ($forum_user['language']) {
				case 'English':
					$loginza_lang = 'en';
					break;

				case 'Russian':
					$loginza_lang = 'ru';
					break;

				case 'French':
					$loginza_lang = 'fr';
					break;

				case 'Ukrainian':
					$loginza_lang = 'ua';
					break;

				default:
					$loginza_lang = 'en';
					break;
			}
		}

		return $loginza_lang;
	}
}

?>
