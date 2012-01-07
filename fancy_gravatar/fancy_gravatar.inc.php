<?php

class Fancy_gravatar {
    private $user_id = NULL;
    private $user_email = NULL;


    public function __construct($user_id, $user_email) {
        $this->set_user_id($user_id);
        $this->set_user_email($user_email);
    }


    // Retrieve gravatar for gravatar server
    public function fetch_gravatar() {
        $gravatar_url = $this->generate_gravatar_url();
        $gravatar_data = get_remote_file($gravatar_url, 10, FALSE, 2);

        if (!empty($gravatar_data) && !empty($gravatar_data['content'])) {
            try {
                $tmpfname = tempnam("/tmp", "GRAVATAR-".md5($this->get_user_email()));
                $handle = @/**/fopen($tmpfname, "w");

                if ($handle !== FALSE) {
                    fwrite($handle, $gravatar_data['content']);
                    fclose($handle);

                    $this->save_gravatar($tmpfname);
                } else {
                    throw new Exception("Fancy_gravatar: can not open temporary file for writing.");
                }
            } catch (Exception $exception) {
                if (!empty($tmpfname) && file_exists($tmpfname)) {
                    unlink($tmpfname);
                }
            }
        }
    }


    // Save gravatar as user avatar
    private function save_gravatar($tmp_gravatar) {
        global $forum_config, $forum_db;

        // Avatar filenames in avatar directory
        $avatar_tmp_name = $forum_config['o_avatars_dir'].'/'.$this->get_user_id().'.tmp';

        // Move the file to the avatar directory. We do this before checking the width/height to circumvent open_basedir restrictions.
        if (!@/**/rename($tmp_gravatar, $avatar_tmp_name)) {
            throw new Exception("Fancy_gravatar: can not move gravatar to avatar directory.");
        }

        try {
            list($gravatar_width, $gravatar_height, $gravatar_type,) = @/**/getimagesize($avatar_tmp_name);

            if (!in_array($gravatar_type, array(IMAGETYPE_JPEG, IMAGETYPE_PNG))) {
                throw new Exception("Fancy_gravatar: invalid gravatar type.");
            }

            if (filesize($avatar_tmp_name) > $forum_config['o_avatars_size']) {
                throw new Exception("Fancy_gravatar: invalid gravatar size.");
            }

            // Determine type
            $avatar_extension = null;
            $avatar_type = FORUM_AVATAR_NONE;
            if ($gravatar_type == IMAGETYPE_JPEG) {
                $avatar_extension = '.jpg';
                $avatar_type = FORUM_AVATAR_JPG;
            } else if ($gravatar_type == IMAGETYPE_PNG) {
                $avatar_extension = '.png';
                $avatar_type = FORUM_AVATAR_PNG;
            } else {
                throw new Exception("Fancy_gravatar: invalid forum avatar type.");
            }

            $avatar_name = $forum_config['o_avatars_dir'].'/'.$this->get_user_id().$avatar_extension;
            if (empty($gravatar_width) ||
                empty($gravatar_height) ||
                $gravatar_width > $forum_config['o_avatars_width'] ||
                $gravatar_height > $forum_config['o_avatars_height']) {
                throw new Exception("Fancy_gravatar: invalid gravatar dimensions.");
            }

            // Delete any old avatars
            delete_avatar($this->get_user_id());

            // Put the new avatar in its place
            @/**/rename($avatar_tmp_name, $avatar_name);
            @/**/chmod($avatar_name, 0644);

            // Avatar
            $avatar_width = (intval($gravatar_width) > 0) ? intval($gravatar_width) : 0;
            $avatar_height = (intval($gravatar_height) > 0) ? intval($gravatar_height) : 0;

            // Save to DB
            $query = array(
                'UPDATE'    => 'users',
                'SET'       => 'avatar=\''.$avatar_type.'\', avatar_height=\''.$avatar_width.'\', avatar_width=\''.$avatar_height.'\'',
                'WHERE'     => 'id='.$this->get_user_id()
            );
            $forum_db->query_build($query) or error(__FILE__, __LINE__);
        } catch (Exception $exception) {
            if (!empty($avatar_tmp_name) && file_exists($avatar_tmp_name)) {
                unlink($avatar_tmp_name);
            }
            throw $exception;
        }
    }


    // Generate URL for gravatar for user based on user email
    private function generate_gravatar_url() {
        global $forum_config;

        $dimensions = min($forum_config['o_avatars_width'], $forum_config['o_avatars_height'], 60);
        return sprintf('http://www.gravatar.com/avatar/%s?size=%s&d=404', md5($this->get_user_email()), $dimensions);
    }


    // Setter for user email
    private function set_user_id($user_id) {
        $user_id = intval($user_id, 10);
        if ($user_id > 0) {
            $this->user_id = $user_id;
            return $this;
        }

        throw new Exception("Fancy_gravatar: invalid user identificator.");
    }


    // Getter for user id
    private function get_user_id() {
        return $this->user_id;
    }


    // Setter for user email
    private function set_user_email($user_email) {
        if (!empty($user_email)) {
            $this->user_email = $user_email;
            return $this;
        }

        throw new Exception("Fancy_gravatar: invalid user email.");
    }


    // Getter for user email
    private function get_user_email() {
        return $this->user_email;
    }
}
