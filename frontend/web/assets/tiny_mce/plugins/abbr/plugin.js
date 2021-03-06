tinymce.PluginManager.add('abbr', function (editor) {
    function showDialog() {

        var data = {},
            parentNode;

        parentNode = editor.dom.getParent(editor.selection.getNode(), 'abbr');

        if (parentNode) {
            data.title = parentNode.title;
            data.abbr = parentNode.innerText || parentNode.textContent;
        } else {
            data.title = '';
            data.abbr = editor.selection.getContent({ format: 'text' })
        }

        editor.windowManager.open({
            title: "Abbreviation",
            data: data,
            body: [
                {
                    name: 'abbr',
                    type: 'textbox',
                    label: 'Abbreviation'
                },
                {
                    name: 'title',
                    type: 'textbox',
                    label: 'Title'
                }
            ],
            onsubmit: function (e) {

                if (parentNode) {
                    editor.execCommand('mceRemoveNode', false, parentNode);
                }

                editor.selection.setNode(tinymce.activeEditor.dom.create('abbr', { title: e.data.title }, e.data.abbr));
            }
        });
    }

    editor.addMenuItem('abbr', {
        text: 'Abbreviation',
        context: 'insert',
        onclick: showDialog
    });

    editor.addButton('abbr', {
        text: '<abbr>',
        title: 'Insert/edit abbreviation',
        icon: false,
        onclick: showDialog
    });
});

// Russian translation
tinymce.addI18n('ru', {
    'Insert/edit abbreviation': 'Вставить/редактировать аббревиатуру',
    'Abbreviation': 'Аббревиатура'
});
