$(function(){
	$('input[name="phone"]').mask("+7 (999) 999-99-99");
	$(".contact-form").submit(function () {
		$.post($(this).attr('action'), $(this).serialize(), function (data){
			alert(data.message);
		});
		return false;
	});
	$('.contact-form-file').submit(function () {
		$.ajax({
			url: $(this).attr('action'),
			type: 'POST',
			data: new FormData($(this)[0]),
			async: false,
			success: function (data) {
			    alert(data.message);
			},
			cache: true,
				contentType: false,
				processData: false
		});
		return false;
	});
});