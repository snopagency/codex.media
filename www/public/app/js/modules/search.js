const ajax = require('@codexteam/ajax');

const search = {

    init: function ({elementId, closerId, inputId}) {

        const element = document.getElementById(elementId);

        if (element) {

            this.modal = element;

        }

        this.closerId = closerId;
        this.inputId = inputId;

    },

    show: function () {

        if (this.modal) {

            this.modal.removeAttribute('hidden');
            document.body.style.overflow = 'hidden';

        }

        const closer = document.getElementById(this.closerId);
        const input = document.getElementById(this.inputId);

        closer && closer.addEventListener('click', () => this.hide());
        input && input.addEventListener('change', () => this.search());

    },

    hide: function () {

        this.modal.setAttribute('hidden', true);
        document.body.style.overflow = 'auto';

    },

    search: function () {

        ajax.post({
            url: '/p/save',
            data: this.form
        }).then((response) => {

            console.log(response);

        });

    }
};

module.exports = search;
