window.activeForm = {};

class ActiveForm {

    constructor(options) {
        this.options = options || {};
        this.validator = {};
        this.loaderId = 'activeform-ajax-submit-loader';
    }

    ajaxSubmit(form, options) {
        const $form = $(form);
        this.$submitButton = $form.find('button[type="submit"]');

        options = options || {};
        options.data = new FormData($form[0]);
        options.url = options.url || $form.prop('action');
        options.type = options.type || $form.prop('method');
        options.beforeSend = options.beforeSend || (() => { this.showLoader(); });
        options.complete = options.complete || (() => { this.removeLoader(); });

        return $.ajax(options);
    }

    showLoader() {
        const $spinner = $($.parseHTML(this.options.ajaxSubmitSpinner));
        $spinner.attr({ 'id': this.loaderId });
        $spinner.insertAfter(this.$submitButton);
        this.$submitButton.attr({ 'disabled': true });
    }

    removeLoader() {
        $('#' + this.loaderId).remove();
        this.$submitButton.attr({ 'disabled': false });
    }

    ajaxValidateSubmit(form, options) {
        const submitHandler = options.submitHandler;
        delete options.submitHandler;
        this.ajaxValidate($(form), options).done(() => {
            submitHandler(form[0]);
        });
    }

    ajaxValidate($form, options) {
        const dfd = new $.Deferred();
        this.doAjaxValidate($form, options).done((data) => {
            Object.keys(data).length ? dfd.reject() : dfd.resolve();
        });

        return dfd.promise();
    }

    doAjaxValidate($form, options) {
        this.$submitButton = $form.find('button[type="submit"]');

        options = options || {};
        options.data = new FormData($form[0]);
        options.dataType = 'json';
        options.url = options.url || $form.prop('action');
        options.type = options.type || $form.prop('method');
        options.success = options.success || ((response) => { this.handleErrors($form, response); });
        options.beforeSend = options.beforeSend || (() => { this.showLoader(); });
        options.complete = options.complete || (() => { this.removeLoader(); });

        return $.ajax(options);
    }

    handleErrors($form, errors) {
        if (typeof errors !== 'object') {
            console.log('Errors argument should be object type.');
            return false;
        }
        this.clearErrors($form);
        this.updateValid($form, errors);
        this.updateErrors($form, errors);
        if (this.options.scrollToError || false) {
            this.scrollToError();
        }
    }

    updateErrors($form, errors) {
        $.each(errors, function (id, message) {
            message = Array.isArray(message) ? message[0] : message;
            const $input = $($form.find('.form-control#' + id + ', #' + id + ' .custom-control-input'));
            if ($input.hasClass('custom-control-input')) { // collection of .custom-control-input'
                $input.last().nextAll('.invalid-feedback').first().text(message);
            } else {
                $input.next('.invalid-feedback').text(message);
            }
            $input.addClass('is-invalid');
        });
    }

    updateValid($form, errors) {
        $.each($form.find('.form-control, .custom-control-input'), function (ind, item) {
            if ($.inArray(item.id, Object.keys(errors)) === -1) {
                $(item).addClass('is-valid');
            }
        });
    }

    clearErrors($form) {
        $.each($form.find('.form-control, .custom-control-input'), function (ind, item) {
            let $item = $(item);
            $item.next('.invalid-feedback').text('');
            $item.removeClass('is-invalid');
        });
    }

    // Animate to first form invalid element, if present
    scrollToError() {
        const offset = this.options.scrollToErrorOffset || 0,
            $firstError = $('.is-invalid').first();
        if (!$firstError.length) {
            return false;
        }
        const position = $firstError.offset().top - parseInt(offset);
        $('html').animate({ scrollTop: position }, 500);
    }
}