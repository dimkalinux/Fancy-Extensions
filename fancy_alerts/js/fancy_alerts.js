PUNBB.fancy_alerts = (function () {
	// GET NEW STATUS
	function get_status() {
		$.ajax({
			type: 'POST',
			url: PUNBB.env.fancy_alerts.autoupdate_url,
			dataType: 'json',
			global: false,
			success: function (data, textStatus, XMLHttpRequest) {
				if (data && data.error != undefined && parseInt(data.error, 10) === 0) {
					update_status(parseInt(data.t, 10), parseInt(data.p, 10));
				}
			},
		});
	}

	// UPDATE STATUS
	function update_status(num_topics, num_posts) {
		// UPDATE NUMS
		$('#fancy_alerts_topic_n').html(num_topics);
		$('#fancy_alerts_quotes_n').html(num_posts);

		// SHOW/HIDE TOPICS
		if (num_topics < 1) {
			$('#fancy_alerts_visit_topics').hide();
		} else {
			$('#fancy_alerts_visit_topics').show();
		}

		// SHOW/HIDE POSTS
		if (num_posts < 1) {
			$('#fancy_alerts_visit_posts').hide();
		} else {
			$('#fancy_alerts_visit_posts').show();
		}
	}

	return {
		init: function () {
			if (parseInt(PUNBB.env.fancy_alerts.autoupdate_time, 10) < 30) {
				return;
			}

			setInterval(get_status, (parseInt(PUNBB.env.fancy_alerts.autoupdate_time, 10) * 1000));
		}
	}
}());

// START HERE
if (typeof jQuery === 'function') {
	PUNBB.common.addDOMReadyEvent(PUNBB.fancy_alerts.init);
}

