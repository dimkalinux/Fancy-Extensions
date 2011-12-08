/*global PUNBB: true */

if (typeof PUNBB === undefined || !PUNBB) {
	var PUNBB = {};
}

PUNBB.fancy_tracker = (function () {
	'use strict';

	function update_stats() {
		var fancy_tracker_current_hash = $('#torrentCurrentHash').attr('rel'),
			fancy_tracker_current_owner_id = parseInt($('#torrentCurrentHash').attr('owner_id'), 10);

		if (!fancy_tracker_current_hash) {
			return;
		}

		$.getJSON(PUNBB.env.fancy_tracker.misc_url,
			{ action: 'fancy_tracker_get_stats', hash: fancy_tracker_current_hash, owner_id: fancy_tracker_current_owner_id },
			function (data, status) {
				if (data && data.result && parseInt(data.result, 10) === 1) {
					$('#torrentSeedNum').text(parseInt(data.s, 10));
					$('#torrentLeechNum').text(parseInt(data.l, 10));

					// Owner seeding - hide nag
					if (parseInt(data.o, 10) === 1) {
						$('#torrentOwnerNag:visible').slideUp(400);
					} else if (parseInt(data.o, 10) === 2) {
						$('#torrentOwnerNag:hidden').slideDown(400);
					}
				}
			}
		);
	}

	return {
		init_viewtopic: function () {
			PUNBB.env.fancy_tracker.update_interval = parseInt(PUNBB.env.fancy_tracker.update_interval, 10) || 60000;
			if (PUNBB.env.fancy_tracker.update_interval < 10 * 1000) {
				PUNBB.env.fancy_tracker.update_interval = 60 * 1000;
			}

			setTimeout(update_stats, 3000);
			setInterval(update_stats, PUNBB.env.fancy_tracker.update_interval);
		}
	};
}());


//
if (PUNBB.env.page === 'viewtopic') {
	PUNBB.common.addDOMReadyEvent(PUNBB.fancy_tracker.init_viewtopic);
}
