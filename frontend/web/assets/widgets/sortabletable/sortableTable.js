// Part of \common\widgets\SortableTable widget

class SortableTable {
    constructor(options) {
        this.options = options || {};
    }

    sort() {
        let self = this,
            sortopts = self.options.sortableOptions;

        sortopts.start = sortopts.start || function (e, ui) {
            // Количество ячеек в ряду таблицы
            let cells = $('thead > tr').find('th').length;
            // Изменяем ui.placeholder, т.к. пустой плейсхолдер <tr> не имеет размера
            ui.placeholder.html('<td colspan="' + cells + '"></td>');
            // Предотвращаем сжатие передвигаемого <tr>, которому присваивается 'position:absolute;'
            ui.helper.css('display', 'table');

            // $(this).find('.sort-placeholder td:nth-child(2)').addClass('hidden-td')
        };

        sortopts.beforeStop = sortopts.beforeStop || function (e, ui) {
            ui.helper.removeAttr('style').removeAttr('class');
        };

        sortopts.update = sortopts.update || function () {
            console.log('updating');
            // Используем uuid: https://stackoverflow.com/questions/60451206/how-to-seperate-uuids-for-jquery-sortable
            let tbl = $(this),
                order = tbl.sortable('serialize', self.serializeOptions());

            $.post(self.options.url, order)
                .fail(function (response) {
                    self.failHandler(response);
                });

            tbl.find(self.options.numberSelector).each(function (i) {
                $(this).text(i + 1);
            });
        };
        // console.log(sortopts);
        $(self.options.selector).sortable(sortopts);
    }

    serializeOptions() {
        return this.options.idFormat === 'uuid' ? { expression: /(.+)_(.+)/ } : {};
    }

    failHandler(response) {
        alertify.alert('Error', response.responseText);
    }


}