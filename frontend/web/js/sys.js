window.sys = (function ($) {
    var pub = {

        /**
         * @return string|undefined the CSRF parameter name. Undefined is returned if CSRF validation is not enabled.
         */
        getCsrfParam: function () {
            return $('meta[name=csrf-param]').attr('content');
        },

        /**
         * @return string|undefined the CSRF token. Undefined is returned if CSRF validation is not enabled.
         */
        getCsrfToken: function () {
            return $('meta[name=csrf-token]').attr('content');
        },

        /**
         * Sets the CSRF token in the meta elements.
         * This method is provided so that you can update the CSRF token with the latest one you obtain from the server.
         * @param name the CSRF token name
         * @param value the CSRF token value
         */
        setCsrfToken: function (name, value) {
            $('meta[name=csrf-param]').attr('content', name);
            $('meta[name=csrf-token]').attr('content', value);
        },

        /**
         * Updates all form CSRF input fields with the latest CSRF token.
         * This method is provided to avoid cached forms containing outdated CSRF tokens.
         */
        refreshCsrfToken: function () {
            var token = pub.getCsrfToken();
            if (token) {
                $('form input[name="' + pub.getCsrfParam() + '"]').val(token);
            }
        },

        /**
         * Displays a confirmation dialog.
         * The default implementation simply displays a js confirmation dialog.
         * You may override this by setting `sys.confirm`.
         * @param message the confirmation message.
         * @param ok a callback to be called when the user confirms the message
         * @param cancel a callback to be called when the user cancels the confirmation
         */
        confirm: function (message, ok, cancel) {
            if (window.confirm(message)) {
                !ok || ok();
            } else {
                !cancel || cancel();
            }
        },

        getQueryParams: function (url) {
            var pos = url.indexOf('?');
            if (pos < 0) {
                return {};
            }

            var pairs = $.grep(url.substring(pos + 1).split('#')[0].split('&'), function (value) {
                return value !== '';
            });
            var params = {};

            for (var i = 0, len = pairs.length; i < len; i++) {
                var pair = pairs[i].split('=');
                var name = decodeURIComponent(pair[0].replace(/\+/g, '%20'));
                var value = decodeURIComponent(pair[1].replace(/\+/g, '%20'));
                if (!name.length) {
                    continue;
                }
                if (params[name] === undefined) {
                    params[name] = value || '';
                } else {
                    if (!$.isArray(params[name])) {
                        params[name] = [params[name]];
                    }
                    params[name].push(value || '');
                }
            }

            return params;
        },

        /**
         * Convert iterator to object
         * 
         * @param Iterator entries i.e URLSearchParams
         * 
         * @return object
         */
        paramsToObject(entries) {
            let result = {}
            for (let entry of entries) { // each 'entry' is a [key, value]
                const [key, value] = entry;
                result[key] = value;
            }
            return result;
        },

        init: function () {
            initCsrfHandler();
            initRedirectHandler();
        },

        /**
         * Returns the URL of the current page without params and trailing slash. Separated and made public for testing.
         * @returns {string}
         */
        getBaseCurrentUrl: function () {
            return window.location.protocol + '//' + window.location.host;
        },

        /**
         * Returns the URL of the current page. Used for testing, you can always call `window.location.href` manually
         * instead.
         * @returns {string}
         */
        getCurrentUrl: function () {
            return window.location.href;
        }
    };

    function initCsrfHandler() {
        // automatically send CSRF token for all AJAX requests
        $.ajaxPrefilter(function (options, originalOptions, xhr) {
            if (!options.crossDomain && pub.getCsrfParam()) {
                xhr.setRequestHeader('X-CSRF-Token', pub.getCsrfToken());
            }
        });
        pub.refreshCsrfToken();
    }

    function initRedirectHandler() {
        // handle AJAX redirection
        $(document).ajaxComplete(function (event, xhr) {
            var url = xhr && xhr.getResponseHeader('X-Redirect');
            if (url) {
                window.location.assign(url);
            }
        });
    }

    return pub;
})(window.jQuery);

window.jQuery(function () {
    window.sys.init(window.sys);
});

jQuery(function ($) {
    $.extend({

        /**
         * Ability to create form on the fly and submit via GET or POST non-ajax request
         * 
         * $.form('/info', { userIds: [1, 2, 3, 4] }, 'GET');
         * <form action="/info" method="GET">
         *  <input type="hidden" name="userIds[]" value="1" />
         *  <input type="hidden" name="userIds[]" value="2" />
         *  <input type="hidden" name="userIds[]" value="3" />
         *  <input type="hidden" name="userIds[]" value="4" />
         * </form>
         * 
         * $.form('/profile', { sender: { first: 'John', last: 'Smith', postIds: null },
         *                 receiver: { first: 'Foo', last: 'Bar', postIds: [1, 2] } });
         * 
         * <form action="/profile" method="POST">
         *  <input type="hidden" name="sender[first]" value="John">
         *  <input type="hidden" name="sender[last]" value="Smith">
         *  <input type="hidden" name="receiver[first]" value="John">
         *  <input type="hidden" name="receiver[last]" value="Smith">
         *  <input type="hidden" name="receiver[postIds][]" value="1">
         *  <input type="hidden" name="receiver[postIds][]" value="2">
         * </form>
         * 
         * With jQuery's .submit() method you can create and submit a form with a simple expression:
         * $.form('http://example.com/search', { q: '[ajax]' }, 'GET').submit();
         * 
         * @param string url Form action url
         * @param object data Form data
         * @param string method Form method
         * 
         * @see https://stackoverflow.com/questions/4583703/jquery-post-request-not-ajax
         */
        form: function (url, data, method) {
            method = method || 'POST';
            data = data || {};

            let form = $('<form>').attr({
                method: method,
                action: url
            }).css({
                display: 'none'
            });

            let addData = (name, value) => {
                if ($.isArray(value)) {
                    for (let i = 0; i < value.length; i++) {
                        let value = value[i];
                        addData(name + '[]', value);
                    }
                } else if (typeof value === 'object') {
                    for (let key in value) {
                        if (value.hasOwnProperty(key)) {
                            addData(name + '[' + key + ']', value[key]);
                        }
                    }
                } else if (value !== null) {
                    form.append($('<input>').attr({
                        type: 'hidden',
                        name: String(name),
                        value: String(value)
                    }));
                }
            };

            for (let key in data) {
                if (data.hasOwnProperty(key)) {
                    addData(key, data[key]);
                }
            }

            return form.appendTo('body');
        },

        /**
         * Find max width of selected elements and set found width to all selected elements
         * 
         * @param string[]|string selector one jQuery element selector or array of selectors
         * 
         * @return void
         */
        sameMaxWidth: function (selector) {
            if (typeof selector === 'string' || selector instanceof String) {
                var data = [selector];
            } else if (Array.isArray(selector)) {
                var data = selector;
            } else {
                console.log('Invalid selector parameter');
                return false;
            }

            data.forEach(function (sel) {
                $(sel).css({
                    'width': '',
                    'min-width': ''
                });

                let width = Math.max.apply(Math, $(sel).map(function () {
                    return Math.ceil($(this).outerWidth());
                }).get());

                $(sel).css({
                    'width': width,
                    'min-width': width
                });
            });
        },
    });
});

/**
* Get random element.
* Usage:
* $('.class').random().click();
*/
jQuery.fn.random = function () {
    var randomIndex = Math.floor(Math.random() * this.length);
    return jQuery(this[randomIndex]);
};

/**
 * Convert "\n\n" into <br> html tag
 * 
 * @param string str
 * 
 * @return string
 */
function nl2br(str) {
    if (typeof str === 'undefined' || str === null) {
        return '';
    }
    return (str + '').replace(/(\r\n|\n\r|\r|\n)/g, '<br>' + '$1');
}

/**
 * Get query param value
 * 
 * @param mixed url
 * @param string key
 * 
 * @return string|null If the given search parameter is found; otherwise, null
 */
function getQueryValue(url, key) {
    const
        newUrl = new URL(url),
        params = new URLSearchParams(newUrl.search);
    return params.get(key);
}

/**
 * Add or update url query string parameter
 * 
 * @param string url
 * @param string key
 * @param string value
 * 
 * @return string Updated url
 */
function updateQueryValue(url, key, value) {
    const
        newUrl = new URL(url),
        params = new URLSearchParams(newUrl.search);
    params.set(key, value);
    newUrl.search = params;
    return newUrl.toString();
}

/**
 * Remove parameter from url query string
 * 
 * @param string url
 * @param string key
 * 
 * @return string Updated url
 */
function removeQueryValue(url, key) {
    const
        newUrl = new URL(url),
        params = new URLSearchParams(newUrl.search);
    params.delete(key);
    newUrl.search = params;
    return newUrl.toString();
}

/**
 * Copy element value to clipboard
 * 
 * @param mixed element
 * 
 * @return void
 */
function copyToClipboard(element) {
    const $temp = $('<input>', {
        style: 'position:absolute;left:-9999px;top:0',
    });
    $('body').append($temp);
    $temp.val($(element).html()).select();
    try {
        document.execCommand('copy');
    } catch (e) {
        console.log('Error copying');
    }
    $temp.remove();
}

/**
 * Pluralize for numbers
 * 
 * @param int number
 * @param array endings Spelling variants corresponding to the numbers 1, 2 and 5
 * Example: `['рубль', 'рубля', 'рублей']`
 * 
 * @return string
 */
function numberPlural(number, endings) {
    var ending, i;
    number = number % 100;
    if (number >= 11 && number <= 19) {
        ending = endings[2];
    }
    else {
        i = number % 10;
        switch (i) {
            case (1): ending = endings[0]; break;
            case (2):
            case (3):
            case (4): ending = endings[1]; break;
            default: ending = endings[2];
        }
    }
    return ending;
}

/**
 * Plural of money
 * 
 * @param mixed sum
 * 
 * @return mixed
 */
function moneyPlural(sum) {
    return numberFormat(sum, 0, ',', ' ') + ' ' + numberPlural(sum, ['рубль', 'рубля', 'рублей']);
}

/**
 * Format number
 * 
 * @param mixed number
 * @param int decimals
 * @param string dec_point
 * @param string thousands_sep
 * 
 * @return string
 */
function numberFormat(number, decimals, dec_point, thousands_sep) // Format a number with grouped thousands
{
    var i, j, kw, kd, km;

    // input sanitation & defaults
    if (isNaN(decimals = Math.abs(decimals))) {
        decimals = 2;
    }
    if (dec_point == undefined) {
        dec_point = ",";
    }
    if (thousands_sep == undefined) {
        thousands_sep = ".";
    }

    i = parseInt(number = (+number || 0).toFixed(decimals)) + "";

    if ((j = i.length) > 3) {
        j = j % 3;
    } else {
        j = 0;
    }

    km = (j ? i.substr(0, j) + thousands_sep : "");
    kw = i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thousands_sep);
    kd = (decimals ? dec_point + Math.abs(number - i).toFixed(decimals).replace(/-/, 0).slice(2) : "");

    return km + kw + kd;
}

/**
 * Convert to integer value.
 * 
 * @param mixed val
 * 
 * @return int
 */
function intVal(val) {
    var out = parseInt(val, 10);
    return typeof out === 'number' ? out : false;
}

/**
 * Convert string to float value
 * 
 * @param string str
 * 
 * @return float
 */
function str2float(str) {
    str = str.toString().replace(/,/g, '.').replace(/[^0-9.]/g, '').replace(/\.$/g, '.00');
    return parseFloat(str, 10);
}