<?php

if (!defined('FORUM')) die();

$lang_fancy_stop_spam = array(
	'Error many links' 					=> 'Too more links in message. Allowed %s links. Reduce number of links and post it again.',
	'Name'								=> 'Settings for Fancy Stop SPAM',

	'First Post Max Links' 				=> 'Links in 1st message',
	'First Post Max Links Help'			=> 'Max allowed links in first message. If value < 0 — checking disabled.',
	'First Post Guest Max Links'		=> 'Links in 1st guest message',
	'First Post Guest Max Links Help'	=> 'Max allowed links in message. If value < 0 — checking disabled.',

	'Go to settings'					=> 'Settings',
	'Settings Name'						=> 'Settings for Fancy Stop SPAM',

	'Register form'					=> 'Register form',
	'Login form'					=> 'Login form',
	'Post form'						=> 'Post form',
	'Other Methods'					=> 'Other methods',
	'First Post Methods'			=> 'First Post methods',
	'Signature Check Method'		=> 'Check signature time',
	'Submit Check Method'			=> 'Check submit value',

	'Enable Honeypot'				=> 'Enable honeypot protection',
	'Enable Timeout'				=> 'Enable timeout protection',
	'Enable Timezone'				=> 'Enable timezone protection (UTC−12:00)',
	'Enable Check Identical'		=> 'Check identical posts',

	'Enable SFS Email'				=> 'Check email by StopForumSpam',
	'Enable SFS IP'					=> 'Check IP by StopForumSpam',

	'Register bot message'			=> 'Sorry, but we think you are bot. You cant register on this forum.',
	'Register bot timeout message'	=> 'Sorry, but we think you are bot because you are fill this form too fast. Wait a few seconds and try to submit again.',
	'Register bot timezone message'	=> 'Sorry, but we think you are bot because you are select timezone UTC−12:00. No human habitations are in this time zone. Select other timezone.',
	'Register bot sfs email message'	=> 'Sorry, but your email identified as spam. You cant register on this forum.',
	'Register bot sfs ip message'	=> 'Sorry, but your ip-address identified as spammers. You cant register on this forum.',
	'Login bot message'				=> 'Sorry, but we think you are bot. You cant login on this forum.',
	'Post bot message'				=> 'Sorry, but we think you are bot. You cant post message on this forum.',
	'Post Identical message'		=> 'Sorry, but you cant post identical messages. Modify message and post it again.',
	'Activate bot message'			=> 'Sorry, but we think you are bot. You cant activate account on this forum.',

	'Honey field'					=> 'Anti SPAM',
	'Honey field help'				=> 'Leave this field empty',

	'Enable Logs'					=> 'Log all spam events',
);

?>
