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
 * These functions are required for building the third party libraries report.
 *
 * @package    core
 * @copyright  2025, Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Extract the leading header comment block from a PHP file content.
 *
 * @param string $header
 * @return string|null
 */
function extract_header_comment(string $header): ?string {
    if (preg_match('#^\s*<\?php\s*(/\*\*.*?\*/)#s', $header, $m)) {
        return $m[1];
    }
    if (preg_match('#^\s*<\?php\s*((?://.*\R)+)#s', $header, $m)) {
        return $m[1];
    }
    return null;
}

/**
 * Normalize common license strings into SPDX-ish IDs where feasible.
 * Only use this function for a simple case such as version.php file.
 *
 * @param string $raw
 * @return ?string
 */
function normalise_license(string $raw): ?string {
    $raw = trim($raw);
    if ($raw === '') {
        return '';
    }

    $l = strtolower($raw);

    if (str_contains($l, 'spdx-license-identifier')) {
        if (preg_match('/spdx-license-identifier:\s*([A-Za-z0-9\.\-\+]+)/i', $raw, $m)) {
            return trim($m[1]);
        }
    }

    $orlater = str_contains($l, 'or later')
        || str_contains($l, 'or any later version')
        || str_contains($l, 'or later version')
        || str_contains($l, 'or any subsequent version')
        || str_contains($l, 'or, at your option, any later version');

    // Academic Free License v3.0.
    if (str_contains($l, 'afl-3.0') || (str_contains($l, 'academic free license') && str_contains($l, '3.0'))) {
        return 'AFL-3.0';
    }

    // Apache license 2.0.
    if (str_contains($l, 'apache-2.0') || (str_contains($l, 'apache') && str_contains($l, '2.0'))) {
        return 'Apache-2.0';
    }

    // Artistic license 2.0.
    if (str_contains($l, 'artistic-2.0') || (str_contains($l, 'artistic') && str_contains($l, '2.0'))) {
        return 'Artistic-2.0';
    }

    // Boost Software License 1.0.
    if (str_contains($l, 'bsl-1.0') || (str_contains($l, 'boost software license') && str_contains($l, '1.0'))) {
        return 'BSL-1.0';
    }

    // BSD family - most specific first.
    if (str_contains($l, 'bsd') && str_contains($l, 'clause')) {
        if (str_contains($l, '0bsd') || str_contains($l, 'zero')) {
            return '0BSD';
        }
        if (str_contains($l, 'bsd-2') || str_contains($l, 'bsd 2')) {
            return 'BSD-2-Clause';
        }
        if (str_contains($l, 'bsd-3') || str_contains($l, 'bsd 3')) {
            if (str_contains($l, 'clear')) {
                return 'BSD-3-Clause-Clear';
            }
            return 'BSD-3-Clause';
        }
        if (str_contains($l, 'bsd-4') || str_contains($l, 'bsd 4')) {
            return 'BSD-4-Clause';
        }
    }

    // Creative Commons - specific before family.
    if (str_contains($l, 'cc-by') || str_contains($l, 'cc by') || str_contains($l, 'creative commons attribution')) {
        if (str_contains($l, '4.0')) {
            if (str_contains($l, '-sa') || str_contains($l, ' sa') || str_contains($l, 'sharealike')) {
                return 'CC-BY-SA-4.0';
            }
            return 'CC-BY-4.0';
        }
    }

    if (
        str_contains($l, 'cc0-1.0')
        || str_contains($l, 'cc0')
        || (str_contains($l, 'creative commons zero') && str_contains($l, '1.0'))
    ) {
        return 'CC0-1.0';
    }

    if (str_contains($l, 'creative commons') || $l === 'cc') {
        return 'CC';
    }

    // WTFPL.
    if (
        str_contains($l, 'do what the f*ck you want to public license')
        || str_contains($l, 'do what the fuck you want to public license')
        || str_contains($l, 'wtfpl')
    ) {
        return 'WTFPL';
    }

    // Educational Community License v2.0.
    if (str_contains($l, 'ecl-2.0') || (str_contains($l, 'educational community license') && str_contains($l, '2.0'))) {
        return 'ECL-2.0';
    }

    // Eclipse Public License.
    if (str_contains($l, 'epl-') || str_contains($l, 'eclipse public license')) {
        if (str_contains($l, '1.0')) {
            return 'EPL-1.0';
        }
        if (str_contains($l, '2.0')) {
            return 'EPL-2.0';
        }
    }

    // European Union Public License 1.1.
    if (str_contains($l, 'eupl-1.1') || (str_contains($l, 'european union public license') && str_contains($l, '1.1'))) {
        return 'EUPL-1.1';
    }

    // AGPL - SPDX-compliant only/or-later forms.
    if ((str_contains($l, 'agpl') || str_contains($l, 'gnu affero general public license')) && str_contains($l, '3')) {
        return $orlater ? 'AGPL-3.0-or-later' : 'AGPL-3.0-only';
    }

    // LGPL family - specific before generic.
    if (str_contains($l, 'lgpl') || str_contains($l, 'gnu lesser general public license')) {
        if (str_contains($l, '2.1')) {
            return $orlater ? 'LGPL-2.1-or-later' : 'LGPL-2.1-only';
        }
        if (str_contains($l, '3.0')) {
            return $orlater ? 'LGPL-3.0-or-later' : 'LGPL-3.0-only';
        }
        return 'LGPL';
    }

    // GPL family - specific before generic.
    if (str_contains($l, 'gnu general public license') || str_contains($l, 'gpl')) {
        if (str_contains($l, '2')) {
            return $orlater ? 'GPL-2.0-or-later' : 'GPL-2.0-only';
        }
        if (str_contains($l, '3')) {
            return $orlater ? 'GPL-3.0-or-later' : 'GPL-3.0-only';
        }
        return 'GPL';
    }

    // ISC.
    if ($l === 'isc' || str_contains($l, 'isc license')) {
        return 'ISC';
    }

    // LPPL.
    if ((str_contains($l, 'lppl') || str_contains($l, 'latex project public license')) && str_contains($l, '1.3c')) {
        return 'LPPL-1.3c';
    }

    // MS-PL.
    if (str_contains($l, 'microsoft public license') || str_contains($l, 'ms-pl')) {
        return 'MS-PL';
    }

    // MIT.
    if ($l === 'mit' || str_contains($l, 'mit license')) {
        return 'MIT';
    }

    // MPL.
    if (str_contains($l, 'mpl-2.0') || (str_contains($l, 'mozilla public license') && str_contains($l, '2.0'))) {
        return 'MPL-2.0';
    }

    // OSL.
    if (str_contains($l, 'osl-3.0') || (str_contains($l, 'open software license') && str_contains($l, '3.0'))) {
        return 'OSL-3.0';
    }

    // PostgreSQL.
    if (str_contains($l, 'postgresql license') || $l === 'postgresql') {
        return 'PostgreSQL';
    }

    // OFL.
    if ((str_contains($l, 'sil open font license') || str_contains($l, 'ofl')) && str_contains($l, '1.1')) {
        return 'OFL-1.1';
    }

    // NCSA.
    if (str_contains($l, 'university of illinois/ncsa open source license') || str_contains($l, 'ncsa')) {
        return 'NCSA';
    }

    // Unlicense.
    if (str_contains($l, 'unlicense')) {
        return 'Unlicense';
    }

    // Zlib.
    if (str_contains($l, 'zlib')) {
        return 'Zlib';
    }

    return null;
}

/**
 * Detect a license from common license files and/or version.php header.
 *
 * @param string $versionphp
 * @return ?string
 */
function detect_license_from_version_file(string $versionphp): ?string {
    if (is_readable($versionphp)) {
        $content = file_get_contents($versionphp, false, null, 0, 20000) ?: '';
        $header = extract_header_comment($content);

        if ($header && preg_match('/SPDX-License-Identifier:\s*([A-Za-z0-9\.\-\+]+)/i', $header, $m)) {
            return trim($m[1]);
        }
        if ($header && preg_match('/@license\s+(.+)/i', $header, $m)) {
            return normalise_license(trim($m[1]));
        }
    }

    return null;
}

/**
 * Detect copyright from version.php header.
 * Returns string.
 *
 * @param string $versionphp
 * @return ?string Copyright.
 */
function detect_copyright_from_version_file(string $versionphp): ?string {
    if (is_readable($versionphp)) {
        $content = file_get_contents($versionphp, false, null, 0, 20000) ?: '';
        $header = extract_header_comment($content);

        if ($header && preg_match('/@copyright\s+(.+)/i', $header, $m)) {
            return trim($m[1]);
        }
    }
    return null;
}

/**
 * Safe read of $plugin->version from version.php.
 *
 * @param string $versionphp
 * @return ?string
 */
function read_plugin_version(string $versionphp): ?string {
    if (!is_readable($versionphp)) {
        return null;
    }
    $content = file_get_contents($versionphp, false, null, 0, 20000);
    if ($content === false) {
        return null;
    }
    if (preg_match('/\$plugin->version\s*=\s*([0-9]+)\s*;/i', $content, $m)) {
        return $m[1];
    }
    if (preg_match('/\$module->version\s*=\s*([0-9]+)\s*;/i', $content, $m)) {
        // Legacy modules.
        return $m[1];
    }
    return null;
}


/**
 * Build an SPDX 2.3 JSON doc from component rows.
 *
 * @param array $components
 * @return array
 */
function build_spdx(array $components): array {
    global $CFG;

    $created = gmdate('Y-m-d\TH:i:s\Z');
    $docns = rtrim($CFG->wwwroot, '/') . '/admin/thirdpartylibs.php#' . time();

    $spdx = [
        'spdxVersion' => 'SPDX-2.3',
        'dataLicense' => 'CC0-1.0',
        'SPDXID' => 'SPDXRef-DOCUMENT',
        'name' => 'Moodle Third-party Components SBOM',
        'documentNamespace' => $docns,
        'creationInfo' => [
            'created' => $created,
            'creators' => ['Tool: moodle-admin-thirdpartylibs'],
        ],
        'packages' => [],
        'relationships' => [],
    ];

    foreach ($components as $r) {
        $idbase = $r->componenttype . '-' . $r->name;
        $id = 'SPDXRef-' . preg_replace('/[^A-Za-z0-9\.\-]+/', '-', $idbase);
        if (strlen($id) > 120) {
            $id = substr($id, 0, 120);
        }

        $license = $r->license ?: '';
        $copyright = $r->copyright ?: '';

        $pkg = [
            'SPDXID' => $id,
            'name' => (string)$r->name,
            'versionInfo' => $r->version ?: null,
            'downloadLocation' => 'NOASSERTION',
            'licenseDeclared' => $license,
            'licenseConcluded' => $license,
            'copyrightText' => $copyright,
            'supplier' => 'NOASSERTION',
            'filesAnalyzed' => false,
        ];
        $pkg = array_filter($pkg, fn($v) => $v !== null);

        $spdx['packages'][] = $pkg;
        $spdx['relationships'][] = [
            'spdxElementId' => 'SPDXRef-DOCUMENT',
            'relationshipType' => 'DESCRIBES',
            'relatedSpdxElement' => $id,
        ];
    }

    return $spdx;
}

/**
 * Build sortable header links.
 *
 * @param string $url Base url.
 * @param string $label Header label.
 * @param string $field Sort field.
 * @param string $currsort Current sort field.
 * @param string $currdir Current sort direction.
 * @param array $params Params list.
 * @return string
 */
function sort_link(string $url, string $label, string $field, string $currsort, string $currdir, array $params): string {
    $dir = 'asc';
    if ($currsort === $field && $currdir === 'asc') {
        $dir = 'desc';
    }
    $params['sort'] = $field;
    $params['dir'] = $dir;
    if ($currsort === $field) {
        $label = $dir == 'asc' ? $label . ' ▼' : $label . ' ▲';
    } else {
        $label = $label . ' ⋮';
    }
    $url = new moodle_url($url, $params);
    return html_writer::link($url, html_writer::span($label, '', ['style' => 'white-space: nowrap;']));
}


/**
 * Render a value with a title tooltip showing source and evidence path.
 *
 * @param string $value
 * @param string $sourcepath
 * @return string
 */
function render_value_with_tooltip(string $value, string $sourcepath): string {
    if (empty($value)) {
        return html_writer::span(
            'unknown',
            'badge bg-warning text-dark',
        );
    }
    if ($sourcepath && $sourcepath !== '') {
        return html_writer::span(
            s($value),
            '',
            ['title' => $sourcepath]
        );
    }

    return s($value);
}

/**
 * Extract copyright from the given text.
 *
 * @param string $content
 * @return ?string Copyright.
 */
function extract_copyright_from_text(string $content): ?string {
    $lines = preg_split('/\R/', $content) ?: [];

    foreach ($lines as $line) {
        $line = trim($line);

        // Strip common comment prefixes/decorations.
        $line = preg_replace('~^(?:/\*+|\*|//+|#|--)?\s*\|?\s*~', '', $line);
        $line = preg_replace('~\s*\|?\s*(?:\*/)?$~', '', $line);
        $line = trim($line);

        if ($line === '') {
            continue;
        }

        // Case @copyright 2017 Pathable.
        if (
            preg_match(
                '~^@copyright\s+(\d{4}(?:\s*[-,]\s*\d{4})*(?:,\s*\d{4})*\s+.+)$~iu',
                $line,
                $m
            )
        ) {
            return 'Copyright (c) ' . trim($m[1]);
        }

        // Copyright (c) 2020 Foo.
        // Copyright 2020 Foo.
        if (
            preg_match(
                '~^(copyright\s*(?:(?:\((?:c|C)\))|©)?\s*\d{4}(?:\s*[-,]\s*\d{4})*(?:,\s*\d{4})*\s+.+)$~iu',
                $line,
                $m
            )
        ) {
            return trim($m[1]);
        }

        // Case © 2020 Foo.
        if (
            preg_match(
                '~^(©\s*\d{4}(?:\s*[-,]\s*\d{4})*(?:,\s*\d{4})*\s+.+)$~u',
                $line,
                $m
            )
        ) {
            return trim($m[1]);
        }

        // Case (c) 2000-2013 John Lim.
        if (
            preg_match(
                '~^(\((?:c|C)\)\s*\d{4}(?:\s*-\s*\d{4})?\s+.+)$~u',
                $line,
                $m
            )
        ) {
            return trim($m[1]);
        }
    }

    return null;
}

/**
 * Scan a plugin root for common license files.
 * @param string $pluginroot Absolute plugin root path.
 * @return array
 */
function infer_copyright_and_license(string $pluginroot): array {
    global $CFG;
    $license = null;
    $licensesource = null;
    $copyright = null;
    $copyrightsource = null;

    if (is_file($pluginroot)) {
        $content = file_get_contents($pluginroot, false, null, 0, 50000);
        if (!empty($content)) {
            $license = detect_license_from_text($content) ?? normalise_license($content);
            $licensesource = $pluginroot;
            $copyright = extract_copyright_from_text($content);
            $copyrightsource = $pluginroot;
        }
    } else if (is_dir($pluginroot)) {
        $wanted = [
            'license',
            'license.txt',
            'license.md',
            'copying',
            'notice',
            'copyright',
        ];
        $iterator = new FilesystemIterator($pluginroot, FilesystemIterator::SKIP_DOTS);

        foreach ($iterator as $fileinfo) {
            if (!$fileinfo->isFile()) {
                continue;
            }

            $filename = strtolower($fileinfo->getFilename());
            if (!in_array($filename, $wanted, true)) {
                continue;
            }
            $filepath = $fileinfo->getPathname();

            // Optional guardrails: skip noisy dirs.
            $normalizedpath = str_replace('\\', '/', $filepath);
            if (
                str_contains($normalizedpath, '/node_modules')
                || str_contains($normalizedpath, '/tests')
                || str_contains($normalizedpath, '/.git/')
            ) {
                continue;
            }
            $content = file_get_contents($filepath, false, null, 0, 50000);
            if ($content === false || trim($content) === '') {
                continue;
            }
            $filelocation = ltrim(substr($filepath, strlen(dirname($CFG->dirroot))), '/');
            if (!$license) {
                $license = detect_license_from_text($content) ?? normalise_license($content);
                $licensesource = $filelocation;
            }
            if (!$copyright) {
                $copyright = extract_copyright_from_text($content);
                $copyrightsource = $filelocation;
            }
        }
    }

    return [
        "license" => $license,
        "licensesource" => $licensesource,
        "copyright" => $copyright,
        "copyrightsource" => $copyrightsource,
    ];
}


/**
 * Get all licenses templates
 * @return array List of templates
 */
function get_all_licenses(): array {
    static $licenses;
    if ($licenses !== null) {
        return $licenses;
    }
    $licensesdir = __DIR__ . '/licenses/';
    foreach (scandir($licensesdir) as $file) {
        if (is_file($licensesdir . $file)) {
            $name = strtoupper(pathinfo($file, PATHINFO_FILENAME));
            $content = file_get_contents($licensesdir . $file);
            $parts = preg_split('/^---\s*$/m', $content, 3);
            $licenses[$name] = $parts[2];
        }
    }

    return $licenses;
}

/**
 * Simple license detector for raw license text.
 * @param string $text  License text.
 * @param float $threshold Similarity threshold.
 * @return ?string License name.
 */
function detect_license_from_text(string $text, float $threshold = 90.0): ?string {
    $knownlicenses = get_all_licenses();
    $input = normalize_license_text($text);
    if ($input === '') {
        return null;
    }

    $inputwords = get_word_set($input);
    if ($inputwords === []) {
        return null;
    }

    $bestid = null;
    $bestscore = 0.0;

    foreach ($knownlicenses as $licenseid => $licensetext) {
        $candidate = normalize_license_text($licensetext);

        $candidatewords = get_word_set($candidate);

        if ($candidatewords === []) {
            continue;
        }

        // Exact normalized token-set match.
        if ($inputwords === $candidatewords) {
            return $licenseid;
        }

        // Similarity match.
        $score = calculate_similarity_score($inputwords, $candidatewords, strlen($input), strlen($candidate));

        if ($score > $bestscore) {
            $bestscore = $score;
            $bestid = $licenseid;
        }
    }

    if ($bestid === null || $bestscore < $threshold) {
        return null;
    }

    return $bestid;
}

/**
 * Normalise the license text.
 * @param string $text  License text.
 * @return string Normalized license text.
 */
function normalize_license_text(string $text): string {
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // Strip HTML tags.
    $text = strip_tags($text);

    // Strip markdown headings, bullets, blockquotes, fenced markers.
    $text = preg_replace('/^\s{0,3}(#{1,6}|\*|-|>)+\s?/m', ' ', $text);
    $text = preg_replace('/```.*?```/s', ' ', $text);

    // Strip URLs.
    $text = preg_replace('~https?://\S+~i', ' ', $text);

    // Strip common copyright lines.
    $text = preg_replace('/copyright\s*\(c\)?\s*[\d,\-\s]+\s+.*$/im', ' ', $text);

    // Lowercase.
    $text = mb_strtolower($text, 'UTF-8');

    // Normalize punctuation variants.
    $replace = [
        '’' => "'",
        '‘' => "'",
        '“' => '"',
        '”' => '"',
        '–' => '-',
        '—' => '-',
        '&' => ' and ',
    ];
    $text = strtr($text, $replace);

    // Keep words, digits, slash, dash, apostrophe; remove most other punctuation.
    $text = preg_replace('/[^\p{L}\p{N}\/\'\-\s]+/u', ' ', $text);

    // Collapse whitespace.
    $text = preg_replace('/\s+/u', ' ', $text);

    return trim($text);
}

/**
 * Get word set for the given license text.
 * @param string $text  License text.
 * @return array Word set.
 */
function get_word_set(string $text): array {
    preg_match_all("/(?:[\p{L}\p{N}\/-](?:'s|(?<=s)')?)+/u", $text, $matches);
    $words = array_values(array_unique($matches[0] ?? []));
    sort($words);

    return $words;
}

/**
 * Calculate similarity score between given licenses.
 * @param array $awords  Word set of license A.
 * @param array $bwords  Word set of license B.
 * @param int $alength  String length of normalized license A.
 * @param int $blength  String length of normalized license A.
 * @return float Similarity score.
 */
function calculate_similarity_score(array $awords, array $bwords, int $alength, int $blength): float {
    $overlap = count(array_intersect($awords, $bwords));
    $total = count($awords) + count($bwords);
    $lengthdeltapenalty = intdiv(abs($alength - $blength), 4);

    if (($total + $lengthdeltapenalty) === 0) {
        return 0.0;
    }
    return ($overlap * 200.0) / ($total + $lengthdeltapenalty);
}
