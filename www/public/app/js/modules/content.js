/**
* Operations with pages
*/
module.exports = (function () {

    /**
     * Toggles classname on passed blocks
     * @param {string} selector
     * @param {string} toggled classname
     */
    const toggle = function ( which, marker ) {

        const elements = document.querySelectorAll( which );

        for (let i = elements.length - 1; i >= 0; i--) {

            elements[i].classList.toggle( marker );

        }

    };

    /**
    * Module uses for toggle custom checkboxes
    * that has 'js-custom-checkbox' class and input[type="checkbox"] included
    * Example:
    * <span class="js-custom-checkbox">
    *    <input type="checkbox" name="" value="1"/>
    * </span>
    */
    const customCheckboxes = {

        /**
        * This class specifies checked custom-checkbox
        * You may set it on serverisde
        */
        CHECKED_CLASS : 'checked',

        init : function () {

            let checkboxes = document.getElementsByClassName('js-custom-checkbox');

            if (checkboxes.length) for (var i = checkboxes.length - 1; i >= 0; i--) {

                checkboxes[i].addEventListener('click', codex.content.customCheckboxes.clicked, false);

            }

        },

        clicked : function () {

            let checkbox  = this,
                input     = this.querySelector('input'),
                isChecked = this.classList.contains(codex.content.customCheckboxes.CHECKED_CLASS);

            checkbox.classList.toggle(codex.content.customCheckboxes.CHECKED_CLASS);

            if (isChecked) {

                input.removeAttribute('checked');

            } else {

                input.setAttribute('checked', 'checked');

            }

        }
    };

    const approvalButtons = {

        CLICKED_CLASS : 'click-again-to-approve',

        init : function () {

            let buttons = document.getElementsByClassName('js-approval-button');

            if (buttons.length) for (let i = buttons.length - 1; i >= 0; i--) {

                buttons[i].addEventListener('click', codex.content.approvalButtons.clicked, false);

            }

        },

        clicked : function (event) {

            let button    = this,
                isClicked = this.classList.contains(codex.content.approvalButtons.CLICKED_CLASS);

            if (!isClicked) {

                /* временное решение, пока нет всплывающего окна подверждения важных действий */
                button.classList.add(codex.content.approvalButtons.CLICKED_CLASS);

                event.preventDefault();

            }

        }
    };

    return {
        customCheckboxes,
        approvalButtons,
        toggle
    };

}());
