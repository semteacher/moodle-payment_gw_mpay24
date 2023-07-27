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
 * Upgrade script for paygw_mpay24.
 *
 * @package    paygw_mpay24
 * @copyright  2022 Wunderbyte Gmbh <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade the plugin.
 *
 * @param int $oldversion the version we are upgrading from
 * @return bool always true
 */
function xmldb_paygw_mpay24_upgrade(int $oldversion): bool {
    global $DB;
    $dbman = $DB->get_manager();

    // Automatically generated Moodle v3.11.0 release upgrade line.
    // Put any upgrade step following this.
    if ($oldversion < 2022080800) {

        // Define field paymentbrand to be added to paygw_mpay24.
        $table = new xmldb_table('paygw_mpay24');
        $field = new xmldb_field('paymentbrand', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'mpay24_orderid');

        // Conditionally launch add field paymentbrand.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Mpay24 savepoint reached.
        upgrade_plugin_savepoint(true, 2022080800, 'paygw', 'mpay24');
    }

    if ($oldversion < 2022080800) {

        // Define field pboriginal to be added to paygw_mpay24.
        $table = new xmldb_table('paygw_mpay24');
        $field = new xmldb_field('pboriginal', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'paymentbrand');

        // Conditionally launch add field pboriginal.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Mpay24 savepoint reached.
        upgrade_plugin_savepoint(true, 2022080800, 'paygw', 'mpay24');
    }

    if ($oldversion < 2023061200) {

        // Changing type of field tid on table paygw_mpay24_openorders to char.
        $table = new xmldb_table('paygw_mpay24_openorders');
        $field = new xmldb_field('tid', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'id');

        // Launch change of type for field tid.
        $dbman->change_field_type($table, $field);

        // Define field price to be added to paygw_mpay24.
        $field = new xmldb_field('price', XMLDB_TYPE_NUMBER, '10, 2', null, null, null, null, 'userid');

        // Conditionally launch add field price.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field status to be added to paygw_mpay24.
        $field = new xmldb_field('status', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0, 'price');

        // Conditionally launch add field price.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Mpay24 savepoint reached.
        upgrade_plugin_savepoint(true, 2023061200, 'paygw', 'mpay24');
    }

    if ($oldversion < 2023072700 ) {

        // Define field timemodified to be added to paygw_mpay24_openorders.
        $table = new xmldb_table('paygw_mpay24_openorders');
        $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'timecreated');

        // Conditionally launch add field timemodified.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Mpay24 savepoint reached.
        upgrade_plugin_savepoint(true, 2023072700 , 'paygw', 'mpay24');
    }

    if ($oldversion < 2023072700   ) {

        // Define field timecreated to be added to paygw_mpay24_openorders.
        $table = new xmldb_table('paygw_mpay24_openorders');
        $field = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'status');

        // Conditionally launch add field timecreated.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Mpay24 savepoint reached.
        upgrade_plugin_savepoint(true, 2023072700 , 'paygw', 'mpay24');
    }

    return true;
}
