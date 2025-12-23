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
 * Mark a password compromised user
 *
 * @package    core
 * @subpackage cli
 * @copyright  2025 Dustin Huynh <dustin@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/moodlelib.php');

$usage = <<<EOL
Marks (or clears) a user as compromised using a user preference.

Usage:
  php admin/cli/mark_compromised.php --userid=ID [--clear]  [--reason="..."]
  php admin/cli/mark_compromised.php --username=USERNAME [--clear]  [--reason="..."]
  php admin/cli/mark_compromised.php --email=EMAIL [--clear]  [--reason="..."]
  php admin/cli/mark_compromised.php --help

Options:
  --userid         Moodle user id
  --username       Username
  --email          Email address
  --clear          Clear the compromised flag (default is to set it)
  --reason         Optional reason/audit note

Examples:
  php  admin/cli/mark_compromised.php --username=jdoe
  php  admin/cli/mark_compromised.php --userid=123 --clear

EOL;

[$options, $unrecognized] = cli_get_params(
    [
        'help' => false,
        'userid' => null,
        'username' => null,
        'email' => null,
        'clear' => false,
        'reason'   => null,
    ],
    [
        'h' => 'help',
    ]
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized), 2);
}

if ($options['help']) {
    echo $usage;
    exit(0);
}
if ($options['userid'] || $options['username'] || $options['email']) {
    // Resolve user.
    try {
        if (!empty($options['userid'])) {
            $user = $DB->get_record('user', ['id' => (int) $options['userid'], 'deleted' => 0], '*', MUST_EXIST);
        } else if (!empty($options['username'])) {
            $user = $DB->get_record('user', ['username' => $options['username'], 'deleted' => 0], '*', MUST_EXIST);
        } else {
            $user = $DB->get_record('user', ['email' => $options['email'], 'deleted' => 0], '*', MUST_EXIST);
        }
    } catch (dml_exception $e) {
        cli_error(get_string('usernotfound', 'user'));
    }
    $flag = empty($options['clear']) ? 1 : 0;
    $reason = $options['reason'] ?? get_string("eventusercompromisereasonfromcli");
    mark_user_as_compromised($user, $flag, $reason);
    echo "OK: user={$user->username} (id={$user->id}) is_compromised={$flag}" . PHP_EOL;
    echo "Reason: {$reason}" . PHP_EOL;
}
exit(0);
