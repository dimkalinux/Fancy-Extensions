<?php

$lang_fancy_simtopics = array(
	'Go to settings'				=> 'Настройки',
	'Settings Name'					=> 'Fancy Similar Topics параметры',
	'Forum Settings Show'			=> 'Показывать похожие темы',
	'Forum Settings Search'			=> 'Искать похожие темы',
	'Settings Num Name'				=> 'Количество топиков',
	'Settings Num Help'				=> 'Сколько похожих тем показывать. По-умолчанию 5 тем. 0 — отключает поиск.',
	'Settings Time Name'			=> 'Не старее, дней',
	'Settings Time Help'			=> 'За какой перид времени искать похожие темы. По-умолчанию 365 дней.',
	'Enable For User Name'			=> 'Похожие темы',
	'Enable For User Help'			=> 'Показывать список похожих тем',
	'Settings One Forum'			=> 'Искать только в текущем форуме',
	'Header'						=> 'Похожие темы',
	'Header One Forum'				=> 'Похожие темы в этом форуме',
	'Edit forum settings head'		=> 'Редактирование настроек похожих тем',
	'Edit forum settings legend'	=> 'Настройки похожих тем',
	'Settings Show for guest'		=> 'Показывать похожие темы для гостей',
	'Settings Show for guest Help'	=> 'Показывать похожие темы для не авторизованых пользователей',
);

// PER-LANG STOPWORDS LIST
$stop_list_fancy_simtopics = array(
'а',
'без', 'более','будем', 'будет', 'будете', 'будешь', 'буду', 'будут','бы','был','была','были','было','быть',
'в','вам','вас','весь','во','вот','все','всего','всех','вы',
'где',
'да','даже','для','до',
'его','ее','если','есть','еще',
'же',
'за',
'здесь',
'и','из','или','им','их',
'к','как','ко','когда','кто',
'ли','либо',
'мне','может','мы',
'на','надо','наш','не','него','нее','нет','ни','них','но','ну',
'о','об','однако','он','она','они','оно','от','очень',
'по','под','при',
'с','со',
'так','такая','также','такие','таким','такими','таких','такого','такое','такой','таком','такому','такою','такую','там','те','тем','тема','темам','темами','темах','теме','темой','темою','тему','темы','то','того','тоже','той','только','том','ты',
'у','уж','уже',
'хотя',
'чего','чей','чем','что','чтобы','чье','чья',
'эта','эти','это',
'я',
);


/*
 * PER-FORUM STOP LIST (forum with id 29)
 * IF EXISTS WILL BE MERGED WITH PER-LANG STOPWORDS LIST
$stop_list_fancy_simtopics_29 = array(
	'example_stopword', 'example_stopword_2'
);
*/


$stop_list_fancy_simtopics_29 = array(
	'продам',
);

$stop_list_fancy_simtopics_69 = array(
	'куплю',
);

$stop_list_fancy_simtopics_61 = array(
	'2010', '(2010)',
	'dvdrip', 'camrip', 'hdrip', 'dvd-5', 'dvd-9',
	'(dvdrip)', '(camrip)', '(hdrip)', '(dvd-5)', '(dvd-9)', '(satrip)',
);

$stop_list_fancy_simtopics_64 = array(
	'2010', '(2010)',
	'dvdrip', 'camrip', 'hdrip', 'dvd-5', 'dvd-9',
	'(dvdrip)', '(camrip)', '(hdrip)', '(dvd-5)', '(dvd-9)', '(satrip)',
);
?>
