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
 * LTS Academy custom frontpage layout.
 *
 * Fetches Moodle courses and renders the full branded homepage template.
 *
 * @package    theme_ltsacademy
 * @copyright  2024 LTS Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/behat/lib.php');
require_once($CFG->dirroot . '/course/lib.php');

// ─── Theme Settings ───────────────────────────────────────────────────────────
$themeconfig = theme_config::load('ltsacademy');
$settings    = $themeconfig->settings;

// Helper: get file URL from theme setting.
function ltsacademy_get_file_url($themeconfig, $filearea) {
    $url = $themeconfig->setting_file_url($filearea, $filearea);
    return $url ?: null;
}

// ─── Hero Section Data ────────────────────────────────────────────────────────
$heroheading  = !empty($settings->heroheading)  ? format_string($settings->heroheading)  : 'Développez vos compétences avec LTS ACADEMY';
$herosubtext  = !empty($settings->herosubtext)  ? format_string($settings->herosubtext)  : 'La formation qui convient le mieux à vos besoins professionnels.';
$herobtntext  = !empty($settings->herobtntext)  ? format_string($settings->herobtntext)  : 'Découvrir nos formations';
$herobtnurl   = !empty($settings->herobtnurl)   ? $settings->herobtnurl  : '/course/index.php';
$herobtn2text = !empty($settings->herobtn2text) ? format_string($settings->herobtn2text) : 'Se connecter';
$herobtn2url  = !empty($settings->herobtn2url)  ? $settings->herobtn2url : '/login/index.php';
$heroimageurl = ltsacademy_get_file_url($themeconfig, 'heroimage');

// ─── Logo ─────────────────────────────────────────────────────────────────────
$logoimageurl = ltsacademy_get_file_url($themeconfig, 'logo');
$bannerimageurl = ltsacademy_get_file_url($themeconfig, 'bannerimage');

// ─── Moodle Courses ───────────────────────────────────────────────────────────
$maxcourses = !empty($settings->maxcourses) ? (int) $settings->maxcourses : 6;

// Fetch visible courses (exclude site course, id=1).
$allcourses  = get_courses('all', 'c.sortorder ASC', 'c.id,c.shortname,c.fullname,c.summary,c.visible');
$courses     = [];
$coursecount = 0;

foreach ($allcourses as $course) {
    if ($course->id == SITEID || !$course->visible) {
        continue;
    }
    if ($coursecount >= $maxcourses) {
        break;
    }

    // Get course image (course summary files area).
    $coursecontext = context_course::instance($course->id);
    $fs            = get_file_storage();
    $courseimageurl = '';

    $files = $fs->get_area_files($coursecontext->id, 'course', 'overviewfiles', false, 'filename', false);
    foreach ($files as $file) {
        $filename = $file->get_filename();
        if ($filename !== '.') {
            $courseimageurl = moodle_url::make_pluginfile_url(
                $file->get_contextid(),
                $file->get_component(),
                $file->get_filearea(),
                null,
                $file->get_filepath(),
                $filename
            )->out();
            break;
        }
    }

    // Truncate summary.
    $summary = strip_tags($course->summary);
    if (strlen($summary) > 120) {
        $summary = substr($summary, 0, 117) . '...';
    }

    $courses[] = [
        'id'          => $course->id,
        'title'       => format_string($course->fullname),
        'shortname'   => $course->shortname,
        'summary'     => $summary,
        'imageurl'    => $courseimageurl ?: '',
        'hasimage'    => !empty($courseimageurl),
        'courseurl'   => (new moodle_url('/course/view.php', ['id' => $course->id]))->out(),
    ];
    $coursecount++;
}

if (empty($courses)) {
    // Populate with 6 premium fallback courses from the Angular web app.
    $courses = [
        [
            'id'          => 'rh',
            'title'       => 'Ressources Humaines',
            'summary'     => 'Gérez le capital humain et développez les talents (recrutement, paie, relations sociales...).',
            'imageurl'    => 'https://images.unsplash.com/photo-1521791136064-7986c2920216?ixlib=rb-4.0.3&auto=format&fit=crop&w=1469&q=80',
            'hasimage'    => true,
            'courseurl'   => '#formations',
        ],
        [
            'id'          => 'marketing',
            'title'       => 'Marketing Digital',
            'summary'     => 'Devenez un expert en stratégie digitale (webmarketing, SEO, community management, réseaux sociaux...).',
            'imageurl'    => 'https://images.unsplash.com/photo-1533750516457-a7f992034fec?ixlib=rb-4.0.3&auto=format&fit=crop&w=1470&q=80',
            'hasimage'    => true,
            'courseurl'   => '#formations',
        ],
        [
            'id'          => 'mecanique',
            'title'       => 'Génie Mécanique',
            'summary'     => 'Concevez et maintenez les systèmes mécaniques de demain (modélisation 3D, SolidWorks, AutoCAD...).',
            'imageurl'    => 'https://ft.univ-tlemcen.dz/assets/uploads/_Images/D%C3%A9partements/mechanic_unsa_arequipa_peru_02-878x426.jpg',
            'hasimage'    => true,
            'courseurl'   => '#formations',
        ],
        [
            'id'          => 'design',
            'title'       => 'Infographie et Design',
            'summary'     => 'Exprimez votre créativité à travers le design graphique (suite Adobe Photoshop, Illustrator, InDesign...).',
            'imageurl'    => 'https://images.unsplash.com/photo-1626785774573-4b799312c95d?ixlib=rb-4.0.3&auto=format&fit=crop&w=1470&q=80',
            'hasimage'    => true,
            'courseurl'   => '#formations',
        ],
        [
            'id'          => 'paramedical',
            'title'       => 'Formation Paramédicale',
            'summary'     => 'Devenez un professionnel de santé compétent et qualifié (aide-soignant, infirmier auxiliaire...).',
            'imageurl'    => 'https://lafactory.ma/wp-content/uploads/2020/05/Paramedical-1024x768.jpeg',
            'hasimage'    => true,
            'courseurl'   => '#formations',
        ],
        [
            'id'          => 'finance',
            'title'       => 'Comptabilité et Finance',
            'summary'     => 'Maîtrisez les chiffres clés de l\'entreprise (comptabilité générale, analytique, gestion financière...).',
            'imageurl'    => 'https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?ixlib=rb-4.0.3&auto=format&fit=crop&w=1470&q=80',
            'hasimage'    => true,
            'courseurl'   => '#formations',
        ],
    ];
}

$hascourses         = !empty($courses);
$coursesviewallurl  = !empty($settings->coursesviewallurl) ? $settings->coursesviewallurl : '/course/index.php';

// ─── Contact Section ──────────────────────────────────────────────────────────
$showcontactform = isset($settings->showcontactform) ? (bool) $settings->showcontactform : true;
$contacturl      = (new moodle_url('/theme/ltsacademy/contact.php'))->out();

// ─── Footer Data ─────────────────────────────────────────────────────────────
$footertagline  = !empty($settings->footertagline)  ? format_string($settings->footertagline)  : 'Formez-vous aux métiers de demain.';
$footeraddress  = !empty($settings->footeraddress)  ? format_string($settings->footeraddress)  : 'Berrechid, Maroc — Centre Ville';
$footerphone    = !empty($settings->footerphone)    ? format_string($settings->footerphone)    : '0660-356877';
$footeremail    = !empty($settings->footeremail)    ? format_string($settings->footeremail)    : 'contact@ltsacademy.ma';
$footerfacebook = !empty($settings->footerfacebook) ? $settings->footerfacebook : '#';
$footerlinkedin = !empty($settings->footerlinkedin) ? $settings->footerlinkedin : '#';
$footertwitter  = !empty($settings->footertwitter)  ? $settings->footertwitter  : '#';
$footercopyright = !empty($settings->footercopyright) ? format_string($settings->footercopyright) : '© 2025 LTS Academy. Tous droits réservés.';

// ─── AI Chatbot ───────────────────────────────────────────────────────────────
$showchatbot   = isset($settings->showchatbot) ? (bool) $settings->showchatbot : true;
$geminiapikey  = !empty($settings->geminiapikey) ? $settings->geminiapikey : 'AIzaSyBfXmZpW-SGcBE43I9kHXfYIbuUbBbnN3Y';
$aiavatarimageurl = ltsacademy_get_file_url($themeconfig, 'aiavatar');

// ─── Navigation / Drawers (inherited from Boost) ──────────────────────────────
$addblockbutton = $OUTPUT->addblockbutton();

if (isloggedin()) {
    $courseindexopen = (get_user_preferences('drawer-open-index', true) == true);
    $blockdraweropen = (get_user_preferences('drawer-open-block') == true);
} else {
    $courseindexopen = false;
    $blockdraweropen = false;
}

if (defined('BEHAT_SITE_RUNNING') && get_user_preferences('behat_keep_drawer_closed') != 1) {
    $blockdraweropen = true;
}

$extraclasses = ['uses-drawers', 'ltsacademy-frontpage'];
if ($courseindexopen) {
    $extraclasses[] = 'drawer-open-index';
}

$blockshtml  = $OUTPUT->blocks('side-pre');
$hasblocks   = (strpos($blockshtml, 'data-block=') !== false || !empty($addblockbutton));
if (!$hasblocks) {
    $blockdraweropen = false;
}

$courseindex = core_course_drawer();
if (!$courseindex) {
    $courseindexopen = false;
}

$bodyattributes       = $OUTPUT->body_attributes($extraclasses);
$forceblockdraweropen = $OUTPUT->firstview_fakeblocks();

$secondarynavigation  = false;
$overflow             = '';
if ($PAGE->has_secondary_navigation()) {
    $tablistnav = $PAGE->has_tablist_secondary_navigation();
    $moremenu   = new \core\navigation\output\more_menu($PAGE->secondarynav, 'nav-tabs', true, $tablistnav);
    $secondarynavigation = $moremenu->export_for_template($OUTPUT);
    $overflowdata = $PAGE->secondarynav->get_overflow_menu_data();
    if (!is_null($overflowdata)) {
        $overflow = $overflowdata->export_for_template($OUTPUT);
    }
}

$primary     = new core\navigation\output\primary($PAGE);
$renderer    = $PAGE->get_renderer('core');
$primarymenu = $primary->export_for_template($renderer);

$buildregionmainsettings = !$PAGE->include_region_main_settings_in_header_actions()
    && !$PAGE->has_secondary_navigation();
$regionmainsettingsmenu  = $buildregionmainsettings ? $OUTPUT->region_main_settings_menu() : false;

$header        = $PAGE->activityheader;
$headercontent = $header->export_for_template($renderer);

// ─── Template Context ─────────────────────────────────────────────────────────
$templatecontext = [
    // Boost inherited data.
    'sitename'                => format_string($SITE->shortname, true, ['context' => context_course::instance(SITEID), 'escape' => false]),
    'output'                  => $OUTPUT,
    'sidepreblocks'           => $blockshtml,
    'hasblocks'               => $hasblocks,
    'bodyattributes'          => $bodyattributes,
    'courseindexopen'         => $courseindexopen,
    'blockdraweropen'         => $blockdraweropen,
    'courseindex'             => $courseindex,
    'primarymoremenu'         => $primarymenu['moremenu'],
    'secondarymoremenu'       => $secondarynavigation ?: false,
    'mobileprimarynav'        => $primarymenu['mobileprimarynav'],
    'usermenu'                => $primarymenu['user'],
    'langmenu'                => $primarymenu['lang'],
    'forceblockdraweropen'    => $forceblockdraweropen,
    'regionmainsettingsmenu'  => $regionmainsettingsmenu,
    'hasregionmainsettingsmenu' => !empty($regionmainsettingsmenu),
    'overflow'                => $overflow,
    'headercontent'           => $headercontent,
    'addblockbutton'          => $addblockbutton,

    // LTS Academy custom data.
    'heroheading'             => $heroheading,
    'herosubtext'             => $herosubtext,
    'herobtntext'             => $herobtntext,
    'herobtnurl'              => $herobtnurl,
    'herobtn2text'            => $herobtn2text,
    'herobtn2url'             => $herobtn2url,
    'heroimageurl'            => $heroimageurl,
    'hasheroimage'            => !empty($heroimageurl),
    'logoimageurl'            => $logoimageurl,
    'haslogo'                 => !empty($logoimageurl),
    'bannerimageurl'          => $bannerimageurl,
    'hasbannerimage'          => !empty($bannerimageurl),

    // Courses.
    'courses'                 => $courses,
    'hascourses'              => $hascourses,
    'coursesviewallurl'       => $coursesviewallurl,

    // Moodle main content area (required by Moodle — renders blocks & page content).
    'main_content'            => $OUTPUT->main_content(),

    // Contact.
    'showcontactform'         => $showcontactform,
    'contacturl'              => $contacturl,
    'sesskey'                 => sesskey(),

    // Footer.
    'footertagline'           => $footertagline,
    'footeraddress'           => $footeraddress,
    'footerphone'             => $footerphone,
    'footeremail'             => $footeremail,
    'footerfacebook'          => $footerfacebook,
    'footerlinkedin'          => $footerlinkedin,
    'footertwitter'           => $footertwitter,
    'footercopyright'         => $footercopyright,

    // Chatbot.
    'showchatbot'             => $showchatbot,
    'geminiapikey'            => $geminiapikey,
    'aiavatarimageurl'        => $aiavatarimageurl,
    'hasaiavatar'             => !empty($aiavatarimageurl),

    // User login state.
    'isloggedin'              => isloggedin(),
    'logouturl'               => (new moodle_url('/login/logout.php', ['sesskey' => sesskey()]))->out(),
    'loginurl'                => (new moodle_url('/login/index.php'))->out(),
    'dashboardurl'            => (new moodle_url('/my/'))->out(),
    'wwwroot'                 => $CFG->wwwroot,
];

// ─── Require AMD JavaScript modules ──────────────────────────────────────────
// Homepage interactions (scroll reveal, contact form, language card).
$PAGE->requires->js_call_amd('theme_ltsacademy/homepage', 'init');

// AI Chatbot (only load if enabled and API key set).
if ($showchatbot) {
    $PAGE->requires->js_call_amd('theme_ltsacademy/chatbot', 'init');
}

echo $OUTPUT->render_from_template('theme_ltsacademy/ltsacademy-frontpage', $templatecontext);

