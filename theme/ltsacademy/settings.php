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
 * LTS Academy theme settings page.
 *
 * @package    theme_ltsacademy
 * @copyright  2024 LTS Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

    // ─────────────────────────────────────────────────────────────────────────
    // Tabs container
    // ─────────────────────────────────────────────────────────────────────────
    $settings = new theme_boost_admin_settingspage_tabs(
        'themesettingltsacademy',
        get_string('configtitle', 'theme_ltsacademy')
    );

    // =========================================================================
    // TAB 1 – General Settings
    // =========================================================================
    $page = new admin_settingpage('theme_ltsacademy_general', get_string('generalsettings', 'theme_ltsacademy'));

    // Preset selector (inherits Boost presets).
    $name = 'theme_ltsacademy/preset';
    $title = get_string('preset', 'theme_boost');
    $description = get_string('preset_desc', 'theme_boost');
    $default = 'default.scss';

    $context = context_system::instance();
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'theme_ltsacademy', 'preset', 0, 'itemid, filepath, filename', false);
    $choices = [];
    foreach ($files as $file) {
        $choices[$file->get_filename()] = $file->get_filename();
    }
    $choices['default.scss'] = 'default.scss';
    $choices['plain.scss']   = 'plain.scss';

    $setting = new admin_setting_configthemepreset($name, $title, $description, $default, $choices, 'boost');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Preset files upload.
    $name = 'theme_ltsacademy/presetfiles';
    $title = get_string('presetfiles', 'theme_boost');
    $description = get_string('presetfiles_desc', 'theme_boost');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'preset', 0,
        ['maxfiles' => 20, 'accepted_types' => ['.scss']]);
    $page->add($setting);

    // Brand colour.
    $name = 'theme_ltsacademy/brandcolor';
    $title = get_string('brandcolor', 'theme_ltsacademy');
    $description = get_string('brandcolor_desc', 'theme_ltsacademy');
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#1e3a8a');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Logo upload.
    $name = 'theme_ltsacademy/logo';
    $title = get_string('logo', 'theme_ltsacademy');
    $description = get_string('logo_desc', 'theme_ltsacademy');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'logo');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Background image.
    $name = 'theme_ltsacademy/backgroundimage';
    $title = get_string('backgroundimage', 'theme_boost');
    $description = get_string('backgroundimage_desc', 'theme_boost');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'backgroundimage');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Raw SCSS pre.
    $setting = new admin_setting_scsscode('theme_ltsacademy/scsspre',
        get_string('rawscsspre', 'theme_boost'), get_string('rawscsspre_desc', 'theme_boost'), '', PARAM_RAW);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Raw SCSS post.
    $setting = new admin_setting_scsscode('theme_ltsacademy/scss',
        get_string('rawscss', 'theme_boost'), get_string('rawscss_desc', 'theme_boost'), '', PARAM_RAW);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $settings->add($page);

    // =========================================================================
    // TAB 2 – Homepage Settings
    // =========================================================================
    $page = new admin_settingpage('theme_ltsacademy_homepage', get_string('homepagesettings', 'theme_ltsacademy'));

    // ── Hero Section ──────────────────────────────────────────────────────────

    $page->add(new admin_setting_heading(
        'theme_ltsacademy/herosection',
        get_string('herosection', 'theme_ltsacademy'),
        ''
    ));

    $name = 'theme_ltsacademy/heroheading';
    $title = get_string('heroheading', 'theme_ltsacademy');
    $description = get_string('heroheading_desc', 'theme_ltsacademy');
    $default = 'Développez vos compétences avec LTS ACADEMY';
    $setting = new admin_setting_configtext($name, $title, $description, $default, PARAM_TEXT);
    $page->add($setting);

    $name = 'theme_ltsacademy/herosubtext';
    $title = get_string('herosubtext', 'theme_ltsacademy');
    $description = get_string('herosubtext_desc', 'theme_ltsacademy');
    $default = 'La formation qui convient le mieux à vos besoins professionnels. Rejoignez-nous pour booster votre carrière.';
    $setting = new admin_setting_configtextarea($name, $title, $description, $default, PARAM_TEXT);
    $page->add($setting);

    $name = 'theme_ltsacademy/herobtntext';
    $title = get_string('herobtntext', 'theme_ltsacademy');
    $description = get_string('herobtntext_desc', 'theme_ltsacademy');
    $setting = new admin_setting_configtext($name, $title, $description, 'Découvrir nos formations', PARAM_TEXT);
    $page->add($setting);

    $name = 'theme_ltsacademy/herobtnurl';
    $title = get_string('herobtnurl', 'theme_ltsacademy');
    $description = get_string('herobtnurl_desc', 'theme_ltsacademy');
    $setting = new admin_setting_configtext($name, $title, $description, '/course/index.php', PARAM_URL);
    $page->add($setting);

    $name = 'theme_ltsacademy/herobtn2text';
    $title = get_string('herobtn2text', 'theme_ltsacademy');
    $description = get_string('herobtn2text_desc', 'theme_ltsacademy');
    $setting = new admin_setting_configtext($name, $title, $description, 'Se connecter', PARAM_TEXT);
    $page->add($setting);

    $name = 'theme_ltsacademy/herobtn2url';
    $title = get_string('herobtn2url', 'theme_ltsacademy');
    $description = get_string('herobtn2url_desc', 'theme_ltsacademy');
    $setting = new admin_setting_configtext($name, $title, $description, '/login/index.php', PARAM_URL);
    $page->add($setting);

    // Hero image upload.
    $name = 'theme_ltsacademy/heroimage';
    $title = get_string('heroimage', 'theme_ltsacademy');
    $description = get_string('heroimage_desc', 'theme_ltsacademy');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'heroimage');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // ── Courses Section ───────────────────────────────────────────────────────

    $page->add(new admin_setting_heading(
        'theme_ltsacademy/coursessection',
        get_string('coursessection', 'theme_ltsacademy'),
        ''
    ));

    $name = 'theme_ltsacademy/maxcourses';
    $title = get_string('maxcourses', 'theme_ltsacademy');
    $description = get_string('maxcourses_desc', 'theme_ltsacademy');
    $setting = new admin_setting_configtext($name, $title, $description, '6', PARAM_INT);
    $page->add($setting);

    $name = 'theme_ltsacademy/coursesviewallurl';
    $title = get_string('coursesviewallurl', 'theme_ltsacademy');
    $description = get_string('coursesviewallurl_desc', 'theme_ltsacademy');
    $setting = new admin_setting_configtext($name, $title, $description, '/course/index.php', PARAM_URL);
    $page->add($setting);

    // ── About / Banner Section ────────────────────────────────────────────────

    $page->add(new admin_setting_heading(
        'theme_ltsacademy/aboutsection',
        get_string('aboutsection', 'theme_ltsacademy'),
        ''
    ));

    $name = 'theme_ltsacademy/bannerimage';
    $title = get_string('bannerimage', 'theme_ltsacademy');
    $description = get_string('bannerimage_desc', 'theme_ltsacademy');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'bannerimage');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // ── Contact Section ───────────────────────────────────────────────────────

    $page->add(new admin_setting_heading(
        'theme_ltsacademy/contactsection',
        get_string('contactsection', 'theme_ltsacademy'),
        ''
    ));

    $name = 'theme_ltsacademy/showcontactform';
    $title = get_string('showcontactform', 'theme_ltsacademy');
    $description = get_string('showcontactform_desc', 'theme_ltsacademy');
    $setting = new admin_setting_configcheckbox($name, $title, $description, 1);
    $page->add($setting);

    $name = 'theme_ltsacademy/contactemail';
    $title = get_string('contactemail', 'theme_ltsacademy');
    $description = get_string('contactemail_desc', 'theme_ltsacademy');
    $setting = new admin_setting_configtext($name, $title, $description, 'contact@ltsacademy.ma', PARAM_EMAIL);
    $page->add($setting);

    // ── Footer ────────────────────────────────────────────────────────────────

    $page->add(new admin_setting_heading(
        'theme_ltsacademy/footersection',
        get_string('footersection', 'theme_ltsacademy'),
        ''
    ));

    $name = 'theme_ltsacademy/footertagline';
    $title = get_string('footertagline', 'theme_ltsacademy');
    $description = get_string('footertagline_desc', 'theme_ltsacademy');
    $setting = new admin_setting_configtext($name, $title, $description,
        'Formez-vous aux métiers de demain et transformez votre entreprise.', PARAM_TEXT);
    $page->add($setting);

    $name = 'theme_ltsacademy/footeraddress';
    $title = get_string('footeraddress', 'theme_ltsacademy');
    $description = get_string('footeraddress_desc', 'theme_ltsacademy');
    $setting = new admin_setting_configtext($name, $title, $description, 'Berrechid, Maroc — Centre Ville', PARAM_TEXT);
    $page->add($setting);

    $name = 'theme_ltsacademy/footerphone';
    $title = get_string('footerphone', 'theme_ltsacademy');
    $description = get_string('footerphone_desc', 'theme_ltsacademy');
    $setting = new admin_setting_configtext($name, $title, $description, '0660-356877', PARAM_TEXT);
    $page->add($setting);

    $name = 'theme_ltsacademy/footeremail';
    $title = get_string('footeremail', 'theme_ltsacademy');
    $description = get_string('footeremail_desc', 'theme_ltsacademy');
    $setting = new admin_setting_configtext($name, $title, $description, 'contact@ltsacademy.ma', PARAM_EMAIL);
    $page->add($setting);

    $name = 'theme_ltsacademy/footerfacebook';
    $title = get_string('footerfacebook', 'theme_ltsacademy');
    $description = get_string('footerfacebook_desc', 'theme_ltsacademy');
    $setting = new admin_setting_configtext($name, $title, $description, '#', PARAM_URL);
    $page->add($setting);

    $name = 'theme_ltsacademy/footerlinkedin';
    $title = get_string('footerlinkedin', 'theme_ltsacademy');
    $description = get_string('footerlinkedin_desc', 'theme_ltsacademy');
    $setting = new admin_setting_configtext($name, $title, $description, '#', PARAM_URL);
    $page->add($setting);

    $name = 'theme_ltsacademy/footertwitter';
    $title = get_string('footertwitter', 'theme_ltsacademy');
    $description = get_string('footertwitter_desc', 'theme_ltsacademy');
    $setting = new admin_setting_configtext($name, $title, $description, '#', PARAM_URL);
    $page->add($setting);

    $name = 'theme_ltsacademy/footercopyright';
    $title = get_string('footercopyright', 'theme_ltsacademy');
    $description = get_string('footercopyright_desc', 'theme_ltsacademy');
    $setting = new admin_setting_configtext($name, $title, $description,
        '© 2025 LTS Academy. Tous droits réservés.', PARAM_TEXT);
    $page->add($setting);

    // ── AI Chatbot ────────────────────────────────────────────────────────────

    $page->add(new admin_setting_heading(
        'theme_ltsacademy/chatbotsection',
        get_string('chatbotsection', 'theme_ltsacademy'),
        ''
    ));

    $name = 'theme_ltsacademy/showchatbot';
    $title = get_string('showchatbot', 'theme_ltsacademy');
    $description = get_string('showchatbot_desc', 'theme_ltsacademy');
    $setting = new admin_setting_configcheckbox($name, $title, $description, 1);
    $page->add($setting);

    $name = 'theme_ltsacademy/geminiapikey';
    $title = get_string('geminiapikey', 'theme_ltsacademy');
    $description = get_string('geminiapikey_desc', 'theme_ltsacademy');
    $setting = new admin_setting_configpasswordunmask($name, $title, $description, '');
    $page->add($setting);

    // AI Avatar upload.
    $name = 'theme_ltsacademy/aiavatar';
    $title = get_string('aiavatar', 'theme_ltsacademy');
    $description = get_string('aiavatar_desc', 'theme_ltsacademy');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'aiavatar');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $settings->add($page);
}
