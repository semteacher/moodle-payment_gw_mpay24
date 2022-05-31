<?php
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
 * This class contains a list of webservice functions related to the mpay24 payment gateway.
 *
 * @package    paygw_mpay24
 * @copyright  2022 Wunderbyte Gmbh <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace paygw_mpay24\external;

use context_system;
use core_payment\helper;
use external_api;
use external_function_parameters;
use external_value;
use core_payment\helper as payment_helper;
use paygw_mpay24\event\payment_error;
use paygw_mpay24\event\payment_successful;
use paygw_mpay24\mpay24_helper;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

class transaction_complete extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'token' => new external_value(PARAM_RAW, 'Purchase token'),
            'itemid' => new external_value(PARAM_INT, 'The item id in the context of the component area'),
            'customer' => new external_value(PARAM_RAW, 'Customer Id'),
            'component' => new external_value(PARAM_COMPONENT, 'The component name'),
            'paymentarea' => new external_value(PARAM_AREA, 'Payment area in the component'),
            'tid' => new external_value(PARAM_TEXT, 'unique transaction id'),
            'ischeckstatus' => new external_value(PARAM_BOOL, 'If initial purchase or cron execution')
        ]);
    }

    /**
     * Perform what needs to be done when a transaction is reported to be complete.
     * This function does not take cost as a parameter as we cannot rely on any provided value.
     *
     * @param string $component Name of the component that the itemid belongs to
     * @param string $paymentarea
     * @param int $itemid An internal identifier that is used by the component
     * @param string $orderid mpay24 order ID
     * @return array
     */
    public static function execute($token, $itemid, $customer, $component, $paymentarea, $tid, $ischeckstatus): array {

        global $USER, $DB, $CFG, $DB;
        self::validate_parameters(self::execute_parameters(), [
            'token' => $token,
            'itemid' => $itemid,
            'customer' => $customer,
            'component' => $component,
            'paymentarea' => $paymentarea,
            'tid' => $tid,
            'ischeckstatus' => $ischeckstatus
        ]);

        $config = (object)helper::get_gateway_configuration($component, $paymentarea, $itemid, 'mpay24');
        $sandbox = $config->environment == 'sandbox';

        $payable = payment_helper::get_payable($component, $paymentarea, $itemid);
        $currency = $payable->get_currency();

        // Add surcharge if there is any.
        $surcharge = helper::get_gateway_surcharge('mpay24');
        $amount = helper::get_rounded_cost($payable->get_amount(), $currency, $surcharge);

        $successurl = helper::get_success_url($component, $paymentarea, $itemid)->__toString();
        $serverurl = $CFG->wwwroot;

        $mpay24helper = new mpay24_helper(
            $serverurl,
            $amount,
            $currency,
            $token,
            $sandbox,
            $customer,
            $config->clientid,
            $config->secret,
            $itemid,
            $tid
        );

        if ($ischeckstatus) {
            $orderdetails = $mpay24helper->check_payment_status($tid);
        } else {
            $orderdetails = $mpay24helper->pay_token($successurl);
        }

        $success = false;
        $message = '';

        if ($orderdetails) {
            if ($ischeckstatus) {
                $returnstatus = $orderdetails->getParam('STATUS');
            } else {
                $returnstatus = $orderdetails->getStatus();
            }
            $transactionid = $tid;
            $url = $serverurl;
            $status = '';
            // SANDBOX OR PROD.
            if ($sandbox == true) {
                if ($returnstatus == 'OK' || $returnstatus == 'BILLED' ) {
                    // Approved.
                    $status = 'success';
                    $message = get_string('payment_successful', 'paygw_mpay24');
                } else {
                    // Not Approved.
                    $status = false;
                }
            } else {
                if ($returnstatus == 'OK' || $returnstatus == 'BILLED' ) {
                    // Approved.
                    $status = 'success';
                    $message = get_string('payment_successful', 'paygw_mpay24');
                } else {
                    // Not Approved.
                    $status = false;
                }
            }

            if ($status == 'success') {
                $url = $successurl;
                $success = true;

                // Check if order is existing.

                $checkorder = $DB->get_record('paygw_mpay24_openorders', array('tid' => $transactionid, 'itemid' => $itemid,
                'userid' => intval($USER->id)));

                $existingdata = $DB->get_record('paygw_mpay24', array('mpay24_orderid' => $transactionid));

                if (!empty($existingdata) || empty($checkorder) ) {
                    // Purchase already stored.
                    $success = false;
                    $message = get_string('internalerror', 'paygw_mpay24');

                } else {

                    try {
                        $paymentid = payment_helper::save_payment(
                        $payable->get_account_id(),
                        $component,
                        $paymentarea,
                        $itemid,
                        (int) $USER->id,
                        $amount,
                        $currency,
                        'mpay24'
                        );

                        // Store mpay24 extra information.
                        $record = new \stdClass();
                        $record->paymentid = $paymentid;
                        $record->mpay24_orderid = $transactionid;

                        $DB->insert_record('paygw_mpay24', $record);
                        // We trigger the payment_successful event.
                        $context = context_system::instance();
                        $event = payment_successful::create(array('context' => $context, 'other' => [
                        'message' => $message,
                        'orderid' => $transactionid
                        ]));
                        $event->trigger();

                        // The order is delivered.
                        payment_helper::deliver_order($component, $paymentarea, $itemid, $paymentid, (int) $USER->id);

                        // Delete transaction after its been delivered.
                        $DB->delete_records('paygw_mpay24_openorders', array('tid' => $transactionid));
                    } catch (\Exception $e) {
                        debugging('Exception while trying to process payment: ' . $e->getMessage(), DEBUG_DEVELOPER);
                        $success = false;
                        $message = get_string('internalerror', 'paygw_mpay24');
                    }
                }
            } else {
                $success = false;
                $message = get_string('payment_error', 'paygw_mpay24');
            }
        } else {
            // Could not capture authorization!
            $success = false;
            $message = get_string('cannotfetchorderdatails', 'paygw_mpay24');
        }

        // If there is no success, we trigger this event.
        if (!$success) {
            // We trigger the payment_successful event.
            $context = context_system::instance();
            $event = payment_error::create(array('context' => $context, 'other' => [
                'message' => $message,
                'orderid' => $transactionid,
                'itemid' => $itemid,
                'component' => $component,
                'paymentarea' => $paymentarea]));
            $event->trigger();
        }

        return [
            'url' => $url,
            'success' => $success,
            'message' => $message,
        ];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_function_parameters
     */
    public static function execute_returns() {
        return new external_function_parameters([
            'url' => new external_value(PARAM_URL, 'Redirect URL.'),
            'success' => new external_value(PARAM_BOOL, 'Whether everything was successful or not.'),
            'message' => new external_value(PARAM_RAW, 'Message (usually the error message).'),
        ]);
    }
}
