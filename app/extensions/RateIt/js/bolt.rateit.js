$('.rateit').bind(
		'rated reset',
		function(e) {

			var ri = $(this);

			// If the user pressed reset, it will get value: 0
			var value = ri.rateit('value');
			var record_id = ri.data('bolt-record-id');
			var contenttype = ri.data('bolt-contenttype');

			$.ajax({
				url : '/ajax/RateIt',
				data : {
					record_id : record_id,
					contenttype : contenttype,
					value : value
				},
				type : 'POST',
				success : function(data) {
					if (value != 0) {
						// Disable voting
						ri.rateit('readonly', true);

						var retval = data.retval;
						var msg = data.msg;
						$('#rateit_response').html('<span>' + msg + '</span>');
						$('#rateit_response').show();
					}

				},
				error : function(jxhr, msg, err) {
					$('#rateit_response').html(
							'<span style=\"color:red\">AJAX error: (' + err
									+ ')</span>');
					$('#rateit_response').show();
				},
				dataType : 'json'
			});
		});

$(document).ready(function(){
    $('.rateit').each(function(){
        $(this).rateit('value', $(this).data('bolt-rateit-value'));
    });
});
