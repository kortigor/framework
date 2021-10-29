"use strict";

const __cookieLifeTime = new Date(new Date().getTime() + 1000 * 60 * 60 * 24 * 365);

$(function () {
    // Bootstrap Tooltips and Popover initialization
    $('[data-toggle="popover"]').popover();
    $('[data-toggle="tooltip"]').tooltip();
});

// Disable default submit on press enter, except forms with .submit-default class
$('html').on('keypress', 'form:not(.submit-default) input', (e) => {
    return e.which !== 13;
});

// Get back on click element with class .get-back
$('html').on('click', '.get-back', () => {
    window.history.back();
});

/*
Show confirmation alert on click element with class .get-confirm
Confirmation message placed in element 'data-message' attribute
Depending of 'data-method' attribute after confirmation will perform:
 - GET (just follow link)
 - POST request (query parameters and values sends inside POST request body)
*/
$('html').on('click', 'a.get-confirm', function (e) {
    e.preventDefault();

    const message = nl2br($(this).data('confirm')),
        method = $(this).data('method') || 'GET',
        href = this.href;

    confirm = alertify.confirm().setting({
        title: '<i class="fas fa-exclamation-triangle"></i> Внимание',
        message: message,
        defaultFocus: 'cancel',
        oncancel: function () {
            alertify.error('Отменено');
        },
        onok: function (e) {
            if (e) {
                switch (method.toUpperCase()) {
                    case 'POST':
                        let url = new URL(href),
                            params = url.searchParams;

                        params.append(sys.getCsrfParam(), sys.getCsrfToken());
                        $.form(url.pathname, sys.paramsToObject(params), 'POST').submit();
                        break;
                    case 'GET':
                        window.location.href = href;
                        break;
                    default:
                        throw new Error('Invalid method');
                }
            }
        },
    });

    confirm.show();
});

// Init bs-custom-file-input
$('html').one('change', '.custom-file input[type=file]', () => {
    bsCustomFileInput.init();
});

// Copy to clipboard
$('html').on('click', '.copy-to-clipboard', function () {
    copyToClipboard(this);
    $(this).tooltip('hide').attr('data-original-title', 'Скопировано').tooltip('show');
});

$('html').on('mouseover', '.copy-to-clipboard', function () {
    $(this).tooltip('hide').attr('data-original-title', 'Копировать').tooltip('show');
});

$('html').on('click', 'a.get-file', function (e) {
    e.preventDefault();
    window.open($(this).attr('href'), '_blank').focus();
});