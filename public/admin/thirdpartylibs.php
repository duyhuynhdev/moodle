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
 * List of 3rd party libs used in moodle and all plugins.
 *
 * @package   admin
 * @copyright 2013 Petr Skoda {@link http://skodak.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->libdir . '/thirdpartyreportlib.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/classes/dataformat.php');

admin_externalpage_setup('thirdpartylibs');

// Inputs: filter/sort/export.
$type = optional_param('type', '', PARAM_ALPHA);
$licensefilter = optional_param('license', '', PARAM_TEXT);
$copyrightfilter = optional_param('copyright', '', PARAM_TEXT);
$locationfilter = optional_param('location', '', PARAM_TEXT);
$sort = optional_param('sort', 'location', PARAM_ALPHANUMEXT);
$dir = optional_param('dir', 'asc', PARAM_ALPHA);
$download = optional_param('download', '', PARAM_ALPHA);
$url = '/admin/thirdpartylibs.php';
$files = ['core' => "$CFG->libdir/thirdpartylibs.xml"];
$versionfiles = [];
$standardplugins = core_plugin_manager::get_standard_plugins();
$plugintypes = core_component::get_plugin_types();

foreach ($plugintypes as $ptype => $ignored) {
    $plugins = core_component::get_plugin_list_with_file($ptype, 'thirdpartylibs.xml', false);
    foreach ($plugins as $plugin => $path) {
        $files[$ptype . '_' . $plugin] = $path;
    }
    $plugins = core_component::get_plugin_list_with_file($ptype, 'version.php', false);
    foreach ($plugins as $plugin => $path) {
        if (in_array($ptype . '_' . $plugin, $standardplugins)) {
            continue;
        }
        if ($ptype == 'filter') {
            $pluginname = get_string('filtername', $ptype . '_' . $plugin);
        } else if ($ptype == 'dataformat') {
            $pluginname = get_string('dataformat', $ptype . '_' . $plugin);
        } else {
            $pluginname = get_string('pluginname', $ptype . '_' . $plugin);
        }
        $versionfiles[$pluginname] = $path;
    }
}

$components = [];
foreach ($versionfiles as $plugin => $versionphp) {
    $license = detect_license_from_version_file($versionphp);
    $copyright = detect_copyright_from_version_file($versionphp);
    $licensetooltip = ltrim(substr($versionphp, strlen(dirname($CFG->dirroot))), '/');
    $copyrighttooltip = $licensetooltip;
    if (empty($license) || empty($copyright)) {
        $pluginroot = rtrim(realpath(dirname($versionphp)), '/\\');
        $infereddata = infer_copyright_and_license($pluginroot);
        if (empty($license)) {
            $license = $infereddata["license"];
            $licensetooltip = $infereddata["licensesource"];
        }
        if (empty($copyright)) {
            $copyright = $infereddata["copyright"];
            $copyrighttooltip = $infereddata["copyrightsource"];
        }
    }
    $version = read_plugin_version($versionphp);
    $base = realpath(dirname($versionphp));
    $location = ltrim(substr($base, strlen(dirname($CFG->dirroot))), '/');
    $components[] = (object)[
        'componenttype' => 'plugin',
        'name' => $plugin,
        'location' => $location,
        'version' => read_plugin_version($versionphp),
        'license' => $license,
        'licensetooltip' => $licensetooltip,
        'copyright' => $copyright,
        'copyrighttooltip' => $copyrighttooltip,
    ];
}

foreach ($files as $component => $xmlpath) {
    $xml = simplexml_load_file($xmlpath);
    foreach ($xml as $lib) {
        $base = realpath(dirname($xmlpath));
        $location = ltrim(substr($base, strlen(dirname($CFG->dirroot))) . '/' . $lib->location, '/');
        $licensetooltip = ltrim(substr($xmlpath, strlen(dirname($CFG->dirroot))), '/');
        $copyrighttooltip = $licensetooltip;
        if (is_dir($CFG->dirroot . $location)) {
            $location .= '/';
        }
        $version = '';
        if (!empty($lib->version)) {
            $version = $lib->version;
        }
        $license = $lib->license;
        if (!empty($lib->licenseversion)) {
            $license .= ' ' . $lib->licenseversion;
        }
        $libname = trim((string)($lib->name ?? ''));
        $copyright = $lib->copyright;
        if (empty($license) || empty($copyright)) {
            $root = dirname($xmlpath) . '/' . $lib->location;
            $infereddata = infer_copyright_and_license($root);
            if (empty($license)) {
                $license = $infereddata["license"];
                $licensetooltip = $infereddata["licensesource"];
            }
            if (empty($copyright)) {
                $copyright = $infereddata["copyright"];
                $copyrighttooltip = $infereddata["copyrightsource"];
            }
        }
        $components[] = (object)[
            'componenttype' => 'library',
            'name' => $libname,
            'location' => $location,
            'version' => $version,
            'license' => $license,
            'licensetooltip' => $licensetooltip,
            'copyright' => $copyright,
            'copyrighttooltip' => $copyrighttooltip,
        ];
    }
}

// Apply filters.
$type = trim($type);
if ($type !== '' && !in_array($type, ['plugin', 'library'], true)) {
    $type = '';
}

$licensefilter = trim($licensefilter);
if ($type !== '' || $licensefilter !== '' || $copyrightfilter !== '' || $locationfilter !== '') {
    $components = array_values(array_filter($components, function ($r) use (
        $type,
        $licensefilter,
        $copyrightfilter,
        $locationfilter
    ) {
        if ($type !== '' && $r->componenttype !== $type) {
            return false;
        }
        if ($licensefilter !== '') {
            if (stripos((string)$r->license, $licensefilter) === false) {
                return false;
            }
        }
        if ($copyrightfilter !== '') {
            if (stripos((string)$r->copyright, $copyrightfilter) === false) {
                return false;
            }
        }
        if ($locationfilter !== '') {
            if (stripos((string)$r->location, $locationfilter) === false) {
                return false;
            }
        }
        return true;
    }));
}

// Sort.
$allowed = ['name', 'version', 'copyright', 'license', 'location', 'componenttype'];
if (!in_array($sort, $allowed, true)) {
    $sort = 'location';
}
$dir = strtolower($dir) === 'desc' ? 'desc' : 'asc';
$mult = ($dir === 'desc') ? -1 : 1;

usort($components, function ($a, $b) use ($sort, $mult) {
    $av = strtolower((string)($a->{$sort} ?? ''));
    $bv = strtolower((string)($b->{$sort} ?? ''));
    if ($av === $bv) {
        // Stable-ish secondary sort to reduce flicker.
        $an = strtolower((string)($a->name ?? ''));
        $bn = strtolower((string)($b->name ?? ''));
        return $mult * strcmp($an, $bn);
    }
    return $mult * strcmp($av, $bv);
});

if ($download === 'csv') {
    $filename = 'thirdpartylibs';
    $columns = [
        'type' => get_string('thirdpartylibrarytype', 'core_admin'),
        'name' => get_string('thirdpartylibraryname', 'core_admin'),
        'location' => get_string('thirdpartylibrarylocation', 'core_admin'),
        'version' => get_string('version'),
        'license' => get_string('license'),
        'copyright' => get_string('thirdpartylibrarycopyright', 'core_admin'),
    ];
    $rows = [];
    foreach ($components as $r) {
        $rows[] = [
            'type' => $r->componenttype ?? '',
            'name' => $r->name ?? '',
            'location' => $r->location ?? '',
            'version' => $r->version ?? '',
            'license' => $r->license ?? '',
            'copyright' => $r->copyright ?? '',
        ];
    }
    $filepath = \core\dataformat::write_data($filename, 'csv', $columns, $rows);

    if (!is_readable($filepath)) {
        throw new \moodle_exception('Could not generate CSV export');
    }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="thirdparty_components.csv"');
    header('Content-Length: ' . filesize($filepath));
    readfile($filepath);
    @unlink($filepath);
    exit;
}

if ($download === 'spdxjson') {
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="spdx.json"');

    $spdx = build_spdx($components);
    echo json_encode($spdx, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('thirdpartylibs', 'core_admin'));

$PAGE->set_url(new moodle_url($url, [
    'type' => $type,
    'license' => $licensefilter,
    'copyright' => $copyrightfilter,
    'location' => $locationfilter,
    'sort' => $sort,
    'dir' => $dir,
]));

$formurl = new moodle_url($url);

echo html_writer::start_tag('form', ['method' => 'get', 'action' => $formurl->out(false), 'class' => 'mb-3']);
echo html_writer::start_div('d-flex flex-wrap gap-2 align-items-end');

echo html_writer::start_div();
echo html_writer::label(get_string('thirdpartylibrarytype', 'core_admin'), 'thirdpartylibs_type', false, ['class' => 'form-label']);
echo html_writer::start_tag('select', ['id' => 'thirdpartylibs_type', 'name' => 'type', 'class' => 'form-select']);
echo html_writer::tag('option', 'All', ['value' => '']);
echo html_writer::tag('option', 'plugin', ['value' => 'plugin', 'selected' => ($type === 'plugin') ? 'selected' : null]);
echo html_writer::tag('option', 'library', ['value' => 'library', 'selected' => ($type === 'library') ? 'selected' : null]);
echo html_writer::end_tag('select');
echo html_writer::end_div();

echo html_writer::start_div();
echo html_writer::label(get_string('license'), 'thirdpartylibs_license', false, ['class' => 'form-label']);
echo html_writer::empty_tag('input', [
    'type' => 'text',
    'id' => 'thirdpartylibs_license',
    'name' => 'license',
    'value' => s($licensefilter),
    'class' => 'form-control',
    'placeholder' => 'e.g. GPL, MIT, Apache',
]);
echo html_writer::end_div();

echo html_writer::start_div();
echo html_writer::label(
    get_string('thirdpartylibrarycopyright', 'core_admin'),
    'thirdpartylibs_copyright',
    false,
    ['class' => 'form-label']
);
echo html_writer::empty_tag('input', [
    'type' => 'text',
    'id' => 'thirdpartylibs_copyright',
    'name' => 'copyright',
    'value' => s($copyrightfilter),
    'class' => 'form-control',
    'placeholder' => 'e.g. 2025 Catalyst IT',
]);
echo html_writer::end_div();

echo html_writer::start_div();
echo html_writer::label(
    get_string('thirdpartylibrarylocation', 'core_admin'),
    'thirdpartylibs_location',
    false,
    ['class' => 'form-label']
);
echo html_writer::empty_tag('input', [
    'type' => 'text',
    'id' => 'thirdpartylibs_location',
    'name' => 'location',
    'value' => s($locationfilter),
    'class' => 'form-control',
    'placeholder' => 'e.g. /ai/placement',
]);
echo html_writer::end_div();

echo html_writer::start_div();
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sort', 'value' => s($sort)]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'dir', 'value' => s($dir)]);
echo html_writer::empty_tag('button', ['type' => 'submit', 'class' => 'btn btn-primary', 'value' => '1']);
echo html_writer::tag('span', get_string('filter'), ['class' => 'px-2']);
echo html_writer::end_div();

echo html_writer::end_div();
echo html_writer::end_tag('form');

// Export buttons.
$baseparams = [
    'type' => $type,
    'license' => $licensefilter,
    'copyright' => $copyrightfilter,
    'location' => $locationfilter,
    'sort' => $sort,
    'dir' => $dir,
];

echo html_writer::start_div('mb-3');
echo $OUTPUT->single_button(
    new moodle_url($url, $baseparams + ['download' => 'csv']),
    get_string('thirdpartylibrarydownloadcsv', 'core_admin'),
    'get'
);
echo $OUTPUT->single_button(
    new moodle_url($url, $baseparams + ['download' => 'spdxjson']),
    get_string('thirdpartylibrarydownloadspdx', 'core_admin'),
    'get'
);
echo html_writer::end_div();

// Render table.
$table = new html_table();
$table->attributes['class'] = 'generaltable admintable table table-striped';
$table->head = [
    sort_link($url, get_string('thirdpartylibrarytype', 'core_admin'), 'componenttype', $sort, $dir, $baseparams),
    sort_link($url, get_string('thirdpartylibraryname', 'core_admin'), 'name', $sort, $dir, $baseparams),
    sort_link($url, get_string('thirdpartylibrarylocation', 'core_admin'), 'location', $sort, $dir, $baseparams),
    sort_link($url, get_string('version'), 'version', $sort, $dir, $baseparams),
    sort_link($url, get_string('license'), 'license', $sort, $dir, $baseparams),
    sort_link($url, get_string('thirdpartylibrarycopyright', 'core_admin'), 'copyright', $sort, $dir, $baseparams),
];

$table->data = [];
foreach ($components as $r) {
    $table->data[] = [
        s($r->componenttype),
        s($r->name),
        html_writer::tag('code', s($r->location)),
        render_value_with_tooltip(s((string)($r->version ?? '')), ''),
        render_value_with_tooltip($r->license ?? '', $r->licensetooltip ?? ''),
        render_value_with_tooltip($r->copyright ?? '', $r->copyrighttooltip ?? ''),
    ];
}

echo html_writer::tag('div', get_string('total') . ': ' . count($components), ['class' => 'mb-2']);
echo html_writer::table($table);

echo $OUTPUT->footer();
