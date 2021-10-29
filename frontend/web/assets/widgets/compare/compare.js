"use strict";

window.compare = {
    counter: '#compare-items-number',
    addBtn: 'button.add-to-compare',
    removeBtn: 'button.head-remove-from-compare'
};
var limitCompare = 2;

$(() => {
    compare.items = new compareProducts('compareItems', __cookieLifeTime, limitCompare);
});

// Widget button
$('html').on('click', compare.addBtn, function () {
    const
        id = $(this).data('id'),
        isAdded = $(this).hasClass('added');

    switch (isAdded) {
        case true:
            if (compare.items.removeItem(id)) {
                $(compare.counter).text(compare.items.numItemsText());
                $(this).html($(this).data('text-notadded')).removeClass('added');
                if ($(this).data('message-removed')) {
                    alertify.warning($(this).data('message-removed'));
                }
            }
            break;

        case false:
            if (compare.items.addItem(id)) {
                $(compare.counter).text(compare.items.numItemsText());
                $(this).html($(this).data('text-added')).addClass('added');
                if ($(this).data('message-added')) {
                    alertify.success($(this).data('message-added'));
                }
            }
    }
});

// Remove item from comparison on compare page
$('html').on('click', compare.removeBtn, function () {
    const id = $(this).data('id');
    if (compare.items.removeItem(id)) {
        window.location.reload();
    }
});

class compareProducts {
    constructor(cookieName, lifeTime, limit) {
        this.lifeTime = lifeTime;
        this.cookieName = cookieName;
        this.limit = limit;
    }

    clear() {
        Cookies.remove('compareItems');
    }

    getItems() {
        const raw = decodeURIComponent(Cookies.get('compareItems') || '');
        return raw.length ? JSON.parse(raw) : [];
    }

    numItems() {
        return this.getItems().length;
    }

    numItemsText() {
        return this.numItems() ? this.numItems().toString() : '';
    }

    addItem(id) {
        const items = this.getItems();
        if (items.indexOf(id) !== -1 || items.length >= this.limit) {
            return false;
        }

        items.push(id);
        this.store(items);
        return true;
    }

    removeItem(id) {
        const items = this.getItems(),
            pos = items.indexOf(id);

        if (pos === -1) {
            return false;
        }

        items.splice(pos, 1);
        this.store(items);
        return true;
    }

    store(items) {
        Cookies.set(this.cookieName, JSON.stringify(items), {
            expires: this.lifeTime,
            sameSite: 'Lax'
        });
    }
}