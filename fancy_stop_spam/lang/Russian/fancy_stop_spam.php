<?php

if (!defined('FORUM')) die();

$lang_fancy_stop_spam = array(
	'Error many links' 					=> 'Слишком много ссылок в сообщении. Разрешено %s ссылок. Уменьшите количество ссылок.',
	'Name'								=> 'Fancy Stop SPAM параметры',

	'First Post Max Links' 				=> 'Ссылок в первом сообщении',
	'First Post Max Links Help'			=> 'Максимум ссылок в первом сообщении. Если значение < 0 — проверка отключена.',
	'First Post Guest Max Links'		=> 'Ссылок в первом сообщении гостя',
	'First Post Guest Max Links Help'	=> 'Максимум ссылок в сообщении гостя. Если значение < 0 — проверка отключена.',

	'Go to settings'					=> 'Настройки',
	'Settings Name'						=> 'Fancy Stop SPAM параметры',

	'Register form'					=> 'Форма регистрации',
	'Login form'					=> 'Форма логина',
	'Post form'						=> 'Форма сообщения',
	'Other Methods'					=> 'Другие методы',
	'First Post Methods'			=> 'Метод Первого сообщения',
	'Signature Check Method'		=> 'Метод Подписи',
	'Submit Check Method'			=> 'Метод Сабмита',

	'Enable Honeypot'				=> 'Включить защиту скрытыми полями',
	'Enable Timeout'				=> 'Включить зашиту таймаутами',
	'Enable Timezone'				=> 'Включить проверку временной зоны (UTC−12:00)',
	'Enable Check Identical'		=> 'Проверять одинаковые сообщения',

	'Enable SFS Email'				=> 'Проверять эл. почту через StopForumSpam',
	'Enable SFS IP'					=> 'Проверять айпи-адрес через StopForumSpam',

	'Register bot message'			=> 'Извините, мы думаем что вы бот. Вы не можете сейчас зарегистрироваться.',
	'Register bot timeout message'	=> 'Извините, мы думаем что вы бот, потому что вы заполнили форму слишком быстро. Подождите несколько секунд и заполните форму снова.',
	'Register bot timezone message'	=> 'Извините, мы думаем что вы бот, потому что вы выбрали временную зону UTC−12:00. В этой зоне нет людей. Выберите любую другую зону.',
	'Register bot sfs email message'	=> 'Извините, мы думаем что вы бот. Ваш адрес электронной почты найден в базе спамеров StopForumSpam. Вы не можете сейчас зарегистрироваться.',
	'Register bot sfs email ip message'	=> 'В течении последнего часа с вашего IP-адреса была попытка регистрации спамера. По прошествии 60 минут вы сможете зарегистрироваться, это мера безопасности. Приносим извинения за неудобства.',
	'Register bot sfs ip message'	=> 'Извините, мы думаем что вы бот. Ваш айпи-адрес найден в базе спамеров StopForumSpam. Вы не можете сейчас зарегистрироваться.',
	'Login bot message'				=> 'Извините, мы думаем что вы бот. Вы не можете сейчас войти на форум.',
	'Post bot message'				=> 'Извините, мы думаем что вы бот. Вы не можете сейчас отправить сообщение.',
	'Post Identical message'		=> 'Извините, но вы отправляете одинаковые сообщения. Измените сообщение и отправьте его снова.',
	'Activate bot message'			=> 'Извините, мы думаем что вы бот. Вы не можите активировать учётную запись.',

	'Honey field'					=> 'Анти СПАМ',
	'Honey field help'				=> 'Оставьте это поле пустым.',

	'Enable Logs'					=> 'Записывать попытки спама в журнал',

	'Section antispam'				=> 'Антиспам',
	'Section antispam welcome'		=> 'Антиспам проверка',
	'Section antispam welcome user'	=> 'Антиспам проверка %s',
	'Status'						=> 'Статус',
	'Status found'					=> 'спамер, найден в базе спамеров',
	'Status not found'				=> 'чисто, не обнаружен в базе',
	'Status error'					=> 'Не удалось получить данные от сервера StopForumSpam',
	'Frequency'						=> 'Частота',
	'Last seen'						=> 'Активность',

	'Admin section antispam'			=> 'Антиспам',
	'Admin submenu information'			=> 'Информация',
	'Admin submenu information header'	=> 'Добро пожаловать в панель управления Fancy stop spam',

	'Admin submenu logs'				=> 'Логи',
	'Admin submenu logs header'			=> 'Detected spam events (latest 100)',

	'Admin submenu new users'			=> 'Новые пользователи',
	'Admin submenu new users header'	=> '15 последних зарегистрированных пользователей',

	'Admin submenu suspicious users'		=> 'Подозрительные пользователи',
	'Admin submenu suspicious users header'	=> 'Подозрительные пользователи',

	'Admin logs disabled message'			=> 'Fancy stop spam логирование отключено %s.',
	'Admin logs disabled message settings'	=> 'в Настройках',
	'Admin logs empty message'				=> '',

	'log event name unknown'				=> 'Unknown',
	'log event name 0'						=> 'Системное сообщение',
	'log event name 1'						=> 'Register submit',
	'log event name 2'						=> 'Register timeout',
	'log event name 3'						=> 'Register timezone',
	'log event name 4'						=> 'Register honeypot',
	'log event name 5'						=> 'Register honeypot empty',
	'log event name 6'						=> 'Register email SFS',
	'log event name 7'						=> 'Register email SFS (cached)',
	'log event name 8'						=> 'Register email SFS IP (cached)',
	'log event name 9'						=> 'Register IP SFS',
	'log event name 10'						=> 'Register IP SFS (cached)',
	'log event name 11'						=> 'Register honeypot repeated',

	'log event name 70'						=> 'Activate submit',
	'log event name 71'						=> 'Activate honeypot',
	'log event name 72'						=> 'Activate honeypot empty',

	'log event name 20'						=> 'Post submit',
	'log event name 21'						=> 'Post timeout',
	'log event name 22'						=> 'Post honeypot',
	'log event name 23'						=> 'Post honeypot empty',

	'log event name 30'						=> 'Identical message post',

	'log event name 40'						=> 'Login honeypot',
	'log event name 41'						=> 'Login honeypot empty',

	'log event name 60'						=> 'Signature hidden',

	'Time'									=> 'Дата',
	'IP'									=> 'IP',
	'Comment'								=> 'Коментарий',
	'Type'									=> 'Тип',
	'User'									=> 'Пользователь',

	'No activity'							=> 'No SPAM activity logged.',
	'No suspicious_users'					=> 'No suspicious users founded.',

	'Number posts'							=> 'Сообщений',

	'Email check'							=> 'Проверка эл. почты',
	'IP check'								=> 'IP проверка',

	'SFS API Key'							=> 'API ключ',
	'SFS API Key Help'						=> 'StopForumSpam API ключ for report spamers',
	'Report to sfs'							=> 'Отправить отчет о спамере в сервис StopForumSpam',

	'Identical check repeated event'		=> 'Identical repeated - user mark as suspicious',
);

?>
