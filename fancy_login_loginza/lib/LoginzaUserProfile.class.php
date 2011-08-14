<?php
/**
 * Класс LoginzaUserProfile предназначен для генерации некоторых полей профиля пользователя сайта,
 * на основе полученного профиля от Loginza API (http://loginza.ru/api-overview).
 *
 * При генерации используются несколько полей данных, что позволяет сгенерировать непереданные
 * данные профиля, на основе имеющихся.
 *
 * Например: Если в профиле пользователя не передано значение nickname, то это значение может быть
 * сгенерированно на основе email или full_name полей.
 *
 * Данный класс - это рабочий пример, который можно использовать как есть,
 * а так же заимствовать в собственном коде или расширять текущую версию под свои задачи.
 *
 * @link http://loginza.ru/api-overview
 * @author Sergey Arsenichev, PRO-Technologies Ltd.
 * @version 1.0
 */
class LoginzaUserProfile {
	/**
	 * Профиль
	 *
	 * @var unknown_type
	 */
	private $profile;

	/**
	 * Данные для транслита
	 *
	 * @var unknown_type
	 */
	private $translate = array(
	'а'=>'a', 'б'=>'b', 'в'=>'v', 'г'=>'g', 'д'=>'d', 'е'=>'e', 'ж'=>'g', 'з'=>'z',
	'и'=>'i', 'й'=>'y', 'к'=>'k', 'л'=>'l', 'м'=>'m', 'н'=>'n', 'о'=>'o', 'п'=>'p',
	'р'=>'r', 'с'=>'s', 'т'=>'t', 'у'=>'u', 'ф'=>'f', 'ы'=>'i', 'э'=>'e', 'А'=>'A',
	'Б'=>'B', 'В'=>'V', 'Г'=>'G', 'Д'=>'D', 'Е'=>'E', 'Ж'=>'G', 'З'=>'Z', 'И'=>'I',
	'Й'=>'Y', 'К'=>'K', 'Л'=>'L', 'М'=>'M', 'Н'=>'N', 'О'=>'O', 'П'=>'P', 'Р'=>'R',
	'С'=>'S', 'Т'=>'T', 'У'=>'U', 'Ф'=>'F', 'Ы'=>'I', 'Э'=>'E', 'ё'=>"yo", 'х'=>"h",
	'ц'=>"ts", 'ч'=>"ch", 'ш'=>"sh", 'щ'=>"shch", 'ъ'=>"", 'ь'=>"", 'ю'=>"yu", 'я'=>"ya",
	'Ё'=>"YO", 'Х'=>"H", 'Ц'=>"TS", 'Ч'=>"CH", 'Ш'=>"SH", 'Щ'=>"SHCH", 'Ъ'=>"", 'Ь'=>"",
	'Ю'=>"YU", 'Я'=>"YA"
	);

	function __construct($profile) {
		$this->profile = $profile;
	}

	public function genNickname () {
		if (!empty($this->profile->nickname)) {
			return $this->profile->nickname;
		} else if (!empty($this->nickname)) {
			return $this->nickname;
		} elseif (!empty($this->profile->email) && preg_match('/^(.+)\@/i', $this->profile->email, $nickname)) {
			return $nickname[1];
		} elseif (($fullname = $this->genFullName())) {
			return $this->normalize($fullname, '.');
		}

		// шаблоны по которым выцепляем ник из identity
		$patterns = array(
			'([^\.]+)\.ya\.ru',
			'openid\.mail\.ru\/[^\/]+\/([^\/?]+)',
			'openid\.yandex\.ru\/([^\/?]+)',
			'([^\.]+)\.myopenid\.com'
		);

		foreach ($patterns as $pattern) {
			if (preg_match('/^https?\:\/\/'.$pattern.'/i', $this->profile->identity, $result)) {
				return $result[1];
			}
		}

		return FALSE;
	}


	//
	public function genUserSite() {
		$web = FALSE;

		if (!empty($this->profile->web->blog)) {
			$web = forum_trim($this->profile->web->blog);
		} elseif (!empty($this->profile->web->default)) {
			$web = forum_trim($this->profile->web->default);
		} else {
			$web = forum_trim($this->profile->identity);
		}

		return $web;
	}


	//
	public function genFullName() {
		$name = FALSE;

		if (!empty($this->profile->name->full_name)) {
			$name = forum_trim($this->profile->name->full_name);
		} else if (!empty($this->profile->name->first_name) && !empty($this->profile->name->last_name)) {
			$name = forum_trim($this->profile->name->first_name.' '.$this->profile->name->last_name);
		} else if (!empty($this->profile->name->first_name)) {
			$name = forum_trim($this->profile->name->first_name);
		}

		return $name;
	}

	public function get_icq() {
		$icq = FALSE;

		if (!empty($this->profile->im->icq) && ctype_digit((string)/**/$this->profile->im->icq)) {
			$icq = forum_trim($this->profile->im->icq);
		}

		return $icq;
	}


	public function get_email() {
		$email = FALSE;

		if (!empty($this->profile->email)) {
			$email = strtolower(forum_trim($this->profile->email));
		}

		return $email;
	}

	/**
	 * Транслит + убирает все лишние символы заменяя на символ $delimer
	 *
	 * @param unknown_type $string
	 * @param unknown_type $delimer
	 * @return unknown
	 */
	private function normalize ($string, $delimer='-') {
		$string = strtr($string, $this->translate);
	    return forum_trim(preg_replace('/[^\w]+/i', $delimer, $string), $delimer);
	}
}

?>