<?php
/**
 * Default SEF URL scheme.
 *
 * @copyright Copyright (C) 2008 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package favorite_topic
 */

$forum_url['favorite'] = 'misc.php?action=favorite&amp;tid=$1&amp;csrf_token=$2';
$forum_url['unfavorite'] = 'misc.php?action=unfavorite&amp;tid=$1&amp;csrf_token=$2';
$forum_url['search_favorite'] = 'search.php?action=show_favorite&amp;user_id=$1';

?>
