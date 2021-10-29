const fbRu = {
	lang: 'ru',
	i18n: {
		'ru': {
			CLOSE: 'Закрыть',
			NEXT: 'Следующий',
			PREV: 'Предыдущий',
			ERROR: 'Не найдено.<br>Пожалуйста попробуйте позднее.',
			PLAY_START: 'Начать слайдшоу',
			PLAY_STOP: 'Остановить слайдшоу',
			FULL_SCREEN: 'Полный экран',
			THUMBS: 'Миниатюры',
			ZOOM: 'Увеличить'
		}
	}
},
	fbConfigSingle = {
		loop: false,
		infobar: true,
		hash: false,
		purpose: 'images',
		buttons: ['zoom', 'slideShow', 'fullScreen', 'close']
	},
	fbConfigProduction = {
		index: 0,
		backFocus: false,
		loop: false,
		infobar: true,
		hash: false,
		purpose: 'images',
		buttons: ['zoom', 'slideShow', 'fullScreen', 'thumbs', 'close'],
		thumbs: {
			autoStart: true,
		},
	};

$.extend($.fancybox.defaults, fbRu, fbConfigSingle);

$(() => {
	$('[data-fancybox="gallery"]').fancybox({
		buttons: ['zoom', 'slideShow', 'fullScreen', 'thumbs', 'close']
	});
});

$('html').on('click', '.fb-close', () => {
	$.fancybox.close();
});

function fbReplaceInCurrentCaption(instance, selector, replacement) {
	var $currentCaption = $.parseHTML(instance.current.opts.caption),
		$captionWrapper = $('<div/>');
	$($currentCaption).find(selector).html(replacement);
	$captionWrapper.html($currentCaption);
	var newCaption = $captionWrapper.html();
	instance.current.opts.$orig.data('caption', newCaption);
	instance.group[instance.current.index].caption = newCaption;
	instance.current.opts.caption = newCaption;
	instance.updateControls();
}