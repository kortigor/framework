$.validator.setDefaults({
	errorElement: 'div',
	errorClass: 'is-invalid',
	validClass: 'is-valid',
	errorPlacement: function (error, element) {
		// Add the 'invalid-feedback' class to the error element
		error.addClass('invalid-feedback');
		// Check if element in input-group
		let $elementInputGroup = $(element).parent('.input-group');
		if ($elementInputGroup.length) {
			error.insertAfter($elementInputGroup.get(0));
		} else if (element.prop('type') === 'checkbox' || element.prop('type') === 'radio') {
			error.insertAfter(element.next('label'));
		} else {
			error.insertAfter(element);
		}
	},
	highlight: function (element, errorClass, validClass) {
		$(element).addClass(errorClass).removeClass(validClass);
		// In case of form placed in Bootstrap Nav Tabs, switch to tab contains invalid element
		let $errTabs = $(element).closest('.tab-pane');
		if ($errTabs.length) {
			$('a[href="#' + $errTabs.get(0).id + '"]').tab('show');
		}
	},
	unhighlight: function (element, errorClass, validClass) {
		$(element).addClass(validClass).removeClass(errorClass);
		let $next = $(element).get(0).nextSibling;
		if ($next && $next.length) {
			$next.remove();
		}
	}
});