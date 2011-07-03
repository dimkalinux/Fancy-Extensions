FORUM.fancy_tracker.functions = function () {
	return {
		updateTrackerStats: function () {
			var fancy_tracker_current_hash = $("#torrentCurrentHash").attr("rel"),
				fancy_tracker_current_owner_id = parseInt($("#torrentCurrentHash").attr("owner_id"), 10);

			if (!fancy_tracker_current_hash) {
				return;
			}

			$.getJSON(FORUM.fancy_tracker.env.misc_url,
				{ action: "fancy_tracker_get_stats", hash: fancy_tracker_current_hash, owner_id: fancy_tracker_current_owner_id },
				function (data, status) {
					if (parseInt(data.result, 10) === 1) {
						$("#torrentSeedNum").text(parseInt(data.s, 10));
						$("#torrentLeechNum").text(parseInt(data.l, 10));

						var ownerSeeding = parseInt(data.o, 10);
						// Owner seeding - hide nag
						if (ownerSeeding === 1) {
							$("#torrentOwnerNag:visible").slideUp(400);
						} else if (ownerSeeding === 2) {
							$("#torrentOwnerNag:hidden").slideDown(400);
						}
					}
				}
			);
		}
	}
}();

