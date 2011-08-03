<?php

class Fancy_alerts {

	//
    public function add_new_topic_alerts($user_id, $topic_id, $last_post_time) {
		global $forum_db, $db_type;

		echo $user_id;

		// Add the topic
		$query = array(
		    'INSERT'	=> 'user_id, topic_id, last_post_id, last_post_time, last_user_view',
		    'INTO'		=> 'fancy_alerts_topics',
		    'VALUES'	=> '\''.$user_id.'\', \''.$topic_id.'\', 0, \''.$last_post_time.'\', 0'
		);

		$forum_db->query_build($query) or error(__FILE__, __LINE__);
    }


	//
    public function del_topic_alerts($topic_id) {
		global $forum_db, $db_type;

		if (!$topic_id || intval($topic_id, 10) < 1) {
			return;
		}

		// Delete the topic alerts
		$query = array(
		    'DELETE'	=> 'fancy_alerts_topics',
		    'WHERE'		=> 'topic_id='.$topic_id
		);

		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		// Delete the quotes alerts
		$query = array(
		    'DELETE'	=> 'fancy_alerts_posts',
		    'WHERE'		=> 'topic_id='.$topic_id
		);

		$forum_db->query_build($query) or error(__FILE__, __LINE__);
    }


	//
    public function del_post_alerts($posts) {
		$this->clear_quote_alerts($posts);
    }


	//
    public function update_topic_alerts($topic_id, $last_post_id, $last_post_time, $post_author_id) {
		global $forum_db, $db_type;

		// Update the topic
		if ($last_post_id != 0) {
		    $query = array(
				'UPDATE'	=> 'fancy_alerts_topics',
				'SET'		=> 'last_post_id='.$last_post_id.', last_post_time='.$last_post_time,
				'WHERE'		=> 'topic_id='.$topic_id.' AND user_id!='.$post_author_id
		    );
		} else {
		    $query = array(
				'UPDATE'	=> 'fancy_alerts_topics',
				'SET'		=> 'last_post_time='.$last_post_time,
				'WHERE'		=> 'topic_id='.$topic_id.' AND user_id!='.$post_author_id
		    );
		}

		$forum_db->query_build($query) or error(__FILE__, __LINE__);
    }


	//
    public function add_quote_alerts($topic_id, $post_id, $post_text) {
		global $forum_db, $db_type, $forum_user;

		$usernames = $this->extract_usernames_from_message($post_text);
		$user_ids = $this->get_user_ids($usernames);
		$this->insert_quote_alerts($user_ids, $topic_id, $post_id);
    }


	//
    public function update_quote_alerts($topic_id, $post_id, $post_text) {
		global $forum_db, $db_type, $forum_user;

		$usernames = $this->extract_usernames_from_message($post_text);
		$user_ids = $this->get_user_ids($usernames);

		// CLEAR ALL PREVIUS ALERTS FOR POST
		$this->clear_quote_alerts($post_id);

		// INSERT NEW
		$this->insert_quote_alerts($user_ids, $topic_id, $post_id);
    }


	// update_owner_view_topic_alerts
    public function on_viewtopic_update_topic_alerts($topic_id, $user_id, $last_view_post_id=0, $last_post_posted) {
		global $forum_db, $db_type;

		$topic_id = intval($topic_id, 10);
		$last_view_post_id = intval($last_view_post_id, 10);
		$now = time();


		if ($last_view_post_id > 0 && $topic_id > 0) {
			$query = array(
				'UPDATE'	=> 'fancy_alerts_topics',
				'SET'		=> 'last_user_view='.$now,
				'WHERE'		=> 'topic_id='.$topic_id.' AND user_id='.$user_id.' AND last_user_view < '.$now.' AND last_post_id!=0'
			);

			$forum_db->query_build($query) or error(__FILE__, __LINE__);
		}
    }

	//
    public function update_topic_alerts_on_delete_last_post($topic_id, $last_post_id, $last_post_posted) {
		global $forum_db, $db_type;

		if ($last_post_id > 0 && $last_post_posted > 0) {
		    $query = array(
				'UPDATE'	=> 'fancy_alerts_topics',
				'SET'		=> 'last_post_id='.$last_post_id.', last_post_time='.$last_post_posted,
				'WHERE'		=> 'topic_id='.$topic_id
		    );

		    $forum_db->query_build($query) or error(__FILE__, __LINE__);
		}
    }


	//
    public function get_num_topics_alerts_for_user($user_id, $user_group_id) {
		global $forum_db, $db_type;

		$query = array(
		    'SELECT'	=> 'COUNT(*) AS num_alerts',
		    'FROM'		=> 'fancy_alerts_topics AS fat',
		    'JOINS'		=> array(
				array(
					'LEFT JOIN'		=> 'topics AS t',
					'ON'			=> '(t.id=fat.topic_id)'
				),
				array(
					'LEFT JOIN'		=> 'forum_perms AS fp',
					'ON'			=> '(fp.forum_id=t.forum_id AND fp.group_id='.$user_group_id.')'
				)
			),
			'WHERE'		=> 'fat.user_id='.$user_id.' AND fat.last_post_id!=0 AND fat.last_post_time > fat.last_user_view AND (fp.read_forum IS NULL OR fp.read_forum=1)'
		);

		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
		return intval($forum_db->result($result), 10);
    }


	//
    public function get_num_quotes_alerts_for_user($user_id, $user_group_id) {
		global $forum_db, $db_type;

		$query = array(
		    'SELECT'	=> 'COUNT(*) AS num_alerts',
		    'FROM'		=> 'fancy_alerts_posts AS fap',
		    'JOINS'		=> array(
				array(
					'LEFT JOIN'		=> 'topics AS t',
					'ON'			=> '(t.id=fap.topic_id)'
				),
				array(
					'LEFT JOIN'		=> 'forum_perms AS fp',
					'ON'			=> '(fp.forum_id=t.forum_id AND fp.group_id='.$user_group_id.')'
				)
			),
			'WHERE'		=> 'user_id='.$user_id.' AND (fp.read_forum IS NULL OR fp.read_forum=1)'
		);

		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		return intval($forum_db->result($result), 10);
    }


//	'WHERE'		=> '(fp.read_forum IS NULL OR fp.read_forum=1) AND t.id='.$tid.' AND t.moved_to IS NULL'

	//
    public function mark_quotes_as_readed($ids, $user_id) {
    	global $forum_db, $db_type;

		if (is_array($ids) && count($ids) > 0 && $user_id > 1) {
			$query = array(
				'DELETE'	=> 'fancy_alerts_posts',
				'WHERE'		=> 'user_id='.$user_id.' AND post_id IN ('.implode(',', $ids).')'
			);

			$forum_db->query_build($query) or error(__FILE__, __LINE__);
		}
    }


	//
    public function mark_all_quotes_as_readed($user_id) {
    	global $forum_db, $db_type;

		if ($user_id > 1) {
			$query = array(
				'DELETE'	=> 'fancy_alerts_posts',
				'WHERE'		=> 'user_id='.$user_id
			);

			$forum_db->query_build($query) or error(__FILE__, __LINE__);
		}
    }


	//
    public function mark_all_topics_as_readed($user_id) {
    	global $forum_db, $db_type;

    	$now = time();

		if ($user_id > 1) {
			$query = array(
				'UPDATE'	=> 'fancy_alerts_topics',
				'SET'		=> 'last_user_view='.$now,
				'WHERE'		=> 'user_id='.$user_id
			);

			$forum_db->query_build($query) or error(__FILE__, __LINE__);
		}
    }


	// CALLED ON USER DELETED
	public function on_del_user($user_id) {
		global $forum_db, $forum_user;

		if ($user_id < 2) {
			return;
		}

		// DEL TOPICS ALERTS
		$query = array(
			'DELETE'	=> 'fancy_alerts_topics',
			'WHERE'		=> 'user_id='.$user_id
		);
		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		// DEL QUOTE ALERTS
		$query = array(
			'DELETE'	=> 'fancy_alerts_posts',
			'WHERE'		=> 'user_id='.$user_id
		);
		$forum_db->query_build($query) or error(__FILE__, __LINE__);
	}




	// MISC ACTION "quotes_mark_read"
	public function action_quotes_mark_read() {
		global $forum_user, $forum_url, $lang_common, $lang_fancy_alerts, $forum_flash, $ext_info;

		if ($forum_user['is_guest']) {
			message($lang_common['No permission']);
		}

		// We validate the CSRF token. If it's set in POST and we're at this point, the token is valid.
		// If it's in GET, we need to make sure it's valid.
		if (!isset($_POST['csrf_token']) && (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== generate_form_token('fancy_alerts_quotes_mark_read'.$forum_user['id']))) {
			csrf_confirm_form();
		}

		// LOAD LANG
		if (!isset($lang_fancy_alerts)) {
			if ($forum_user['language'] != 'English' && file_exists($ext_info['path'].'/lang/'.$forum_user['language'].'/'.$ext_info['id'].'.php')) {
				require $ext_info['path'].'/lang/'.$forum_user['language'].'/'.$ext_info['id'].'.php';
			} else {
				require $ext_info['path'].'/lang/English/'.$ext_info['id'].'.php';
			}
		}

		// MARK
		$this->mark_all_quotes_as_readed($forum_user['id']);


		$forum_flash->add_info($lang_fancy_alerts['Mark all quotes redirect']);

		// REDIRECT TO INDEX
		redirect(forum_link($forum_url['index']), $lang_fancy_alerts['Mark all quotes redirect']);
	}


	// MISC ACTION "topics_mark_read"
	public function action_topics_mark_read() {
		global $forum_user, $forum_url, $lang_common, $lang_fancy_alerts, $forum_flash, $ext_info;

		if ($forum_user['is_guest']) {
			message($lang_common['No permission']);
		}

		// We validate the CSRF token. If it's set in POST and we're at this point, the token is valid.
		// If it's in GET, we need to make sure it's valid.
		if (!isset($_POST['csrf_token']) && (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== generate_form_token('fancy_alerts_topics_mark_read'.$forum_user['id']))) {
			csrf_confirm_form();
		}

		// LOAD LANG
		if (!isset($lang_fancy_alerts)) {
			if ($forum_user['language'] != 'English' && file_exists($ext_info['path'].'/lang/'.$forum_user['language'].'/'.$ext_info['id'].'.php')) {
				require $ext_info['path'].'/lang/'.$forum_user['language'].'/'.$ext_info['id'].'.php';
			} else {
				require $ext_info['path'].'/lang/English/'.$ext_info['id'].'.php';
			}
		}

		// MARK
		$this->mark_all_topics_as_readed($forum_user['id']);

		$forum_flash->add_info($lang_fancy_alerts['Mark all topics redirect']);

		// REDIRECT TO INDEX
		redirect(forum_link($forum_url['index']), $lang_fancy_alerts['Mark all topics redirect']);
	}


	// MISC ACTION "fancy_alerts_topics_on"
	public function action_alerts_topics_on() {
		global $forum_db, $forum_user, $forum_url, $lang_common, $lang_fancy_alerts, $forum_flash, $ext_info;

		if ($forum_user['is_guest']) {
			message($lang_common['No permission']);
		}

		// TOPIC ID
		$tid = isset($_GET['tid']) ? intval($_GET['tid']) : 0;
		if ($tid < 1) {
			message($lang_common['Bad request']);
		}

		// We validate the CSRF token. If it's set in POST and we're at this point, the token is valid.
		// If it's in GET, we need to make sure it's valid.
		if (!isset($_POST['csrf_token']) && (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== generate_form_token('fancy_alerts_topics_on'.$tid.$forum_user['id']))) {
			csrf_confirm_form();
		}

		// LOAD LANG
		if (!isset($lang_fancy_alerts)) {
			if ($forum_user['language'] != 'English' && file_exists($ext_info['path'].'/lang/'.$forum_user['language'].'/'.$ext_info['id'].'.php')) {
				require $ext_info['path'].'/lang/'.$forum_user['language'].'/'.$ext_info['id'].'.php';
			} else {
				require $ext_info['path'].'/lang/English/'.$ext_info['id'].'.php';
			}
		}

		// GET TOPIC LAST_POST_TIME AND SUBJECT
		// Make sure the user can view the topic
		$query = array(
			'SELECT'	=> 'subject, last_post, last_post_id',
			'FROM'		=> 'topics AS t',
			'JOINS'		=> array(
				array(
					'LEFT JOIN'		=> 'forum_perms AS fp',
					'ON'			=> '(fp.forum_id=t.forum_id AND fp.group_id='.$forum_user['g_id'].')'
				)
			),
			'WHERE'		=> '(fp.read_forum IS NULL OR fp.read_forum=1) AND t.id='.$tid.' AND t.moved_to IS NULL'
		);
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
		$cur_topic = $forum_db->fetch_assoc($result);

		if (!$cur_topic) {
			message($lang_common['Bad request']);
		}

		// DEL CURRENT TOPIC ALERTS
		$query = array(
			'DELETE'	=> 'fancy_alerts_topics',
			'WHERE'		=> 'user_id='.$forum_user['id'].' AND topic_id='.$tid
		);
		$forum_db->query_build($query) or error(__FILE__, __LINE__);


		// ADD NEW
		$this->add_new_topic_alerts($forum_user['id'], $tid, $cur_topic['last_post']);

		$forum_flash->add_info($lang_fancy_alerts['Alerts Topics on redirect']);

		// REDIRECT TO INDEX
		redirect(forum_link($forum_url['topic'], array($tid, sef_friendly($cur_topic['subject']))), $lang_fancy_alerts['Alerts Topics on redirect']);
	}


	// MISC ACTION "fancy_alerts_topics_off"
	public function action_alerts_topics_off() {
		global $forum_db, $forum_user, $forum_url, $lang_common, $lang_fancy_alerts, $forum_flash, $ext_info;

		if ($forum_user['is_guest']) {
			message($lang_common['No permission']);
		}

		// TOPIC ID
		$tid = isset($_GET['tid']) ? intval($_GET['tid']) : 0;
		if ($tid < 1) {
			message($lang_common['Bad request']);
		}

		// We validate the CSRF token. If it's set in POST and we're at this point, the token is valid.
		// If it's in GET, we need to make sure it's valid.
		if (!isset($_POST['csrf_token']) && (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== generate_form_token('fancy_alerts_topics_off'.$tid.$forum_user['id']))) {
			csrf_confirm_form();
		}

		// LOAD LANG
		if (!isset($lang_fancy_alerts)) {
			if ($forum_user['language'] != 'English' && file_exists($ext_info['path'].'/lang/'.$forum_user['language'].'/'.$ext_info['id'].'.php')) {
				require $ext_info['path'].'/lang/'.$forum_user['language'].'/'.$ext_info['id'].'.php';
			} else {
				require $ext_info['path'].'/lang/English/'.$ext_info['id'].'.php';
			}
		}

		// GET TOPIC LAST_POST_TIME AND SUBJECT
		// Make sure the user can view the topic
		$query = array(
			'SELECT'	=> 'subject, last_post',
			'FROM'		=> 'topics AS t',
			'JOINS'		=> array(
				array(
					'LEFT JOIN'		=> 'forum_perms AS fp',
					'ON'			=> '(fp.forum_id=t.forum_id AND fp.group_id='.$forum_user['g_id'].')'
				)
			),
			'WHERE'		=> '(fp.read_forum IS NULL OR fp.read_forum=1) AND t.id='.$tid.' AND t.moved_to IS NULL'
		);
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
		$cur_topic = $forum_db->fetch_assoc($result);

		if (!$cur_topic) {
			message($lang_common['Bad request']);
		}

		// DEL CURRENT TOPIC ALERTS
		$query = array(
			'DELETE'	=> 'fancy_alerts_topics',
			'WHERE'		=> 'user_id='.$forum_user['id'].' AND topic_id='.$tid
		);
		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		$forum_flash->add_info($lang_fancy_alerts['Alerts Topics off redirect']);

		// REDIRECT TO INDEX
		redirect(forum_link($forum_url['topic'], array($tid, sef_friendly($cur_topic['subject']))), $lang_fancy_alerts['Alerts Topics off redirect']);
	}


	public function action_get_status() {
		global $forum_db, $forum_user, $forum_url, $lang_common, $lang_fancy_alerts;

		$result = array('error'=> 1, 't' => 0, 'p' => 0);

		if ($forum_user['is_guest']) {
			exit(json_encode($result));
		}

		// TOPIC ID
		if ($forum_user['id'] < 2) {
			exit(json_encode($result));
		}

		$result['t'] = $this->get_num_topics_alerts_for_user($forum_user['id'], $forum_user['group_id']);
		$result['p'] = $this->get_num_quotes_alerts_for_user($forum_user['id'], $forum_user['group_id']);
		$result['error'] = 0;

		exit(json_encode($result));
	}


	//
    private function extract_usernames_from_message($post_text) {
		$usernames = array();
		$text_lines = explode("\n", $post_text);

		if (is_array($text_lines) && count($text_lines) > 0) {
			foreach ($text_lines as $line) {

				// DEFAULT QUOTE STYLE
				if (strpos($line, '[quote=') !== FALSE) {
					// PARSE LINE WITH QUOTE
					if (preg_match_all('#\[quote=(&quot;|"|\'|)(.*?)\\1\]#e', $line, $match)) {
						if (isset($match[2])) {
							$line_usernames = $match[2];

							foreach($line_usernames as $line_username) {
								$line_username = forum_trim($line_username);
								if (!empty($line_username)) {
									array_push($usernames, $line_username);
								}
							}
						}
					}
				} else {
					// [b]username[/b],
					if ('[b]' == utf8_substr($line, 0, 3)) {
						if (preg_match('#^\[b\](.*?)\[\/b\],#e', $line, $match)) {
							if (isset($match[1])) {
								$line_username = forum_trim($match[1]);
								if (!empty($line_username)) {
									// OK, have username
									array_push($usernames, $line_username);
								}
							}
						}
					}

				}
			}
		}

		// MAKE UNIQUE
		if (count($usernames) > 0) {
			$usernames = array_unique($usernames);
		}

		// MAKE NOT VERY BIG
		if (count($usernames) > 5) {
			array_splice($usernames, 5);
		}

		return $usernames;
    }


	//
	private function get_user_ids($usernames) {
		global $forum_db, $db_type, $forum_user;

    	$user_ids = array();

		if (is_array($usernames) && count($usernames) > 0) {
			function escape_usernames($username) {
				global $forum_db;
				return "'".$forum_db->escape($username)."'";
			}

			$e_usernames = array_map('escape_usernames', $usernames);

			// GET USER ids
			$query = array(
				'SELECT'	=> 'id',
				'FROM'		=> 'users',
				'WHERE'		=> 'username IN ('.implode(',', $e_usernames).')'
			);

			$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
			if ($result) {
				while ($row = $forum_db->fetch_row($result)) {
					if ($row[0] > 1 && ($row[0] != $forum_user['id'])) {
						array_push($user_ids, $row[0]);
					}
				}
			}
		}

		return $user_ids;
    }


	//
    private function insert_quote_alerts($user_ids, $topic_id, $post_id) {
    	global $forum_db, $db_type;
		// Add QUOTE ALERTS
		if (is_array($user_ids) && count($user_ids) > 0) {
			foreach ($user_ids as $user_id) {
				$query = array(
					'INSERT'	=> 'user_id, topic_id, post_id',
					'INTO'		=> 'fancy_alerts_posts',
					'VALUES'	=> '\''.$user_id.'\', \''.$topic_id.'\', \''.$post_id.'\''
				);

				$forum_db->query_build($query) or error(__FILE__, __LINE__);
			}
		}
    }


	//
    private function clear_quote_alerts($posts) {
		global $forum_db, $db_type;


		if (is_array($posts) && count($posts) > 0) {
			$query = array(
				'DELETE'	=> 'fancy_alerts_posts',
				'WHERE'		=> 'post_id IN('.implode(',', $posts).')'
			);
			$forum_db->query_build($query) or error(__FILE__, __LINE__);
		} else {
			$post_id = intval($posts, 10);

			if ($post_id > 0) {
				$query = array(
					'DELETE'	=> 'fancy_alerts_posts',
					'WHERE'		=> 'post_id='.$post_id
				);
				$forum_db->query_build($query) or error(__FILE__, __LINE__);
			}
		}
    }

    private function get_topic_owner($topic_id) {
		$owner_id = 0;

		$query = array(
			'SELECT'	=> 'p.poster_id AS owner_id',
			'FROM'		=> 'topics AS t',
			'JOINS' 	=> array(
				array(
					'LEFT JOIN'	=> 'posts AS p',
					'ON'		=> '(t.first_post_id=p.id)'
				),
			),
		);
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

    }
}

?>
