jQuery(document).ready(function($) {

	jQuery('#datetimepicker').datetimepicker();
	/*
	 * $(".tfdate").datepicker({ dateFormat : 'yy-mm-dd', showOn : 'button' });
	 */

	$('#username').autocomplete({
		source: function (request, response) {
	        //var matcher = new RegExp( $.ui.autocomplete.escapeRegex(request.term), "i" );
	        $.ajax({
	        	type : 'POST',
	        	url: 'admin-ajax.php',
	            dataType: "json",
	            data : 'action=get_users&name=' + request,
	            success: function (data) {
	                response(data);
	            }
	        });
	    },
	    minLength: 1
	});

	/*
	$('#username').autoComplete({
		source : function(name, response) {
			$.ajax({
				type : 'POST',
				dataType : 'json',
				url : 'admin-ajax.php',
				data : 'action=get_users&name=' + name,
				success : function(data) {
					response(data);
				}
			});
		}
	});
	*/

});