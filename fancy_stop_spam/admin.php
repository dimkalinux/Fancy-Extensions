<?php

if (!defined('FORUM_ROOT'))
    define('FORUM_ROOT', '../../');
require FORUM_ROOT.'include/common.php';
require FORUM_ROOT.'include/common_admin.php';

if (!$forum_user['is_admmod'])
    message($lang_common['No permission']);

// Load the admin.php language file
require FORUM_ROOT.'lang/'.$forum_user['language'].'/admin_common.php';

$section = isset($_GET['section']) ? $_GET['section'] : 'logs';

if ($section == 'logs') {
    $forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;
    $forum_page['crumbs'] = array(
        array($forum_config['o_board_title'], forum_link($forum_url['index'])),
        array($lang_admin_common['Forum administration'], forum_link($forum_url['admin_index'])),
        array($lang_fancy_stop_spam['Admin section antispam'], forum_link($forum_url['fancy_stop_spam_admin_section'])),
        $lang_fancy_stop_spam['Admin submenu logs']
    );

    define('FORUM_PAGE_SECTION', 'fancy_stop_spam');
    define('FORUM_PAGE', 'admin-fancy_stop_spam_logs');
    require FORUM_ROOT.'header.php';
    ob_start();
?>
    <div class="main-subhead">
        <h2 class="hn"><span><?php echo $lang_fancy_stop_spam['Admin submenu logs header'] ?></span></h2>
    </div>
    <div class="main-content main-frm">
<?php if ($forum_config['o_fancy_stop_spam_use_logs'] == '0') { ?>
        <div class="ct-box info-box">
            <p><?php echo sprintf($lang_fancy_stop_spam['Admin logs disabled message'],
                '<a href="'.forum_link($forum_url['admin_settings_features']).'#fancy_stop_spam_settings">'.
                    $lang_fancy_stop_spam['Admin logs disabled message settings'].'</a>')  ?>
            </p>
        </div>
<?php } ?>
        <?php
            $fancy_stop_spam = Fancy_stop_spam::singleton();
            echo $fancy_stop_spam->print_logs();
        ?>
    </div>
<?php
    $tpl_temp = forum_trim(ob_get_contents());
    $tpl_main = str_replace('<!-- forum_main -->', $tpl_temp, $tpl_main);
    ob_end_clean();
    require FORUM_ROOT.'footer.php';
} else if ($section == 'new_users') {
    $forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;
    $forum_page['crumbs'] = array(
        array($forum_config['o_board_title'], forum_link($forum_url['index'])),
        array($lang_admin_common['Forum administration'], forum_link($forum_url['admin_index'])),
        array($lang_fancy_stop_spam['Admin section antispam'], forum_link($forum_url['fancy_stop_spam_admin_section'])),
        $lang_fancy_stop_spam['Admin submenu new users']
    );

    define('FORUM_PAGE_SECTION', 'fancy_stop_spam');
    define('FORUM_PAGE', 'admin-fancy_stop_spam_new_users');
    require FORUM_ROOT.'header.php';
    ob_start();
?>
    <div class="main-subhead">
        <h2 class="hn"><span><?php echo $lang_fancy_stop_spam['Admin submenu new users header'] ?></span></h2>
    </div>
    <div class="main-content main-frm">
        <?php
            $fancy_stop_spam = Fancy_stop_spam::singleton();
            $fancy_stop_spam->print_new_users();
        ?>
    </div>
<?php
    $tpl_temp = forum_trim(ob_get_contents());
    $tpl_main = str_replace('<!-- forum_main -->', $tpl_temp, $tpl_main);
    ob_end_clean();
    require FORUM_ROOT.'footer.php';
 } else if ($section == 'suspicious_users') {
    $forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;
    $forum_page['crumbs'] = array(
        array($forum_config['o_board_title'], forum_link($forum_url['index'])),
        array($lang_admin_common['Forum administration'], forum_link($forum_url['admin_index'])),
        array($lang_fancy_stop_spam['Admin section antispam'], forum_link($forum_url['fancy_stop_spam_admin_section'])),
        $lang_fancy_stop_spam['Admin submenu suspicious users']
    );

    define('FORUM_PAGE_SECTION', 'fancy_stop_spam');
    define('FORUM_PAGE', 'admin-fancy_stop_spam_suspicious_users');
    require FORUM_ROOT.'header.php';
    ob_start();
?>
    <div class="main-subhead">
        <h2 class="hn"><span><?php echo $lang_fancy_stop_spam['Admin submenu suspicious users header'] ?></span></h2>
    </div>
    <div class="main-content main-frm">
        <?php
            $fancy_stop_spam = Fancy_stop_spam::singleton();
            $fancy_stop_spam->print_suspicious_users();
        ?>
    </div>
<?php
    $tpl_temp = forum_trim(ob_get_contents());
    $tpl_main = str_replace('<!-- forum_main -->', $tpl_temp, $tpl_main);
    ob_end_clean();
    require FORUM_ROOT.'footer.php';
} else {
    message($lang_common['Bad request']);
}
