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

use core_payment\helper;
use context_system;
use DateTime;
use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use paygw_mpay24\task\check_status;
use stdClass;
use paygw_mpay24\event\payment_added;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/payment/gateway/mpay24/thirdparty/mpay24/bootstrap.php');

use Mpay24\Mpay24;
use Mpay24\Mpay24Order;

/**
 * Get config for js class.
 */
class get_config_for_js extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'component' => new external_value(PARAM_COMPONENT, 'Component'),
            'paymentarea' => new external_value(PARAM_AREA, 'Payment area in the component'),
            'itemid' => new external_value(PARAM_INT, 'An identifier for payment area in the component'),
        ]);
    }


    /**
     * Returns the config values required by the mpay24 JavaScript SDK.
     *
     * @param string $component
     * @param string $paymentarea
     * @param int $itemid
     * @return string[]
     */
    public static function execute(string $component, string $paymentarea, int $itemid): array {
        global $CFG, $USER, $SESSION, $DB;

        $systemcontext = context_system::instance();
        self::validate_context($systemcontext);

        self::validate_parameters(self::execute_parameters(), [
            'component' => $component,
            'paymentarea' => $paymentarea,
            'itemid' => $itemid,
        ]);

        $config = helper::get_gateway_configuration($component, $paymentarea, $itemid, 'mpay24');
        $payable = helper::get_payable($component, $paymentarea, $itemid);
        $surcharge = helper::get_gateway_surcharge('mpay24');

        $language = $USER->lang;
        $secret = $config['secret'];
        $entityid = $config['clientid'];
        $root = $CFG->wwwroot;
        $environment = $config['environment'];

        $string = bin2hex(openssl_random_pseudo_bytes(8));
        $now = new DateTime();
        $timestamp = $now->getTimestamp();
        $amount = number_format($payable->get_amount(), 2);

        $merchanttransactionid = $string . $timestamp;
        $record = new \stdClass();
        $record->tid = $merchanttransactionid;
        $record->itemid = $itemid;
        $record->userid = intval($USER->id);
        $record->price = $amount;
        $record->status = 0;
        $record->timecreated = time();
        $record->timemodified = time();

        // Check for duplicate.
        if (!$existingrecord = $DB->get_record('paygw_mpay24_openorders', ['itemid' => $itemid, 'userid' => $USER->id])) {
            $id = $DB->insert_record('paygw_mpay24_openorders', $record);

            // We trigger the payment_added event.
            $context = context_system::instance();
            $event = payment_added::create([
                'context' => $context,
                'userid' => $USER->id,
                'objectid' => $id,
                'other' => [
                    'orderid' => $merchanttransactionid,
                ],
            ]);
            $event->trigger();
        } else {
            // If we already have an entry with the exact same itemid and userid, we actually will use the same merchant id.
            // This will prevent a successful payment and we thus avoid duplicate entries in DB.
            $merchanttransactionid = $existingrecord->tid;
        }

        if ($environment == 'sandbox') {
            $mpay24 = new Mpay24($entityid, $secret, true);
        } else {
            $mpay24 = new Mpay24($entityid, $secret, false);
        }

        $tokenizer = $mpay24->token("CC");
        $tokenizerlocation = $tokenizer->getLocation();
        $token = urlencode($tokenizer->getToken());

        // Create task to check status.
        // We have to check 1 minute before item gets deleted from cache.
        $userid = $USER->id;
        $now = time();
        if (get_config('local_shopping_cart', 'expirationtime') && get_config('local_shopping_cart', 'expirationtime') > 2) {
            $expirationminutes = get_config('local_shopping_cart', 'expirationtime') - 1;
            $nextruntime = strtotime('+' . $expirationminutes . ' min', $now);
        } else {
            // Fallback.
            $nextruntime = strtotime('+30 min', $now);
        }

        $taskdata = new stdClass();
        $taskdata->token = '';
        $taskdata->itemid = $itemid;
        $taskdata->customer = $config['clientid'];
        $taskdata->component = $component;
        $taskdata->paymentarea = $paymentarea;
        $taskdata->tid = $merchanttransactionid;
        $taskdata->ischeckstatus = true;
        $taskdata->resourcepath = '';
        $taskdata->userid = $USER->id;

        $checkstatustask = new check_status();
        $checkstatustask->set_userid($userid);
        $checkstatustask->set_next_run_time($nextruntime);
        $checkstatustask->set_custom_data($taskdata);
        \core\task\manager::reschedule_or_queue_adhoc_task($checkstatustask);

        return [
            'clientid' => $config['clientid'],
            'brandname' => $config['brandname'],
            'cost' => helper::get_rounded_cost($payable->get_amount(), $payable->get_currency(), $surcharge),
            'currency' => $payable->get_currency(),
            'rooturl' => $root,
            'environment' => $environment,
            'language' => $language,
            'token' => $token,
            'tokenizerlocation' => $tokenizerlocation,
            'tid' => $merchanttransactionid,

        ];
    }


    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'clientid' => new external_value(PARAM_TEXT, 'mpay24 client ID'),
            'brandname' => new external_value(PARAM_TEXT, 'Brand name'),
            'cost' => new external_value(PARAM_FLOAT, 'Cost with gateway surcharge'),
            'currency' => new external_value(PARAM_TEXT, 'Currency'),
            'rooturl' => new external_value(PARAM_TEXT, 'Moodle Root URI'),
            'environment' => new external_value(PARAM_TEXT, 'Prod or Sandbox'),
            'language' => new external_value(PARAM_TEXT, 'language'),
            'token' => new external_value(PARAM_TEXT, 'token'),
            'tokenizerlocation' => new external_value(PARAM_TEXT, 'tokenizerlocation'),
            'tid' => new external_value(PARAM_TEXT, 'unique transaction id'),
        ]);
    }
}
