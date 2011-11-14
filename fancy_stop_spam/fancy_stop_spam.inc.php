<?php

// CONFIG
define('FANCY_STOP_SPAM_MESSAGE_MIN_LENGTH_FOR_IDENTICAL_CHECK', 24);
define('FANCY_STOP_SPAM_USER_MAX_POSTS_FOR_CHECK', 5);
define('FANCY_STOP_SPAM_USER_MIN_FORM_FILL_TIME', 3);
define('FANCY_STOP_SPAM_USER_IDENTICAL_POST_LIFETIME', 10800);
define('FANCY_STOP_SPAM_EMAIL_SFS_CACHE_LIFETIME', 259200);
define('FANCY_STOP_SPAM_EMAIL_IP_SFS_CACHE_LIFETIME', 3600);
define('FANCY_STOP_SPAM_IP_SFS_CACHE_LIFETIME', 259200);
define('FANCY_STOP_SPAM_SIGNATURE_HIDE_TIME', 600);
define('FANCY_STOP_SPAM_IP_ACTIVITY_LIFETIME', 7776000);    // 90 days

define('FANCY_STOP_SPAM_SUBMIT_MARK', ' ');

// LOGS EVENTS REGISTER
define('FANCY_STOP_SPAM_LOG_REGISTER_SUBMIT', 1);
define('FANCY_STOP_SPAM_LOG_REGISTER_TIMEOUT', 2);
define('FANCY_STOP_SPAM_LOG_REGISTER_TIMEZONE', 3);
define('FANCY_STOP_SPAM_LOG_REGISTER_HONEYPOT', 4);
define('FANCY_STOP_SPAM_LOG_REGISTER_HONEYPOT_EMPTY', 5);
define('FANCY_STOP_SPAM_LOG_REGISTER_EMAIL_SFS', 6);
define('FANCY_STOP_SPAM_LOG_REGISTER_EMAIL_SFS_CACHE', 7);
define('FANCY_STOP_SPAM_LOG_REGISTER_EMAIL_SFS_IP_CACHE', 8);
define('FANCY_STOP_SPAM_LOG_REGISTER_IP_SFS', 9);
define('FANCY_STOP_SPAM_LOG_REGISTER_IP_SFS_CACHE', 10);

// LOGS EVENTS ACTIVATE
define('FANCY_STOP_SPAM_LOG_ACTIVATE_SUBMIT', 70);
define('FANCY_STOP_SPAM_LOG_ACTIVATE_HONEYPOT', 71);
define('FANCY_STOP_SPAM_LOG_ACTIVATE_HONEYPOT_EMPTY', 72);


// LOGS EVENTS POST
define('FANCY_STOP_SPAM_LOG_POST_SUBMIT', 20);
define('FANCY_STOP_SPAM_LOG_POST_TIMEOUT', 21);
define('FANCY_STOP_SPAM_LOG_POST_HONEYPOT', 22);
define('FANCY_STOP_SPAM_LOG_POST_HONEYPOT_EMPTY', 23);

// LOGS EVENTS LOGIN
define('FANCY_STOP_SPAM_LOG_LOGIN_HONEYPOT', 40);
define('FANCY_STOP_SPAM_LOG_LOGIN_HONEYPOT_EMPTY', 41);

// LOGS EVENTS SIGNATURE
define('FANCY_STOP_SPAM_LOG_SIGNATURE_HIDDEN', 60);


//
function fancy_stop_spam_log($activity_type, $user_id, $user_ip, $comment='') {
    global $forum_db, $forum_config;

    // LOGS enabled?
    if ($forum_config['o_fancy_stop_spam_use_logs'] == '0') {
        return TRUE;
    }

    $comment = utf8_substr($comment, 0, 200);

    // CLEAR OLD ENTRIES
    fancy_stop_spam_clear_old_logs();

    $now = time();

    $query = array(
        'INSERT'    => 'user_id, ip, activity_type, activity_time, comment',
        'INTO'      => 'fancy_stop_spam_logs',
        'VALUES'    => '\''.intval($user_id, 10).'\', \''.fancy_stop_spam_ip2long($user_ip).'\', \''.intval($activity_type, 10).'\', \''.$now.'\', \''.$forum_db->escape($comment).'\''
    );
    $forum_db->query_build($query) or error(__FILE__, __LINE__);
}


// Clear old logs
function fancy_stop_spam_clear_old_logs() {
    global $forum_db;

    if (fancy_stop_spam_get_num_logs() > 500) {
        $max_old_id = fancy_stop_spam_get_last_old_id_logs();

        if ($max_old_id > 0) {
            // DEL OLDEST
            $query = array(
                'DELETE'    => 'fancy_stop_spam_logs',
                'WHERE'     => 'id < '.$max_old_id
            );
            $forum_db->query_build($query) or error(__FILE__, __LINE__);
        }
    }
}


//
function fancy_stop_spam_get_num_logs() {
    global $forum_db;

    $query = array(
        'SELECT'    => 'COUNT(*) AS num',
        'FROM'      => 'fancy_stop_spam_logs',
    );
    $result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
    return intval($forum_db->result($result), 10);
}


//
function fancy_stop_spam_get_last_old_id_logs() {
    global $forum_db;

    $query = array(
        'SELECT'    => 'id',
        'FROM'      => 'fancy_stop_spam_logs',
        'ORDER BY'  => 'id DESC',
        'LIMIT'     => '450, 1'
    );
    $result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
    return intval($forum_db->result($result), 10);
}

//
function fancy_stop_spam_inc_user_bot($user_id) {
    global $forum_db;

    $user_id = intval($user_id, 10);

    // Update the user table
    if ($user_id > 0) {
        $query = array(
            'UPDATE'    => 'users',
            'SET'       => 'fancy_stop_spam_bot=fancy_stop_spam_bot+1',
            'WHERE'     => 'id='.$user_id
        );
        $forum_db->query_build($query) or error(__FILE__, __LINE__);
    }
}

//
function fancy_stop_spam_add_identical_message($poster_id, $post_id, $message_hash, $posted) {
    global $forum_db;

    // REMOVE EXPIRED
    fancy_stop_spam_prune_expired_identical();

    // Add the post hash
    $query = array(
        'INSERT'    => 'poster_id, post_id, post_hash, posted',
        'INTO'      => 'fancy_stop_spam_identical_posts',
        'VALUES'    => '\''.intval($poster_id, 10).'\', '.intval($post_id, 10).', \''.$forum_db->escape($message_hash).'\', '.$posted
    );
    $forum_db->query_build($query) or error(__FILE__, __LINE__);
}

//
function fancy_stop_spam_prune_expired_identical() {
    global $forum_db;

    // REMOVE EXPIRED - 3 HOUR
    $query = array(
        'DELETE'    => 'fancy_stop_spam_identical_posts',
        'WHERE'     => 'posted < '.(time() - FANCY_STOP_SPAM_USER_IDENTICAL_POST_LIFETIME)
    );
    $forum_db->query_build($query) or error(__FILE__, __LINE__);
}

//
function fancy_stop_spam_check_is_identical_spam($poster_id, $message_hash) {
    global $forum_db;
    $is_spam = FALSE;

    // REMOVE EXPIRED
    fancy_stop_spam_prune_expired_identical();

    $query = array(
        'SELECT'    => 'COUNT(f.id)',
        'FROM'      => 'fancy_stop_spam_identical_posts AS f',
        'WHERE'     => 'f.poster_id='.intval($poster_id).' AND post_hash=\''.$forum_db->escape($message_hash).'\''
    );
    $result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
    $num_identical_messages = $forum_db->result($result, 0);

    return (bool)/**/($num_identical_messages > 0);
}


//
function fancy_stop_spam_get_num_links_in_text($post_message) {
    $num_links_http = $num_links_www = 0;

    if (function_exists('mb_substr_count')) {
        $num_links_http = mb_substr_count($post_message, 'http', 'UTF-8');
        $num_links_www = mb_substr_count($post_message, 'www', 'UTF-8');
    } else {
        $num_links_http = substr_count($post_message, 'http');
        $num_links_www = substr_count($post_message, 'www');
    }

    return max($num_links_http, $num_links_www);
}

//
function fancy_stop_spam_check_max_links($post_message) {
    global $forum_user, $forum_config, $lang_fancy_stop_spam;

    // Load LANG
    if (!isset($lang_fancy_stop_spam)) {
        if ($forum_user['language'] != 'English' && file_exists($ext_info['path'].'/lang/'.$forum_user['language'].'/'.$ext_info['id'].'.php')) {
            require $ext_info['path'].'/lang/'.$forum_user['language'].'/'.$ext_info['id'].'.php';
        } else {
            require $ext_info['path'].'/lang/English/'.$ext_info['id'].'.php';
        }
    }

    $return = TRUE;

    $max_links = intval($forum_config['o_fancy_stop_spam_max_links'], 10);
    if ($forum_user['is_guest']) {
        $max_links = intval($forum_config['o_fancy_stop_spam_max_guest_links'], 10);
    }

    do {
        if ($max_links < 0) {
            break;
        }

        if ($forum_user['num_posts'] > FANCY_STOP_SPAM_USER_MAX_POSTS_FOR_CHECK) {
            break;
        }

        if ($forum_user['is_admmod']) {
            break;
        }

        if (fancy_stop_spam_get_num_links_in_text($post_message) > $max_links) {
            $return = sprintf($lang_fancy_stop_spam['Error many links'], $max_links);
        }
    } while(FALSE);

    return $return;
}

//
function fancy_stop_spam_debug_log($x, $m = null) {
    if (!defined('FANCY_STOP_SPAM_DEBUG_LOG')) {
        return;
    }

    if (is_writable(FANCY_STOP_SPAM_DEBUG_LOG)) {
        if (is_array($x)) {
            ob_start();
            print_r($x);
            $x = $m.($m != null ? "\n" : '').ob_get_clean();
        } else {
            $x .= "\n";
        }

        error_log(strftime('%c').' '.$x . "\n", 3, FANCY_STOP_SPAM_DEBUG_LOG);
    }
}


function fancy_stop_spam_check_by_sfs(&$errors, $data = array()) {
    global $forum_db, $forum_user, $forum_config, $lang_fancy_stop_spam;

    $need_check_email = ($forum_config['o_fancy_stop_spam_register_form_sfs_email'] == '1' && !empty($data['email']));
    $need_check_ip = ($forum_config['o_fancy_stop_spam_register_form_sfs_ip'] == '1' && !empty($data['ip']));
    $spam_data = NULL;

    // IP CHECKS
    if ($need_check_ip) {
         // Clear ip cache
        $query = array(
            'DELETE'    => 'fancy_stop_spam_sfs_ip_cache',
            'WHERE'     => 'added < '.(time() - FANCY_STOP_SPAM_IP_SFS_CACHE_LIFETIME)
        );
        $result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

        $query = array(
            'SELECT'    => 'COUNT(ip)',
            'FROM'      => 'fancy_stop_spam_sfs_ip_cache',
            'WHERE'     => 'ip=\''.$forum_db->escape(fancy_stop_spam_ip2long($data['ip'])).'\''
        );
        $result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
        if ($forum_db->result($result) > 0) {
            fancy_stop_spam_log(FANCY_STOP_SPAM_LOG_REGISTER_IP_SFS_CACHE, $forum_user['id'], get_remote_address());
            message($lang_fancy_stop_spam['Register bot sfs email message']);
        }

        if (is_null($spam_data)) {
            $spam_data = fancy_stop_spam_make_request_to_sfs($data);
            if ($spam_data !== FALSE && isset($spam_data['ip']) && is_array($spam_data['ip'])) {
                if (!empty($spam_data['ip']['appears']) && !empty($spam_data['ip']['frequency']) && intval($spam_data['ip']['frequency'], 10) > 1) {
                    if (!empty($spam_data['ip']['lastseen']) && $spam_data['ip']['lastseen'] > (time() - FANCY_STOP_SPAM_IP_ACTIVITY_LIFETIME)) {
                        $query = array(
                            'INSERT'    => 'ip, added',
                            'INTO'      => 'fancy_stop_spam_sfs_ip_cache',
                            'VALUES'    => '\''.$forum_db->escape(fancy_stop_spam_ip2long(get_remote_address())).'\', '.time()
                        );
                        $forum_db->query_build($query) or error(__FILE__, __LINE__);
                        fancy_stop_spam_log(FANCY_STOP_SPAM_LOG_REGISTER_IP_SFS, $forum_user['id'], get_remote_address());
                        message($lang_fancy_stop_spam['Register bot sfs ip message']);
                    }
                }
            }
        }
    }

    // EMAIL CHECKS
    if ($need_check_email) {
        // Clear email cache
        $query = array(
            'DELETE'    => 'fancy_stop_spam_sfs_email_cache',
            'WHERE'     => 'added < '.(time() - FANCY_STOP_SPAM_EMAIL_SFS_CACHE_LIFETIME)
        );
        $forum_db->query_build($query) or error(__FILE__, __LINE__);

        // Check email in email cache
        $query = array(
            'SELECT'    => 'COUNT(email)',
            'FROM'      => 'fancy_stop_spam_sfs_email_cache',
            'WHERE'     => 'email=\''.$forum_db->escape($data['email']).'\''
        );
        $result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
        if ($forum_db->result($result) > 0) {
            fancy_stop_spam_log(FANCY_STOP_SPAM_LOG_REGISTER_EMAIL_SFS_CACHE, $forum_user['id'], get_remote_address());
            message($lang_fancy_stop_spam['Register bot sfs email message']);
        }

        // Check ip in email cache
        if (!empty($data['ip'])) {
            $query = array(
                'SELECT'    => 'COUNT(ip)',
                'FROM'      => 'fancy_stop_spam_sfs_email_cache',
                'WHERE'     => 'ip=\''.$forum_db->escape(fancy_stop_spam_ip2long($data['ip'])).'\' AND added < '.time() - FANCY_STOP_SPAM_EMAIL_IP_SFS_CACHE_LIFETIME
            );
            $result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
            if ($forum_db->result($result) > 0) {
                fancy_stop_spam_log(FANCY_STOP_SPAM_LOG_REGISTER_EMAIL_SFS_IP_CACHE, $forum_user['id'], get_remote_address());
                $errors[] = $lang_fancy_stop_spam['Register bot sfs email ip message'];
                return;
            }
        }

        // Check email in SFS
        if (is_null($spam_data)) {
            $spam_data = fancy_stop_spam_make_request_to_sfs($data);
        }
        if ($spam_data !== FALSE && isset($spam_data['email']) && is_array($spam_data['email'])) {
            if (!empty($spam_data['email']['appears'])) {
                fancy_stop_spam_log(FANCY_STOP_SPAM_LOG_REGISTER_EMAIL_SFS, $forum_user['id'], get_remote_address());

                // Add to cache
                $query = array(
                    'INSERT'    => 'email, added, ip',
                    'INTO'      => 'fancy_stop_spam_sfs_email_cache',
                    'VALUES'    => '\''.$forum_db->escape($data['email']).'\', '.time().', '.fancy_stop_spam_ip2long(get_remote_address())
                );
                $forum_db->query_build($query) or error(__FILE__, __LINE__);
                message($lang_fancy_stop_spam['Register bot sfs email message']);
            }
        }
    }
}



function fancy_stop_spam_make_request_to_sfs($data = array()) {
    $result = FALSE;

    if (!empty($data)) {
        if (function_exists('json_decode')) {
            $data['f'] = 'json';
        } else {
            $data['f'] = 'serial';
        }

        $data['unix'] = '1';

        $check_url = 'http://www.stopforumspam.com/api?'.http_build_query($data);
        $check_result = get_remote_file($check_url, 12, FALSE, 2);

        if (isset($check_result['content']) !== FALSE && !empty($check_result['content'])) {
            if ($data['f'] == 'json') {
                $result_data = json_decode($check_result['content'], TRUE);
            } else {
                $result_data = unserialize($check_result['content']);
            }

            if (!empty($result_data)) {
                if (is_array($result_data) && isset($result_data['success']) && intval($result_data['success'], 10) === 1) {
                    $result = $result_data;
                }
            }
        }
    }

    return $result;
}

function fancy_stop_spam_ip2long($ip) {
    return sprintf('%u', ip2long($ip));
}
