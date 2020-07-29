const ajax = require('@codexteam/ajax');

const search = {

    results: [],

    init: function ({elementId, closerId, inputId, resultsId, placeholderId}) {

        const element = document.getElementById(elementId);

        if (element) {

            this.modal = element;

        }

        this.closerId = closerId;
        this.inputId = inputId;
        this.resultsId = resultsId;
        this.placeholderId = placeholderId;

    },

    show: function () {

        if (this.modal) {

            this.modal.removeAttribute('hidden');
            document.body.style.overflow = 'hidden';

        }

        const closer = document.getElementById(this.closerId);
        const input = document.getElementById(this.inputId);

        closer && closer.addEventListener('click', () => this.hide());
        input && input.addEventListener('keydown', (event) => this.search(event.target.value));

    },

    hide: function () {

        this.modal.setAttribute('hidden', true);
        document.body.style.overflow = 'auto';

    },

    search: function (value) {

        ajax.post({
            url: '/search',
            data: {
                word: value
            },
            type: ajax.contentType.FORM
        }).then(response => {

            const results = document.getElementById(this.resultsId);

            if (results) {

                results.removeAttribute('hidden');
                results.innerHTML = response.body['searchResults'];

            }

            document.getElementById(this.placeholderId).hidden = true;

        });

    }
};

module.exports = search;
