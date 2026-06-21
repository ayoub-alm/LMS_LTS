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
 * Contact form handler for LTS Academy theme.
 * Accepts POST data, validates it, and sends an email to the configured address.
 *
 * @package    theme_ltsacademy
 * @copyright  2024 LTS Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Bootstrap Moodle without requiring login.
define('NO_MOODLE_COOKIES', false);
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/moodlelib.php');

// ─── Only accept POST ──────────────────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

// ─── CSRF protection ──────────────────────────────────────────────────────────
$sesskey = required_param('sesskey', PARAM_RAW);
if (!confirm_sesskey($sesskey)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid session key.']);
    exit;
}

// ─── Rate limiting (simple session-based) ─────────────────────────────────────
$sessionkey  = 'ltsacademy_contact_last';
$cooldown    = 60; // seconds between submissions
$lastsubmit  = isset($_SESSION[$sessionkey]) ? (int) $_SESSION[$sessionkey] : 0;

if ((time() - $lastsubmit) < $cooldown) {
    $remaining = $cooldown - (time() - $lastsubmit);
    http_response_code(429);
    echo json_encode([
        'success' => false,
        'error'   => "Veuillez attendre {$remaining} secondes avant d'envoyer un nouveau message."
    ]);
    exit;
}

// ─── Collect and sanitise inputs ──────────────────────────────────────────────
$email   = trim(optional_param('email',   '', PARAM_EMAIL));
$phone   = trim(optional_param('phone',   '', PARAM_TEXT));
$name    = trim(optional_param('name',    '', PARAM_TEXT));
$ville   = trim(optional_param('ville',   '', PARAM_TEXT));
$objet   = trim(optional_param('objet',   '', PARAM_TEXT));
$message = trim(optional_param('message', '', PARAM_TEXT));

// ─── Validation ───────────────────────────────────────────────────────────────
$errors = [];

if (empty($email)) {
    $errors[] = 'L\'adresse email est obligatoire.';
} elseif (!validate_email($email)) {
    $errors[] = 'L\'adresse email est invalide.';
}

if (empty($name)) {
    $errors[] = 'Le nom est obligatoire.';
} elseif (strlen($name) < 2) {
    $errors[] = 'Le nom doit comporter au moins 2 caractères.';
}

if (empty($objet)) {
    $errors[] = 'L\'objet est obligatoire.';
}

if (empty($message)) {
    $errors[] = 'Le message est obligatoire.';
} elseif (strlen($message) < 10) {
    $errors[] = 'Le message doit comporter au moins 10 caractères.';
} elseif (strlen($message) > 5000) {
    $errors[] = 'Le message ne doit pas dépasser 5000 caractères.';
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => implode(' ', $errors)]);
    exit;
}

// ─── Build email ──────────────────────────────────────────────────────────────
$themeconfig = theme_config::load('ltsacademy');
$contactemail = !empty($themeconfig->settings->contactemail)
    ? $themeconfig->settings->contactemail
    : $CFG->noreplyaddress;

// Build recipient user object.
$recipient = new stdClass();
$recipient->email     = $contactemail;
$recipient->firstname = 'LTS Academy';
$recipient->lastname  = 'Contact';
$recipient->maildisplay = 0;
$recipient->mailformat  = 1; // HTML email

// Build sender user object (from visitor).
$sender = new stdClass();
$sender->email     = $email;
$sender->firstname = $name;
$sender->lastname  = '';
$sender->maildisplay = 0;
$sender->mailformat  = 1;

// ─── Email subject ────────────────────────────────────────────────────────────
$subject = '[LTS Academy Contact] ' . clean_text($objet, FORMAT_PLAIN);

// ─── Email HTML body ──────────────────────────────────────────────────────────
$htmlbody = '
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: Inter, Roboto, sans-serif; color: #1e293b; max-width: 640px; margin: 0 auto; padding: 24px;">

  <div style="background: linear-gradient(135deg, #1e3a8a, #2563eb); border-radius: 12px; padding: 32px; text-align: center; margin-bottom: 24px;">
    <h1 style="color: white; margin: 0; font-size: 24px; font-weight: 800;">LTS ACADEMY</h1>
    <p style="color: rgba(255,255,255,0.75); margin: 6px 0 0; font-size: 14px;">Nouveau message via le formulaire de contact</p>
  </div>

  <div style="background: #f8fafc; border-radius: 12px; padding: 28px; margin-bottom: 20px; border: 1px solid #e2e8f0;">
    <h2 style="color: #1e3a8a; font-size: 18px; margin: 0 0 20px;">Informations du contact</h2>

    <table style="width: 100%; border-collapse: collapse;">
      <tr>
        <td style="padding: 8px 0; font-weight: 600; color: #64748b; width: 120px;">Nom :</td>
        <td style="padding: 8px 0; color: #1e293b;">' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</td>
      </tr>
      <tr>
        <td style="padding: 8px 0; font-weight: 600; color: #64748b;">Email :</td>
        <td style="padding: 8px 0;"><a href="mailto:' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '" style="color: #2563eb;">' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '</a></td>
      </tr>
      ' . (!empty($phone) ? '
      <tr>
        <td style="padding: 8px 0; font-weight: 600; color: #64748b;">Téléphone :</td>
        <td style="padding: 8px 0; color: #1e293b;">' . htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') . '</td>
      </tr>' : '') . '
      ' . (!empty($ville) ? '
      <tr>
        <td style="padding: 8px 0; font-weight: 600; color: #64748b;">Ville :</td>
        <td style="padding: 8px 0; color: #1e293b;">' . htmlspecialchars($ville, ENT_QUOTES, 'UTF-8') . '</td>
      </tr>' : '') . '
      <tr>
        <td style="padding: 8px 0; font-weight: 600; color: #64748b;">Objet :</td>
        <td style="padding: 8px 0; color: #1e293b;">' . htmlspecialchars($objet, ENT_QUOTES, 'UTF-8') . '</td>
      </tr>
    </table>
  </div>

  <div style="background: #ffffff; border-radius: 12px; padding: 28px; border: 1px solid #e2e8f0; border-left: 4px solid #1e3a8a;">
    <h3 style="color: #1e3a8a; font-size: 16px; margin: 0 0 14px;">Message :</h3>
    <p style="white-space: pre-wrap; line-height: 1.7; color: #1e293b; margin: 0;">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>
  </div>

  <div style="text-align: center; margin-top: 28px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
    <p style="font-size: 12px; color: #94a3b8; margin: 0;">
      Message reçu le ' . userdate(time(), '%d/%m/%Y à %H:%M') . '<br>
      Envoyé via le formulaire de contact du site LTS Academy Moodle
    </p>
  </div>

</body>
</html>';

// Plain text fallback.
$textbody = "Nouveau message de contact LTS Academy\n\n"
    . "Nom     : {$name}\n"
    . "Email   : {$email}\n"
    . (!empty($phone) ? "Tél     : {$phone}\n" : '')
    . (!empty($ville) ? "Ville   : {$ville}\n" : '')
    . "Objet   : {$objet}\n\n"
    . "Message :\n{$message}\n\n"
    . "---\nReçu le " . userdate(time(), '%d/%m/%Y à %H:%M');

// ─── Send the email ───────────────────────────────────────────────────────────
$sent = email_to_user($recipient, $sender, $subject, $textbody, $htmlbody);

if ($sent) {
    // Record submission time for rate limiting.
    $_SESSION[$sessionkey] = time();

    // Optionally log to Moodle event log.
    if (class_exists('core\event\base')) {
        // Simple log — could be replaced with a custom event.
        add_to_log(SITEID, 'theme_ltsacademy', 'contact_form', '', "From: {$name} <{$email}> — {$objet}");
    }

    echo json_encode(['success' => true, 'message' => 'Votre message a bien été envoyé.']);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'L\'envoi a échoué. Veuillez nous contacter directement au 0660-356877 ou contact@ltsacademy.ma.'
    ]);
}
exit;
