<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace core\hook;

/**
 * Class check_password_compromised
 *
 * @package    core
 * @copyright  2025 Dustin Huynh <dustinhuynh@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\core\attribute\tags('core')]
#[\core\attribute\label('Validate if a password has been compromised')]
class check_password_compromised {
    /** @var string Check error message. */
    private string $error;

    /**
     * Constructor for the hook.
     *
     * @param string $password The password to validate.
     */
    public function __construct(
        /** @var string The password to validate */
        public readonly string $password
    ) {
        $this->error = '';
    }

    /**
     * Add an error message.
     * @param string $error The error message.
     */
    public function add_error(string $error): void {
        $this->error = trim($error);
    }

    /**
     * Get an error message.
     *
     * @return string
     */
    public function get_error(): string {
        return $this->error;
    }
}
