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
require_once($CFG->dirroot . '/payment/gateway/mpay24/thirdparty/mpay24/bootstrap.php');

use Mpay24\Mpay24;
use Mpay24\Mpay24Order;

class get_redirect_payments extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'component' => new external_value(PARAM_COMPONENT, 'The component name'),
            'paymentarea' => new external_value(PARAM_AREA, 'Payment area in the component'),
            'itemid' => new external_value(PARAM_INT, 'The item id in the context of the component area'),
            'tid' => new external_value(PARAM_TEXT, 'unique transaction id'),
        ]);
    }

    public static function execute($component, $paymentarea, $itemid, $tid) {
        GLOBAL $CFG;

        self::validate_parameters(self::execute_parameters(), [
            'component' => $component,
            'paymentarea' => $paymentarea,
            'itemid' => $itemid,
            'tid' => $tid,
        ]);
        $config = helper::get_gateway_configuration($component, $paymentarea, $itemid, 'mpay24');
        $payable = helper::get_payable($component, $paymentarea, $itemid);
        $surcharge = helper::get_gateway_surcharge('mpay24');
        $environment = $config['environment'];
        $price = helper::get_rounded_cost($payable->get_amount(), $payable->get_currency(), $surcharge);
        $root = $CFG->wwwroot;

        if ($environment == 'sandbox') {
            $mpay24 = new Mpay24($config['clientid'], $config['secret'], true);
        } else {
            $mpay24 = new Mpay24($config['clientid'], $config['secret'], false);
        }

        $redirecturl = $root . "/payment/gateway/mpay24/checkout.php?token=''&customer=" .
            $config['clientid'] . "&itemid=" . $itemid . "&component=" . $component .
            "&paymentarea=" . $paymentarea . "&tid=" . $tid . "&ischeckstatus=true";
        $mdxi = new Mpay24Order();
        $mdxi->Order->Tid = $tid;
        $mdxi->Order->Price = $price;
        $mdxi->Order->URL->Success      = $redirecturl;
        $mdxi->Order->URL->Error        = $redirecturl;
        $mdxi->Order->URL->Confirmation = $redirecturl;

        $url = $mpay24->paymentPage($mdxi)->getLocation();

        return [
            'url' => $url,
        ];

    }

    /**
     * Returns description of method result value.
     *
     * @return external_function_parameters
     */
    public static function execute_returns() {
        return new external_function_parameters([
            'url' => new external_value(PARAM_URL, 'Redirect URL.')
        ]);
    }
}
