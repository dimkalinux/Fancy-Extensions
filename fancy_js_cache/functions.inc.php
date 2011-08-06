<?php

class FANCY_JS_CACHE {
	private $ext_info;
	private $current_fancy_css_base;


	//
	public function __construct($ext_info) {
		$this->ext_info = $ext_info;
		$this->current_fancy_css_base = null;
	}


	//
	public function run_css(&$libs) {
		global $forum_config, $base_url, $forum_loader, $fancy_css_base;

		// check for cache writeable dir
		if(!is_writable($this->ext_info['path'].'/cache/')) {
			return;
		}

		$fancyCSS_CacheFiles = $libs_proccessed = array();

		// config
		$css_cache_base_url = empty($forum_config['o_fancy_js_cache_css_cdn']) ? $this->ext_info['url'] : $forum_config['o_fancy_js_cache_css_cdn'].'/extensions/fancy_js_cache';

		$first_css_key_in_libs = false;

		foreach ($libs as $key => $lib) {
			if ($lib['type'] != 'url') {
				continue;
			}

			if ($lib['noscript'] === TRUE) {
				continue;
			}

			if ($lib['preprocess'] === FALSE) {
				continue;
			}

			if ($lib['media'] == 'print') {
				continue;
			}

			$file = array();
			$file['file'] = forum_trim($this->fancy_strip_querystring($lib['data']));
			$file['file_raw'] = $lib['data'];
			$file['file_on_server'] = FORUM_ROOT.substr($file['file_raw'], utf8_strlen($base_url));
			$file['base_name'] = basename(substr($file['file_raw'], utf8_strlen($base_url)));

			array_push($fancyCSS_CacheFiles, $file);
			if ($first_css_key_in_libs === false) {
				$first_css_key_in_libs = $key;
			} else {
				// Save key for unset in the end
				array_push($libs_proccessed, $key);
			}
			unset($file);
		}

		// Helper for rewrite CSS urls
		function fancy_css_url_cb($p) {
			global $fancy_css_base;

			$quoteChar = ($p[1][0] === "'" || $p[1][0] === '"') ? $p[1][0] : '';
			$uri = ($quoteChar === '') ? $p[1] : substr($p[1], 1, strlen($p[1]) - 2);

			if ((strpos($uri, 'http') === 0) || (strpos($uri, '/') === 0) || (strpos($uri, 'ftp') === 0) || (strpos($uri, 'data:') === 0)) {
				return $p[0];
			} else {
				return "url({$quoteChar}{$fancy_css_base}{$uri}{$quoteChar})";
			}
		}

		// Helper for rewrite AlphaImageLoader
		function fancy_css_src_cb($p) {
			global $fancy_css_base;

			$_m = array();
			$_a = explode(',', $p[1]);

			foreach ($_a as $b) {
				$b = forum_trim($b);
				$_b = explode('=', $b);

				if ($_b[0] == 'src') {
					$quoteChar = ($_b[1][0] === "'" || $_b[1][0] === '"') ? $_b[1][0] : '';
					$url = ($quoteChar === '') ? $_b[1] : substr($_b[1], 1, strlen($_b[1]) - 2);

					if ((strpos($url, 'http') === 0) || (strpos($url, '/') === 0) || (strpos($url, 'ftp') === 0) || (strpos($url, 'data:') === 0)) {
						// do nothing
					} else {
						$_b[1] = "{$quoteChar}{$fancy_css_base}{$url}{$quoteChar}";
					}
				}
				array_push($_m, implode('=', $_b));
			}

			return 'AlphaImageLoader('.implode(',', $_m).')';
		}

		// CSS
		if (count($fancyCSS_CacheFiles) < 1) {
			return;
		}

		$fancy_css_cache_key = 'css_cache_'.$this->create_hash($fancyCSS_CacheFiles).'.css';
		$fancy_css_cache_file = $this->ext_info['path'].'/cache/'.$fancy_css_cache_key;
		$fancy_css_output = '';
		$cache_writed_ok = FALSE;

		if (file_exists($fancy_css_cache_file)) {
			$cache_writed_ok = TRUE;
		} else {
			foreach ($fancyCSS_CacheFiles as $fancy_css_url) {
				$fancy_css_base = null;

				$fancy_css_file = $fancy_css_url['file_on_server'];
				$fancy_css_filename = $fancy_css_url['base_name'];
				$fancy_css_base = substr($fancy_css_url['file'], 0, -utf8_strlen(basename($fancy_css_url['file'])));

				if (file_exists($fancy_css_file)) {
					$fancy_css_content_file = file($fancy_css_file);

					// rewrite urls
					foreach ($fancy_css_content_file as $line_num => $line) {

						// CHECK URL(
						if (stripos($line, 'url(') !== FALSE) {
							$this->css_rerwite_urls($line);
						}

						// CHECK MICROSOFT AlphaImageLoader
						if (stripos($line, 'AlphaImageLoader') !== FALSE) {
							$this->css_rewrite_microsoft_rules($line);
						}

						// ADD REWRITED LINE
						$fancy_css_content_file[$line_num] = $line;
					}

					$fancy_css_output .= implode('', $fancy_css_content_file);
				}
			}

			// WRITE CACHE FILE
			$cache_writed_ok = $this->write_cache_file($fancy_css_cache_file, $fancy_css_output);
			unset($fancy_css_output);
		}

		// include new css
		if ($cache_writed_ok) {
			$libs[$first_css_key_in_libs]['data'] = $css_cache_base_url.'/cache/'.$fancy_css_cache_key;

			// unset old
			if (count($libs_proccessed) > 0) {
				foreach ($libs_proccessed as $key) {
					unset($libs[$key]);
				}
			}
		}
		unset($fancyCSS_CacheFiles);
	}


	//
	private function css_rerwite_urls(&$line) {
		// REPLACE url( with DELIMETER
		$line = preg_replace('/url\(/siu', '|7|url(', $line);

		// SPLIT LINE by URL(
		$u_lines = preg_split('/\|7\|/isu', $line);
		$line = '';

		foreach ($u_lines as $u_line) {
			if (stripos($u_line, 'url(') !== FALSE) {
				// CLEAN URL
				$u_line = preg_replace('/
					url\\(      # url(
					\\s*
					([^\\)]+?)  # 1 = URI (really just a bunch of non right parenthesis)
					\\s*
					\\)         # )
					/x', 'url($1)', $u_line);

				$line .= preg_replace_callback('/url\\(\\s*([^\\)\\s]+)\\s*\\)/is', 'fancy_css_url_cb', $u_line);
			} else {
				$line .= $u_line;
			}
		}
	}


	//
	private function css_rewrite_microsoft_rules(&$line) {
		// REPLACE url( with DELIMETER
		$line = preg_replace('/AlphaImageLoader/siu', '|8|AlphaImageLoader', $line);

		// SPLIT LINE by URL(
		$u_lines = preg_split('/\|8\|/isu', $line);
		$line = '';

		foreach ($u_lines as $u_line) {
			if (stripos($u_line, 'AlphaImageLoader') !== FALSE) {
				$line .= preg_replace_callback('/
					AlphaImageLoader\\(			# AlphaImageLoader(
					\\s*
					([^\\)]+?)
					\\s*
					\\)							# )
					/xis', 'fancy_css_src_cb', $u_line);
			} else {
				$line .= $u_line;
			}
		}
	}


	//
	private function fancy_strip_querystring($path) {
		if ($commapos = strpos($path, '?')) {
			$path = substr($path, 0, $commapos);
		}
		if ($numberpos = strpos($path, '#')) {
			$path = substr($path, 0, $numberpos);
		}
		return $path;
	}


	//
	private function create_hash($arr) {
		global $forum_config;

		$content = '';
		foreach ($arr as $fancy_url) {
			$file_name = $fancy_url['file_on_server'];
			$file_mtime = @/**/filemtime($fancy_url['file_on_server']);

			$content .= $file_name;
			if ($file_mtime !== FALSE) {
				$content .= ' '.$file_mtime;
			}
		}

		return $forum_config['o_fancy_js_cache_index'].'_'.sprintf("%u", crc32($content));
	}


	//
	private function write_cache_file($file, $content) {
		// Open
		$handle = @fopen($file, 'r+b'); // @ - file may not exist
		if (!$handle) {
			$handle = fopen($file, 'wb');
			if (!$handle) {
				return false;
			}
		}

		// Lock
		flock($handle, LOCK_EX);
		ftruncate($handle, 0);

		// Write
		if (fwrite($handle, $content) === false) {
			// Unlock and close
			flock($handle, LOCK_UN);
			fclose($handle);

			return false;
		}

		// Unlock and close
		flock($handle, LOCK_UN);
		fclose($handle);

		return true;
	}
}

?>
