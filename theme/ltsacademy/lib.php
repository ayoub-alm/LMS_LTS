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
 * LTS Academy theme lib.php — SCSS callbacks and file serving.
 *
 * @package    theme_ltsacademy
 * @copyright  2024 LTS Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Returns the main SCSS content for the theme.
 *
 * @param theme_config $theme
 * @return string
 */
function theme_ltsacademy_get_main_scss_content($theme) {
    global $CFG;

    $scss = '';

    // Load Boost's default preset first as base.
    $filename = !empty($theme->settings->preset) ? $theme->settings->preset : null;
    $fs = get_file_storage();
    $context = context_system::instance();

    if ($filename == 'default.scss') {
        $scss .= file_get_contents($CFG->dirroot . '/theme/boost/scss/preset/default.scss');
    } else if ($filename == 'plain.scss') {
        $scss .= file_get_contents($CFG->dirroot . '/theme/boost/scss/preset/plain.scss');
    } else if ($filename && ($presetfile = $fs->get_file($context->id, 'theme_ltsacademy', 'preset', 0, '/', $filename))) {
        $scss .= $presetfile->get_content();
    } else {
        $scss .= file_get_contents($CFG->dirroot . '/theme/boost/scss/preset/default.scss');
    }

    // Append our custom theme SCSS on top.
    $scss .= file_get_contents($CFG->dirroot . '/theme/ltsacademy/scss/ltsacademy.scss');

    return $scss;
}

/**
 * Get compiled (pre-built) CSS fallback.
 *
 * @return string
 */
function theme_ltsacademy_get_precompiled_css() {
    global $CFG;
    // Fall back to Boost's precompiled CSS.
    return file_get_contents($CFG->dirroot . '/theme/boost/style/moodle.css');
}

/**
 * Inject SCSS variables before the main content (pre-scss).
 *
 * @param theme_config $theme
 * @return string
 */
function theme_ltsacademy_get_pre_scss($theme) {
    global $CFG;

    $scss = '';

    // Brand/primary colour.
    $configurable = [
        'brandcolor' => ['primary'],
    ];

    foreach ($configurable as $configkey => $targets) {
        $value = isset($theme->settings->{$configkey}) ? $theme->settings->{$configkey} : null;
        if (empty($value)) {
            continue;
        }
        array_map(function($target) use (&$scss, $value) {
            $scss .= '$' . $target . ': ' . $value . ";\n";
        }, (array) $targets);
    }

    if (!empty($theme->settings->scsspre)) {
        $scss .= $theme->settings->scsspre;
    }

    return $scss;
}

/**
 * Inject extra SCSS after the main content (extra-scss).
 *
 * @param theme_config $theme
 * @return string
 */
function theme_ltsacademy_get_extra_scss($theme) {
    $content = '';

    // Background image.
    $imageurl = $theme->setting_file_url('backgroundimage', 'backgroundimage');
    if (!empty($imageurl)) {
        $content .= '@media (min-width: 768px) {';
        $content .= 'body { ';
        $content .= "background-image: url('$imageurl'); background-size: cover;";
        $content .= ' } }';
    }

    return !empty($theme->settings->scss) ? "{$theme->settings->scss} \n {$content}" : $content;
}

/**
 * Serve uploaded theme files (logo, hero image, banner image, etc.)
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context  $context
 * @param string   $filearea
 * @param array    $args
 * @param bool     $forcedownload
 * @param array    $options
 * @return bool
 */
function theme_ltsacademy_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    $allowed_areas = [
        'logo', 'backgroundimage', 'heroimage', 'bannerimage',
        'aiavatar', 'loginbackgroundimage',
    ];

    if ($context->contextlevel == CONTEXT_SYSTEM && in_array($filearea, $allowed_areas)) {
        $theme = theme_config::load('ltsacademy');
        if (!array_key_exists('cacheability', $options)) {
            $options['cacheability'] = 'public';
        }
        return $theme->setting_file_serve($filearea, $args, $forcedownload, $options);
    } else {
        send_file_not_found();
    }
}
