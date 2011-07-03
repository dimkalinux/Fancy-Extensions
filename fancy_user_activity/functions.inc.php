<?php

class Fancy_user_activity {
    //
    private $_user_id = 0;
    private $_user_ip = 0;

    //
    const LOGIN = 1;
    const CHANGE_PASS = 2;
    const CHANGE_KEYPASS = 3;
    const CHANGE_EMAIL = 4;


    //
    public function activity_login($user_id, $user_ip) {
		$this->log_activity(self::LOGIN, $user_id, $user_ip);
    }

    //
    public function activity_change_pass($user_id, $user_ip) {
		$this->log_activity(self::CHANGE_PASS, $user_id, $user_ip);
    }

    //
    public function activity_change_keypass($user_id, $user_ip) {
		$this->log_activity(self::CHANGE_KEYPASS, $user_id, $user_ip);
    }

    //
    public function activity_change_email($user_id, $user_ip) {
		$this->log_activity(self::CHANGE_EMAIL, $user_id, $user_ip);
    }


    //
    public function show_activity() {
		global $forum_db, $forum_user, $forum_config, $forum_page, $lang_fancy_user_activity, $user, $id;

		$out = '';

		$query = array(
			'SELECT'	=> 'a.activity_type, INET_NTOA(a.ip) AS ip, a.activity_time',
			'FROM'	=> 'fancy_user_activity AS a',
			'WHERE'	=> 'a.user_id='.$id,
			'ORDER BY'	=> 'a.id DESC',
			'LIMIT'	=> '50'
		);

		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		if (!$forum_db->num_rows($result)) {
			$out = '<div class="ct-box info-box"><p>'.$lang_fancy_user_activity['No activity'].'</p></div>';
		} else {
			while ($cur_act = $forum_db->fetch_assoc($result)) {
				$out .= '<tr> <td>'.$this->get_activity_name($cur_act['activity_type']).'</td> <td>'.$cur_act['ip'].'</td> <td>'.format_time($cur_act['activity_time']).'</td> </tr>';
			}

			$summary = sprintf(($forum_user['id'] == $id) ? $lang_fancy_user_activity['Activity welcome'] : $lang_fancy_user_activity['Activity welcome user'], forum_htmlencode($user['username']));

	    	$table = '<div class="ct-group">
		<table cellpadding="0" summary="'.$summary.'">
		<thead>
		<tr>
		    <th class="tc0" scope="col">'.$lang_fancy_user_activity['Type'].'</th>
		    <th class="tc1" scope="col">'.$lang_fancy_user_activity['IP'].'</th>
		    <th class="tc2" scope="col">'.$lang_fancy_user_activity['Time'].'</th>
		</tr>
		</thead>
		<tbody>%s</tbody>
		</table>
	    </div>';

		$out = sprintf($table, $out);
		}

		echo $out;
    }

    //
    private function get_activity_name($activity_type=0) {
		global $lang_fancy_user_activity;

		$an = '';
		switch ($activity_type) {
			case self::LOGIN:
			$an = $lang_fancy_user_activity['Activity type login'];
			break;

			case self::CHANGE_PASS:
			$an = $lang_fancy_user_activity['Activity type pass'];
			break;

			case self::CHANGE_KEYPASS:
			$an = $lang_fancy_user_activity['Activity type keypass'];
			break;

			 case self::CHANGE_EMAIL:
			$an = $lang_fancy_user_activity['Activity type email'];
			break;

			default:
			$an = $lang_fancy_user_activity['Activity type error'];
			break;
		}

		return $an;
    }


    private function log_activity($activity_type, $user_id, $user_ip) {
		global $forum_db;

		// CHECK USER ID
		$user_id = intval($user_id, 10);
		if ($user_id < 2) {
	    	return;
		}

		// CLEAR OLD ENTRIES
		$this->clear_old_activity($user_id);

		$now = time();

		$query = array(
	    	'INSERT'	=> 'user_id, ip, activity_type, activity_time',
	    	'INTO'		=> 'fancy_user_activity',
	    	'VALUES'	=> '\''.intval($user_id, 10).'\', INET_ATON(\''.$user_ip.'\'), \''.$activity_type.'\', \''.$now.'\''
		);

		$forum_db->query_build($query) or error(__FILE__, __LINE__);
    }

    //
    private function clear_old_activity($user_id) {
		global $forum_db, $db_type;

		if ($this->get_num_logs_for_user($user_id) > 300) {
			$max_old_id = $this->get_last_old_id_logs_for_user($user_id);

			if ($max_old_id > 0) {
				// DEL OLDEST
				$query = array(
					'DELETE'	=> 'fancy_user_activity',
					'WHERE'		=> 'user_id='.$user_id.' AND id < '.$max_old_id
				);

				$forum_db->query_build($query) or error(__FILE__, __LINE__);
			}
		}
    }

    //
    private function get_num_logs_for_user($user_id) {
		global $forum_db, $db_type;

		$query = array(
	    	'SELECT'	=> 'COUNT(*) AS num',
	    	'FROM'		=> 'fancy_user_activity',
	    	'WHERE'		=> 'user_id='.$user_id
		);
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
		return intval($forum_db->result($result), 10);
    }

	private function get_last_old_id_logs_for_user($user_id) {
		global $forum_db, $db_type;

		$query = array(
	    	'SELECT'	=> 'id',
	    	'FROM'		=> 'fancy_user_activity',
	    	'WHERE'		=> 'user_id='.$user_id,
			'ORDER BY'	=> 'id DESC',
			'LIMIT'		=> '200, 1'
		);
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
		return intval($forum_db->result($result), 10);
    }
}

?>
