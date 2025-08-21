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
 * Class check_password_policy
 *
 * @package    core
 * @copyright  2025 Dustin Huynh <dustinhuynh@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\core\attribute\tags('core')]
#[\core\attribute\label('Validate a password against the configured password policy')]
#[\core\attribute\hook\replaces_callbacks('check_password_policy')]
class check_password_policy {
    /** @var array List of errors. */
    private array $errors;

    /**
     * Constructor for the hook.
     *
     * @param string $password The password to validate.
     * @param ?\stdClass $user The loggedin user.
     * @param ?bool $compcheck The compromised check flag.
     */
    public function __construct(
        /** @var string The password to validate */
        public readonly string $password,
        /** @var ?\stdClass The loggedin user */
        public readonly ?\stdClass $user = null,
        /** @var ?bool The compromised check flag */
        public readonly ?bool $compcheck = true
    ) {
        $this->errors = [];
    }

    /**
     * Add an error messages.
     * @param string $error The error message.
     */
    public function add_errors(string $error): void {
        $this->errors[] = trim($error);
    }

    /**
     * Get the list of error messages.
     *
     * @return array
     */
    public function get_errors(): array {
        return $this->errors;
    }

    /**
     * Process legacy callbacks.
     */
    public function process_legacy_callbacks(): void {
        $pluginswithfunction = get_plugins_with_function(
            function: 'check_password_policy',
            migratedtohook: true,
        );
        foreach ($pluginswithfunction as $plugins) {
            foreach ($plugins as $function) {
                try {
                    $pluginerr = $function($this->password, $this->user);
                    if ($pluginerr) {
                        $this->errors[] = $pluginerr;
                    }
                } catch (\Throwable $e) {
                    debugging("Exception calling '$function'", DEBUG_DEVELOPER, $e->getTrace());
                }
            }
        }
    }
}
