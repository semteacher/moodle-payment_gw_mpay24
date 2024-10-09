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
 * This file contains the definition for the renderable classes for the booking instance
 *
 * @package   paygw_mpay24
 * @copyright 2021 Georg Maißer {@link http://www.wunderbyte.at}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_mpay24\output;

use renderer_base;
use renderable;
use stdClass;
use templatable;
use core_payment\helper;

/**
 * This class prepares data for displaying a booking option instance
 *
 * @package paygw_mpay24
 * @author Georg Maißer
 * @copyright 2022 Wunderbyte Gmbh <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class checkout implements renderable, templatable {

    /** @var array $checkoutid */
    public $data = [];

    /**
     * In the Constructor, we gather all the data we need ans store it in the data property.
     *
     * @param string $token
     * @param int $itemid
     * @param string $customer
     * @param string $component
     * @param string $paymentarea
     * @param string $tid
     * @param bool $ischeckstatus
     *
     */
    public function __construct(
        string $token,
        int $itemid,
        string $customer,
        string $component,
        string $paymentarea,
        string $tid,
        bool $ischeckstatus) {

        $this->data['token'] = $token;
        $this->data['itemid'] = $itemid;
        $this->data['customer'] = $customer;
        $this->data['component'] = $component;
        $this->data['paymentarea'] = $paymentarea;
        $this->data['tid'] = $tid;
        $this->data['ischeckstatus'] = $ischeckstatus;

    }

    /**
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {

        return $this->data;
    }
}
