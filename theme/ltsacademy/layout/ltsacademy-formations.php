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
 * LTS Academy theme custom formations page layout controller.
 *
 * @package    theme_ltsacademy
 * @copyright  2024 LTS Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$themeconfig = theme_config::load('ltsacademy');
$settings    = $themeconfig->settings;

// Helper: get file URL from theme setting.
function ltsacademy_form_get_file_url($themeconfig, $filearea) {
    $url = $themeconfig->setting_file_url($filearea, $filearea);
    return $url ?: null;
}

$logoimageurl = ltsacademy_form_get_file_url($themeconfig, 'logo');

// All 12 Formations from LTS App
$formations = [
    [
        'id'          => 'rh',
        'title'       => 'Ressources Humaines',
        'subtitle'    => 'Gérez le capital humain et développez les talents.',
        'summary'     => 'Recrutement, paie, relations sociales, formation, et administration du personnel.',
        'presentation'=> 'Apprenez à gérer le recrutement, la paie, la formation et les relations sociales au sein de l\'entreprise.',
        'imageurl'    => 'https://images.unsplash.com/photo-1521791136064-7986c2920216?ixlib=rb-4.0.3&auto=format&fit=crop&w=1469&q=80',
        'category'    => 'Management'
    ],
    [
        'id'          => 'marketing',
        'title'       => 'Marketing Digital',
        'subtitle'    => 'Devenez un expert en stratégie digitale et communication.',
        'summary'     => 'SEO, marketing de contenu, réseaux sociaux, publicité payante et web analytics.',
        'presentation'=> 'Cette formation vous permet de maîtriser les outils du webmarketing, du SEO aux réseaux sociaux, pour booster la visibilité d\'une entreprise.',
        'imageurl'    => 'https://images.unsplash.com/photo-1533750516457-a7f992034fec?ixlib=rb-4.0.3&auto=format&fit=crop&w=1470&q=80',
        'category'    => 'Management'
    ],
    [
        'id'          => 'mecanique',
        'title'       => 'Génie Mécanique',
        'subtitle'    => 'Concevez et maintenez les systèmes mécaniques de demain.',
        'summary'     => 'CAO/DAO SolidWorks, AutoCAD, automatisation et maintenance industrielle.',
        'presentation'=> 'Notre formation couvre la conception, la fabrication et la maintenance des systèmes mécaniques. Vous apprendrez à utiliser des logiciels de CAO.',
        'imageurl'    => 'https://ft.univ-tlemcen.dz/assets/uploads/_Images/D%C3%A9partements/mechanic_unsa_arequipa_peru_02-878x426.jpg',
        'category'    => 'Industrie'
    ],
    [
        'id'          => 'design',
        'title'       => 'Infographie et Design',
        'subtitle'    => 'Exprimez votre créativité à travers le design graphique.',
        'summary'     => 'Maîtrise de la suite Adobe (Photoshop, Illustrator, InDesign) et identité visuelle.',
        'presentation'=> 'Maîtrisez la suite Adobe (Photoshop, Illustrator, InDesign) et les principes du design pour créer des visuels professionnels.',
        'imageurl'    => 'https://images.unsplash.com/photo-1626785774573-4b799312c95d?ixlib=rb-4.0.3&auto=format&fit=crop&w=1470&q=80',
        'category'    => 'Technologie'
    ],
    [
        'id'          => 'paramedical',
        'title'       => 'Formation Paramédicale',
        'subtitle'    => 'Devenez un professionnel de santé compétent et qualifié.',
        'summary'     => 'Aide-soignant, infirmier auxiliaire, soins à domicile et secourisme.',
        'presentation'=> 'Le secteur paramédical recrute activement. Notre formation vous prépare aux métiers de soins et d\'assistance dans un environnement exigeant.',
        'imageurl'    => 'https://lafactory.ma/wp-content/uploads/2020/05/Paramedical-1024x768.jpeg',
        'category'    => 'Santé'
    ],
    [
        'id'          => 'finance',
        'title'       => 'Comptabilité et Finance',
        'subtitle'    => 'Maîtrisez les chiffres clés et la gestion financière.',
        'summary'     => 'Comptabilité générale, fiscale, analytique et gestion de trésorerie.',
        'presentation'=> 'Une formation complète pour devenir autonome en comptabilité générale, déclarations fiscales et gestion financière.',
        'imageurl'    => 'https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?ixlib=rb-4.0.3&auto=format&fit=crop&w=1470&q=80',
        'category'    => 'Management'
    ],
    [
        'id'          => 'web-development',
        'title'       => 'Développement Web Full-Stack',
        'subtitle'    => 'Créez des applications web modernes et performantes.',
        'summary'     => 'HTML, CSS, JavaScript, React, Node.js, bases de données et déploiement.',
        'presentation'=> 'Acquérez les compétences indispensables pour concevoir et développer des sites web dynamiques et des applications web modernes de A à Z.',
        'imageurl'    => 'https://ccsav.ca/wp-content/uploads/2022/11/metier-developpeur-web-1.jpg',
        'category'    => 'Technologie'
    ],
    [
        'id'          => 'langues',
        'title'       => 'Formations en Langues',
        'subtitle'    => 'Communiquez à l\'international sans frontières.',
        'summary'     => 'Anglais (TOEFL/IELTS), Français professionnel et Allemand.',
        'presentation'=> 'La maîtrise des langues est cruciale pour votre employabilité. Nos cours pratiques et intensifs s\'adaptent à votre niveau.',
        'imageurl'    => 'https://www.ipac-traductions.com/wp-content/uploads/2021/05/langues-les-plus-parlees-1080x675-1.jpg',
        'category'    => 'Général'
    ],
    [
        'id'          => 'systemes-reseaux',
        'title'       => 'Systèmes et Réseaux',
        'subtitle'    => 'Assurez la sécurité et la connectivité des parcs informatiques.',
        'summary'     => 'Administration Linux/Windows, sécurité, cloud computing et routage CISCO.',
        'presentation'=> 'Apprenez à installer, configurer, dépanner et sécuriser les serveurs et les infrastructures de communication des entreprises.',
        'imageurl'    => 'https://images.unsplash.com/photo-1558494949-ef526b0042a0?ixlib=rb-4.0.3&auto=format&fit=crop&w=1470&q=80',
        'category'    => 'Technologie'
    ],
    [
        'id'          => 'logistique',
        'title'       => 'Logistique et Transport',
        'subtitle'    => 'Optimisez les flux et la supply chain industrielle.',
        'summary'     => 'Gestion d\'entrepôt, douane, gestion des stocks et transports nationaux.',
        'presentation'=> 'Maîtrisez les techniques logistiques et douanières pour fluidifier la circulation des marchandises au niveau national et international.',
        'imageurl'    => 'https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?ixlib=rb-4.0.3&auto=format&fit=crop&w=1470&q=80',
        'category'    => 'Industrie'
    ],
    [
        'id'          => 'big-data',
        'title'       => 'Big Data et IA',
        'subtitle'    => 'Exploitez la puissance des données pour piloter les décisions.',
        'summary'     => 'Python, SQL, algorithms de Machine Learning et data visualization.',
        'presentation'=> 'Entrez dans le domaine de la science des données et découvrez comment concevoir des modèles d\'apprentissage automatique.',
        'imageurl'    => 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?ixlib=rb-4.0.3&auto=format&fit=crop&w=1470&q=80',
        'category'    => 'Technologie'
    ],
    [
        'id'          => 'qhse',
        'title'       => 'Responsable QHSE',
        'subtitle'    => 'Garantissez la qualité et la sécurité en milieu de travail.',
        'summary'     => 'Normes ISO 9001, 14001, 45001, audit et prévention des risques.',
        'presentation'=> 'Le Responsable QHSE définit et met en œuvre les politiques de management de la qualité, d\'hygiène industrielle, et d\'environnement.',
        'imageurl'    => 'https://www.onisep.fr/var/onisep/storage/images/7/2/2/4/9564227-7-fre-FR/530f0f6cd392-charge-e-hygiene-securite-environnement-HSE.jpg',
        'category'    => 'Industrie'
    ]
];

// Footer settings
$footertagline  = !empty($settings->footertagline)  ? format_string($settings->footertagline)  : 'Formez-vous aux métiers de demain.';
$footeraddress  = !empty($settings->footeraddress)  ? format_string($settings->footeraddress)  : 'Berrechid, Maroc — Centre Ville';
$footerphone    = !empty($settings->footerphone)    ? format_string($settings->footerphone)    : '0660-356877';
$footeremail    = !empty($settings->footeremail)    ? format_string($settings->footeremail)    : 'contact@ltsacademy.ma';
$footerfacebook = !empty($settings->footerfacebook) ? $settings->footerfacebook : '#';
$footerlinkedin = !empty($settings->footerlinkedin) ? $settings->footerlinkedin : '#';
$footertwitter  = !empty($settings->footertwitter)  ? $settings->footertwitter  : '#';
$footercopyright = !empty($settings->footercopyright) ? format_string($settings->footercopyright) : '© 2025 LTS Academy. Tous droits réservés.';

$showchatbot   = isset($settings->showchatbot) ? (bool) $settings->showchatbot : true;
$geminiapikey  = !empty($settings->geminiapikey) ? $settings->geminiapikey : 'AIzaSyBfXmZpW-SGcBE43I9kHXfYIbuUbBbnN3Y';
$aiavatarimageurl = ltsacademy_form_get_file_url($themeconfig, 'aiavatar');

$primary     = new core\navigation\output\primary($PAGE);
$renderer    = $PAGE->get_renderer('core');
$primarymenu = $primary->export_for_template($renderer);

$extraclasses = ['uses-drawers', 'ltsacademy-frontpage'];
$bodyattributes = $OUTPUT->body_attributes($extraclasses);

$templatecontext = [
    'sitename'                => format_string($SITE->shortname, true, ['context' => context_course::instance(SITEID), 'escape' => false]),
    'output'                  => $OUTPUT,
    'bodyattributes'          => $bodyattributes,
    'logoimageurl'            => $logoimageurl,
    'haslogo'                 => !empty($logoimageurl),
    'primarymoremenu'         => $primarymenu['moremenu'],
    'secondarymoremenu'       => false,
    'mobileprimarynav'        => $primarymenu['mobileprimarynav'],
    'usermenu'                => $primarymenu['user'],
    'langmenu'                => $primarymenu['lang'],
    'isloggedin'              => isloggedin(),
    'logouturl'               => (new moodle_url('/login/logout.php', ['sesskey' => sesskey()]))->out(),
    'loginurl'                => (new moodle_url('/login/index.php'))->out(),
    'dashboardurl'            => (new moodle_url('/my/'))->out(),
    'wwwroot'                 => $CFG->wwwroot,
    'formations'              => $formations,

    // Footer
    'footertagline'           => $footertagline,
    'footeraddress'           => $footeraddress,
    'footerphone'             => $footerphone,
    'footeremail'             => $footeremail,
    'footerfacebook'          => $footerfacebook,
    'footerlinkedin'          => $footerlinkedin,
    'footertwitter'           => $footertwitter,
    'footercopyright'         => $footercopyright,

    // Chatbot
    'showchatbot'             => $showchatbot,
    'geminiapikey'            => $geminiapikey,
    'aiavatarimageurl'        => $aiavatarimageurl,
    'hasaiavatar'             => !empty($aiavatarimageurl),
];

// Require AMD modules
$PAGE->requires->js_call_amd('theme_ltsacademy/homepage', 'init');
if ($showchatbot) {
    $PAGE->requires->js_call_amd('theme_ltsacademy/chatbot', 'init');
}

echo $OUTPUT->render_from_template('theme_ltsacademy/ltsacademy-formations', $templatecontext);
