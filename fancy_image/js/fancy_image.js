/*global PUNBB: true */

PUNBB.fancy_image = (function () {
	"use strict";

	function fancy_image_run() {
		$(".fancyimagethumbs > img").each(function () {
			if ($(this).data("fancy") === true) {
				return;
			}

			$(this).data("fancy", true);

			var $image = $(this),
				image_preview_url = $image.attr("rel"),
				$image_link = $image.parents("a").eq(0),
				group_id = $image.parents(".post").find("h4").attr("id");

			if (!$image_link) {
				return;
			}

			if (image_preview_url) {
				$image_link.attr("href", image_preview_url);

				if (group_id) {
					$image_link.attr("rel", group_id);
				}

				$image_link.addClass("fancy_zoom");
				$image.removeAttr("rel");
			}

			$image_link.fancybox({
				"zoomSpeedIn": 100,
				"zoomSpeedOut": 100,
				"padding": 0,
				"cyclic": true,
				"overlayShow": false,
				"showCloseButton": true,
				"changeSpeed": 100,
				"changeFade": 100,
				"hideOnContentClick": true,
				"transitionIn": "none",
				"transitionOut": "none",
				"centerOnScroll": false,
				"titleFormat": formatTitle,
				"showNavArrows": true,
				"titleShow": true,
				"enableEscapeButton": true,
				"titlePosition": "over",
				"onStart": function (currentArray, currentIndex, currentOpts) {
					if (getOriginal_url(currentArray[currentIndex].href) === null) {
						currentOpts.titleShow = false;
					}
				},
				"onComplete": function (currentArray, currentIndex, currentOpts) {
					var original_url = getOriginal_url(currentArray[currentIndex].href),
						preview_size = 0;

					if (original_url !== null) {
						$("#fancy_title_link").attr("href", original_url);
					}

					if ($("#fancybox-img").length > 0) {
						preview_size = $("#fancybox-img").height() * $("#fancybox-img").width();
						if (preview_size < 300000) {
							$("#fancybox-title").hide();
						}
					}
				}
			});
		});
	}


	//
	function formatTitle(title, currentArray, currentIndex, currentOpts) {
		return "<div id=\"fancybox-title-over\"><a id=\"fancy_title_link\" href=\"\">"+PUNBB.env.fancy_image.lang_title+"</a></div>";
	}


	//
	function getOriginal_url(current_url) {
		var original_url = null;

		// check for pic.lg.ua
		if (current_url.indexOf("pic.lg.ua") > 0) {
			original_url = current_url.replace("pv_", "");
		} else if (current_url.indexOf("iteam.net.ua/uploads/") > 0) {
			original_url = current_url.replace("N_", "O_");
		} else if (current_url.indexOf("imageshack.us") > 0) {
			original_url = current_url.replace(".th.", ".");
		} else if (current_url.indexOf("radikal.ru") > 0) {
			original_url = null;
		} else if (current_url.indexOf("piccy.info") > 0) {
			original_url = null;
		} else if (current_url.indexOf("imagepost.ru") > 0) {
			original_url = null;
		} else if (current_url.indexOf("ipicture.ru") > 0) {
			original_url = null;
		} else if (current_url.indexOf("imageshost.ru") > 0) {
			original_url = null;
		}

		return original_url;
	}


	return {
		init: function () {
			$(document).bind("run.fancy_image", fancy_image_run).trigger("run.fancy_image");
		}
	};
}());

// One onload handler
$(document).ready(PUNBB.fancy_image.init);
