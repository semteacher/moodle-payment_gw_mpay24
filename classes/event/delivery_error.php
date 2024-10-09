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
 * The delivery_error event for mpay24
 *
 * @package    paygw_mpay24
 * @copyright  2023 Wunderbyte Gmbh <info@wunderbyte.at>
 * @author     Bernhard Fischer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace paygw_mpay24\event;

/**
 * The delivery_error event class.
 *
 * @package    paygw_mpay24
 * @copyright  2023 Wunderbyte Gmbh <info@wunderbyte.at>
 * @author     Bernhard Fischer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delivery_error extends \core\event\base {

    /**
     * Init
     *
     * @return void
     *
     */
    protected function init(): void {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    /**
     * Get name
     *
     * @return string
     *
     */
    public static function get_name(): string {
        return get_string('delivery_error', 'paygw_mpay24');
    }

    /**
     * Get_description
     *
     * @return string
     *
     */
    public function get_description(): string {
        return "The user with the id {$this->userid} has tried to pay, but an error occured on delivery: " .
            $this->other['message'];
    }

    /**
     * Get url
     *
     * @return \moodle_url
     *
     */
    public function get_url(): \moodle_url {
        return new \moodle_url('/payment/gateway/mpay24/checkout.php');
    }
}
