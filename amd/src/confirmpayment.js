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
 * mpay24 repository module to encapsulate all of the AJAX requests that can be sent for mpay24.
 *
 * @module     paygw_mpay24/confirmpayment
 * @copyright  2022 Georg Maißer <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import ModalFactory from 'core/modal_factory';
import ModalEvents from 'core/modal_events';

export const init = (token, itemid, customer, component, paymentarea, tid, ischeckstatus) => {


    Ajax.call([{
        methodname: "paygw_mpay24_create_transaction_complete",
        args: {
            token, itemid, customer, component, paymentarea, tid, ischeckstatus
        },
        done: function(data) {


            require(['jquery'], function($) {
                require(['core/str'], function(str) {

                    var strings = [
                        {
                            key: 'success',
                            component: 'paygw_mpay24'
                        },
                        {
                            key: 'error',
                            component: 'paygw_mpay24'
                        },
                        {
                            key: 'proceed',
                            component: 'paygw_mpay24',
                        }
                    ];

                    var localStrings = str.get_strings(strings);
                    $.when(localStrings).done(function(localizedEditStrings) {

                        ModalFactory.create({
                            type: ModalFactory.types.CANCEL,
                            title: data.success == true ? localizedEditStrings[0] : localizedEditStrings[1],
                            body: data.message,
                            buttons: {
                                cancel: localizedEditStrings[2],
                            },
                        })
                        .then(function(modal) {
                            var root = modal.getRoot();
                            root.on(ModalEvents.cancel, function() {
                                location.href = data.url;
                            });
                            modal.show();
                            return true;
                        }).catch(e => {
                            // eslint-disable-next-line no-console
                            console.log(e);
                        });

                    });
                });
            });


        },
        fail: function() {
            // eslint-disable-next-line no-console
            // console.log("ex:" + ex);
        },
    }]);

};