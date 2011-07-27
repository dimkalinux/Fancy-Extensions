<?php

class Fancy_simtopics {
	//
	public function get_simtopics($topic_subject, $forum_id, $topic_id) {
		// IMPORT GLOBALS
		global $forum_db, $db_type, $forum_user, $forum_url, $stop_list_fancy_simtopics, $forum_config, $lang_fancy_simtopics;

		// WORK ONLY ON MYSQL
		if (!in_array($db_type, array('mysqli','mysql'))) {
			return '';
		}

		$stop_list = array();

		// PER-LANG stoplist
		if (!isset($stop_list_fancy_simtopics)) {
			$stop_list_fancy_simtopics = array();
		}

		// CONSTRUCT PER-FORUM STOPWORDS VAR NAME
		$_stop_words_var = 'stop_list_fancy_simtopics_'.intval($forum_id, 10);

		// PER-FORUM stoplist
		if (isset($GLOBALS[$_stop_words_var]) && is_array($GLOBALS[$_stop_words_var])) {
			// MERGE STOP-WORDS
			$stop_list = array_merge($stop_list_fancy_simtopics, $GLOBALS[$_stop_words_var]);
		} else {
			$stop_list = $stop_list_fancy_simtopics;
		}

		// NUM TO SHOW
		$num_show = (intval($forum_config['o_fancy_simtopics_num_topics'], 10) > 0) ? intval($forum_config['o_fancy_simtopics_num_topics'], 10) : 0;
		if ($num_show < 1) {
			return '';
		}

		// TIME TO SHOW
		$time_show_query_part = '';
		$time_show = (intval($forum_config['o_fancy_simtopics_time_topics'], 10) > 0) ? (time() - (intval($forum_config['o_fancy_simtopics_time_topics'], 10) * 86400)) : 0;
		if ($time_show > 1) {
			$time_show_query_part = 'AND t.posted > '.$time_show;
		}

		// CLEAR SUBJECT
		$topic_subject = $this->clear_topics_subject($topic_subject, $stop_list, $forum_user['language']);

		// DONT SEARCH In THIS FORUMS
		$skip_forums_query_part = '';
		$skip_forums_ids = $this->get_forum_id_without_searches();
		if (!empty($skip_forums_ids)) {
			$skip_forums_query_part = 'AND t.forum_id NOT IN ('.implode(',', $skip_forums_ids).')';
		}

		// SEARCH ONE FORUM
		$header = $lang_fancy_simtopics['Header'];
		$search_one_forum_query_part = '';
		if ($forum_config['o_fancy_simtopics_one_forum'] == '1' && $forum_id > 0) {
			$search_one_forum_query_part = 'AND t.forum_id='.$forum_id;
			$header = $lang_fancy_simtopics['Header One Forum'];
		}

		// BUILD QUERY
		$query = array(
			'SELECT'	=> 't.id, t.subject, t.closed, MATCH (subject) AGAINST (\''.$forum_db->escape($topic_subject).'\') AS score',
			'FROM'		=> 'topics AS t',
			'JOINS'		=> array(
				array(
					'INNER JOIN'	=> 'forums AS f',
					'ON'			=> 'f.id=t.forum_id'
				),
				array(
					'LEFT JOIN'		=> 'forum_perms AS fp',
					'ON'			=> '(fp.forum_id=f.id AND fp.group_id='.$forum_user['g_id'].')'
				)
			),
			'WHERE'		=> 't.id != '.$topic_id.' AND t.moved_to IS NULL '.$search_one_forum_query_part.' '.$skip_forums_query_part.' '.$time_show_query_part.' AND MATCH (subject) AGAINST (\''.$forum_db->escape($topic_subject).'\') >= 0.5',
			'ORDER'		=> 'score DESC',
			'LIMIT'		=> $num_show,
		);

		($hook = get_hook('fancy_simtopics_get_query')) ? eval($hook) : null;

		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		// BUILD LINK LIST
		$topic_links = array();
		while ($sim_topic = $forum_db->fetch_assoc($result)) {
			$li_class = ($sim_topic['closed']) ? 'class="fancy_closed"' : '';
			$topic_links[] = '<li '.$li_class.'><a href="'.forum_link($forum_url['topic'], array($sim_topic['id'], sef_friendly($sim_topic['subject']))).'">'.forum_htmlencode($sim_topic['subject']).'</a></li>';

			($hook = get_hook('fancy_simtopics_in_row_end')) ? eval($hook) : null;
		}

		return !empty($topic_links) ? sprintf('<div id="fancy_simtopics_block" class="brd crumbs"><h3>'.$header.'</h3><ul>%s</ul></div>', implode(' ', $topic_links)) : '';
	}


	//
	private function clear_topics_subject($topic_subject, $stop_list_fancy_simtopics, $lang='English') {
		$word_list = array();
		$topic_subject = forum_trim(preg_replace('/[ \t]+/', ' ', $topic_subject)); // strip extra whitespaces and tabs

		// REMOVE SHORT
		if (!empty($topic_subject)) {
			// Put all unique words in the title into an array, and remove uppercases
			$word_list = array_unique(explode(' ', utf8_strtolower($topic_subject)));

			if ($lang != 'English') {
				foreach ($word_list as $key => $word) {
					// Lets eliminate all words of 2 characters or less
					if (utf8_strlen(forum_trim($word)) < 3) {
						unset($word_list[$key]);
					}
				}
			}
		}

		// Remove any stop words from our array
		if (!empty($word_list) && !empty($stop_list_fancy_simtopics)) {
			// MAKE STOPLIST lowercased
			function _makeitlow($value) {
    			return utf8_strtolower($value);
			}

			$stop_list_fancy_simtopics = array_map('_makeitlow', $stop_list_fancy_simtopics);

			$word_list = array_diff($word_list, $stop_list_fancy_simtopics);
		}

		// Rebuild our cleaned up topic title
		$topic_subject = !empty($word_list) ? implode(' ', $word_list) : '';
		return $topic_subject;
	}


	private function get_forum_id_without_searches() {
		global $forum_db;

		$forums = array();

		$query = array(
			'SELECT'	=> 'f.id',
			'FROM'		=> 'forums as f',
			'WHERE'		=> 'f.fancy_simtopics_search=0'
		);
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		while ($row = $forum_db->fetch_assoc($result)) {
			array_push($forums, intval($row['id'], 10));
		}

		return $forums;
	}
}


?>
