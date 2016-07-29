(function ($) {

	$(function () {

		if( window.CPTP_Settings_Pointer ) {
			$("#menu-settings .wp-has-submenu").pointer({
				content: CPTP_Settings_Pointer.content,
				position: {"edge": "left", "align": "center"},
				close: function () {
					$.post('admin-ajax.php', {
						action: 'dismiss-wp-pointer',
						pointer: CPTP_Settings_Pointer.name
					})

				}
			}).pointer("open");
		}

	})

})(jQuery);
