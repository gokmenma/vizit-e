/*
	Reusable button loading helper for jQuery + Bootstrap spinners
	Usage:
		$("#saveBtn").startLoading({ text: "Kaydediliyor..." });
		// ...after work
		$("#saveBtn").stopLoading();

	Promise helper:
		ButtonLoading.withLoading($("#saveBtn"), () => axios.post(...))
			.then(...)
			.catch(...);
*/
(function (root, factory) {
	if (typeof module === "object" && module.exports) {
		module.exports = factory(require("jquery"));
	} else {
		root.ButtonLoading = factory(root.jQuery || root.$);
	}
})(typeof self !== "undefined" ? self : this, function ($) {
	if (!$) {
		// jQuery required
		return {
			withLoading: function () {
				throw new Error("ButtonLoading requires jQuery to be loaded.");
			},
		};
	}

	var DATA_KEYS = {
		loading: "bl.loading",
		html: "bl.origHtml",
		width: "bl.origWidth",
		ariaBusy: "bl.origAriaBusy",
		disabled: "bl.origDisabled",
		tabindex: "bl.origTabindex",
	};

	function isNativeDisableCapable($el) {
		return $el.is("button, input, select, textarea");
	}

	function buildSpinnerHtml(opts) {
		var spinnerSizeClass = opts.size === "sm" ? " spinner-border-sm" : "";
		var spinner =
			'<span class="' +
			(opts.spinnerClass || "spinner-border") +
			spinnerSizeClass +
			'" role="status" aria-hidden="true"></span>';

		if (opts.placement === "append") {
			return (opts.text || "") + " " + spinner;
		}
		if (opts.placement === "prepend") {
			return spinner + " " + (opts.text || "");
		}
		// replace
		return spinner + (opts.text ? " " + opts.text : "");
	}

	function startLoading($btn, options) {
		var opts = $.extend(
			{
				text: "Kaydediliyor...",
				size: "sm", // "sm" | "md"
				placement: "replace", // "replace" | "prepend" | "append"
				keepWidth: true,
				spinnerClass: "spinner-border",
			},
			options || {}
		);

		return $btn.each(function () {
			var $el = $(this);
			if ($el.data(DATA_KEYS.loading)) return; // already loading

			// Store original state
			$el.data(DATA_KEYS.loading, true);
			$el.data(DATA_KEYS.html, $el.html());

			if (opts.keepWidth) {
				var w = $el.outerWidth();
				$el.data(DATA_KEYS.width, w);
				// Avoid layout shift while content changes
				$el.css("width", w + "px");
			}

			// Accessibility
			$el.data(DATA_KEYS.ariaBusy, $el.attr("aria-busy"));
			$el.attr("aria-busy", "true");

			// Disable interactions
			if (isNativeDisableCapable($el)) {
				$el.data(DATA_KEYS.disabled, $el.prop("disabled"));
				$el.prop("disabled", true);
			} else {
				// For <a> or other elements
				$el.data(DATA_KEYS.tabindex, $el.attr("tabindex"));
				$el.addClass("disabled").attr({ "aria-disabled": "true", tabindex: -1 });
			}

			// Content update
			var spinnerHtml = buildSpinnerHtml(opts);
			if (opts.placement === "replace") {
				$el.html(spinnerHtml);
			} else if (opts.placement === "prepend") {
				$el.prepend(spinnerHtml);
			} else {
				$el.append(spinnerHtml);
			}
		});
	}

	function stopLoading($btn) {
		return $btn.each(function () {
			var $el = $(this);
			if (!$el.data(DATA_KEYS.loading)) return; // not loading

			// Restore content
			var origHtml = $el.data(DATA_KEYS.html);
			if (origHtml !== undefined) {
				$el.html(origHtml);
			}

			// Restore width
			var origWidth = $el.data(DATA_KEYS.width);
			if (origWidth !== undefined) {
				$el.css("width", "");
			}

			// Restore accessibility
			var origBusy = $el.data(DATA_KEYS.ariaBusy);
			if (origBusy === undefined || origBusy === null) {
				$el.removeAttr("aria-busy");
			} else {
				$el.attr("aria-busy", origBusy);
			}

			// Re-enable
			if (isNativeDisableCapable($el)) {
				var wasDisabled = $el.data(DATA_KEYS.disabled);
				$el.prop("disabled", !!wasDisabled);
			} else {
				var origTab = $el.data(DATA_KEYS.tabindex);
				$el.removeClass("disabled").removeAttr("aria-disabled");
				if (origTab === undefined || origTab === null) {
					$el.removeAttr("tabindex");
				} else {
					$el.attr("tabindex", origTab);
				}
			}

			// Cleanup flags
			$el.removeData(DATA_KEYS.loading)
				.removeData(DATA_KEYS.html)
				.removeData(DATA_KEYS.width)
				.removeData(DATA_KEYS.ariaBusy)
				.removeData(DATA_KEYS.disabled)
				.removeData(DATA_KEYS.tabindex);
		});
	}

	// jQuery plugin API
	$.fn.startLoading = function (options) {
		return startLoading(this, options);
	};
	$.fn.stopLoading = function () {
		return stopLoading(this);
	};

	// Convenience helper for promise-like tasks
	var ButtonLoading = {
		withLoading: function ($btn, taskOrPromise, options) {
			// taskOrPromise can be a function returning a promise or a promise itself
			startLoading($btn, options);
			var p;
			try {
				p = typeof taskOrPromise === "function" ? taskOrPromise() : taskOrPromise;
			} catch (e) {
				stopLoading($btn);
				return Promise.reject(e);
			}
			return Promise.resolve(p)
				.then(function (res) {
					stopLoading($btn);
					return res;
				})
				.catch(function (err) {
					stopLoading($btn);
					throw err;
				});
		},
		start: function ($btn, options) {
			return startLoading($btn, options);
		},
		stop: function ($btn) {
			return stopLoading($btn);
		},
	};

	return ButtonLoading;
});

// Optional: Auto-bind buttons with data-loading attribute
// Example: <button data-loading="true" data-loading-text="Kaydediliyor..."></button>
$(document).on("click", "[data-loading]", function () {
	console.log("Button clicked");
	var $btn = $(this);
	var text = $btn.attr("data-loading-text") || "Kaydediliyor...";
	var size = $btn.attr("data-loading-size") || "sm";
	var placement = $btn.attr("data-loading-placement") || "replace";
	$btn.startLoading({ text: text, size: size, placement: placement });
});

