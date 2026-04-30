
$(document).on('ready', function() {
	initClipboard();
	initDatePickers();
	
	// Стрелки навигации
	$('#scroll-box').on('click', '#scroll-up', function() {
		$('html, body').animate({ scrollTop: 0 }, 100);
		return false;
	});
	$('#scroll-box').on('click', '#scroll-down', function() {
		$('html, body').animate({ scrollTop: $(document).height() - $(window).height() }, 100);
		return false;
	});
	
	// Быстрый выбор периода
	$('#id_range').on('change', function() {
		 selectRange( $(this).val() );
	});	
	
	// נגן inline - לחיצה על כפתור השמעה
	$('body').on('click', '.img_play', function() {
		var $btn = $(this);
		var $row = $btn.closest('tr');
		var $existingPlayer = $row.next('.inline-player-row');

		// סגור נגן פתוח באותה שורה
		if ($existingPlayer.length > 0) {
			$existingPlayer.find('audio')[0].pause();
			$existingPlayer.remove();
			$btn.css('opacity', '');
			return;
		}

		// סגור כל נגן אחר פתוח
		$('.inline-player-row').each(function() {
			$(this).find('audio')[0].pause();
			$(this).prev('tr').find('.img_play').css('opacity', '');
			$(this).remove();
		});

		var link = $btn.data('link');
		var title = (playerTitle === true) ? $btn.data('title') : '';
		var colspan = $row.find('td').length;

		var playerHtml =
			'<tr class="inline-player-row">' +
				'<td colspan="' + colspan + '">' +
					'<div class="inline-player-container">' +
						(title ? '<div class="player-title">' + title + '</div>' : '') +
						'<audio class="inline-audio" controls>' +
							'<source src="' + link + '">' +
						'</audio>' +
						'<div class="player-speed">' +
							'<span>מהירות:</span>' +
							'<button class="speed-btn active" data-speed="1">x1</button>' +
							'<button class="speed-btn" data-speed="1.5">x1.5</button>' +
							'<button class="speed-btn" data-speed="2">x2</button>' +
						'</div>' +
					'</div>' +
				'</td>' +
			'</tr>';

		$row.after(playerHtml);
		$btn.css('opacity', '0.5');

		var audio = $row.next('.inline-player-row').find('audio')[0];
		if (playerAutoplay === true) {
			audio.play();
		}
	});

	// שליטה במהירות נגן
	$('body').on('click', '.speed-btn', function() {
		var $btn = $(this);
		var speed = parseFloat($btn.data('speed'));
		var audio = $btn.closest('.inline-player-container').find('audio')[0];
		audio.playbackRate = speed;
		$btn.closest('.player-speed').find('.speed-btn').removeClass('active');
		$btn.addClass('active');
	});
	
	// Проверка обновлений
	$('#check-updates').on('click', function() {
		$.ajax ({
			type: 'post',
			url: '',
			data: 'check_updates=1',
			dataType: 'json',
			timeout: 7000,
			cache: false,
			success: function(data) {
				if (data['success'] === true) {
					alert(data['message']);
				} else {
					alert('Не удалось проверить обновления!');
				}
			},
			error: function(xhr, str) {
				alert('Не удалось проверить обновления!');
			},			
		});
	});
	
	// Удалить запись
	$('body').on('click', '.img_delete', function() {
		$elem = $(this);
		if ( confirm('Вы действительно хотите удалить эту запись?') ) {
			$.ajax ({
				type: 'post',
				url: '',
				data: 'delete_record=' + $elem.data('path'),
				dataType: 'json',
				timeout: 7000,
				cache: false,
				success: function(data) {
					if (data['success'] === true) {
						$elem.closest('.record_col').hide().html('<div class="img_notfound"></div>').fadeIn('slow');
					} else {
						alert('Ошибка удаления: ' + data['message']);
					}
				},
				error: function(xhr, str) {
					alert('Не удалось удалить запись!');
				},			
			});
		}
	});
	
	// Отправка формы
	$('#form_submit').on('click', function() {
		$form = $('form');
		$config = $.query.get('config');
		$config = $config != false ? '?config=' + $config : '';
		$.ajax ({
			type: 'post',
			url: '' + $config,
			data: $form.serialize(),
			cache: false,
			beforeSend: function(data) {
				$('#form-loader').show();
			},
			success: function(data) {
				if ( data.trim() != '' ) {
					$('#content').html(data);
					injectQuickFilter();
				} else {
					$('#content').html('<div id="content-msg">אין נתונים עם הפרמטרים שנבחרו</div>');
				}
				showScroll();
			},
			error: function(xhr, str) {
				$('#content').html('<div id="content-msg">Не удалось получить данные</div>');
			},
			complete: function(data) {
				$('#form-loader').hide();
			}
		});
		return false;
	});
	
	// Показать спойлеры
	$('#show_spoilers span').on('click', function() {
		$('.spoilers').toggle('fast');
		showScroll();
		return false;
	});
	
	// Изменение комментария
	$('body').on('click', '.userfield', function() {
		if (userfieldEdit === true) {
			$elem = $(this);
			$text = $elem.text().trim();
			$elem.html(
				'<div class="userfield-box">' +
					'<input data-oldtext="'+$text+'" value="'+$text+'" type="text">' +
					'<br>' +
					'<button class="btn btn-default userfield-save">&#10003;</button>' + 
					'<button class="btn btn-default userfield-cancel">&#215;</button>' +
				'</div>'
			);
			$elem.removeClass('userfield');
		}
	});
	$('body').on('click', '.userfield-save', function() {
		$elem = $(this);
		$userfield = $elem.closest('td');
		$id = $elem.closest('tr').data('id');
		$text = $userfield.find('input').val().trim();
		$params = {
			'id' : $id,
			'text' : $text,
		};
		$.ajax ({
			type: 'post',
			url: '',
			data: 'edit_userfield=' + JSON.stringify($params),
			dataType: 'json',
			timeout: 7000,
			cache: false,
			success: function(data) {
				if (data['success'] === true) {
					$userfield.find('input').data('oldtext', $text);
					$userfield.text($text).addClass('userfield');
				} else {
					$elem.removeClass('btn-default').addClass('btn-danger');
				}
			},
			error: function(xhr, str) {
				$elem.removeClass('btn-default').addClass('btn-danger');
			},
		});
	});	
	$('body').on('click', '.userfield-cancel', function() {
		$userfield = $(this).closest('td');
		$text = $userfield.find('input').data('oldtext');
		$userfield.text($text).addClass('userfield');
	});
	
});

// Показать навигацию
function showScroll() {
	if (scrollShow === true) {
		var $bodyHeight = $('body').height(),
			$docHeight = $(window).height(),
			$scroll = $('#scroll-box');
		
		if ($bodyHeight > $docHeight) {
			$scroll.show('fast');
		} else {
			$scroll.hide('fast');
		}
	}
}

// Быстрый выбор периода
function selectRange(range) {
	var curr = new Date,
		first,
		last;
	
	switch (range) {
		case 'td':
			first = curr.getDate();
			last = new Date(curr.setDate(first));
			first = new Date(curr.setDate(first));
			break;
		case 'yd':
			first = curr.getDate()-1;
			last = new Date(curr.setDate(first));
			first = new Date(curr.setDate(first));
			break;
		case '3d':
			first = curr.getDate()-2;
			last = new Date(curr.setDate(first+2));
			first = new Date(curr.setDate(first));
			break;
		case 'tw':
			// В Воскресенье не работает. Выводится дата на след. неделю
			first = curr.getDate()-curr.getDay()+1;
			last = first + 6;
			first = new Date((new Date(curr)).setDate(first));
			last = new Date((new Date(curr)).setDate(last));
			break;
		case 'pw':
			first = curr.getDate()-7-curr.getDay()+1;
			last = new Date(curr.setDate(first+6));
			first = new Date(curr.setDate(first));
			break;
		case '3w':
			// В Воскресенье не работает. Выводится дата на след. неделю
			first = curr.getDate()-curr.getDay()+1;
			last = first + 6;
			last = new Date((new Date(curr)).setDate(last));
			first = curr.getDate()-14-curr.getDay()+1;
			first = new Date((new Date(curr)).setDate(first));
			break;
		case 'tm':
			first = new Date(curr.getFullYear(), curr.getMonth(), 1);
			last = new Date(curr.getFullYear(), curr.getMonth()+1, 0);
			break;
		case 'pm':
			first = new Date(curr.getFullYear(), curr.getMonth()-1, 1);
			last = new Date(curr.getFullYear(), curr.getMonth(), 0);
			break;	
		case '3m':
			first = new Date(curr.getFullYear(), curr.getMonth()-2, 1);
			last = new Date(curr.getFullYear(), curr.getMonth()+1, 0);
			break;
		default:
			first = curr.getDate();
			last = new Date(curr.getFullYear(), curr.getMonth()+1, 0);
			first = new Date(curr.setDate(first));
	}
	
	if (typeof(first) !== 'undefined') {
		$('#startmonth').prop('selectedIndex', first.getMonth());
		$('#startday').val(first.getDate());
		
		var $selector = $('#startyear');
		$selector.find('option').each(function(index, element) {
			if ( element.value == first.getFullYear() ) {
				$selector.prop('selectedIndex', index);
				return false;
			}
		});
		$('#endmonth').prop('selectedIndex', last.getMonth());
		$('#endday').val(last.getDate());
		
		$selector = $('#endyear');
		$selector.find('option').each(function(index, element) {
			if ( element.value == last.getFullYear() ) {
				$selector.prop('selectedIndex', index);
				return false;
			}
		});		
	}
}

// אתחול flatpickr במקום dropdowns של תאריכים
function initDatePickers() {
	if (typeof flatpickr === 'undefined') return;

	// הסתר את ה-dropdowns המקוריים
	$('#startday, #startmonth, #startyear, #starthour, #startmin').closest('td').find('select[name^="start"]').hide();
	$('#endday, #endmonth, #endyear, #endhour, #endmin').closest('td').find('select[name^="end"]').hide();

	// מצא את המיקום הנכון להכנסת הפיקרים
	var $startMin = $('#startmin');
	var $endMin = $('#endmin');

	// הוסף input לתאריך התחלה
	$startMin.after('<input type="text" id="fp-start" class="fp-date-input" placeholder="תאריך התחלה" readonly>');
	$endMin.after('<input type="text" id="fp-end" class="fp-date-input" placeholder="תאריך סיום" readonly>');

	flatpickr.localize(flatpickr.l10ns.he);

	var fpStart = flatpickr('#fp-start', {
		enableTime: true,
		dateFormat: 'd/m/Y H:i',
		time_24hr: true,
		defaultDate: new Date(
			$('#startyear').val(),
			parseInt($('#startmonth').val()) - 1,
			$('#startday').val(),
			$('#starthour').val(),
			$('#startmin').val()
		),
		onChange: function(selectedDates) {
			if (!selectedDates[0]) return;
			var d = selectedDates[0];
			$('#startday').val(d.getDate());
			$('#startmonth').prop('selectedIndex', d.getMonth());
			$('#startyear option[value="' + d.getFullYear() + '"]').prop('selected', true);
			$('#starthour').prop('selectedIndex', d.getHours());
			$('#startmin').prop('selectedIndex', d.getMinutes());
		}
	});

	var fpEnd = flatpickr('#fp-end', {
		enableTime: true,
		dateFormat: 'd/m/Y H:i',
		time_24hr: true,
		defaultDate: new Date(
			$('#endyear').val(),
			parseInt($('#endmonth').val()) - 1,
			$('#endday').val(),
			$('#endhour').val(),
			$('#endmin').val()
		),
		onChange: function(selectedDates) {
			if (!selectedDates[0]) return;
			var d = selectedDates[0];
			$('#endday').val(d.getDate());
			$('#endmonth').prop('selectedIndex', d.getMonth());
			$('#endyear option[value="' + d.getFullYear() + '"]').prop('selected', true);
			$('#endhour').prop('selectedIndex', d.getHours());
			$('#endmin').prop('selectedIndex', d.getMinutes());
		}
	});

	// עדכן flatpickr כשמשתמשים בבחירת טווח מהיר
	var origSelectRange = window.selectRange;
	window.selectRange = function(range) {
		origSelectRange(range);
		setTimeout(function() {
			var startDate = new Date(
				$('#startyear').val(),
				parseInt($('#startmonth').val()) - 1,
				$('#startday').val(),
				$('#starthour').val() || 0,
				$('#startmin').val() || 0
			);
			var endDate = new Date(
				$('#endyear').val(),
				parseInt($('#endmonth').val()) - 1,
				$('#endday').val(),
				$('#endhour').val() || 23,
				$('#endmin').val() || 59
			);
			fpStart.setDate(startDate, true);
			fpEnd.setDate(endDate, true);
		}, 50);
	};
}

// הזרקת תיבת חיפוש מהיר מעל טבלת התוצאות
function injectQuickFilter() {
	if ($('.quick-filter-box').length > 0) return;
	var $table = $('#content table.cdr').first();
	if ($table.length === 0) return;

	var filterHtml =
		'<div class="quick-filter-box">' +
			'<input type="text" id="quick-filter" placeholder="סינון מהיר בתוצאות..." autocomplete="off">' +
			'<span class="filter-count"></span>' +
		'</div>';

	$table.before(filterHtml);

	$('#quick-filter').on('input', function() {
		var term = $(this).val().toLowerCase().trim();
		var visible = 0;
		$('#content .record').each(function() {
			var $row = $(this);
			var match = term === '' || $row.text().toLowerCase().indexOf(term) !== -1;
			$row.toggle(match);
			if (!match) {
				$row.next('.inline-player-row').hide();
			} else {
				visible++;
			}
		});
		$('.filter-count').text(term ? 'מוצגות ' + visible + ' שורות' : '');
	});
}

// Копирование в буфер
function initClipboard() {
	var clipboard = new Clipboard('[data-clipboard]');
	clipboard.on('success', function (e) {
		html_pulse(e.trigger, '<span class="copied">Copied!</span>');
	});
}

// Изменить текст элемента на newtext и вернуть обратно с импульсом. elem - ID элемента
function html_pulse( elem, newtext ) {
	$oldtext = $(elem).html();
	$(elem).fadeTo(
		'normal',
		0.01,
		function() {
			$(elem)
			.html(newtext)
			.css('opacity', 1)
			.fadeTo(
				'slow', 1,
				function() {
					$(elem).fadeTo('normal', 0.01, function() { $(elem).html( $oldtext ).css('opacity', 1); });
				}
			);
		}
	);
}