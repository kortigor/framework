"use strict";

window.cart = {
    counter: '#cart-items-number',
    addBtn: 'button.add-to-cart',
    form: {
        form: 'form#cartForm',
        removeBtn: 'button.remove-from-cart',
        saveBtn: 'button#cartSave',
        save: () => {
            let isChanged = false;
            const rows = $(cart.form.form).find('tr[data-id]');
            rows.each(function (ind, row) {
                const
                    $row = $(row),
                    id = $row.data('id'),
                    quantity = $row.find('input[name$="[quantity]"]').val(),
                    comment = $row.find('input[name$="[comment]"]').val();

                if (cart.items.setItemNumber(id, quantity)) {
                    isChanged = true;
                }

                if (cart.items.setItemComment(id, comment)) {
                    isChanged = true;
                }
            });

            return isChanged;
        }
    },
    items: {}
};

$(() => {
    cart.items = new cartProducts('cartItems', __cookieLifeTime);
});

// Widget button
$('html').on('click', cart.addBtn, function () {
    const
        id = $(this).data('id'),
        isAdded = $(this).hasClass('added');

    switch (isAdded) {
        case true:
            if (cart.items.removeItem(id)) {
                $(cart.counter).text(cart.items.numItemsText());
                $(this).html($(this).data('text-notadded')).removeClass('added');
                if ($(this).data('message-removed')) {
                    alertify.warning($(this).data('message-removed'));
                }
            }
            break;

        case false:
            if (cart.items.addItem(id)) {
                $(cart.counter).text(cart.items.numItemsText());
                $(this).html($(this).data('text-added')).addClass('added');
                if ($(this).data('message-added')) {
                    alertify.success($(this).data('message-added'));
                }
            }
    }
});

// Remove item from cart on cart page
$('html').on('click', cart.form.removeBtn, function () {
    const id = $(this).data('id');
    if (cart.items.removeItem(id)) {
        window.location.reload();
    }
});

// Save cart items on cart page
$('html').on('click', cart.form.saveBtn, function () {
    if (cart.form.save()) {
        window.location.reload();
    }
});

// Save cart items before form submit
$('html').on('submit', cart.form.form, function () {
    cart.form.save();
    return true;
});

class cartProducts {

    constructor(cookieName, lifeTime) {
        this.lifeTime = lifeTime;
        this.cookieName = cookieName;
    }

    clear() {
        Cookies.remove(this.cookieName);
    }

    getItems() {
        const raw = decodeURIComponent(Cookies.get(this.cookieName) || '');
        return raw.length ? JSON.parse(raw) : {};
    }

    numItems() {
        return Object.keys(this.getItems()).length;
    }

    numItemsText() {
        return this.numItems() ? this.numItems().toString() : '';
    }

    addItem(id) {
        const items = this.getItems();
        if (items.hasOwnProperty(id)) {
            return false;
        }
        items[id] = { quantity: 1, comment: '' };
        this.store(items);
        return true;
    }

    removeItem(id) {
        const items = this.getItems();
        if (!items.hasOwnProperty(id)) {
            return false;
        }
        delete items[id];
        this.store(items);
        return true;
    }

    increaseItem(id) {
        const items = this.getItems();
        if (!items.hasOwnProperty(id)) {
            return false;
        }
        items[id].quantity++;
        this.store(items);
        return true;
    }

    decreaseItem(id) {
        const items = this.getItems();
        if (!items.hasOwnProperty(id)) {
            return false;
        }

        items[id].quantity--;
        if (items[id].quantity === 0) {
            return false;
        }

        this.store(items);
        return true;
    }

    setItemNumber(id, number) {
        const items = this.getItems();
        if (!items.hasOwnProperty(id)) {
            return false;
        }
        number = parseInt(number, 10);
        if (items[id].quantity === number) {
            return false;
        }
        if (number <= 0) {
            this.removeItem(id);
            return true;
        }

        items[id].quantity = number;
        this.store(items);
        return true;
    }

    setItemComment(id, comment) {
        const items = this.getItems();
        if (!items.hasOwnProperty(id)) {
            return false;
        }
        if (items[id].comment === comment) {
            return false;
        }

        items[id].comment = comment;
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