jQuery.fn.view = function($val, $name = 'view'){
	return this.each(function(){
		$(this).attr($name, $val);
	});
};

function p($data, $title = false, $message = ''){
	if($title !== false)
		$message += $title+': ';
	$message += $data;
	console.log($message);
}

$(function(){

	$('input[name="Телефон"]').mask('+7 (999) 999-99-99');

	var $fbut = $('fbut-form');	// Главный блок формы.
	var $fbut_close = $('fbut-close');	// Кнопки закрытия формы.

	function init_fbut(){	// Запуск отображения формы
		$fbut.removeAttr('style');
		p('Форма запущена.', 'fBut');
	}

	function open_fbut($fbut_id, $fbut_t = false){	// Открыть форму и показать конкретную
		if($fbut_t != false){
			title_fbut($fbut_id, $fbut_t);
		}

		$fbut.view('show');
		$('fbut-wrap#'+$fbut_id).view('show');
		p('Форма открыта. [#'+$fbut_id+']', 'fBut');
	}

	function close_fbut(){	// Закрыть форму
		$fbut.view('hide');
		$('fbut-wrap[view="show"]').view('hide');
		p('Форма закрыта.', 'fBut');
	}

	function fbut_file($fbut_file){
		var $filename = $fbut_file.val().replace(/.*\\/, "");
		var $files = $fbut_file.prop('files');
		var $filesName = "";

		if($files.length == 0){
			$filesName = $fbut_file.siblings('span').attr("placeholder");
		}else{
			if($files.length > 1){
				p('Выбрано '+$files.length+' файла', 'fbut');
				$fbut_file.siblings('span').attr({'count':$files.length});
			}

			for(i = 0; i < $files.length; i++){
				if(i != 0) $filesName += ", ";
				$filesName += $files[i].name;		
			}	
		}
		
		$fbut_file.siblings('span').text($filesName);
	}

	function title_fbut(fbut_id, $fbut_t){
		$('fbut-wrap#'+$fbut_id+' input[name="Заголовок"]').val($fbut_t);
		p('Установлен заголовок "'+$fbut_t+'" [#'+$fbut_id+']', 'fbut');
	}

	$('fbut-file input[type="file"]').change(function(){
		fbut_file($(this));
	});


	init_fbut();	// Запускаем отображение формы.

	$fbut_close.click(function(){
		close_fbut();
	});

	$('[fbut]').click(function(){
		$fbut_id = $(this).attr('fbut');
		$fbut_t = $(this).attr('fbut_t');
		
		if($fbut_t == undefined)
			$fbut_t = false;

		open_fbut($fbut_id, $fbut_t);
		return false;
	});


	//////////////// ################ АЯКС ФОРМА ################ ////////////////

	function load_fbut(){
		$('fbut-load').toggleClass('send');
		p('Анимация отправки данных.', 'fBut');
	}

	function info_fbut($data){
		for($exit = 0; $exit != 1;){
			if($data.indexOf('\n') + 1)
				$data = $data.replace("\n", "<br>");
			else
				$exit = 1;
		}

		$("fbut-info > div").html($data);
		$('fbut-info').view('show');
		setTimeout(function(){
			$('fbut-info').view('hide');
		}, 5000);
	}

	$('fbut-form  > fbut-wrap > form[ajax]').on('submit', function(e){
		$('fbut-wrap[view="show"]').view('hide');
		e.preventDefault();
		var $that = $(this),
		formData = new FormData($that.get(0));
		$.ajax({
			url: '/mail/index.php',
			type: 'POST',
			dataType: 'text',
			contentType: false,
			processData: false,
			data: formData,
			beforeSend: function(){
				load_fbut();
			},
			error: function(req, text, error){
				console.error('Упс! Ошибочка: ' + text + ' | ' + error);
				console.log('error');
			},
			complete: function(){
				load_fbut();
				close_fbut();
			},
			success: function(json){
				if(json){
					info_fbut(json);
				}
			}
		});
		$(this).trigger("reset");
	});

	//////////////// ################ АЯКС ФОРМА ################ ////////////////
	
});