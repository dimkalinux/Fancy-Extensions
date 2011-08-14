/*global PUNBB: true */

PUNBB.fancy_addthis = (function () {
	'use strict';

	// AddThis global config
	var addthis_config = {
		ui_click: true,
		ui_delay: 75,
		ui_508_compliant: false
	};

	return {
		init: function () {
			var i, el, cl, share_config, link_list;

			if (document.getElementsByClassName) {
				link_list = document.getElementsByClassName('fancy-addthis-link');
			} else {
				link_list = PUNBB.common.arrayOfMatched(function (x) {
					return PUNBB.common.hasClass(x, 'fancy-addthis-link');
				}, document.getElementsByTagName("span"));
			}

			for (i = 0, cl = link_list.length; i < cl; i += 1) {
				el = link_list[i];
				share_config = {
					url: el.getAttribute('data-share-url')
				};

				addthis.button(el, addthis_config, share_config);
				PUNBB.common.addClass(el, 'js_link');
			}
		}
	};
}());

// One onload handler
PUNBB.common.addDOMReadyEvent(PUNBB.fancy_addthis.init);
