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
 * User compromise cleared event.
 *
 * @package    core
 * @copyright  2025 Jaydn Cunningham <jaydncunningham@catalyst-au.net>
 * @copyright  2025 Dustin Huynh <dustinhuynh@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace core\event;

/**
 * Event emitted when user's compromised password is cleared.
 *
 * @property-read array $other {
 *      Extra information about event.
 *
 *      - string reason: (optional) update reason.
 * }
 */
class user_compromise_cleared extends base {
    /**
     * Initialise required event data properties.
     */
    protected function init() {
        $this->context = \context_system::instance();
        $this->data['objecttable'] = 'user';
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Returns localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventusercompromisecleared');
    }

    /**
     * Returns non-localised event description with id's for admin use only.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->objectid' has been cleared of compromise.";
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/user/view.php', ['id' => $this->objectid]);
    }

    /**
     * Used for maping events on restore
     * @return array
     */
    public static function get_objectid_mapping() {
        return ['db' => 'user', 'restore' => 'user'];
    }

    /**
     * Used for mapping events on restore
     *
     * @return bool
     */
    public static function get_other_mapping() {
        // Nothing to map.
        return false;
    }
}
