import { Utils } from './utils';

const CodexEditor = require('codex.editor');

/** Tools for the Editor */
const SimpleImage = require('codex.editor.simple-image');
const Header = require('codex.editor.header');
const List = require('codex.editor.list');
const Quote = require('codex.editor.quote');
const Delimiter = require('codex.editor.delimiter');
const CodeTool = require('codex.editor.code');

/** Inline Tools */
const Marker = require('codex.editor.marker');
const InlineCode = require('codex.editor.inline-code');

let editor;

class Writing {

    constructor() {

        editor = null;

    }

    /**
     * Init editor with given data
     *
     * @param {{blocks: Array}|Object} data
     */
    runEditor(data = {}) {

        const editorData = !Utils.isEmptyObject(data) ? data : this.defaultEditorData;

        editor = new CodexEditor({
            tools: {
                image: SimpleImage,

                header: {
                    class: Header,
                    config: {
                        placeholder: 'Title'
                    }
                },

                list: {
                    class: List,
                    inlineToolbar: true
                },

                quote: {
                    class: Quote,
                    inlineToolbar: true,
                    config: {
                        quotePlaceholder: 'Enter a quote',
                        captionPlaceholder: 'Quote\'s author'
                    }
                },

                delimiter: Delimiter,

                code: CodeTool,

                marker: Marker,

                inlineCode: InlineCode
            },

            data: editorData,
        });

    }

    submit(button) {

        const buttonLoadingClass = 'loading';

        /**
       * Prevent multiple submitting
       */
        if (button.classList.contains(buttonLoadingClass)) {

            return;

        }

        var title = document.forms.atlas.elements['title'],
            form;

        if (!title.value.trim()) {

            codex.editor.notifications.notification({
                type: 'warn',
                message: 'Заполните заголовок'
            });

            console.log('Заполните заголовок');


            return;

        }

        form = Writing.getForm();

        button.classList.add(buttonLoadingClass);

        editor.saver.save()
            .then(function (savedData) {

                form.elements['content'].value = JSON.stringify(savedData); // JSON.stringify({items: codex.editor.state.jsonOutput});

                codex.ajax.call({
                    url: '/p/save',
                    data: new FormData(form),
                    success: (response) => {

                        button.classList.remove(buttonLoadingClass);
                        Writing.submitResponse(response);

                    },
                    type: 'POST'
                });

            });

    };

    static getForm() {

        let atlasForm = document.forms.atlas;

        if (!atlasForm) return;

        return atlasForm;

    };

    /**
     * Response handler for page saving
     * @param response
     */
    static submitResponse(response) {

        response = JSON.parse(response);

        if (response.success) {

            window.location = response.redirect;
            return;

        }

        codex.editor.notifications.notification({
            type: 'warn',
            message: response.message
        });

    };

    /**
     * Submits writing form for opening in full-screan page without saving
     */
    openEditorFullscreen() {

        const form = Writing.getForm();

        editor.saver.save()
            .then(function (savedData) {

                form.elements['content'].value = JSON.stringify(savedData);

                form.submit();

            });

    };

    /**
     * Data to be placed to the empty editor on init
     *
     * @returns {{blocks: Array}}
     */
    get defaultEditorData() {

        return {
            blocks: [
                // {
                //     type: 'paragraph',
                //     data: {
                //         text: 'hello world'
                //     }
                // },
                // {
                //     type: 'header',
                //     data: {
                //         text: 'sample title',
                //         level: 2
                //     }
                // }
            ]
        };

    }

    /**
    * Show form and hide placeholder
    *
    * @param  {Element} targetClicked       placeholder with wrapper
    * @param  {String}  formId               remove 'hide' from this form by id
    * @param  {String}  hidePlaceholderClass add this class to placeholder
    */
    open(targetClicked, formId, hidePlaceholderClass) {

        const holder = targetClicked;

        document.getElementById(formId).classList.remove('hide');
        holder.classList.add(hidePlaceholderClass);
        holder.onclick = null;

    };

}

module.exports = new Writing();