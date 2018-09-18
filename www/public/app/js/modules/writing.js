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

const save = Utils.debounce(() => {

    editor.saver.save()
        .then((savedData) => {

            /**
       * Send POST request
       */
            return new Promise((resolve, reject) => {

                let xmlhttp = window.XMLHttpRequest ? new window.XMLHttpRequest() : new window.ActiveXObject('Microsoft.XMLHTTP');

                xmlhttp.open('POST', '/');
                xmlhttp.setRequestHeader('Content-Type', 'application/json;charset=UTF-8');
                xmlhttp.onreadystatechange = () => {

                    if (xmlhttp.readyState === 4) {

                        const response = xmlhttp.responseText;

                        if (xmlhttp.status === 200) {

                            resolve(response);

                        } else {

                            reject(response);

                        }

                    }

                };
                xmlhttp.send(JSON.stringify(savedData));

            });

        })
        .then(response => {

            response = JSON.parse(response);

            console.log('Server response: ', response);

            window.history.pushState({}, '', response.id);

        })
        .catch(err => {

            console.log('Error was occurred while saving: ', err);

        });

}, 1000);

class Writing {

    constructor() {

        editor = null;

    }

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

            onReady: Writing.onReady
        });

    }

    get defaultEditorData() {

        return {
            blocks: [
                // {
                //     type: 'header',
                //     data: {
                //         text: '',
                //         level: 2
                //     }
                // }
            ]
        };

    }

}

module.exports = new Writing();