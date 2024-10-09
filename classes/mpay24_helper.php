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
 * Contains helper class to work with mpay24 REST API.
 *
 * @package paygw_mpay24
 * @author Georg Maißer
 * @copyright 2022 Wunderbyte Gmbh <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_mpay24;

use curl;
use core_payment\helper;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot . '/payment/gateway/mpay24/thirdparty/mpay24/bootstrap.php');

use Mpay24\Mpay24;
use Mpay24\Mpay24Order;

class mpay24_helper {

    /**
     * @var string The base API URL
     */
    private $baseurl;

    /**
     * @var string
     */
    private $currency;

    /**
     * @var float
     */
    private $amount;

    /**
     * @var string The oauth bearer token
     */
    private $token;

    /**
     * @var boolean sandbox
     */
    private $sandbox;

    /**
     * @var string customer
     */
    private $customer;

    /**
     * @var string Client ID
     */
    private $clientid;

    /**
     * @var string Mpay24 Secret
     */
    private $secret;

    /**
     * @var string Item ID
     */
    private $itemid;

    /**
     * @var string Unique transaction id
     */
    private $tid;

    /**
     * helper constructor.
     *
     * @param string $baseurl
     * @param float $amount
     * @param string $currency
     * @param string $token
     * @param bool $sandbox
     * @param string $customer
     * @param string $clientid
     * @param string $secret
     * @param int $itemid
     * @param string $tid
     *
     */
    public function __construct(string $baseurl, float $amount, string $currency, string $token, bool $sandbox, string $customer,
    string $clientid, string $secret, int $itemid, string $tid) {
        $this->currency = $currency;
        $this->baseurl = $baseurl;
        $this->token = $token;
        $this->sandbox = $sandbox;
        $this->amount = $amount;
        $this->customer = $customer;
        $this->clientid = $clientid;
        $this->secret = $secret;
        $this->itemid = $itemid;
        $this->tid = $tid;
    }

    public function pay_token($succesurl) {
        global $CFG;

        if ($this->sandbox) {
            $mpay24 = new Mpay24($this->clientid, $this->secret, true);
        } else {
            $mpay24 = new Mpay24($this->clientid, $this->secret, false);
        }

        $payment = [
            "amount" => $this->amount,
            "currency" => $this->currency,
            "token" => $this->token,
        ];

        $additional = [
            "successURL"   => $succesurl,
        ];

        $result = $mpay24->payment("TOKEN", $this->tid, $payment, $additional);

        return $result;
    }

    public function check_payment_status($tid) {
        if ($this->sandbox) {
            $mpay24 = new Mpay24($this->clientid, $this->secret, true);
        } else {
            $mpay24 = new Mpay24($this->clientid, $this->secret, false);
        }

        $status = $mpay24->paymentStatusByTID($tid);
        return $status;
    }
}
