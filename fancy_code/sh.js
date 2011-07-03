/**
 * SyntaxHighlighter
 * http://alexgorbatchev.com/
 *
 * @version
 * 2.0.287 (February 06 2009)
 *
 * @author
 * Alex Gorbatchev
 *
 * @copyright
 * Copyright (C) 2004-2009 Alex Gorbatchev.
 *
 * Licensed under a GNU Lesser General Public License.
 * http://creativecommons.org/licenses/LGPL/2.1/
 *
 * SyntaxHighlighter is donationware. You are allowed to download, modify and distribute
 * the source code in accordance with LGPL 2.1 license, however if you want to use
 * SyntaxHighlighter on your site or include it in your product, you must donate.
 * http://alexgorbatchev.com/wiki/SyntaxHighlighter:Donate
 */
//
// Begin anonymous function. This is used to contain local scope variables without polutting global scope.
//
if (!window.SyntaxHighlighter) var SyntaxHighlighter = function() {

// Shortcut object which will be assigned to the SyntaxHighlighter variable.
// This is a shorthand for local reference in order to avoid long namespace
// references to SyntaxHighlighter.whatever...
var sh = {
	defaults : {
		/** Additional CSS class names to be added to highlighter elements. */
		'class-name' : '',

		/** First line number. */
		'first-line' : 1,

		/** Font size of the SyntaxHighlighter block. */
		'font-size' : null,

		/** Lines to highlight. */
		'highlight' : null,

		/** Enables or disables smart tabs. */
		'smart-tabs' : true,

		/** Gets or sets tab size. */
		'tab-size' : 4,

		/** Enables or disables ruler. */
		'ruler' : false,

		/** Enables or disables gutter. */
		'gutter' : true,

		/** Enables or disables toolbar. */
		'toolbar' : true,

		/** Forces code view to be collapsed. */
		'collapse' : false,

		/** Enables or disables automatic links. */
		'auto-links' : true,

		/** Gets or sets light mode. Equavalent to turning off gutter and toolbar. */
		'light' : false
	},

	config : {
		/** Path to the copy to clipboard SWF file. */
		clipboardSwf : null,

		/** Width of an item in the toolbar. */
		toolbarItemWidth : 16,

		/** Height of an item in the toolbar. */
		toolbarItemHeight : 16,

		/** Blogger mode flag. */
		bloggerMode : false,

		/** Name of the tag that SyntaxHighlighter will automatically look for. */
		tagName : 'pre',

		strings : {
			expandSource : 'expand source',
			viewSource : 'source',
			copyToClipboard : 'copy to clipboard',
			copyToClipboardConfirmation : 'The code is in your clipboard now',
			print : 'print',
			help : '?',
			alert: 'SyntaxHighlighter\n\n',
			noBrush : 'Can\'t find brush for: ',
			brushNotHtmlScript : 'Brush wasn\'t configured for html-script option: ',

			// this is populated by the build script
			aboutDialog : '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><title>About SyntaxHighlighter</title></head><body style="font-family:Georgia,"Times New Roman",Times,serif;background-color:#fff;color:#000;font-size:1em;text-align:center;"><div style="text-align:center;margin-top:3em;"><div style="font-family:Geneva,Arial,Helvetica,sans-serif;font-size:xx-large;">SyntaxHighlighter</div><div style="font-size:.75em;margin-bottom:4em;"><div>version 2.0.287 (February 06 2009)</div><div><a href="http://alexgorbatchev.com" target="_blank" style="color:#0099FF;text-decoration:none;">http://alexgorbatchev.com</a></div></div><div>JavaScript code syntax highlighter.</div><div>Copyright 2004-2009 Alex Gorbatchev.</div></div></body></html>'
		},

		/** If true, output will show HTML produces instead. */
		debug : false
	},

	/** Internal 'global' variables. */
	vars : {
		discoveredBrushes : null,
		spaceWidth : null,
		printFrame : null,
		highlighters : {}
	},

	/** This object is populated by user included external brush files. */
	brushes : {},

	/** Common regular expressions. */
	regexLib : {
		multiLineCComments			: /\/\*[\s\S]*?\*\//gm,
		singleLineCComments			: /\/\/.*$/gm,
		singleLinePerlComments		: /#.*$/gm,
		doubleQuotedString			: /"(?:\.|(\\\")|[^\""\n])*"/g,
		singleQuotedString			: /'(?:\.|(\\\')|[^\''\n])*'/g,
		multiLineDoubleQuotedString	: /"(?:\.|(\\\")|[^\""])*"/g,
		multiLineSingleQuotedString	: /'(?:\.|(\\\')|[^\''])*'/g,
		url							: /\w+:\/\/[\w-.\/?%&=]*/g,

		/** <?= ?> tags. */
		phpScriptTags 				: { left: /(&lt;|<)\?=?/g, right: /\?(&gt;|>)/g },

		/** <%= %> tags. */
		aspScriptTags				: { left: /(&lt;|<)%=?/g, right: /%(&gt;|>)/g },

		/** <script></script> tags. */
		scriptScriptTags			: { left: /(&lt;|<)\s*script.*?(&gt;|>)/gi, right: /(&lt;|<)\/\s*script\s*(&gt;|>)/gi }
	},

	toolbar : {
		/**
		 * Creates new toolbar for a highlighter.
		 * @param {Highlighter} highlighter    Target highlighter.
		 */
		create : function(highlighter)
		{
			var div = document.createElement('div'),
				items = sh.toolbar.items;

			div.className = 'toolbar';

			for (var name in items) {
				var constructor = items[name],
					command = new constructor(highlighter),
					element = command.create();

				highlighter.toolbarCommands[name] = command;

				if (element == null) {
					continue;
				}

				if (typeof(element) == 'string') {
					element = sh.toolbar.createButton(element, highlighter.id, name);
				}

				element.className += 'item ' + name;
				div.appendChild(element);
			}

			return div;
		},

		/**
		 * Create a standard anchor button for the toolbar.
		 * @param {String} label			Label text to display.
		 * @param {String} highlighterId	Highlighter ID that this button would belong to.
		 * @param {String} commandName		Command name that would be executed.
		 * @return {Element}				Returns an 'A' element.
		 */
		createButton : function(label, highlighterId, commandName)
		{
			var a = document.createElement('a'),
				style = a.style,
				config = sh.config,
				width = config.toolbarItemWidth,
				height = config.toolbarItemHeight
				;

			a.href = '#' + commandName;
			a.title = label;
			a.highlighterId = highlighterId;
			a.commandName = commandName;
			a.innerHTML = label;

			if (isNaN(width) == false)
				style.width = width + 'px';

			if (isNaN(height) == false)
				style.height = height + 'px';

			a.onclick = function(e)
			{
				try
				{
					sh.toolbar.executeCommand(
						this,
						e || window.event,
						this.highlighterId,
						this.commandName
					);
				}
				catch(e)
				{
					//sh.utils.alert(e.message);
				}

				return false;
			};

			return a;
		},

		/**
		 * Executes a toolbar command.
		 * @param {Element}		sender  		Sender element.
		 * @param {MouseEvent}	event			Original mouse event object.
		 * @param {String}		highlighterId	Highlighter DIV element ID.
		 * @param {String}		commandName		Name of the command to execute.
		 * @return {Object} Passes out return value from command execution.
		 */
		executeCommand : function(sender, event, highlighterId, commandName, args)
		{
			var highlighter = sh.vars.highlighters[highlighterId],
				command
				;

			if (highlighter == null || (command = highlighter.toolbarCommands[commandName]) == null)
				return null;

			return command.execute(sender, event, args);
		},

		/** Collection of toolbar items. */
		items : {
			expandSource : function(highlighter)
			{
				this.create = function()
				{
					if (highlighter.getParam('collapse') != true)
						return;

					return sh.config.strings.expandSource;
				};

				this.execute = function(sender, event, args)
				{
					var div = highlighter.div;

					sender.parentNode.removeChild(sender);
					div.className = div.className.replace('collapsed', '');
				};
			},

			/**
			 * Command to open a new window and display the original unformatted source code inside.
			 */
			viewSource : function(highlighter) {
				this.create = function() {
					return sh.config.strings.viewSource;
				};

				this.execute = function(sender, event, args) {
					var code = sh.utils.unindent(sh.utils.fixForBlogger(highlighter.originalCode).replace(/</g, '&lt;')),
						el_h = $("#"+highlighter.id),
						el_l = el_h.find(".lines");

					if (el_h.data('original') != 1) {
						highlighter.lines_code = el_l.html();

						el_l.fader(250, function() {
							el_l.html('<div class="as_code">'+code+'</div>');
							el_h.data('original', 1);
						});
					} else {
						el_l.fader(250, function() {
							el_l.html(highlighter.lines_code);
							el_h.data('original', 0);
						});
					}
				};
			}
		}
	},

	utils : {
		guid : function(prefix)
		{
			return prefix + Math.round(Math.random() * 1000000).toString();
		},

		/**
		 * Merges two objects. Values from obj2 override values in obj1.
		 * Function is NOT recursive and works only for one dimensional objects.
		 * @param {Object} obj1 First object.
		 * @param {Object} obj2 Second object.
		 * @return {Object} Returns combination of both objects.
		 */
		merge: function(obj1, obj2)
		{
			var result = {}, name;

			for (name in obj1)
				result[name] = obj1[name];

			for (name in obj2)
				result[name] = obj2[name];

			return result;
		},

		/**
		 * Attempts to convert string to boolean.
		 * @param {String} value Input string.
		 * @return {Boolean} Returns true if input was "true", false if input was "false" and value otherwise.
		 */
		toBoolean: function(value)
		{
			switch (value) {
				case "true":
					return true;

				case "false":
					return false;
			}

			return value;
		},


		/**
		 * Adds event handler to the target object.
		 * @param {Object} obj		Target object.
		 * @param {String} type		Name of the event.
		 * @param {Function} func	Handling function.
		 */
		addEvent: function(obj, type, func)
		{
			if (obj.attachEvent)
			{
				obj['e' + type + func] = func;
				obj[type + func] = function()
				{
					obj['e' + type + func](window.event);
				}
				obj.attachEvent('on' + type, obj[type + func]);
			}
			else
			{
				obj.addEventListener(type, func, false);
			}
		},

		/**
		 * Displays an alert.
		 * @param {String} str String to display.
		 */
		alert: function(str)
		{
			alert(sh.config.strings.alert + str)
		},

		/**
		 * Finds a brush by its alias.
		 *
		 * @param {String} alias	Brush alias.
		 * @param {Boolean} alert	Suppresses the alert if false.
		 * @return {Brush}			Returns bursh constructor if found, null otherwise.
		 */
		findBrush: function(alias, alert)
		{
			var brushes = sh.vars.discoveredBrushes,
				result = null
				;

			if (brushes == null)
			{
				brushes = {};

				// Find all brushes
				for (var brush in sh.brushes)
				{
					var aliases = sh.brushes[brush].aliases;

					if (aliases == null)
						continue;

					for (var i = 0; i < aliases.length; i++) {
						brushes[aliases[i]] = brush;
					}
				}

				sh.vars.discoveredBrushes = brushes;
			}

			result = sh.brushes[brushes[alias]];

			if (result == null && alert != false) {
				sh.utils.alert(sh.config.strings.noBrush + alias);
			}

			return result;
		},

		/**
		 * Executes a callback on each line and replaces each line with result from the callback.
		 * @param {Object} str			Input string.
		 * @param {Object} callback		Callback function taking one string argument and returning a string.
		 */
		eachLine: function(str, callback)
		{
			var lines = str.split('\n');

			for (var i = 0, ll = lines.length; i < ll; i++) {
				lines[i] = callback(lines[i]);
			}

			return lines.join('\n');
		},

		/**
		 * Creates rules looking div.
		 */
		createRuler: function()
		{
			var div = document.createElement('div'),
				ruler = document.createElement('div'),
				showEvery = 10,
				i = 1
				;

			while (i <= 150)
			{
				if (i % showEvery === 0)
				{
					div.innerHTML += i;
					i += (i + '').length;
				}
				else
				{
					div.innerHTML += '&middot;';
					i++;
				}
			}

			ruler.className = 'ruler line';
			ruler.appendChild(div);

			return ruler;
		},

		/**
		 * This is a special trim which only removes first and last empty lines
		 * and doesn't affect valid leading space on the first line.
		 *
		 * @param {String} str   Input string
		 * @return {String}      Returns string without empty first and last lines.
		 */
		trimFirstAndLastLines: function(str)
		{
			return str.replace(/^[ ]*[\n]+|[\n]*[ ]*$/g, '');
		},

		/**
		 * Parses key/value pairs into hash object.
		 *
		 * Understands the following formats:
		 * - name: word;
		 * - name: [word, word];
		 * - name: "string";
		 * - name: 'string';
		 *
		 * For example:
		 *   name1: value; name2: [value, value]; name3: 'value'
		 *
		 * @param {String} str    Input string.
		 * @return {Object}       Returns deserialized object.
		 */
		parseParams: function(str)
		{
			var match,
				result = {},
				arrayRegex = new XRegExp("^\\[(?<values>(.*?))\\]$"),
				regex = new XRegExp(
					"(?<name>[\\w-]+)" +
					"\\s*:\\s*" +
					"(?<value>" +
						"[\\w-%#]+|" +		// word
						"\\[.*?\\]|" +		// [] array
						'".*?"|' +			// "" string
						"'.*?'" +			// '' string
					")\\s*;?",
					"g"
				)
				;

			while ((match = regex.exec(str)) != null)
			{
				var value = match.value
					.replace(/^['"]|['"]$/g, '') // strip quotes from end of strings
					;

				// try to parse array value
				if (value != null && arrayRegex.test(value))
				{
					var m = arrayRegex.exec(value);
					value = m.values.length > 0 ? m.values.split(/\s*,\s*/) : [];
				}

				result[match.name] = value;
			}

			return result;
		},

		/**
		 * Wraps each line of the string into <code/> tag with given style applied to it.
		 *
		 * @param {String} str   Input string.
		 * @param {String} css   Style name to apply to the string.
		 * @return {String}      Returns input string with each line surrounded by <span/> tag.
		 */
		decorate: function(str, css)
		{
			if (str == null || str.length == 0 || str == '\n') {
				return str;
			}

			str = str.replace(/</g, '&lt;');

			// Replace two or more sequential spaces with &nbsp; leaving last space untouched.
			str = str.replace(/ {2,}/g, function(m)
			{
				var spaces = '';

				for (var i = 0; i < m.length - 1; i++) {
					spaces += '&nbsp;';
				}

				return spaces + ' ';
			});

			// Split each line and apply <span class="...">...</span> to them so that
			// leading spaces aren't included.
			if (css != null) {
				str = sh.utils.eachLine(str, function(line) {
					if (line.length == 0) {
						return '';
					}

					var spaces = '';

					line = line.replace(/^(&nbsp;| )+/, function(s) {
						spaces = s;
						return '';
					});

					if (line.length == 0) {
						return spaces;
					}

					return [spaces, '<code class="', css, '">', line, '</code>'].join('');
				});
			}

			return str;
		},

		/**
		 * Pads number with zeros until it's length is the same as given length.
		 *
		 * @param {Number} number	Number to pad.
		 * @param {Number} length	Max string length with.
		 * @return {String}			Returns a string padded with proper amount of '0'.
		 */
		padNumber : function(number, length)
		{
			var result = number.toString();

			while (result.length < length)
				result = '0' + result;

			return result;
		},

		/**
		 * Measures width of a single space character.
		 * @return {Number} Returns width of a single space character.
		 */
		measureSpace : function()
		{
			var container = document.createElement('div'),
				span,
				result = 0,
				body = document.body,
				id = sh.utils.guid('measureSpace'),

				// variable names will be compressed, so it's better than a plain string
				divOpen = '<div class="',
				closeDiv = '</div>',
				closeSpan = '</span>'
				;

			// we have to duplicate highlighter nested structure in order to get an acurate space measurment
			container.innerHTML =
				divOpen + 'syntaxhighlighter">'
					+ divOpen + 'lines">'
						+ divOpen + 'line">'
							+ divOpen + 'content'
								+ '"><span class="block"><span id="' + id + '">&nbsp;' + closeSpan + closeSpan
							+ closeDiv
						+ closeDiv
					+ closeDiv
				+ closeDiv
				;

			body.appendChild(container);
			span = document.getElementById(id);

			if (/opera/i.test(navigator.userAgent))
			{
				var style = window.getComputedStyle(span, null);
				result = parseInt(style.getPropertyValue("width"));
			}
			else
			{
				result = span.offsetWidth;
			}

			body.removeChild(container);

			return result;
		},

		/**
		 * Replaces tabs with spaces.
		 *
		 * @param {String} code		Source code.
		 * @param {Number} tabSize	Size of the tab.
		 * @return {String}			Returns code with all tabs replaces by spaces.
		 */
		processTabs : function(code, tabSize)
		{
			var tab = '';

			for (var i = 0; i < tabSize; i++)
				tab += ' ';

			return code.replace(/\t/g, tab);
		},

		/**
		 * Replaces tabs with smart spaces.
		 *
		 * @param {String} code    Code to fix the tabs in.
		 * @param {Number} tabSize Number of spaces in a column.
		 * @return {String}        Returns code with all tabs replaces with roper amount of spaces.
		 */
		processSmartTabs : function(code, tabSize)
		{
			var lines = code.split('\n'),
				tab = '\t',
				spaces = ''
				;

			// Create a string with 1000 spaces to copy spaces from...
			// It's assumed that there would be no indentation longer than that.
			for (var i = 0; i < 50; i++)
				spaces += '                    '; // 20 spaces * 50

			// This function inserts specified amount of spaces in the string
			// where a tab is while removing that given tab.
			function insertSpaces(line, pos, count)
			{
				return line.substr(0, pos)
					+ spaces.substr(0, count)
					+ line.substr(pos + 1, line.length) // pos + 1 will get rid of the tab
					;
			};

			// Go through all the lines and do the 'smart tabs' magic.
			code = sh.utils.eachLine(code, function(line)
			{
				if (line.indexOf(tab) == -1)
					return line;

				var pos = 0;

				while ((pos = line.indexOf(tab)) != -1)
				{
					// This is pretty much all there is to the 'smart tabs' logic.
					// Based on the position within the line and size of a tab,
					// calculate the amount of spaces we need to insert.
					var spaces = tabSize - pos % tabSize;
					line = insertSpaces(line, pos, spaces);
				}

				return line;
			});

			return code;
		},

		fixForBlogger : function(str)
		{
			return (sh.config.bloggerMode == true) ? str.replace(/<br\s*\/?>|&lt;br\s*\/?&gt;/gi, '\n') : str;
		},

		/**
		 * Removes all white space at the begining and end of a string.
		 *
		 * @param {String} str   String to trim.
		 * @return {String}      Returns string without leading and following white space characters.
		 */
		trim: function(str)
		{
			return str.replace(/\s*$/g, '').replace(/^\s*/, '');
		},

		/**
		 * Unindents a block of text by the lowest common indent amount.
		 * @param {String} str   Text to unindent.
		 * @return {String}      Returns unindented text block.
		 */
		unindent: function(str)
		{
			var lines = sh.utils.fixForBlogger(str).split('\n'),
				indents = new Array(),
				regex = /^\s*/,
				min = 1000
				;

			// go through every line and check for common number of indents
			for (var i = 0; i < lines.length && min > 0; i++)
			{
				var line = lines[i];

				if (sh.utils.trim(line).length == 0)
					continue;

				var matches = regex.exec(line);

				// In the event that just one line doesn't have leading white space
				// we can't unindent anything, so bail completely.
				if (matches == null)
					return str;

				min = Math.min(matches[0].length, min);
			}

			// trim minimum common number of white space from the begining of every line
			if (min > 0)
				for (var i = 0; i < lines.length; i++)
					lines[i] = lines[i].substr(min);

			return lines.join('\n');
		},

		/**
		 * Callback method for Array.sort() which sorts matches by
		 * index position and then by length.
		 *
		 * @param {Match} m1	Left object.
		 * @param {Match} m2    Right object.
		 * @return {Number}     Returns -1, 0 or -1 as a comparison result.
		 */
		matchesSortCallback: function(m1, m2)
		{
			// sort matches by index first
			if(m1.index < m2.index)
				return -1;
			else if(m1.index > m2.index)
				return 1;
			else
			{
				// if index is the same, sort by length
				if(m1.length < m2.length)
					return -1;
				else if(m1.length > m2.length)
					return 1;
			}

			return 0;
		},

		/**
		 * Executes given regular expression on provided code and returns all
		 * matches that are found.
		 *
		 * @param {String} code    Code to execute regular expression on.
		 * @param {Object} regex   Regular expression item info from <code>regexList</code> collection.
		 * @return {Array}         Returns a list of Match objects.
		 */
		getMatches: function(code, regexInfo)
		{
			function defaultAdd(match, regexInfo)
			{
				return [new sh.Match(match[0], match.index, regexInfo.css)];
			};

			var index = 0,
				match = null,
				result = [],
				func = regexInfo.func ? regexInfo.func : defaultAdd
				;

			while((match = regexInfo.regex.exec(code)) != null)
				result = result.concat(func(match, regexInfo));

			return result;
		},

		processUrls: function(code)
		{
			return code.replace(sh.regexLib.url, function(m)
			{
				return '<a href="' + m + '">' + m + '</a>';
			});
		}
	}, // end of utils

	/**
	 * Shorthand to highlight all elements on the page that are marked as
	 * SyntaxHighlighter source code.
	 *
	 * @param {Object} globalParams		Optional parameters which override element's
	 * 									parameters. Only used if element is specified.
	 *
	 * @param {Object} element	Optional element to highlight. If none is
	 * 							provided, all elements in the current document
	 * 							are highlighted.
	 */
	highlight : function(globalParams, element)
	{
		function toArray(source)
		{
			/*var result = [];

			for (var i = 0; i < source.length; i++)
				result.push(source[i]);

			return result;*/
			return $.makeArray(source);
		};

		var elements = element ? [element] : toArray($(sh.config.tagName)),
			propertyName = 'innerHTML',
			highlighter = null;

		if (elements.length === 0) {
			return;
		}

		for (var i = 0; i < elements.length; i++) {
			var target = elements[i],
				params = sh.utils.parseParams(target.className),
				brushName;

			// local params take precedence over globals
			params = sh.utils.merge(globalParams, params);
			brushName = params['brush'];

			if (brushName == null) {
				continue;
			}

			// Instantiate a brush
			if (params['html-script'] == 'true') {
				highlighter = new sh.HtmlScript(brushName);
			} else {
				var brush = sh.utils.findBrush(brushName);

				if (brush) {
					highlighter = new brush();
				} else {
					continue;
				}
			}

			highlighter.highlight(target[propertyName], params);

			var result = highlighter.div;

			if (sh.config.debug) {
				result = document.createElement('textarea');
				result.value = highlighter.div.innerHTML;
				result.style.width = '70em';
				result.style.height = '30em';
			}

			$(target).html(result);//, target);
		}
	},

	/**
	 * Main entry point for the SyntaxHighlighter.
	 * @param {Object} params Optional params to apply to all highlighted elements.
	 */
	all : function(params)
	{
		sh.utils.addEvent(
			window,
			'load',
			function() { sh.highlight(params); }
		);
	}
}; // end of sh

/** Match object */
sh.Match = function(value, index, css)
{
	this.value = value;
	this.index = index;
	this.length = value.length;
	this.css = css;
};

sh.Match.prototype.toString = function()
{
	return this.value;
};

/**
 * Simulates HTML code with a scripting language embedded.
 *
 * @param {String} scriptBrushName Brush name of the scripting language.
 */
sh.HtmlScript = function(scriptBrushName)
{
	var scriptBrush = sh.utils.findBrush(scriptBrushName),
		xmlBrush = new sh.brushes.Xml(),
		bracketsRegex = null;

	if (scriptBrush == null)
		return;

	scriptBrush = new scriptBrush();
	this.xmlBrush = xmlBrush;

	if (scriptBrush.htmlScript == null)
	{
		sh.utils.alert(sh.config.strings.brushNotHtmlScript + scriptBrushName);
		return;
	}

	xmlBrush.regexList.push(
		{ regex: scriptBrush.htmlScript.code, func: process }
	);

	function offsetMatches(matches, offset)
	{
		for (var j = 0, jl = matches.length; j < jl; j++)
			matches[j].index += offset;
	}

	function process(match, info)
	{
		var code = match.code,
			matches = [],
			regexList = scriptBrush.regexList,
			offset = match.index + match.left.length,
			htmlScript = scriptBrush.htmlScript,
			result;

		for (var i = 0, rl = regexList.length; i < rl; i++) {
			result = sh.utils.getMatches(code, regexList[i]);
			offsetMatches(result, offset);
			matches = matches.concat(result);
		}

		if (htmlScript.left != null && match.left != null) {
			result = sh.utils.getMatches(match.left, htmlScript.left);
			offsetMatches(result, match.index);
			matches = matches.concat(result);
		}

		if (htmlScript.right != null && match.right != null) {
			result = sh.utils.getMatches(match.right, htmlScript.right);
			offsetMatches(result, match.index + match[0].lastIndexOf(match.right));
			matches = matches.concat(result);
		}

		return matches;
	}
};

sh.HtmlScript.prototype.highlight = function(code, params) {
	this.xmlBrush.highlight(code, params);
	this.div = this.xmlBrush.div;
}

/**
 * Main Highlither class.
 * @constructor
 */
sh.Highlighter = function()
{
};

sh.Highlighter.prototype = {
	/**
	 * Returns value of the parameter passed to the highlighter.
	 * @param {String} name				Name of the parameter.
	 * @param {Object} defaultValue		Default value.
	 * @return {Object}					Returns found value or default value otherwise.
	 */
	getParam : function(name, defaultValue)
	{
		var result = this.params[name];
		return sh.utils.toBoolean(result == null ? defaultValue : result);
	},

	/**
	 * Shortcut to document.createElement().
	 * @param {String} name		Name of the element to create (DIV, A, etc).
	 * @return {HTMLElement}	Returns new HTML element.
	 */
	create: function(name)
	{
		//return $(name);
		//return
		return document.createElement(name);
	},

	/**
	 * Checks if one match is inside another.
	 * @param {Match} match   Match object to check.
	 * @return {Boolean}      Returns true if given match was inside another, false otherwise.
	 */
	isMatchNested: function(match)
	{
		for (var i = 0; i < this.matches.length; i++)
		{
			var item = this.matches[i];

			if (item === null)
				continue;

			if ((match.index > item.index) && (match.index < item.index + item.length))
				return true;
		}

		return false;
	},

	/**
	 * Applies all regular expression to the code and stores all found
	 * matches in the `this.matches` array.
	 * @param {Array} regexList		List of regular expressions.
	 * @param {String} code			Source code.
	 * @return {Array}				Returns list of matches.
	 */
	findMatches: function(regexList, code)
	{
		var result = [];

		if (regexList != null)
			for (var i = 0; i < regexList.length; i++)
				result = result.concat(sh.utils.getMatches(code, regexList[i]));

		// sort the matches
		result = result.sort(sh.utils.matchesSortCallback);

		return result;
	},

	/**
	 * Checks to see if any of the matches are inside of other matches.
	 * This process would get rid of highligted strings inside comments,
	 * keywords inside strings and so on.
	 */
	removeNestedMatches: function()
	{
		for (var i = 0; i < this.matches.length; i++)
			if (this.isMatchNested(this.matches[i]))
				this.matches[i] = null;
	},

	/**
	 * Splits block of text into individual DIV lines.
	 * @param {String} code     Code to highlight.
	 * @return {String}         Returns highlighted code in HTML form.
	 */
	createDisplayLines : function(code)
	{
		var lines = code.split(/\n/g),
			firstLine = parseInt(this.getParam('first-line')),
			padLength = (firstLine + lines.length).toString().length,
			highlightedLines = this.getParam('highlight', [])
			;

		code = '';

		for (var i = 0; i < lines.length; i++)
		{
			var line = lines[i],
				indent = /^(&nbsp;|\s)+/.exec(line),
				lineClass = 'line alt' + (i % 2 == 0 ? 1 : 2),
				lineNumber = sh.utils.padNumber(firstLine + i, padLength),
				highlighted = highlightedLines.indexOf((firstLine + i).toString()) != -1,
				spaces = null
				;

			if (indent != null)
			{
				spaces = indent[0].toString();
				line = line.substr(spaces.length);
				spaces = spaces.replace(/&nbsp;/g, ' ');
				indent = sh.vars.spaceWidth * spaces.length;
			}
			else
			{
				indent = 0;
			}

			line = sh.utils.trim(line);

			if (line.length == 0)
				line = '&nbsp;';

			if (highlighted)
				lineClass += ' highlighted';

			code +=
				'<div class="' + lineClass + '">'
					+ '<code class="number">' + lineNumber + '.</code>'
					+ '<span class="content">'
						+ (spaces != null ? '<code class="spaces">' + spaces.replace(/\s/g, '&nbsp;') + '</code>' : '')
						+ '<span class="block" style="margin-left: ' + indent + 'px;">' + line + '</span>'
					+ '</span>'
				+ '</div>'
			;
		}

		return code;
	},

	/**
	 * Finds all matches in the source code.
	 * @param {String} code		Source code to process matches in.
	 * @param {Array} matches	Discovered regex matches.
	 * @return {String} Returns formatted HTML with processed mathes.
	 */
	processMatches: function(code, matches)
	{
		var pos = 0,
			result = '',
			decorate = sh.utils.decorate // make an alias to save some bytes
			;

		// Finally, go through the final list of matches and pull the all
		// together adding everything in between that isn't a match.
		for (var i = 0; i < matches.length; i++)
		{
			var match = matches[i];

			if (match === null || match.length === 0)
				continue;

			result += decorate(code.substr(pos, match.index - pos), 'plain')
				+ decorate(match.value, match.css)
				;

			pos = match.index + match.length;
		}

		// don't forget to add whatever's remaining in the string
		result += decorate(code.substr(pos), 'plain');

		return result;
	},

	/**
	 * Highlights the code and returns complete HTML.
	 * @param {String} code     Code to highlight.
	 * @param {Object} params   Parameters object.
	 */
	highlight: function(code, params)
	{
		var conf = sh.config,
			vars = sh.vars,
			div,
			tabSize
			;

		this.params = {};
		this.div = null;
		this.lines = null;
		this.lines_code = null;
		this.code = null;
		this.bar = null;
		this.toolbarCommands = {};
		this.id = sh.utils.guid('highlighter_');

		// register this instance in the highlighters list
		vars.highlighters[this.id] = this;

		if (code === null)
			code = '';

		// Measure width of a single space.
		if (vars.spaceWidth === null)
			vars.spaceWidth = sh.utils.measureSpace();

		// local params take precedence over defaults
		this.params = sh.utils.merge(sh.defaults, params || {});

		// process light mode
		if (this.getParam('light') == true)
			this.params.toolbar = this.params.gutter = false;

		this.div = div = this.create('DIV');
		this.lines = this.create('DIV');
		this.lines.className = 'lines';

		div.className = 'syntaxhighlighter';
		div.id = this.id;

		if (this.getParam('collapse'))
			div.className += ' collapsed';

		if (this.getParam('gutter') == false)
			div.className += ' nogutter';

		div.className += ' ' + this.getParam('class-name');
		div.style.fontSize = this.getParam('font-size', ''); // IE7 can't take null

		this.originalCode = code;
		this.code = sh.utils.trimFirstAndLastLines(code)
			.replace(/\r/g, ' ') // IE lets these buggers through
			;

		tabSize = this.getParam('tab-size');

		// replace tabs with spaces
		this.code = this.getParam('smart-tabs') == true
			? sh.utils.processSmartTabs(this.code, tabSize)
			: sh.utils.processTabs(this.code, tabSize)
			;

		this.code = sh.utils.unindent(this.code);

		// add controls toolbar
		if (this.getParam('toolbar'))
		{
			this.bar = this.create('DIV');
			this.bar.className = 'bar';
			this.bar.appendChild(sh.toolbar.create(this));
			div.appendChild(this.bar);
		}

		// add columns ruler
		if (this.getParam('ruler'))
			div.appendChild(sh.utils.createRuler());

		div.appendChild(this.lines);

		this.matches = this.findMatches(this.regexList, this.code);
		this.removeNestedMatches();

		code = this.processMatches(this.code, this.matches);

		// finally, split all lines so that they wrap well
		code = this.createDisplayLines(sh.utils.trim(code));

		// finally, process the links
		if (this.getParam('auto-links'))
			code = sh.utils.processUrls(code);

		$(this.lines).html(code);
	},

	/**
	 * Converts space separated list of keywords into a regular expression string.
	 * @param {String} str    Space separated keywords.
	 * @return {String}       Returns regular expression string.
	 */
	getKeywords: function(str)
	{
		str = str
			.replace(/^\s+|\s+$/g, '')
			.replace(/\s+/g, '\\b|\\b')
			;

		return '\\b' + str + '\\b';
	},

	/**
	 * Makes a brush compatible with the `html-script` functionality.
	 * @param {Object} regexGroup Object containing `left` and `right` regular expressions.
	 */
	forHtmlScript: function(regexGroup)
	{
		this.htmlScript = {
			left : { regex: regexGroup.left, css: 'script' },
			right : { regex: regexGroup.right, css: 'script' },
			code : new XRegExp(
				"(?<left>" + regexGroup.left.source + ")" +
				"(?<code>.*?)" +
				"(?<right>" + regexGroup.right.source + ")",
				"sgi"
				)
		};
	}
}; // end of Highlighter

return sh;
}(); // end of anonymous function

if (!Array.indexOf)
	/**
	 * Finds an index of element in the array.
	 * @ignore
	 * @param {Object} searchElement
	 * @param {Number} fromIndex
	 * @return {Number} Returns index of element if found; -1 otherwise.
	 */
	Array.prototype.indexOf = function (searchElement, fromIndex)
	{
		fromIndex = Math.max(fromIndex || 0, 0);

		for (var i = fromIndex; i < this.length; i++)
			if(this[i] == searchElement)
				return i;

		return -1;
	};

/**
 * XRegExp 0.6.1
 * (c) 2007-2008 Steven Levithan
 * <http://stevenlevithan.com/regex/xregexp/>
 * MIT License
 *
 * provides an augmented, cross-browser implementation of regular expressions
 * including support for additional modifiers and syntax. several convenience
 * methods and a recursive-construct parser are also included.
 */

// prevent running twice, which would break references to native globals
if (!window.XRegExp) {
// anonymous function to avoid global variables
(function () {
// copy various native globals for reference. can't use the name ``native``
// because it's a reserved JavaScript keyword.
var real = {
        exec:    RegExp.prototype.exec,
        match:   String.prototype.match,
        replace: String.prototype.replace,
        split:   String.prototype.split
    },
    /* regex syntax parsing with support for all the necessary cross-
       browser and context issues (escapings, character classes, etc.) */
    lib = {
        part:       /(?:[^\\([#\s.]+|\\(?!k<[\w$]+>|[pP]{[^}]+})[\S\s]?|\((?=\?(?!#|<[\w$]+>)))+|(\()(?:\?(?:(#)[^)]*\)|<([$\w]+)>))?|\\(?:k<([\w$]+)>|[pP]{([^}]+)})|(\[\^?)|([\S\s])/g,
        replaceVar: /(?:[^$]+|\$(?![1-9$&`']|{[$\w]+}))+|\$(?:([1-9]\d*|[$&`'])|{([$\w]+)})/g,
        extended:   /^(?:\s+|#.*)+/,
        quantifier: /^(?:[?*+]|{\d+(?:,\d*)?})/,
        classLeft:  /&&\[\^?/g,
        classRight: /]/g
    },
    indexOf = function (array, item, from) {
        for (var i = from || 0; i < array.length; i++)
            if (array[i] === item) return i;
        return -1;
    },
    brokenExecUndef = /()??/.exec("")[1] !== undefined,
    plugins = {};

/**
 * Accepts a pattern and flags, returns a new, extended RegExp object.
 * differs from a native regex in that additional flags and syntax are
 * supported and browser inconsistencies are ameliorated.
 * @ignore
 */
XRegExp = function (pattern, flags) {
    if (pattern instanceof RegExp) {
        if (flags !== undefined)
            throw TypeError("can't supply flags when constructing one RegExp from another");
        return pattern.addFlags(); // new copy
    }

    var flags           = flags || "",
        singleline      = flags.indexOf("s") > -1,
        extended        = flags.indexOf("x") > -1,
        hasNamedCapture = false,
        captureNames    = [],
        output          = [],
        part            = lib.part,
        match, cc, len, index, regex;

    part.lastIndex = 0; // in case the last XRegExp compilation threw an error (unbalanced character class)

    while (match = real.exec.call(part, pattern)) {
        // comment pattern. this check must come before the capturing group check,
        // because both match[1] and match[2] will be non-empty.
        if (match[2]) {
            // keep tokens separated unless the following token is a quantifier
            if (!lib.quantifier.test(pattern.slice(part.lastIndex)))
                output.push("(?:)");
        // capturing group
        } else if (match[1]) {
            captureNames.push(match[3] || null);
            if (match[3])
                hasNamedCapture = true;
            output.push("(");
        // named backreference
        } else if (match[4]) {
            index = indexOf(captureNames, match[4]);
            // keep backreferences separate from subsequent literal numbers
            // preserve backreferences to named groups that are undefined at this point as literal strings
            output.push(index > -1 ?
                "\\" + (index + 1) + (isNaN(pattern.charAt(part.lastIndex)) ? "" : "(?:)") :
                match[0]
            );
        // unicode element (requires plugin)
        } else if (match[5]) {
            output.push(plugins.unicode ?
                plugins.unicode.get(match[5], match[0].charAt(1) === "P") :
                match[0]
            );
        // character class opening delimiter ("[" or "[^")
        // (non-native unicode elements are not supported within character classes)
        } else if (match[6]) {
            if (pattern.charAt(part.lastIndex) === "]") {
                // for cross-browser compatibility with ECMA-262 v3 behavior,
                // convert [] to (?!) and [^] to [\S\s].
                output.push(match[6] === "[" ? "(?!)" : "[\\S\\s]");
                part.lastIndex++;
            } else {
                // parse the character class with support for inner escapes and
                // ES4's infinitely nesting intersection syntax ([&&[^&&[]]]).
                cc = XRegExp.matchRecursive("&&" + pattern.slice(match.index), lib.classLeft, lib.classRight, "", {escapeChar: "\\"})[0];
                output.push(match[6] + cc + "]");
                part.lastIndex += cc.length + 1;
            }
        // dot ("."), pound sign ("#"), or whitespace character
        } else if (match[7]) {
            if (singleline && match[7] === ".") {
                output.push("[\\S\\s]");
            } else if (extended && lib.extended.test(match[7])) {
                len = real.exec.call(lib.extended, pattern.slice(part.lastIndex - 1))[0].length;
                // keep tokens separated unless the following token is a quantifier
                if (!lib.quantifier.test(pattern.slice(part.lastIndex - 1 + len)))
                    output.push("(?:)");
                part.lastIndex += len - 1;
            } else {
                output.push(match[7]);
            }
        } else {
            output.push(match[0]);
        }
    }

    regex = RegExp(output.join(""), real.replace.call(flags, /[sx]+/g, ""));
    regex._x = {
        source:       pattern,
        captureNames: hasNamedCapture ? captureNames : null
    };
    return regex;
};

/**
 * Barebones plugin support for now (intentionally undocumented)
 * @ignore
 * @param {Object} name
 * @param {Object} o
 */
XRegExp.addPlugin = function (name, o) {
    plugins[name] = o;
};

/**
 * Adds named capture support, with values returned as ``result.name``.
 *
 * Also fixes two cross-browser issues, following the ECMA-262 v3 spec:
 *  - captured values for non-participating capturing groups should be returned
 *    as ``undefined``, rather than the empty string.
 *  - the regex's ``lastIndex`` should not be incremented after zero-length
 *    matches.
 * @ignore
 */
RegExp.prototype.exec = function (str) {
    var match = real.exec.call(this, str),
        name, i, r2;

    if (match) {
        // fix browsers whose exec methods don't consistently return
        // undefined for non-participating capturing groups
        if (brokenExecUndef && match.length > 1) {
            // r2 doesn't need /g or /y, but they shouldn't hurt
            r2 = new RegExp("^" + this.source + "$(?!\\s)", this.getNativeFlags());
            real.replace.call(match[0], r2, function () {
                for (i = 1; i < arguments.length - 2; i++) {
                    if (arguments[i] === undefined) match[i] = undefined;
                }
            });
        }
        // attach named capture properties
        if (this._x && this._x.captureNames) {
            for (i = 1; i < match.length; i++) {
                name = this._x.captureNames[i - 1];
                if (name) match[name] = match[i];
            }
        }
        // fix browsers that increment lastIndex after zero-length matches
        if (this.global && this.lastIndex > (match.index + match[0].length))
            this.lastIndex--;
    }
    return match;
};
})(); // end anonymous function
} // end if(!window.XRegExp)

/**
 * intentionally undocumented
 * @ignore
 */
RegExp.prototype.getNativeFlags = function () {
    return (this.global     ? "g" : "") +
           (this.ignoreCase ? "i" : "") +
           (this.multiline  ? "m" : "") +
           (this.extended   ? "x" : "") +
           (this.sticky     ? "y" : "");
};

/**
 * Accepts flags; returns a new XRegExp object generated by recompiling
 * the regex with the additional flags (may include non-native flags).
 * The original regex object is not altered.
 * @ignore
 */
RegExp.prototype.addFlags = function (flags) {
    var regex = new XRegExp(this.source, (flags || "") + this.getNativeFlags());
    if (this._x) {
        regex._x = {
            source:       this._x.source,
            captureNames: this._x.captureNames ? this._x.captureNames.slice(0) : null
        };
    }
    return regex;
};

/**
 * Accepts a context object and string; returns the result of calling
 * ``exec`` with the provided string. the context is ignored but is
 * accepted for congruity with ``Function.prototype.call``.
 * @ignore
 */
RegExp.prototype.call = function (context, str) {
    return this.exec(str);
};

/**
 * Accepts a context object and arguments array; returns the result of
 * calling ``exec`` with the first value in the arguments array. the context
 * is ignored but is accepted for congruity with ``Function.prototype.apply``.
 * @ignore
 */
RegExp.prototype.apply = function (context, args) {
    return this.exec(args[0]);
};

/**
 * Accepts a pattern and flags; returns an XRegExp object. if the pattern
 * and flag combination has previously been cached, the cached copy is
 * returned, otherwise the new object is cached.
 * @ignore
 */
XRegExp.cache = function (pattern, flags) {
    var key = "/" + pattern + "/" + (flags || "");
    return XRegExp.cache[key] || (XRegExp.cache[key] = new XRegExp(pattern, flags));
};

/**
 * Accepts a string; returns the string with regex metacharacters escaped.
 * the returned string can safely be used within a regex to match a literal
 * string. escaped characters are [, ], {, }, (, ), -, *, +, ?, ., \, ^, $,
 * |, #, [comma], and whitespace.
 * @ignore
 */
XRegExp.escape = function (str) {
    return str.replace(/[-[\]{}()*+?.\\^$|,#\s]/g, "\\$&");
};

/**
 * Accepts a string to search, left and right delimiters as regex pattern
 * strings, optional regex flags (may include non-native s, x, and y flags),
 * and an options object which allows setting an escape character and changing
 * the return format from an array of matches to a two-dimensional array of
 * string parts with extended position data. returns an array of matches
 * (optionally with extended data), allowing nested instances of left and right
 * delimiters. use the g flag to return all matches, otherwise only the first
 * is returned. if delimiters are unbalanced within the subject data, an error
 * is thrown.
 *
 * This function admittedly pushes the boundaries of what can be accomplished
 * sensibly without a "real" parser. however, by doing so it provides flexible
 * and powerful recursive parsing capabilities with minimal code weight.
 *
 * Warning: the ``escapeChar`` option is considered experimental and might be
 * changed or removed in future versions of XRegExp.
 *
 * unsupported features:
 *  - backreferences within delimiter patterns when using ``escapeChar``.
 *  - although providing delimiters as regex objects adds the minor feature of
 *    independent delimiter flags, it introduces other limitations and is only
 *    intended to be done by the ``XRegExp`` constructor (which can't call
 *    itself while building a regex).
 *
 * @ignore
 */
XRegExp.matchRecursive = function (str, left, right, flags, options) {
    var options      = options || {},
        escapeChar   = options.escapeChar,
        vN           = options.valueNames,
        flags        = flags || "",
        global       = flags.indexOf("g") > -1,
        ignoreCase   = flags.indexOf("i") > -1,
        multiline    = flags.indexOf("m") > -1,
        sticky       = flags.indexOf("y") > -1,
        /* sticky mode has its own handling in this function, which means you
           can use flag "y" even in browsers which don't support it natively */
        flags        = flags.replace(/y/g, ""),
        left         = left  instanceof RegExp ? (left.global  ? left  : left.addFlags("g"))  : new XRegExp(left,  "g" + flags),
        right        = right instanceof RegExp ? (right.global ? right : right.addFlags("g")) : new XRegExp(right, "g" + flags),
        output       = [],
        openTokens   = 0,
        delimStart   = 0,
        delimEnd     = 0,
        lastOuterEnd = 0,
        outerStart, innerStart, leftMatch, rightMatch, escaped, esc;

    if (escapeChar) {
        if (escapeChar.length > 1) throw SyntaxError("can't supply more than one escape character");
        if (multiline)             throw TypeError("can't supply escape character when using the multiline flag");
        escaped = XRegExp.escape(escapeChar);
        /* Escape pattern modifiers:
            /g - not needed here
            /i - included
            /m - **unsupported**, throws error
            /s - handled by XRegExp when delimiters are provided as strings
            /x - handled by XRegExp when delimiters are provided as strings
            /y - not needed here; supported by other handling in this function
        */
        esc = new RegExp(
            "^(?:" + escaped + "[\\S\\s]|(?:(?!" + left.source + "|" + right.source + ")[^" + escaped + "])+)+",
            ignoreCase ? "i" : ""
        );
    }

    while (true) {
        /* advance the starting search position to the end of the last delimiter match.
           a couple special cases are also covered:
            - if using an escape character, advance to the next delimiter's starting position,
              skipping any escaped characters
            - first time through, reset lastIndex in case delimiters were provided as regexes
        */
        left.lastIndex = right.lastIndex = delimEnd +
            (escapeChar ? (esc.exec(str.slice(delimEnd)) || [""])[0].length : 0);

        leftMatch  = left.exec(str);
        rightMatch = right.exec(str);

        // only keep the result which matched earlier in the string
        if (leftMatch && rightMatch) {
            if (leftMatch.index <= rightMatch.index)
                 rightMatch = null;
            else leftMatch  = null;
        }

        /* paths*:
        leftMatch | rightMatch | openTokens | result
        1         | 0          | 1          | ...
        1         | 0          | 0          | ...
        0         | 1          | 1          | ...
        0         | 1          | 0          | throw
        0         | 0          | 1          | throw
        0         | 0          | 0          | break
        * - does not include the sticky mode special case
          - the loop ends after the first completed match if not in global mode
        */

        if (leftMatch || rightMatch) {
            delimStart = (leftMatch || rightMatch).index;
            delimEnd   = (leftMatch ? left : right).lastIndex;
        } else if (!openTokens) {
            break;
        }

        if (sticky && !openTokens && delimStart > lastOuterEnd)
            break;

        if (leftMatch) {
            if (!openTokens++) {
                outerStart = delimStart;
                innerStart = delimEnd;
            }
        } else if (rightMatch && openTokens) {
            if (!--openTokens) {
                if (vN) {
                    if (vN[0] && outerStart > lastOuterEnd)
                               output.push([vN[0], str.slice(lastOuterEnd, outerStart), lastOuterEnd, outerStart]);
                    if (vN[1]) output.push([vN[1], str.slice(outerStart,   innerStart), outerStart,   innerStart]);
                    if (vN[2]) output.push([vN[2], str.slice(innerStart,   delimStart), innerStart,   delimStart]);
                    if (vN[3]) output.push([vN[3], str.slice(delimStart,   delimEnd),   delimStart,   delimEnd]);
                } else {
                    output.push(str.slice(innerStart, delimStart));
                }
                lastOuterEnd = delimEnd;
                if (!global)
                    break;
            }
        } else {
            // reset lastIndex in case delimiters were provided as regexes
            left.lastIndex = right.lastIndex = 0;
            throw Error("subject data contains unbalanced delimiters");
        }

        // if the delimiter matched an empty string, advance delimEnd to avoid an infinite loop
        if (delimStart === delimEnd)
            delimEnd++;
    }

    if (global && !sticky && vN && vN[0] && str.length > lastOuterEnd)
        output.push([vN[0], str.slice(lastOuterEnd), lastOuterEnd, str.length]);

    // reset lastIndex in case delimiters were provided as regexes
    left.lastIndex = right.lastIndex = 0;

    return output;
};


/**
 * SyntaxHighlighter
 * http://alexgorbatchev.com/
 *
 * @version
 * 2.0.287 (February 06 2009)
 *
 * @author
 * Alex Gorbatchev
 *
 * @copyright
 * Copyright (C) 2004-2009 Alex Gorbatchev.
 *
 * Licensed under a GNU Lesser General Public License.
 * http://creativecommons.org/licenses/LGPL/2.1/
 *
 * SyntaxHighlighter is donationware. You are allowed to download, modify and distribute
 * the source code in accordance with LGPL 2.1 license, however if you want to use
 * SyntaxHighlighter on your site or include it in your product, you must donate.
 * http://alexgorbatchev.com/wiki/SyntaxHighlighter:Donate
 */
SyntaxHighlighter.brushes.Bash = function()
{
	var keywords =	'if fi then elif else for do done until while break continue case function return in eq ne gt lt ge le';
	var commands =  'alias apropos awk bash bc bg builtin bzip2 cal cat cd cfdisk chgrp chmod chown chroot' +
					'cksum clear cmp comm command cp cron crontab csplit cut date dc dd ddrescue declare df ' +
					'diff diff3 dig dir dircolors dirname dirs du echo egrep eject enable env ethtool eval ' +
					'exec exit expand export expr false fdformat fdisk fg fgrep file find fmt fold format ' +
					'free fsck ftp gawk getopts grep groups gzip hash head history hostname id ifconfig ' +
					'import install join kill less let ln local locate logname logout look lpc lpr lprint ' +
					'lprintd lprintq lprm ls lsof make man mkdir mkfifo mkisofs mknod more mount mtools ' +
					'mv netstat nice nl nohup nslookup open op passwd paste pathchk ping popd pr printcap ' +
					'printenv printf ps pushd pwd quota quotacheck quotactl ram rcp read readonly renice ' +
					'remsync rm rmdir rsync screen scp sdiff sed select seq set sftp shift shopt shutdown ' +
					'sleep sort source split ssh strace su sudo sum symlink sync tail tar tee test time ' +
					'times touch top traceroute trap tr true tsort tty type ulimit umask umount unalias ' +
					'uname unexpand uniq units unset unshar useradd usermod users uuencode uudecode v vdir ' +
					'vi watch wc whereis which who whoami Wget xargs yes'
					;

	this.regexList = [
		{ regex: SyntaxHighlighter.regexLib.singleLinePerlComments,		css: 'comments' },		// one line comments
		{ regex: SyntaxHighlighter.regexLib.doubleQuotedString,			css: 'string' },		// double quoted strings
		{ regex: new RegExp(this.getKeywords(keywords), 'gm'),			css: 'keyword' },		// keywords
		{ regex: new RegExp(this.getKeywords(commands), 'gm'),			css: 'functions' }		// commands
		];
}

SyntaxHighlighter.brushes.Bash.prototype	= new SyntaxHighlighter.Highlighter();
SyntaxHighlighter.brushes.Bash.aliases		= ['bash', 'shell'];

/**
 * SyntaxHighlighter
 * http://alexgorbatchev.com/
 *
 * @version
 * 2.0.287 (February 06 2009)
 *
 * @author
 * Alex Gorbatchev
 *
 * @copyright
 * Copyright (C) 2004-2009 Alex Gorbatchev.
 *
 * Licensed under a GNU Lesser General Public License.
 * http://creativecommons.org/licenses/LGPL/2.1/
 *
 * SyntaxHighlighter is donationware. You are allowed to download, modify and distribute
 * the source code in accordance with LGPL 2.1 license, however if you want to use
 * SyntaxHighlighter on your site or include it in your product, you must donate.
 * http://alexgorbatchev.com/wiki/SyntaxHighlighter:Donate
 */
SyntaxHighlighter.brushes.CSharp = function()
{
	var keywords =	'abstract as base bool break byte case catch char checked class const ' +
					'continue decimal default delegate do double else enum event explicit ' +
					'extern false finally fixed float for foreach get goto if implicit in int ' +
					'interface internal is lock long namespace new null object operator out ' +
					'override params private protected public readonly ref return sbyte sealed set ' +
					'short sizeof stackalloc static string struct switch this throw true try ' +
					'typeof uint ulong unchecked unsafe ushort using virtual void while';

	function fixComments(match, regexInfo)
	{
		var css = (match[0].indexOf("///") == 0)
			? 'color1'
			: 'comments'
			;

		return [new SyntaxHighlighter.Match(match[0], match.index, css)];
	}

	this.regexList = [
		{ regex: SyntaxHighlighter.regexLib.singleLineCComments,	func : fixComments },		// one line comments
		{ regex: SyntaxHighlighter.regexLib.multiLineCComments,		css: 'comments' },			// multiline comments
		{ regex: SyntaxHighlighter.regexLib.doubleQuotedString,		css: 'string' },			// strings
		{ regex: SyntaxHighlighter.regexLib.singleQuotedString,		css: 'string' },			// strings
		{ regex: /^\s*#.*/gm,										css: 'preprocessor' },		// preprocessor tags like #region and #endregion
		{ regex: new RegExp(this.getKeywords(keywords), 'gm'),		css: 'keyword' }			// c# keyword
		];

	this.forHtmlScript(SyntaxHighlighter.regexLib.aspScriptTags);
};

SyntaxHighlighter.brushes.CSharp.prototype	= new SyntaxHighlighter.Highlighter();
SyntaxHighlighter.brushes.CSharp.aliases	= ['c#', 'c-sharp', 'csharp'];

/**
 * SyntaxHighlighter
 * http://alexgorbatchev.com/
 *
 * @version
 * 2.0.287 (February 06 2009)
 *
 * @author
 * Alex Gorbatchev
 *
 * @copyright
 * Copyright (C) 2004-2009 Alex Gorbatchev.
 *
 * Licensed under a GNU Lesser General Public License.
 * http://creativecommons.org/licenses/LGPL/2.1/
 *
 * SyntaxHighlighter is donationware. You are allowed to download, modify and distribute
 * the source code in accordance with LGPL 2.1 license, however if you want to use
 * SyntaxHighlighter on your site or include it in your product, you must donate.
 * http://alexgorbatchev.com/wiki/SyntaxHighlighter:Donate
 */
SyntaxHighlighter.brushes.Cpp = function()
{
	// Copyright 2006 Shin, YoungJin

	var datatypes =	'ATOM BOOL BOOLEAN BYTE CHAR COLORREF DWORD DWORDLONG DWORD_PTR ' +
					'DWORD32 DWORD64 FLOAT HACCEL HALF_PTR HANDLE HBITMAP HBRUSH ' +
					'HCOLORSPACE HCONV HCONVLIST HCURSOR HDC HDDEDATA HDESK HDROP HDWP ' +
					'HENHMETAFILE HFILE HFONT HGDIOBJ HGLOBAL HHOOK HICON HINSTANCE HKEY ' +
					'HKL HLOCAL HMENU HMETAFILE HMODULE HMONITOR HPALETTE HPEN HRESULT ' +
					'HRGN HRSRC HSZ HWINSTA HWND INT INT_PTR INT32 INT64 LANGID LCID LCTYPE ' +
					'LGRPID LONG LONGLONG LONG_PTR LONG32 LONG64 LPARAM LPBOOL LPBYTE LPCOLORREF ' +
					'LPCSTR LPCTSTR LPCVOID LPCWSTR LPDWORD LPHANDLE LPINT LPLONG LPSTR LPTSTR ' +
					'LPVOID LPWORD LPWSTR LRESULT PBOOL PBOOLEAN PBYTE PCHAR PCSTR PCTSTR PCWSTR ' +
					'PDWORDLONG PDWORD_PTR PDWORD32 PDWORD64 PFLOAT PHALF_PTR PHANDLE PHKEY PINT ' +
					'PINT_PTR PINT32 PINT64 PLCID PLONG PLONGLONG PLONG_PTR PLONG32 PLONG64 POINTER_32 ' +
					'POINTER_64 PSHORT PSIZE_T PSSIZE_T PSTR PTBYTE PTCHAR PTSTR PUCHAR PUHALF_PTR ' +
					'PUINT PUINT_PTR PUINT32 PUINT64 PULONG PULONGLONG PULONG_PTR PULONG32 PULONG64 ' +
					'PUSHORT PVOID PWCHAR PWORD PWSTR SC_HANDLE SC_LOCK SERVICE_STATUS_HANDLE SHORT ' +
					'SIZE_T SSIZE_T TBYTE TCHAR UCHAR UHALF_PTR UINT UINT_PTR UINT32 UINT64 ULONG ' +
					'ULONGLONG ULONG_PTR ULONG32 ULONG64 USHORT USN VOID WCHAR WORD WPARAM WPARAM WPARAM ' +
					'char bool short int __int32 __int64 __int8 __int16 long float double __wchar_t ' +
					'clock_t _complex _dev_t _diskfree_t div_t ldiv_t _exception _EXCEPTION_POINTERS ' +
					'FILE _finddata_t _finddatai64_t _wfinddata_t _wfinddatai64_t __finddata64_t ' +
					'__wfinddata64_t _FPIEEE_RECORD fpos_t _HEAPINFO _HFILE lconv intptr_t ' +
					'jmp_buf mbstate_t _off_t _onexit_t _PNH ptrdiff_t _purecall_handler ' +
					'sig_atomic_t size_t _stat __stat64 _stati64 terminate_function ' +
					'time_t __time64_t _timeb __timeb64 tm uintptr_t _utimbuf ' +
					'va_list wchar_t wctrans_t wctype_t wint_t signed';

	var keywords =	'break case catch class const __finally __exception __try ' +
					'const_cast continue private public protected __declspec ' +
					'default delete deprecated dllexport dllimport do dynamic_cast ' +
					'else enum explicit extern if for friend goto inline ' +
					'mutable naked namespace new noinline noreturn nothrow ' +
					'register reinterpret_cast return selectany ' +
					'sizeof static static_cast struct switch template this ' +
					'thread throw true false try typedef typeid typename union ' +
					'using uuid virtual void volatile whcar_t while';

	var functions =	'_asm assert isalnum isalpha iscntrl isdigit isgraph islower isprint' +
					'ispunct isspace isupper isxdigit tolower toupper errno localeconv ' +
					'setlocale acos asin atan atan2 ceil cos cosh exp fabs floor fmod ' +
					'frexp ldexp log log10 modf pow sin sinh sqrt tan tanh jmp_buf ' +
					'longjmp setjmp raise signal sig_atomic_t va_arg va_end va_start ' +
					'clearerr fclose feof ferror fflush fgetc fgetpos fgets fopen ' +
					'fprintf fputc fputs fread freopen fscanf fseek fsetpos ftell ' +
					'fwrite getc getchar gets perror printf putc putchar puts remove ' +
					'rename rewind scanf setbuf setvbuf sprintf sscanf tmpfile tmpnam ' +
					'ungetc vfprintf vprintf vsprintf abort abs atexit atof atoi atol ' +
					'bsearch calloc cprintf div exit free getenv labs ldiv malloc mblen mbstowcs ' +
					'mbtowc qsort rand realloc srand strtod strtol strtoul system ' +
					'wcstombs wctomb memchr memcmp memcpy memmove memset strcat strchr ' +
					'strcmp strcoll strcpy strcspn strerror strlen strncat strncmp ' +
					'strncpy strpbrk strrchr strspn strstr strtok strxfrm asctime ' +
					'clock ctime difftime gmtime localtime mktime strftime time';

	this.regexList = [
		{ regex: SyntaxHighlighter.regexLib.singleLineCComments,	css: 'comments' },			// one line comments
		{ regex: SyntaxHighlighter.regexLib.multiLineCComments,		css: 'comments' },			// multiline comments
		{ regex: SyntaxHighlighter.regexLib.doubleQuotedString,		css: 'string' },			// strings
		{ regex: SyntaxHighlighter.regexLib.singleQuotedString,		css: 'string' },			// strings
		{ regex: /^ *#.*/gm,										css: 'preprocessor' },
		{ regex: new RegExp(this.getKeywords(datatypes), 'gm'),		css: 'color1' },
		{ regex: new RegExp(this.getKeywords(functions), 'gm'),		css: 'functions' },
		{ regex: new RegExp(this.getKeywords(keywords), 'gm'),		css: 'keyword' }
		];
};

SyntaxHighlighter.brushes.Cpp.prototype	= new SyntaxHighlighter.Highlighter();
SyntaxHighlighter.brushes.Cpp.aliases	= ['cpp', 'c'];
/**
 * SyntaxHighlighter
 * http://alexgorbatchev.com/
 *
 * @version
 * 2.0.287 (February 06 2009)
 *
 * @author
 * Alex Gorbatchev
 *
 * @copyright
 * Copyright (C) 2004-2009 Alex Gorbatchev.
 *
 * Licensed under a GNU Lesser General Public License.
 * http://creativecommons.org/licenses/LGPL/2.1/
 *
 * SyntaxHighlighter is donationware. You are allowed to download, modify and distribute
 * the source code in accordance with LGPL 2.1 license, however if you want to use
 * SyntaxHighlighter on your site or include it in your product, you must donate.
 * http://alexgorbatchev.com/wiki/SyntaxHighlighter:Donate
 */
SyntaxHighlighter.brushes.CSS = function()
{
	function getKeywordsCSS(str)
	{
		return '\\b([a-z_]|)' + str.replace(/ /g, '(?=:)\\b|\\b([a-z_\\*]|\\*|)') + '(?=:)\\b';
	};

	function getValuesCSS(str)
	{
		return '\\b' + str.replace(/ /g, '(?!-)(?!:)\\b|\\b()') + '\:\\b';
	};

	var keywords =	'ascent azimuth background-attachment background-color background-image background-position ' +
					'background-repeat background baseline bbox border-collapse border-color border-spacing border-style border-top ' +
					'border-right border-bottom border-left border-top-color border-right-color border-bottom-color border-left-color ' +
					'border-top-style border-right-style border-bottom-style border-left-style border-top-width border-right-width ' +
					'border-bottom-width border-left-width border-width border bottom cap-height caption-side centerline clear clip color ' +
					'content counter-increment counter-reset cue-after cue-before cue cursor definition-src descent direction display ' +
					'elevation empty-cells float font-size-adjust font-family font-size font-stretch font-style font-variant font-weight font ' +
					'height left letter-spacing line-height list-style-image list-style-position list-style-type list-style margin-top ' +
					'margin-right margin-bottom margin-left margin marker-offset marks mathline max-height max-width min-height min-width orphans ' +
					'outline-color outline-style outline-width outline overflow padding-top padding-right padding-bottom padding-left padding page ' +
					'page-break-after page-break-before page-break-inside pause pause-after pause-before pitch pitch-range play-during position ' +
					'quotes right richness size slope src speak-header speak-numeral speak-punctuation speak speech-rate stemh stemv stress ' +
					'table-layout text-align top text-decoration text-indent text-shadow text-transform unicode-bidi unicode-range units-per-em ' +
					'vertical-align visibility voice-family volume white-space widows width widths word-spacing x-height z-index';

	var values =	'above absolute all always aqua armenian attr aural auto avoid baseline behind below bidi-override black blink block blue bold bolder '+
					'both bottom braille capitalize caption center center-left center-right circle close-quote code collapse compact condensed '+
					'continuous counter counters crop cross crosshair cursive dashed decimal decimal-leading-zero default digits disc dotted double '+
					'embed embossed e-resize expanded extra-condensed extra-expanded fantasy far-left far-right fast faster fixed format fuchsia '+
					'gray green groove handheld hebrew help hidden hide high higher icon inline-table inline inset inside invert italic '+
					'justify landscape large larger left-side left leftwards level lighter lime line-through list-item local loud lower-alpha '+
					'lowercase lower-greek lower-latin lower-roman lower low ltr marker maroon medium message-box middle mix move narrower '+
					'navy ne-resize no-close-quote none no-open-quote no-repeat normal nowrap n-resize nw-resize oblique olive once open-quote outset '+
					'outside overline pointer portrait pre print projection purple red relative repeat repeat-x repeat-y rgb ridge right right-side '+
					'rightwards rtl run-in screen scroll semi-condensed semi-expanded separate se-resize show silent silver slower slow '+
					'small small-caps small-caption smaller soft solid speech spell-out square s-resize static status-bar sub super sw-resize '+
					'table-caption table-cell table-column table-column-group table-footer-group table-header-group table-row table-row-group teal '+
					'text-bottom text-top thick thin top transparent tty tv ultra-condensed ultra-expanded underline upper-alpha uppercase upper-latin '+
					'upper-roman url visible wait white wider w-resize x-fast x-high x-large x-loud x-low x-slow x-small x-soft xx-large xx-small yellow';

	var fonts =		'[mM]onospace [tT]ahoma [vV]erdana [aA]rial [hH]elvetica [sS]ans-serif [sS]erif [cC]ourier mono sans serif';

	this.regexList = [
		{ regex: SyntaxHighlighter.regexLib.multiLineCComments,		css: 'comments' },	// multiline comments
		{ regex: SyntaxHighlighter.regexLib.doubleQuotedString,		css: 'string' },	// double quoted strings
		{ regex: SyntaxHighlighter.regexLib.singleQuotedString,		css: 'string' },	// single quoted strings
		{ regex: /\#[a-fA-F0-9]{3,6}/g,								css: 'value' },		// html colors
		{ regex: /(-?\d+)(\.\d+)?(px|em|pt|\:|\%|)/g,				css: 'value' },		// sizes
		{ regex: /!important/g,										css: 'color3' },	// !important
		{ regex: new RegExp(getKeywordsCSS(keywords), 'gm'),		css: 'keyword' },	// keywords
		{ regex: new RegExp(getValuesCSS(values), 'g'),				css: 'value' },		// values
		{ regex: new RegExp(this.getKeywords(fonts), 'g'),			css: 'color1' }		// fonts
		];

	this.forHtmlScript({
		left: /(&lt;|<)\s*style.*?(&gt;|>)/gi,
		right: /(&lt;|<)\/\s*style\s*(&gt;|>)/gi
		});
};

SyntaxHighlighter.brushes.CSS.prototype	= new SyntaxHighlighter.Highlighter();
SyntaxHighlighter.brushes.CSS.aliases	= ['css'];
/**
 * SyntaxHighlighter
 * http://alexgorbatchev.com/
 *
 * @version
 * 2.0.287 (February 06 2009)
 *
 * @author
 * Alex Gorbatchev
 *
 * @copyright
 * Copyright (C) 2004-2009 Alex Gorbatchev.
 *
 * Licensed under a GNU Lesser General Public License.
 * http://creativecommons.org/licenses/LGPL/2.1/
 *
 * SyntaxHighlighter is donationware. You are allowed to download, modify and distribute
 * the source code in accordance with LGPL 2.1 license, however if you want to use
 * SyntaxHighlighter on your site or include it in your product, you must donate.
 * http://alexgorbatchev.com/wiki/SyntaxHighlighter:Donate
 */
SyntaxHighlighter.brushes.Delphi = function()
{
	var keywords =	'abs addr and ansichar ansistring array as asm begin boolean byte cardinal ' +
					'case char class comp const constructor currency destructor div do double ' +
					'downto else end except exports extended false file finalization finally ' +
					'for function goto if implementation in inherited int64 initialization ' +
					'integer interface is label library longint longword mod nil not object ' +
					'of on or packed pansichar pansistring pchar pcurrency pdatetime pextended ' +
					'pint64 pointer private procedure program property pshortstring pstring ' +
					'pvariant pwidechar pwidestring protected public published raise real real48 ' +
					'record repeat set shl shortint shortstring shr single smallint string then ' +
					'threadvar to true try type unit until uses val var varirnt while widechar ' +
					'widestring with word write writeln xor';

	this.regexList = [
		{ regex: /\(\*[\s\S]*?\*\)/gm,								css: 'comments' },  	// multiline comments (* *)
		{ regex: /{(?!\$)[\s\S]*?}/gm,								css: 'comments' },  	// multiline comments { }
		{ regex: SyntaxHighlighter.regexLib.singleLineCComments,	css: 'comments' },  	// one line
		{ regex: SyntaxHighlighter.regexLib.singleQuotedString,		css: 'string' },		// strings
		{ regex: /\{\$[a-zA-Z]+ .+\}/g,								css: 'color1' },		// compiler Directives and Region tags
		{ regex: /\b[\d\.]+\b/g,									css: 'value' },			// numbers 12345
		{ regex: /\$[a-zA-Z0-9]+\b/g,								css: 'value' },			// numbers $F5D3
		{ regex: new RegExp(this.getKeywords(keywords), 'gm'),		css: 'keyword' }		// keyword
		];
};

SyntaxHighlighter.brushes.Delphi.prototype	= new SyntaxHighlighter.Highlighter();
SyntaxHighlighter.brushes.Delphi.aliases	= ['delphi', 'pascal'];
/**
 * SyntaxHighlighter
 * http://alexgorbatchev.com/
 *
 * @version
 * 2.0.287 (February 06 2009)
 *
 * @author
 * Alex Gorbatchev
 *
 * @copyright
 * Copyright (C) 2004-2009 Alex Gorbatchev.
 *
 * Licensed under a GNU Lesser General Public License.
 * http://creativecommons.org/licenses/LGPL/2.1/
 *
 * SyntaxHighlighter is donationware. You are allowed to download, modify and distribute
 * the source code in accordance with LGPL 2.1 license, however if you want to use
 * SyntaxHighlighter on your site or include it in your product, you must donate.
 * http://alexgorbatchev.com/wiki/SyntaxHighlighter:Donate
 */
SyntaxHighlighter.brushes.Diff = function()
{
	this.regexList = [
		{ regex: /^\+\+\+.*$/gm,		css: 'color2' },
		{ regex: /^\-\-\-.*$/gm,		css: 'color2' },
		{ regex: /^\s.*$/gm,			css: 'color1' },
		{ regex: /^@@.*@@$/gm,			css: 'variable' },
		{ regex: /^\+[^\+]{1}.*$/gm,	css: 'string' },
		{ regex: /^\-[^\-]{1}.*$/gm,	css: 'comments' }
		];
};

SyntaxHighlighter.brushes.Diff.prototype	= new SyntaxHighlighter.Highlighter();
SyntaxHighlighter.brushes.Diff.aliases		= ['diff', 'patch'];
/**
 * SyntaxHighlighter
 * http://alexgorbatchev.com/
 *
 * @version
 * 2.0.287 (February 06 2009)
 *
 * @author
 * Alex Gorbatchev
 *
 * @copyright
 * Copyright (C) 2004-2009 Alex Gorbatchev.
 *
 * Licensed under a GNU Lesser General Public License.
 * http://creativecommons.org/licenses/LGPL/2.1/
 *
 * SyntaxHighlighter is donationware. You are allowed to download, modify and distribute
 * the source code in accordance with LGPL 2.1 license, however if you want to use
 * SyntaxHighlighter on your site or include it in your product, you must donate.
 * http://alexgorbatchev.com/wiki/SyntaxHighlighter:Donate
 */
SyntaxHighlighter.brushes.Groovy = function()
{
	// Contributed by Andres Almiray
	// http://jroller.com/aalmiray/entry/nice_source_code_syntax_highlighter

	var keywords =	'as assert break case catch class continue def default do else extends finally ' +
					'if in implements import instanceof interface new package property return switch ' +
					'throw throws try while public protected private static';
	var types    =  'void boolean byte char short int long float double';
	var constants = 'null';
	var methods   = 'allProperties count get size '+
					'collect each eachProperty eachPropertyName eachWithIndex find findAll ' +
					'findIndexOf grep inject max min reverseEach sort ' +
					'asImmutable asSynchronized flatten intersect join pop reverse subMap toList ' +
					'padRight padLeft contains eachMatch toCharacter toLong toUrl tokenize ' +
					'eachFile eachFileRecurse eachB yte eachLine readBytes readLine getText ' +
					'splitEachLine withReader append encodeBase64 decodeBase64 filterLine ' +
					'transformChar transformLine withOutputStream withPrintWriter withStream ' +
					'withStreams withWriter withWriterAppend write writeLine '+
					'dump inspect invokeMethod print println step times upto use waitForOrKill '+
					'getText';

	this.regexList = [
		{ regex: SyntaxHighlighter.regexLib.singleLineCComments,				css: 'comments' },		// one line comments
		{ regex: SyntaxHighlighter.regexLib.multiLineCComments,					css: 'comments' },		// multiline comments
		{ regex: SyntaxHighlighter.regexLib.doubleQuotedString,					css: 'string' },		// strings
		{ regex: SyntaxHighlighter.regexLib.singleQuotedString,					css: 'string' },		// strings
		{ regex: /""".*"""/g,													css: 'string' },		// GStrings
		{ regex: new RegExp('\\b([\\d]+(\\.[\\d]+)?|0x[a-f0-9]+)\\b', 'gi'),	css: 'value' },			// numbers
		{ regex: new RegExp(this.getKeywords(keywords), 'gm'),					css: 'keyword' },		// goovy keyword
		{ regex: new RegExp(this.getKeywords(types), 'gm'),						css: 'color1' },		// goovy/java type
		{ regex: new RegExp(this.getKeywords(constants), 'gm'),					css: 'constants' },		// constants
		{ regex: new RegExp(this.getKeywords(methods), 'gm'),					css: 'functions' }		// methods
		];

	this.forHtmlScript(SyntaxHighlighter.regexLib.aspScriptTags);
}

SyntaxHighlighter.brushes.Groovy.prototype	= new SyntaxHighlighter.Highlighter();
SyntaxHighlighter.brushes.Groovy.aliases	= ['groovy'];
/**
 * SyntaxHighlighter
 * http://alexgorbatchev.com/
 *
 * @version
 * 2.0.287 (February 06 2009)
 *
 * @author
 * Alex Gorbatchev
 *
 * @copyright
 * Copyright (C) 2004-2009 Alex Gorbatchev.
 *
 * Licensed under a GNU Lesser General Public License.
 * http://creativecommons.org/licenses/LGPL/2.1/
 *
 * SyntaxHighlighter is donationware. You are allowed to download, modify and distribute
 * the source code in accordance with LGPL 2.1 license, however if you want to use
 * SyntaxHighlighter on your site or include it in your product, you must donate.
 * http://alexgorbatchev.com/wiki/SyntaxHighlighter:Donate
 */
SyntaxHighlighter.brushes.JScript = function()
{
	var keywords =	'abstract boolean break byte case catch char class const continue debugger ' +
					'default delete do double else enum export extends false final finally float ' +
					'for function goto if implements import in instanceof int interface long native ' +
					'new null package private protected public return short static super switch ' +
					'synchronized this throw throws transient true try typeof var void volatile while with';

	this.regexList = [
		{ regex: SyntaxHighlighter.regexLib.singleLineCComments,	css: 'comments' },			// one line comments
		{ regex: SyntaxHighlighter.regexLib.multiLineCComments,		css: 'comments' },			// multiline comments
		{ regex: SyntaxHighlighter.regexLib.doubleQuotedString,		css: 'string' },			// double quoted strings
		{ regex: SyntaxHighlighter.regexLib.singleQuotedString,		css: 'string' },			// single quoted strings
		{ regex: /\s*#.*/gm,										css: 'preprocessor' },		// preprocessor tags like #region and #endregion
		{ regex: new RegExp(this.getKeywords(keywords), 'gm'),		css: 'keyword' }			// keywords
		];

	this.forHtmlScript(SyntaxHighlighter.regexLib.scriptScriptTags);
};

SyntaxHighlighter.brushes.JScript.prototype	= new SyntaxHighlighter.Highlighter();
SyntaxHighlighter.brushes.JScript.aliases	= ['js', 'jscript', 'javascript'];
/**
 * SyntaxHighlighter
 * http://alexgorbatchev.com/
 *
 * @version
 * 2.0.287 (February 06 2009)
 *
 * @author
 * Alex Gorbatchev
 *
 * @copyright
 * Copyright (C) 2004-2009 Alex Gorbatchev.
 *
 * Licensed under a GNU Lesser General Public License.
 * http://creativecommons.org/licenses/LGPL/2.1/
 *
 * SyntaxHighlighter is donationware. You are allowed to download, modify and distribute
 * the source code in accordance with LGPL 2.1 license, however if you want to use
 * SyntaxHighlighter on your site or include it in your product, you must donate.
 * http://alexgorbatchev.com/wiki/SyntaxHighlighter:Donate
 */
SyntaxHighlighter.brushes.Java = function()
{
	var keywords =	'abstract assert boolean break byte case catch char class const ' +
					'continue default do double else enum extends ' +
					'false final finally float for goto if implements import ' +
					'instanceof int interface long native new null ' +
					'package private protected public return ' +
					'short static strictfp super switch synchronized this throw throws true ' +
					'transient try void volatile while';

	this.regexList = [
		{ regex: SyntaxHighlighter.regexLib.singleLineCComments,	css: 'comments' },		// one line comments
		{ regex: SyntaxHighlighter.regexLib.multiLineCComments,		css: 'comments' },		// multiline comments
		{ regex: SyntaxHighlighter.regexLib.doubleQuotedString,		css: 'string' },		// strings
		{ regex: SyntaxHighlighter.regexLib.singleQuotedString,		css: 'string' },		// strings
		{ regex: /\b([\d]+(\.[\d]+)?|0x[a-f0-9]+)\b/gi,				css: 'value' },			// numbers
		{ regex: /(?!\@interface\b)\@[\$\w]+\b/g,					css: 'color1' },		// annotation @anno
		{ regex: /\@interface\b/g,									css: 'color2' },		// @interface keyword
		{ regex: new RegExp(this.getKeywords(keywords), 'gm'),		css: 'keyword' }		// java keyword
		];

	this.forHtmlScript(SyntaxHighlighter.regexLib.aspScriptTags);
};

SyntaxHighlighter.brushes.Java.prototype	= new SyntaxHighlighter.Highlighter();
SyntaxHighlighter.brushes.Java.aliases		= ['java'];
/**
 * SyntaxHighlighter
 * http://alexgorbatchev.com/
 *
 * @version
 * 2.0.287 (February 06 2009)
 *
 * @author
 * Alex Gorbatchev
 *
 * @copyright
 * Copyright (C) 2004-2009 Alex Gorbatchev.
 *
 * Licensed under a GNU Lesser General Public License.
 * http://creativecommons.org/licenses/LGPL/2.1/
 *
 * SyntaxHighlighter is donationware. You are allowed to download, modify and distribute
 * the source code in accordance with LGPL 2.1 license, however if you want to use
 * SyntaxHighlighter on your site or include it in your product, you must donate.
 * http://alexgorbatchev.com/wiki/SyntaxHighlighter:Donate
 */
SyntaxHighlighter.brushes.Php = function()
{
	var funcs	=	'abs acos acosh addcslashes addslashes ' +
					'array_change_key_case array_chunk array_combine array_count_values array_diff '+
					'array_diff_assoc array_diff_key array_diff_uassoc array_diff_ukey array_fill '+
					'array_filter array_flip array_intersect array_intersect_assoc array_intersect_key '+
					'array_intersect_uassoc array_intersect_ukey array_key_exists array_keys array_map '+
					'array_merge array_merge_recursive array_multisort array_pad array_pop array_product '+
					'array_push array_rand array_reduce array_reverse array_search array_shift '+
					'array_slice array_splice array_sum array_udiff array_udiff_assoc '+
					'array_udiff_uassoc array_uintersect array_uintersect_assoc '+
					'array_uintersect_uassoc array_unique array_unshift array_values array_walk '+
					'array_walk_recursive atan atan2 atanh base64_decode base64_encode base_convert '+
					'basename bcadd bccomp bcdiv bcmod bcmul bindec bindtextdomain bzclose bzcompress '+
					'bzdecompress bzerrno bzerror bzerrstr bzflush bzopen bzread bzwrite ceil chdir '+
					'checkdate checkdnsrr chgrp chmod chop chown chr chroot chunk_split class_exists '+
					'closedir closelog copy cos cosh count count_chars date decbin dechex decoct '+
					'deg2rad delete ebcdic2ascii echo empty end ereg ereg_replace eregi eregi_replace error_log '+
					'error_reporting escapeshellarg escapeshellcmd eval exec exit exp explode extension_loaded '+
					'feof fflush fgetc fgetcsv fgets fgetss file_exists file_get_contents file_put_contents '+
					'fileatime filectime filegroup fileinode filemtime fileowner fileperms filesize filetype '+
					'floatval flock floor flush fmod fnmatch fopen fpassthru fprintf fputcsv fputs fread fscanf '+
					'fseek fsockopen fstat ftell ftok getallheaders getcwd getdate getenv gethostbyaddr gethostbyname '+
					'gethostbynamel getimagesize getlastmod getmxrr getmygid getmyinode getmypid getmyuid getopt '+
					'getprotobyname getprotobynumber getrandmax getrusage getservbyname getservbyport gettext '+
					'gettimeofday gettype glob gmdate gmmktime iconv ini_alter ini_get ini_get_all ini_restore ini_set '+
					'interface_exists intval ip2long is_a is_array is_bool is_callable is_dir is_double '+
					'is_executable is_file is_finite is_float is_infinite is_int is_integer is_link is_long '+
					'is_nan is_null is_numeric is_object is_readable is_real is_resource is_scalar is_soap_fault '+
					'is_string is_subclass_of is_uploaded_file is_writable is_writeable mkdir mktime nl2br '+
					'parse_ini_file parse_str parse_url passthru pathinfo readlink realpath rewind rewinddir rmdir '+
					'round str_ireplace str_pad str_repeat str_replace str_rot13 str_shuffle str_split '+
					'str_word_count strcasecmp strchr strcmp strcoll strcspn strftime strip_tags stripcslashes '+
					'stripos stripslashes stristr strlen strnatcasecmp strnatcmp strncasecmp strncmp strpbrk '+
					'strpos strptime strrchr strrev strripos strrpos strspn strstr strtok strtolower strtotime '+
					'strtoupper strtr strval substr substr_compare';

	var keywords =	'and or xor array as break case ' +
					'cfunction class const continue declare default die do else ' +
					'elseif enddeclare endfor endforeach endif endswitch endwhile ' +
					'extends for foreach function include include_once global if ' +
					'new old_function return static switch use require require_once ' +
					'var while abstract interface public implements extends private protected throw';

	var constants	= '__FILE__ __LINE__ __METHOD__ __FUNCTION__ __CLASS__';

	this.regexList = [
		{ regex: SyntaxHighlighter.regexLib.singleLineCComments,	css: 'comments' },			// one line comments
		{ regex: SyntaxHighlighter.regexLib.multiLineCComments,		css: 'comments' },			// multiline comments
		{ regex: SyntaxHighlighter.regexLib.doubleQuotedString,		css: 'string' },			// double quoted strings
		{ regex: SyntaxHighlighter.regexLib.singleQuotedString,		css: 'string' },			// single quoted strings
		{ regex: /\$\w+/g,											css: 'variable' },			// variables
		{ regex: new RegExp(this.getKeywords(funcs), 'gmi'),		css: 'functions' },			// common functions
		{ regex: new RegExp(this.getKeywords(constants), 'gmi'),	css: 'constants' },			// constants
		{ regex: new RegExp(this.getKeywords(keywords), 'gm'),		css: 'keyword' }			// keyword
		];

	this.forHtmlScript(SyntaxHighlighter.regexLib.phpScriptTags);
};

SyntaxHighlighter.brushes.Php.prototype	= new SyntaxHighlighter.Highlighter();
SyntaxHighlighter.brushes.Php.aliases	= ['php'];
/**
 * SyntaxHighlighter
 * http://alexgorbatchev.com/
 *
 * @version
 * 2.0.287 (February 06 2009)
 *
 * @author
 * Alex Gorbatchev
 *
 * @copyright
 * Copyright (C) 2004-2009 Alex Gorbatchev.
 *
 * Licensed under a GNU Lesser General Public License.
 * http://creativecommons.org/licenses/LGPL/2.1/
 *
 * SyntaxHighlighter is donationware. You are allowed to download, modify and distribute
 * the source code in accordance with LGPL 2.1 license, however if you want to use
 * SyntaxHighlighter on your site or include it in your product, you must donate.
 * http://alexgorbatchev.com/wiki/SyntaxHighlighter:Donate
 */
SyntaxHighlighter.brushes.Plain = function()
{
};

SyntaxHighlighter.brushes.Plain.prototype = new SyntaxHighlighter.Highlighter();
SyntaxHighlighter.brushes.Plain.aliases = ['text', 'plain'];
/**
 * SyntaxHighlighter
 * http://alexgorbatchev.com/
 *
 * @version
 * 2.0.287 (February 06 2009)
 *
 * @author
 * Alex Gorbatchev
 *
 * @copyright
 * Copyright (C) 2004-2009 Alex Gorbatchev.
 *
 * Licensed under a GNU Lesser General Public License.
 * http://creativecommons.org/licenses/LGPL/2.1/
 *
 * SyntaxHighlighter is donationware. You are allowed to download, modify and distribute
 * the source code in accordance with LGPL 2.1 license, however if you want to use
 * SyntaxHighlighter on your site or include it in your product, you must donate.
 * http://alexgorbatchev.com/wiki/SyntaxHighlighter:Donate
 */
SyntaxHighlighter.brushes.Python = function()
{
	// Contributed by Gheorghe Milas

    var keywords =  'and assert break class continue def del elif else ' +
                    'except exec finally for from global if import in is ' +
                    'lambda not or pass print raise return try yield while';

    var special =  'None True False self cls class_';

    this.regexList = [
        { regex: SyntaxHighlighter.regexLib.singleLinePerlComments, css: 'comments' },
        { regex: /^\s*@\w+/gm, 										css: 'decorator' },
        { regex: /(['\"]{3})([^\1])*?\1/gm, 						css: 'comments' },
        { regex: /"(?!")(?:\.|\\\"|[^\""\n])*"/gm, 					css: 'string' },
        { regex: /'(?!')(?:\.|(\\\')|[^\''\n])*'/gm, 				css: 'string' },
        { regex: /\+|\-|\*|\/|\%|=|==/gm, 								css: 'keyword' },
        { regex: /\b\d+\.?\w*/g, 									css: 'value' },
        { regex: new RegExp(this.getKeywords(keywords), 'gm'), 		css: 'keyword' },
        { regex: new RegExp(this.getKeywords(special), 'gm'), 		css: 'color1' }
        ];

	this.forHtmlScript(SyntaxHighlighter.regexLib.aspScriptTags);
};

SyntaxHighlighter.brushes.Python.prototype  = new SyntaxHighlighter.Highlighter();
SyntaxHighlighter.brushes.Python.aliases    = ['py', 'python'];
/**
 * SyntaxHighlighter
 * http://alexgorbatchev.com/
 *
 * @version
 * 2.0.287 (February 06 2009)
 *
 * @author
 * Alex Gorbatchev
 *
 * @copyright
 * Copyright (C) 2004-2009 Alex Gorbatchev.
 *
 * Licensed under a GNU Lesser General Public License.
 * http://creativecommons.org/licenses/LGPL/2.1/
 *
 * SyntaxHighlighter is donationware. You are allowed to download, modify and distribute
 * the source code in accordance with LGPL 2.1 license, however if you want to use
 * SyntaxHighlighter on your site or include it in your product, you must donate.
 * http://alexgorbatchev.com/wiki/SyntaxHighlighter:Donate
 */
SyntaxHighlighter.brushes.Ruby = function()
{
	// Contributed by Erik Peterson.

	var keywords =	'alias and BEGIN begin break case class def define_method defined do each else elsif ' +
					'END end ensure false for if in module new next nil not or raise redo rescue retry return ' +
					'self super then throw true undef unless until when while yield';

	var builtins =	'Array Bignum Binding Class Continuation Dir Exception FalseClass File::Stat File Fixnum Fload ' +
					'Hash Integer IO MatchData Method Module NilClass Numeric Object Proc Range Regexp String Struct::TMS Symbol ' +
					'ThreadGroup Thread Time TrueClass';

	this.regexList = [
		{ regex: SyntaxHighlighter.regexLib.singleLinePerlComments,	css: 'comments' },		// one line comments
		{ regex: SyntaxHighlighter.regexLib.doubleQuotedString,		css: 'string' },		// double quoted strings
		{ regex: SyntaxHighlighter.regexLib.singleQuotedString,		css: 'string' },		// single quoted strings
		{ regex: /\b[A-Z0-9_]+\b/g,									css: 'constants' },		// constants
		{ regex: /:[a-z][A-Za-z0-9_]*/g,							css: 'color2' },		// symbols
		{ regex: /(\$|@@|@)\w+/g,									css: 'variable bold' },	// $global, @instance, and @@class variables
		{ regex: new RegExp(this.getKeywords(keywords), 'gm'),		css: 'keyword' },		// keywords
		{ regex: new RegExp(this.getKeywords(builtins), 'gm'),		css: 'color1' }			// builtins
		];

	this.forHtmlScript(SyntaxHighlighter.regexLib.aspScriptTags);
};

SyntaxHighlighter.brushes.Ruby.prototype	= new SyntaxHighlighter.Highlighter();
SyntaxHighlighter.brushes.Ruby.aliases		= ['ruby', 'rails', 'ror'];
/**
 * SyntaxHighlighter
 * http://alexgorbatchev.com/
 *
 * @version
 * 2.0.287 (February 06 2009)
 *
 * @author
 * Alex Gorbatchev
 *
 * @copyright
 * Copyright (C) 2004-2009 Alex Gorbatchev.
 *
 * Licensed under a GNU Lesser General Public License.
 * http://creativecommons.org/licenses/LGPL/2.1/
 *
 * SyntaxHighlighter is donationware. You are allowed to download, modify and distribute
 * the source code in accordance with LGPL 2.1 license, however if you want to use
 * SyntaxHighlighter on your site or include it in your product, you must donate.
 * http://alexgorbatchev.com/wiki/SyntaxHighlighter:Donate
 */
SyntaxHighlighter.brushes.Sql = function()
{
	var funcs	=	'abs avg case cast coalesce convert count current_timestamp ' +
					'current_user day isnull left lower month nullif replace right ' +
					'session_user space substring sum system_user upper user year';

	var keywords =	'absolute action add after alter as asc at authorization begin bigint ' +
					'binary bit by cascade char character check checkpoint close collate ' +
					'column commit committed connect connection constraint contains continue ' +
					'create cube current current_date current_time cursor database date ' +
					'deallocate dec decimal declare default delete desc distinct double drop ' +
					'dynamic else end end-exec escape except exec execute false fetch first ' +
					'float for force foreign forward free from full function global goto grant ' +
					'group grouping having hour ignore index inner insensitive insert instead ' +
					'int integer intersect into is isolation key last level load local max min ' +
					'minute modify move name national nchar next no numeric of off on only ' +
					'open option order out output partial password precision prepare primary ' +
					'prior privileges procedure public read real references relative repeatable ' +
					'restrict return returns revoke rollback rollup rows rule schema scroll ' +
					'second section select sequence serializable set size smallint static ' +
					'statistics table temp temporary then time timestamp to top transaction ' +
					'translation trigger true truncate uncommitted union unique update values ' +
					'varchar varying view when where with work';

	var operators =	'all and any between cross in join like not null or outer some';

	this.regexList = [
		{ regex: /--(.*)$/gm,												css: 'comments' },			// one line and multiline comments
		{ regex: SyntaxHighlighter.regexLib.multiLineDoubleQuotedString,	css: 'string' },			// double quoted strings
		{ regex: SyntaxHighlighter.regexLib.multiLineSingleQuotedString,	css: 'string' },			// single quoted strings
		{ regex: new RegExp(this.getKeywords(funcs), 'gmi'),				css: 'color2' },			// functions
		{ regex: new RegExp(this.getKeywords(operators), 'gmi'),			css: 'color1' },			// operators and such
		{ regex: new RegExp(this.getKeywords(keywords), 'gmi'),				css: 'keyword' }			// keyword
		];
};

SyntaxHighlighter.brushes.Sql.prototype	= new SyntaxHighlighter.Highlighter();
SyntaxHighlighter.brushes.Sql.aliases	= ['sql'];

/**
 * SyntaxHighlighter
 * http://alexgorbatchev.com/
 *
 * @version
 * 2.0.287 (February 06 2009)
 *
 * @author
 * Alex Gorbatchev
 *
 * @copyright
 * Copyright (C) 2004-2009 Alex Gorbatchev.
 *
 * Licensed under a GNU Lesser General Public License.
 * http://creativecommons.org/licenses/LGPL/2.1/
 *
 * SyntaxHighlighter is donationware. You are allowed to download, modify and distribute
 * the source code in accordance with LGPL 2.1 license, however if you want to use
 * SyntaxHighlighter on your site or include it in your product, you must donate.
 * http://alexgorbatchev.com/wiki/SyntaxHighlighter:Donate
 */
SyntaxHighlighter.brushes.Vb = function()
{
	var keywords =	'AddHandler AddressOf AndAlso Alias And Ansi As Assembly Auto ' +
					'Boolean ByRef Byte ByVal Call Case Catch CBool CByte CChar CDate ' +
					'CDec CDbl Char CInt Class CLng CObj Const CShort CSng CStr CType ' +
					'Date Decimal Declare Default Delegate Dim DirectCast Do Double Each ' +
					'Else ElseIf End Enum Erase Error Event Exit False Finally For Friend ' +
					'Function Get GetType GoSub GoTo Handles If Implements Imports In ' +
					'Inherits Integer Interface Is Let Lib Like Long Loop Me Mod Module ' +
					'MustInherit MustOverride MyBase MyClass Namespace New Next Not Nothing ' +
					'NotInheritable NotOverridable Object On Option Optional Or OrElse ' +
					'Overloads Overridable Overrides ParamArray Preserve Private Property ' +
					'Protected Public RaiseEvent ReadOnly ReDim REM RemoveHandler Resume ' +
					'Return Select Set Shadows Shared Short Single Static Step Stop String ' +
					'Structure Sub SyncLock Then Throw To True Try TypeOf Unicode Until ' +
					'Variant When While With WithEvents WriteOnly Xor';

	this.regexList = [
		{ regex: /'.*$/gm,										css: 'comments' },			// one line comments
		{ regex: SyntaxHighlighter.regexLib.doubleQuotedString,	css: 'string' },			// strings
		{ regex: /^\s*#.*$/gm,									css: 'preprocessor' },		// preprocessor tags like #region and #endregion
		{ regex: new RegExp(this.getKeywords(keywords), 'gm'),	css: 'keyword' }			// vb keyword
		];

	this.forHtmlScript(SyntaxHighlighter.regexLib.aspScriptTags);
};

SyntaxHighlighter.brushes.Vb.prototype	= new SyntaxHighlighter.Highlighter();
SyntaxHighlighter.brushes.Vb.aliases	= ['vb', 'vbnet'];
/**
 * SyntaxHighlighter
 * http://alexgorbatchev.com/
 *
 * @version
 * 2.0.287 (February 06 2009)
 *
 * @author
 * Alex Gorbatchev
 *
 * @copyright
 * Copyright (C) 2004-2009 Alex Gorbatchev.
 *
 * Licensed under a GNU Lesser General Public License.
 * http://creativecommons.org/licenses/LGPL/2.1/
 *
 * SyntaxHighlighter is donationware. You are allowed to download, modify and distribute
 * the source code in accordance with LGPL 2.1 license, however if you want to use
 * SyntaxHighlighter on your site or include it in your product, you must donate.
 * http://alexgorbatchev.com/wiki/SyntaxHighlighter:Donate
 */
SyntaxHighlighter.brushes.Xml = function()
{
	function process(match, regexInfo)
	{
		var constructor = SyntaxHighlighter.Match,
			code = match[0],
			tag = new XRegExp('(&lt;|<)[\\s\\/\\?]*(?<name>[:\\w-\\.]+)', 'xg').exec(code),
			result = []
			;

		if (match.attributes != null)
		{
			var attributes,
				regex = new XRegExp('(?<name> [\\w:\\-\\.]+)' +
									'\\s*=\\s*' +
									'(?<value> ".*?"|\'.*?\'|\\w+)',
									'xg');

			while ((attributes = regex.exec(code)) != null)
			{
				result.push(new constructor(attributes.name, match.index + attributes.index, 'color1'));
				result.push(new constructor(attributes.value, match.index + attributes.index + attributes[0].indexOf(attributes.value), 'string'));
			}
		}

		if (tag != null)
			result.push(
				new constructor(tag.name, match.index + tag[0].indexOf(tag.name), 'keyword')
			);

		return result;
	}

	this.regexList = [
		{ regex: new XRegExp('(\\&lt;|<)\\!\\[[\\w\\s]*?\\[(.|\\s)*?\\]\\](\\&gt;|>)', 'gm'),			css: 'color2' },	// <![ ... [ ... ]]>
		{ regex: new XRegExp('(\\&lt;|<)!--\\s*.*?\\s*--(\\&gt;|>)', 'gm'),								css: 'comments' },	// <!-- ... -->
		{ regex: new XRegExp('(&lt;|<)[\\s\\/\\?]*(\\w+)(?<attributes>.*?)[\\s\\/\\?]*(&gt;|>)', 'sg'), func: process }
	];
};

SyntaxHighlighter.brushes.Xml.prototype	= new SyntaxHighlighter.Highlighter();
SyntaxHighlighter.brushes.Xml.aliases	= ['xml', 'xhtml', 'xslt', 'html', 'xhtml'];



/**
 * Code Syntax Highlighter.
 * Version 1.5.2
 * Copyright (C) 2004-2008 Alex Gorbatchev
 * http://www.dreamprojections.com/syntaxhighlighter/
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, version 3 of the License.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
 /* Perl brush contributed by Marty Kube */
SyntaxHighlighter.brushes.Perl = function()
{
    var funcs =
	'abs accept alarm atan2 bind binmode chdir chmod chomp chop chown chr ' +
	'chroot close closedir connect cos crypt defined delete each endgrent ' +
	'endhostent endnetent endprotoent endpwent endservent eof exec exists ' +
	'exp fcntl fileno flock fork format formline getc getgrent getgrgid ' +
	'getgrnam gethostbyaddr gethostbyname gethostent getlogin getnetbyaddr ' +
	'getnetbyname getnetent getpeername getpgrp getppid getpriority ' +
	'getprotobyname getprotobynumber getprotoent getpwent getpwnam getpwuid ' +
	'getservbyname getservbyport getservent getsockname getsockopt glob ' +
	'gmtime grep hex index int ioctl join keys kill lc lcfirst length link ' +
	'listen localtime lock log lstat map mkdir msgctl msgget msgrcv msgsnd ' +
	'oct open opendir ord pack pipe pop pos print printf prototype push ' +
	'quotemeta rand read readdir readline readlink readpipe recv rename ' +
	'reset reverse rewinddir rindex rmdir scalar seek seekdir select semctl ' +
	'semget semop send setgrent sethostent setnetent setpgrp setpriority ' +
	'setprotoent setpwent setservent setsockopt shift shmctl shmget shmread ' +
	'shmwrite shutdown sin sleep socket socketpair sort splice split sprintf ' +
	'sqrt srand stat study substr symlink syscall sysopen sysread sysseek ' +
	'system syswrite tell telldir time times tr truncate uc ucfirst umask ' +
	'undef unlink unpack unshift utime values vec wait waitpid warn write';

    var keywords =
	'bless caller continue dbmclose dbmopen die do dump else elsif eval exit ' +
	'for foreach goto if import last local my next no our package redo ref ' +
	'require return sub tie tied unless untie until use wantarray while';

    this.regexList = [
	{ regex: new RegExp('#[^!].*$', 'gm'),                  css: 'comments'     }, //comments
	{ regex: new RegExp('^\\s*#!.*$', 'gm'),                css: 'preprocessor' }, //shebang
	{ regex: SyntaxHighlighter.regexLib.doubleQuotedString, css: 'string'       },
	{ regex: SyntaxHighlighter.regexLib.singleQuotedString, css: 'string'       },
	{ regex: new RegExp('(\\$|@|%)\\w+', 'g'),              css: 'variable'     },
	{ regex: new RegExp(this.getKeywords(funcs), 'gmi'),    css: 'functions'    },
	{ regex: new RegExp(this.getKeywords(keywords), 'gm'),  css: 'keyword'      }
    ];

    this.forHtmlScript(SyntaxHighlighter.regexLib.phpScriptTags);
}

SyntaxHighlighter.brushes.Perl.prototype  = new SyntaxHighlighter.Highlighter();
SyntaxHighlighter.brushes.Perl.aliases  = ['perl', 'Perl'];
