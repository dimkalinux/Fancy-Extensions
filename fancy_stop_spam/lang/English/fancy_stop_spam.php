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

	'Register bot message'			=> 'Sorry, but we think you are bot. You can not register on this forum.',
	'Register bot timeout message'	=> 'Sorry, but we think you are bot because you are fill this form too fast. Wait a few seconds and try to submit again.',
	'Register bot timezone message'	=> 'Sorry, but we think you are bot because you are select timezone UTC−12:00. No human habitations are in this time zone. Select other timezone.',
	'Register bot sfs email message'	=> 'Sorry, but your email identified as spam. You can not register on this forum.',
	'Register bot sfs email ip message'	=> 'A spammer was try registered with the same IP address as you within the last hour. To prevent registration flooding, at least an hour has to pass between registrations from the same IP. Sorry for the inconvenience.',
	'Register bot sfs ip message'	=> 'Sorry, but your ip-address identified as spammers. You can not register on this forum.',
	'Login bot message'				=> 'Sorry, but we think you are bot. You can not login on this forum.',
	'Post bot message'				=> 'Sorry, but we think you are bot. You can not post message on this forum.',
	'Post Identical message'		=> 'Sorry, but you can not post identical messages. Modify message and post it again.',
	'Activate bot message'			=> 'Sorry, but we think you are bot. You can not activate account on this forum.',

	'Honey field'					=> 'Anti SPAM',
	'Honey field help'				=> 'Leave this field empty',

	'Enable Logs'					=> 'Log all spam events',

	'Section antispam'				=> 'Antispam',
	'Section antispam welcome'		=> 'Antispam check',
	'Section antispam welcome user'	=> 'Antispam check %s\'s',
	'Status'						=> 'Status',
	'Status found'					=> 'spammer, found in database',
	'Status not found'				=> 'clean, not found in database',
	'Status error'					=> 'Can not get info from StopForumSpam server',
	'Frequency'						=> 'Frequency',
	'Last seen'						=> 'Last seen',

	'Admin section antispam'			=> 'Antispam',
	'Admin submenu information'			=> 'Information',
	'Admin submenu information header'	=> 'Welcome to Fancy stop spam administration control panel',

	'Admin submenu logs'				=> 'Logs',
	'Admin submenu logs header'			=> 'Detected spam events (latest 100)',

	'Admin submenu new users'			=> 'New users',
	'Admin submenu new users header'	=> 'Latest 15 registered users',

	'Admin submenu suspicious users'		=> 'Suspicious users',
	'Admin submenu suspicious users header'	=> 'Suspicious users',

	'Admin logs disabled message'			=> 'Fancy stop spam logging disabled %s.',
	'Admin logs disabled message settings'	=> 'in Settings',
	'Admin logs empty message'				=> '',

	'log event name unknown'				=> 'Unknown',
	'log event name 0'						=> 'System message',
	'log event name 1'						=> 'Register submit',
	'log event name 2'						=> 'Register timeout',
	'log event name 3'						=> 'Register timezone',
	'log event name 4'						=> 'Register honeypot',
	'log event name 5'						=> 'Register honeypot empty',
	'log event name 6'						=> 'Register email SFS',
	'log event name 7'						=> 'Register email SFS (cached)',
	'log event name 8'						=> 'Register email SFS IP (cached)',
	'log event name 9'						=> 'Register IP SFS',
	'log event name 10'						=> 'Register IP SFS (cached)',
	'log event name 11'						=> 'Register honeypot repeated',

	'log event name 70'						=> 'Activate submit',
	'log event name 71'						=> 'Activate honeypot',
	'log event name 72'						=> 'Activate honeypot empty',

	'log event name 20'						=> 'Post submit',
	'log event name 21'						=> 'Post timeout',
	'log event name 22'						=> 'Post honeypot',
	'log event name 23'						=> 'Post honeypot empty',

	'log event name 30'						=> 'Identical message post',

	'log event name 40'						=> 'Login honeypot',
	'log event name 41'						=> 'Login honeypot empty',

	'log event name 60'						=> 'Signature hidden',

	'Time'									=> 'Time',
	'IP'									=> 'IP',
	'Comment'								=> 'Comment',
	'Type'									=> 'Type',
	'User'									=> 'User',

	'No activity'							=> 'No SPAM activity logged.',
	'No suspicious_users'					=> 'No suspicious users founded.',

	'Number posts'							=> 'Posts',

	'Email check'							=> 'Email check',
	'IP check'								=> 'IP check',

	'SFS API Key'							=> 'API key',
	'SFS API Key Help'						=> 'StopForumSpam API key for report spamers',
	'Report to sfs'							=> 'Report spamers data to StopForumSpam service',

	'Identical check repeated event'		=> 'Identical repeated - user mark as suspicious',
);
