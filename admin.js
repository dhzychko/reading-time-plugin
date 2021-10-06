jQuery( document ).ready(
	function() {
		jQuery( "#clear-previous-calculations-test-plugin" ).click(
			function( event ) {
				event.preventDefault();
				jQuery.ajax(
					{
						type: "POST",
						url: ajaxurl,
						data: { action: 'reset_reading_time' }
					}
				)
			}
		);
	}
);
