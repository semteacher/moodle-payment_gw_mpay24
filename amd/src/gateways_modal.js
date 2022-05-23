/* eslint-disable max-len */
/* eslint-disable no-unused-vars */
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This module is responsible for mpay24 content in the gateways modal.
 *
 * @module     paygw_mpay24/gateway_modal
 * @copyright  2022 Wunderbyte Gmbh <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as Repository from './repository';
import Templates from 'core/templates';
import Truncate from 'core/truncate';
import ModalFactory from 'core/modal_factory';
import ModalEvents from 'core/modal_events';
import {
    get_string as getString
} from 'core/str';

/**
 * Creates and shows a modal that contains a placeholder.
 *
 * @returns {Promise<Modal>}
 */
const showModalWithPlaceholder = async() => {
    const modal = await ModalFactory.create({
        body: await Templates.render('paygw_mpay24/mpay24_button_placeholder', {})
    });
    modal.show();
    return modal;
};

/**
 * Process the payment.
 *
 * @param {string} component Name of the component that the itemId belongs to
 * @param {string} paymentArea The area of the component that the itemId belongs to
 * @param {number} itemId An internal identifier that is used by the component
 * @param {string} description Description of the payment
 * @returns {Promise<string>}
 */
export const process = (component, paymentArea, itemId, description) => {
    return Promise.all([
            showModalWithPlaceholder(),
            Repository.getConfigForJs(component, paymentArea, itemId),
        ])
        .then(([modal, mpay24Config]) => {
            return Promise.all([
                modal,
                mpay24Config,
            ]);
        })
        .then(([modal, mpay24Config]) => {

            const language = mpay24Config.language;
            const iframe = document.createElement('iframe');
            iframe.setAttribute('src', mpay24Config.tokenizerlocation);
            iframe.setAttribute('style', "width: 100%");
            const buttons = document.createElement('form');
            let buttontext = '';
            if (language == 'de') {
                buttontext = "Mit Kreditkarte zahlen";
            } else {
                buttontext = "Pay with creditcard";
            }
            buttons.innerHTML = `<form id="paymentform"  method="POST">
            <input name="token" type="hidden" value="${mpay24Config.token}" />
            <button id="paybutton" name="type" value="TOKEN" type="submit" disabled="true">${buttontext}</button>
            </form>`;
            const container = document.createElement('div');
            container.insertAdjacentElement('afterbegin', iframe);
            container.insertAdjacentElement('beforeend', buttons);

            const script = document.createElement('script');
            script.type = 'text/javascript';
            const code = `
            window.addEventListener("message", checkValid, false);
            function checkValid(form) {
            var data = JSON.parse(form.data);
            if (data.valid === "true") {
                document.getElementById("paybutton").disabled=false;
            }
            }
            `;
            script.appendChild(document.createTextNode(code));

            document.head.appendChild(script);

            buttons.addEventListener('submit', (event) => {

                const formData = new FormData(event.target);
                const entries = [...formData.entries()];
                const values = [...formData.values()];
                event.preventDefault();
                const url = `${mpay24Config.rooturl}/payment/gateway/mpay24/checkout.php?token=${values[0]}&customer=${mpay24Config.clientid}&itemid=${itemId}&component=${component}&paymentarea=${paymentArea}&tid=${mpay24Config.tid}`;
                window.location.href = url;

            });

            modal.setBody(container);

            return '';
        }).then(x => {
            const promise = new Promise(resolve => {
                window.addEventListener('onbeforeunload', (e) => {
                    promise.resolve();
                });
            });
            return promise;
        });
};
