/*jslint browser: true, maxerr: 50, indent: 4 */
/*global PUNBB: true */

PUNBB.fancy_spoiler = (function () {
	'use strict';

	//
	function visible(el) {
		return (el.offsetWidth !== 0);
	}

	//
	function spoiler_switcher_onclick(switcher) {
		return function () {
			var switcher_link = switcher,
				spoiler_block = switcher_link.nextSibling;

			if (spoiler_block && PUNBB.common.hasClass(spoiler_block, 'fancy_spoiler')) {
				if (!visible(spoiler_block)) {
					spoiler_block.style.display = 'block';
					if (switcher_link.getAttribute('data-lang-close')) {
						switcher_link.innerHTML = '<strong>-</strong>&nbsp;'+switcher_link.getAttribute('data-lang-close');
					} else {
						switcher_link.innerHTML = '<strong>-</strong>&nbsp;'+switcher_link.innerHTML.substr(24);
					}
				} else {
					spoiler_block.style.display = 'none';
					if (switcher_link.getAttribute('data-lang-open')) {
						switcher_link.innerHTML = '<strong>+</strong>&nbsp;'+switcher_link.getAttribute('data-lang-open');
					} else {
						switcher_link.innerHTML = '<strong>+</strong>&nbsp;'+switcher_link.innerHTML.substr(24);
					}
				}

				return false;
			}

			return true;
		};
	}


	return {

		//
		init: function () {
			// Find all Spoiler Switchers links
			var spoiler_links = PUNBB.common.arrayOfMatched(function (el) {
				return (PUNBB.common.hasClass(el, 'fancy_spoiler_switcher_header'));
			}, document.getElementsByTagName('div'));


			// Bind click event
			PUNBB.common.map(function (el) {
				el.onclick = spoiler_switcher_onclick(el);
			}, spoiler_links);
		}
	};
}());

// One onload handler
PUNBB.common.addDOMReadyEvent(PUNBB.fancy_spoiler.init);
