<?php

define('FANCY_TRACKER_FORUM_MODE_NO_TORRENTS', 0);
define('FANCY_TRACKER_FORUM_MODE_ONLY_TORRENTS', 1);
define('FANCY_TRACKER_FORUM_MODE_TORRENTS_AND_POSTS', 2);
define('FANCY_TRACKER_GS_NO_AUTHOR', 0);
define('FANCY_TRACKER_GS_SEED_AUTHOR', 1);
define('FANCY_TRACKER_GS_NO_SEED_AUTHOR', 2);


class Fancy_Tracker {
	public static function bytestostring($bytes, $precision = 0) {
		global $forum_user, $lang_tracker;

		if (file_exists(FORUM_ROOT.'extensions/fancy_tracker/lang/'.$forum_user['language'].'/fancy_tracker.php')) {
			require_once FORUM_ROOT.'extensions/fancy_tracker/lang/'.$forum_user['language'].'/fancy_tracker.php';
		} else {
			require_once FORUM_ROOT.'extensions/fancy_tracker/lang/English/fancy_tracker.php';
		}

		if ($bytes < 1024) {
			return "${bytes}&nbsp;{$lang_tracker['size_b']}";
		} else if ($bytes < 1048576) {
			return round(($bytes/1024), 1).'&nbsp;'.$lang_tracker['size_kb'];
		} else if ($bytes < 1073741824) {
			return round(($bytes/1048576), 1).'&nbsp;'.$lang_tracker['size_mb'];
		} else if ($bytes < 1099511627776) {
			return round(($bytes/1073741824), 1).'&nbsp;'.$lang_tracker['size_gb'];
		} else {
			return round(($bytes/1099511627776), 2).'&nbsp;'.$lang_tracker['size_tb'];
		}
	}

	public static function hex2bin($value) {
		return pack("H*", $value);
	}

	public static function benc_encode($array) {
		$string = '';
		$encoder = new Fancy_Tracker_BEncode;
		$encoder->decideEncode($array, $string);
		return $string;
	}

	public static function benc_decode($value) {
		$decoder = new Fancy_Tracker_BDecode;
		$return = $decoder->decodeEntry($value);
		return $return[0];
	}

	public static function benc_error($error) {
		global $forum_db;

		$forum_db->close();

		header('Content-Type: text/plain');
		exit(Fancy_Tracker::benc_encode(array('failure reason' => $error)));
	}

	public static function is_info_hash($hash) {
		return (strlen($hash) === 40 && preg_match('/^[0-9a-f]+$/', $hash));
	}
}


class Fancy_Tracker_BDecode {
	public function numberdecode($wholefile, $start) {
		$ret[0] = 0;
		$offset = $start;

		// Funky handling of negative numbers and zero
		$negative = FALSE;
		if ($wholefile[$offset] == '-') {
			$negative = TRUE;
			$offset++;
		}
		if ($wholefile[$offset] == '0') {
			$offset++;
			if ($negative)
				return array(FALSE);
			if ($wholefile[$offset] == ':' || $wholefile[$offset] == 'e') {
				$offset++;
				$ret[0] = 0;
				$ret[1] = $offset;
				return $ret;
			}
			return array(FALSE);
		}

		while (TRUE) {
			if ($wholefile[$offset] >= '0' && $wholefile[$offset] <= '9') {
				$ret[0] *= 10;
				$ret[0] += ord($wholefile[$offset]) - ord("0");
				$offset++;
			}
			// Tolerate : or e because this is a multiuse function
			else if ($wholefile[$offset] == 'e' || $wholefile[$offset] == ':') {
				$ret[1] = $offset+1;
				if ($negative) {
					if ($ret[0] == 0)
						return array(FALSE);
					$ret[0] = - $ret[0];
				}
				return $ret;
			}
			else
				return array(FALSE);
		}

	}

	public function decodeEntry($wholefile, $offset=0)	{
		if ($wholefile[$offset] == 'd')
			return $this->decodeDict($wholefile, $offset);
		if ($wholefile[$offset] == 'l')
			return $this->decodelist($wholefile, $offset);
		if ($wholefile[$offset] == "i")
		{
			$offset++;
			return $this->numberdecode($wholefile, $offset);
		}
		// String value: decode number, then grab substring
		$info = $this->numberdecode($wholefile, $offset);
		if ($info[0] === FALSE)
			return array(FALSE);
		$ret[0] = substr($wholefile, $info[1], $info[0]);
		$ret[1] = $info[1]+strlen($ret[0]);
		return $ret;
	}

	public function decodeList($wholefile, $start) {
		$offset = $start+1;
		$i = 0;
		if ($wholefile[$start] != 'l')
			return array(FALSE);
		$ret = array();
		while (TRUE) {
			if ($wholefile[$offset] == 'e')
				break;
			$value = $this->decodeEntry($wholefile, $offset);
			if ($value[0] === FALSE)
				return array(FALSE);
			$ret[$i] = $value[0];
			$offset = $value[1];
			$i ++;
		}

		// The empy list is an empty array. Seems fine.
		$final[0] = $ret;
		$final[1] = $offset+1;
		return $final;
	}

	// Tries to construct an array
	public function decodeDict($wholefile, $start=0) {
		$offset = $start;
		if ($wholefile[$offset] == 'l')
			return $this->decodeList($wholefile, $start);
		if ($wholefile[$offset] != 'd')
			return FALSE;
		$ret = array();
		$offset++;
		while (TRUE) {
			if ($wholefile[$offset] == 'e') {
				$offset++;
				break;
			}
			$left = $this->decodeEntry($wholefile, $offset);
			if (!$left[0])
				return FALSE;
			$offset = $left[1];
			if ($wholefile[$offset] == 'd') {
				// Recurse
				$value = $this->decodedict($wholefile, $offset);
				if (!$value[0])
					return FALSE;
				$ret[addslashes($left[0])] = $value[0];
				$offset= $value[1];
				continue;
			} else if ($wholefile[$offset] == 'l') {
				$value = $this->decodeList($wholefile, $offset);
				if (!$value[0] && is_bool($value[0]))
					return FALSE;
				$ret[addslashes($left[0])] = $value[0];
				$offset = $value[1];
			} else {
	 			$value = $this->decodeEntry($wholefile, $offset);
				if ($value[0] === FALSE)
					return FALSE;
				$ret[addslashes($left[0])] = $value[0];
				$offset = $value[1];
			}
		}
		if (empty($ret))
			$final[0] = TRUE;
		else
			$final[0] = $ret;
		$final[1] = $offset;
	   	return $final;
	}
}


class Fancy_Tracker_BEncode {
	// Encodes strings, integers and empty dictionaries.
	// $unstrip is set to TRUE when decoding dictionary keys
	public function encodeEntry($entry, &$fd, $unstrip = FALSE) {
		if (is_bool($entry)) {
			$fd .= "de";
			return;
		}

		if (is_int($entry) || is_float($entry)) {
			$fd .= "i".$entry."e";
			return;
		}
		if ($unstrip)
			$myentry = stripslashes($entry);
		else
			$myentry = $entry;
		$length = strlen($myentry);
		$fd .= $length.":".$myentry;
		return;
	}

	// Encodes lists
	public function encodeList($array, &$fd) {
		$fd .= "l";

		// The empty list is defined as array();
		if (empty($array)) {
			$fd .= "e";
			return;
		}

		for ($i = 0; isset($array[$i]); $i++) {
			$this->decideEncode($array[$i], $fd);
		}

		$fd .= "e";
	}

	// Passes lists and dictionaries accordingly, and has encodeEntry handle
	// the strings and integers.
	public function decideEncode($unknown, &$fd) {
		if (is_array($unknown)) {
			if (isset($unknown[0]) || empty($unknown))
				return $this->encodeList($unknown, $fd);
			else
				return $this->encodeDict($unknown, $fd);
		}
		$this->encodeEntry($unknown, $fd);
	}

	// Encodes dictionaries
	public function encodeDict($array, &$fd) {
		$fd .= "d";
		if (is_bool($array)) {
			$fd .= "e";
			return;
		}
		// NEED TO SORT!
		//$newarray = $this->makeSorted($array);
		ksort($array, SORT_STRING);

		foreach($array as $left => $right) {
			$this->encodeEntry($left, $fd, TRUE);
			$this->decideEncode($right, $fd);
		}

		$fd .= "e";
		return;
	}
}

?>
