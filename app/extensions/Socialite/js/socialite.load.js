/*!
 * Heavily based on code by Tom Morton http://twmorton.com @tmort
 * GPL v2+
 */
jQuery(function($) {

	Socialite.process(); //processing each instance before we take any action. Makes sure pinterest loads individually and not all together.

	if ( $("body").hasClass("socialite-scroll") ) { //If set to 'scroll'
		var	articles = $('.social-buttons'), socialised = { }, win = $(window), updateArticles, onUpdate, updateTimeout;

		updateArticles = function()
		{
			// viewport bounds
			var	wT = win.scrollTop(),
				wL = win.scrollLeft(),
				wR = wL + win.width(),
				wB = wT + win.height();
			// check which articles are visible and socialise!
			for (var i = 0; i < articles.length; i++) {
				if (socialised[i]) {
					continue;
				}
				// article bounds
				var	art = $(articles[i]),
					aT = art.offset().top,
					aL = art.offset().left,
					aR = aL + art.width(),
					aB = aT + art.height();
				// vertial point inside viewport
				if ((aT >= wT && aT <= wB) || (aB >= wT && aB <= wB)) {
					// horizontal point inside viewport
					if ((aL >= wL && aL <= wR) || (aR >= wL && aR <= wR)) {
						socialised[i] = true;
						Socialite.load(articles[i]);
					}
				}
			}
		};

		onUpdate = function()
		{
			if (updateTimeout) {
				clearTimeout(updateTimeout);
			}
			updateTimeout = setTimeout(updateArticles, 100);
		};

		win.on('resize', onUpdate).on('scroll', onUpdate);

		setTimeout(updateArticles, 100);

	} else { //If not set to 'scroll', default to hover

		$('.social-buttons').parent().one('mouseenter', function(){
			Socialite.load($(this)[0]);
		});

	}

});//theend