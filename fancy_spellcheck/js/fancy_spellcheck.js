if (typeof PUNBB === "undefined" || !PUNBB) {
	var FORUM = {};
}

PUNBB.fancy_spellcheck = (function () {
	var $link = null,
		speller = null,
		$t = null,
		$s = null,
		$span = null,
		ok_text_hash = 0;


	function get_saved_options() {
		var dic = {};
		if ($.cookie('yandex.spell') && $.cookie('yandex.spell').length > 1) {
			var attrs = unescape($.cookie('yandex.spell')).split("&");
            for (var j = 0; j < attrs.length; ++j) {
                var pair = attrs[j].split("=");
                dic[pair[0]] = unescape(pair[1] || "");
            }
        }
        return dic;
	}


	//
	function get_checked_text(clean) {
		var _t = ($s.length) ? $s.val() + '\n' + $t.val() : $t.val();

		return (clean) ? clean_text(_t) : _t;
	}

	//
	function get_checked_text_length(uri) {
		if (uri) {
			return encodeURIComponent(get_checked_text(true)).length;
		} else {
			return get_checked_text().length;
		}
	}


	// CLEAN TEXT FOR
	function clean_text(str) {
		// TRIM
		var _str = str.replace(/^\s+|\s+$/g, '');

		// REMOVE IMG
		_str = _str.replace(/\[img\]\S+\[\/img\]/g, ' ');

		return _str;
	}

	// FAST CHECK
	function pre_check(cb_ok, cb_error) {
		if ($span.hasClass('busy')) {
			return;
		}

		var params = get_saved_options(),
			timer_reached = false,
			response_received = false,
			response = '';


		//
		function check_status_end() {
			if (timer_reached && response_received) {
				// enable link
				$span.removeClass('busy');

				if (response && typeof response === 'object') {
					if (response.length === 0) {
						cb_ok();
					} else {
						cb_error(response);
					}
				} else {
					alert('Сервис проверки правописания не отвечает.');
				}
			}
		}

		// HIDE OK
		$(document).stopTime('fancy_check_spelling_ok');
		$('#fancy_check_spelling_ok').css('opacity', '0');

		// TOO SHORT for ERRORS
		if (get_checked_text_length(false) < 3) {
			_.delay(cb_ok);
			return;
		}

			// CHANGED?
		if (ok_text_hash === $.sha1(get_checked_text())) {
			_.delay(cb_ok);
			return;
		}


		// TOO LONG FOR BACKGROUND CHECKING
		if (get_checked_text_length(true) > 6000) {
			_.delay(cb_errors);
			return;
		}

		// SHOW PROGRESS
		$span.addClass('busy').blur();

		// устраиваем гонку между таймером и запросом
		$(document).oneTime(750, 'fancy_checkspell_uit', function () {
			timer_reached = true;
			check_status_end();
		});

		// ABORT TIMEOUT HANDLER
		$(document).stopTime('fancy_checkspell_error').oneTime(10000, 'fancy_checkspell_error', function () {
			timer_reached = true;
			response_received = true;
			check_status_end();
		});


		// GET DEFAULT LANG
		if (PUNBB.env.user_lang == 'English') {
			_def_lang = 'en,ru';
		} else {
			_def_lang = 'ru';
		}

		// OPTIONS
		var _lang = params.lang || _def_lang,
			_options = parseInt(s(params.options) || 22, 10);

		if (_lang == 'en') {
			_options &= ~0x0090;
		} else if ((_options & 0x0090) == 0) {
			_lang += ',en';
		}



		// RUN
		$.ajax({
			type: 'GET',
			url: 'http://speller.yandex.net/services/spellservice.json/checkText',
			data: { text: get_checked_text(true), lang: _lang, options: _options },
			dataType: 'jsonp',
			global: false,
			success: function (data, status) {
				// STOP ABORT TIMER
				$(document).stopTime('fancy_checkspell_error');

				response_received = true;
				response = data || '';
				check_status_end();
			}
		});

	}


	//
	function cb_no_errors() {
		// SAVE HASH OF VALID TEXT
		ok_text_hash = $.sha1(get_checked_text());

		// SHOW OK
		$('#fancy_check_spelling_ok').fadeTo(250, 1.0, function () {
			$(document).stopTime('fancy_check_spelling_ok').oneTime(2000, 'fancy_check_spelling_ok', function () {
				$('#fancy_check_spelling_ok').fadeTo(250, 0);
			});
		});
	}


	// CHECK DEFAULT
	function cb_errors(response) {
		var check_els = [],
			errors_count = 0,
			errors = response || null,
			params = get_saved_options();


		// TRY DEAL WITH USERDIC
		if (errors && errors.length > 0) {
			errors_count = errors.length;

			var user_dic = get_user_dic_elems();

			_.each(errors, function (error) {
				// NON REPEATED CHECK WITH DIC
				if (error.code !== 2 && user_dic && typeof(user_dic[norm_word(error.word)]) != 'undefined') {
					errors_count -= 1;
					return true;
				}
			});

			if (errors_count === 0) {
				cb_no_errors();
				return;
			}
		}


		// GET DEFAULT LANG
		if (PUNBB.env.user_lang == 'English') {
			_def_lang = 'en,ru';
		} else {
			_def_lang = 'ru';
		}

		// OPTIONS
		var _lang = params.lang || _def_lang,
			_options = parseInt(s(params.options) || 22, 10);

		if (_lang == 'en') {
			_options &= ~0x0090;
		} else if ((_options & 0x0090) == 0) {
			_lang += ',en';
		}

		// INIT SPELLER
		speller = new Speller({
			url: PUNBB.env.fancy_spellcheck.speller_url,
			lang: _lang,
			options:_options
		});

		if (check_els.length < 1) {
			if ($s.length) {
				check_els.push(document.getElementById($s.attr('id')));
			}
			check_els.push(document.getElementById($t.attr('id')));
		}
		speller.check(check_els);
	}


	function get_user_dic_elems() {
		var str = String(PUNBB.fancy_spellcheck_storage_local.getItem('yandex.userdic') || ''),
			arr = str.split("\n")
			items = {};

		for (var i = 0; i < arr.length; ++i) {
			var word = norm_word(arr[i]);
			if (word) {
				this.items[word] = '';
			}
		}

		return items;
	}

	function norm_word(s) {
		return s.replace(/\s+/g, ' ').replace(/^\s+|\s+$/g, '');
	}

	function s(value) {
		return typeof(value) == 'undefined' ? '' : value.toString();
	}


	// PUBLIC
	return {
		init: function () {
			$t = $('textarea[name="req_message"]').length ?  $('textarea[name="req_message"]') : $('textarea[name="signature"]');
			$s = $('input[name="req_subject"]').length ? $('input[name="req_subject"]') : '';

			var $d = $('#fancy_spellcheck_block');

			if (!$d || !$t.length) {
				return;
			}

			$link = $('#fancy_spellcheck_link');
			$span = $('#fancy_spellcheck_span');

			// BIND EVENTS
			$(document).bind('check.fancy_spellcheck', function () {
				pre_check(cb_no_errors, cb_errors);
			});

			// BIND TO LINK
			$link.bind('click', function () {
				pre_check(cb_no_errors, cb_errors);
			});
		}
	};
}());



// RUN
PUNBB.common.addDOMReadyEvent(PUNBB.fancy_spellcheck.init);


// SPELLER.YANDEX
function Speller(a){a=a||{};this.url=a.url||".";this.args={defLang:a.lang||"ru",defOptions:a.options||4,spellDlg:a.spellDlg||{width:550,height:550},optDlg:a.optDlg||{width:450,height:480},userDicDlg:a.userDicDlg||{width:400,height:450}}}Speller.IGNORE_UPPERCASE=1;Speller.IGNORE_DIGITS=2;Speller.IGNORE_URLS=4;Speller.FIND_REPEAT=8;Speller.IGNORE_LATIN=16;Speller.FLAG_LATIN=128;Speller.prototype.check=function(a){this.showDialog(this.url+"/spelldlg.html",this.args.spellDlg,a)};
Speller.prototype.optionsDialog=function(){this.showDialog(this.url+"/spellopt.html",this.args.optDlg)};
Speller.prototype.showDialog=function(a,b,d){var c=this.args,e={ctrls:d,lang:c.lang,options:c.options,defLang:c.defLang,defOptions:c.defOptions,optDlg:c.optDlg,userDicDlg:c.userDicDlg},f=d=0;if(window.outerWidth){d=window.screenX+(window.outerWidth-b.width>>1);f=window.screenY+(window.outerHeight-b.height>>1)}if(window.showModalDialog&&navigator.userAgent.indexOf("Opera")<0){b="dialogWidth:"+b.width+"px;dialogHeight:"+b.height+"px;scroll:no;help:no;status:no;";if(navigator.userAgent.indexOf("Firefox")>=
0)b+="dialogLeft:"+d+"px;dialogTop:"+f+"px;";window.showModalDialog(a,e,b);c.lang=e.lang;c.options=e.options}else{var g=a.replace(/[\/\.]/g,"");b="width="+b.width+",height="+b.height+",toolbar=no,status=no,menubar=no,directories=no,resizable=no";if(d||f)b+=",left="+d+",top="+f;window.theDlgArgs=e;window.open(a,g,b).onunload=function(){c.lang=e.lang;c.options=e.options}}};
