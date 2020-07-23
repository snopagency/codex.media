const search = {

    modal: null,

    init: function ({id}) {

        const element = document.getElementById(id);

        if (element) {

            this.modal = element;

        }

    },

    show: function () {

        if (this.modal) {

            this.modal.removeAttribute('hidden');
            document.body.style.overflow = 'hidden';

        }

    },

    hide: function () {

        if (this.modal) {

            this.modal.setAttribute('hidden', true);
            document.body.style.overflow = 'auto';

        }

    }
};

module.exports = search;
