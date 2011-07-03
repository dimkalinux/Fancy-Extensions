<?php

// Make sure no one attempts to run this script "directly"
if (!defined('FORUM')) {
	exit;
}

$forum_url['fancy_alerts_topics_goto_alerts'] 		= 'search.php?action=fancy_alerts_topics_show&amp;user_id=$1';
$forum_url['fancy_alerts_quotes_goto_alerts'] 		= 'search.php?action=fancy_alerts_quotes_show&amp;user_id=$1';
$forum_url['fancy_alerts_quotes_mark_read'] 		= 'misc.php?action=fancy_alerts_quotes_mark_read&amp;csrf_token=$1';
$forum_url['fancy_alerts_topics_mark_read'] 		= 'misc.php?action=fancy_alerts_topics_mark_read&amp;csrf_token=$1';

$forum_url['fancy_alerts_topics_turn_on'] 			= 'misc.php?action=fancy_alerts_topics_on&amp;tid=$1&amp;csrf_token=$2';
$forum_url['fancy_alerts_topics_turn_off'] 			= 'misc.php?action=fancy_alerts_topics_off&amp;tid=$1&amp;csrf_token=$2';

$forum_url['fancy_alerts_search_my_alerts_topics'] 	= 'search.php?action=show_fancy_alerts_my_topics&amp;user_id=$1';

$forum_url['fancy_alerts_autoupdate_status'] 		= 'misc.php?action=fancy_alerts_update_status';

?>
