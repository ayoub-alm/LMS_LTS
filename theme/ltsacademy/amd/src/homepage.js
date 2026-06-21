// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * LTS Academy Homepage interactions — AMD module
 * Handles: scroll reveal, contact form AJAX, language level card, navbar effects.
 *
 * @module     theme_ltsacademy/homepage
 * @copyright  2024 LTS Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/ajax', 'core/notification', 'core/log'], function($, Ajax, Notification, log) {
    'use strict';

    // ─── Scroll Reveal ────────────────────────────────────────────────────────
    function initScrollReveal() {
        var $elements = $('.lts-reveal');
        if (!$elements.length) { return; }

        // Immediately reveal anything above the fold
        checkReveal();

        // Debounced scroll handler
        var scrollTimer;
        $(window).on('scroll.ltsreveal', function() {
            clearTimeout(scrollTimer);
            scrollTimer = setTimeout(checkReveal, 60);
        });
    }

    function checkReveal() {
        var windowBottom = $(window).scrollTop() + $(window).height();
        $('.lts-reveal:not(.lts-visible)').each(function() {
            var elemTop = $(this).offset().top;
            if (elemTop < windowBottom - 80) {
                var $el = $(this);
                // Stagger siblings
                var delay = $(this).index('.lts-reveal') * 0.05;
                setTimeout(function() {
                    $el.addClass('lts-visible');
                }, delay * 1000);
            }
        });
    }

    // ─── Contact Form ─────────────────────────────────────────────────────────
    function initContactForm() {
        var $form = $('#lts-contact-form');
        if (!$form.length) { return; }

        $form.on('submit', function(e) {
            e.preventDefault();
            submitContactForm($form);
        });
    }

    function submitContactForm($form) {
        var $submit = $('#contact-submit');
        var $success = $('#lts-contact-success');
        var $error   = $('#lts-contact-error');
        var $errMsg  = $('#lts-contact-error-msg');

        // Reset alerts
        $success.hide();
        $error.hide();

        // Basic client-side validation
        var email   = $('#contact-email').val().trim();
        var name    = $('#contact-name').val().trim();
        var objet   = $('#contact-objet').val().trim();
        var message = $('#contact-message').val().trim();

        if (!email || !name || !objet || !message) {
            $errMsg.text('Veuillez remplir tous les champs obligatoires (*).');
            $error.show();
            return;
        }

        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            $errMsg.text('Veuillez entrer une adresse email valide.');
            $error.show();
            return;
        }

        // Loading state
        $submit.prop('disabled', true).html(
            '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-2" style="animation:spin 1s linear infinite"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>' +
            'Envoi en cours...'
        );

        var config = window.ltsAcademyConfig || {};
        var contactUrl = config.contactUrl || '';
        var sesskey    = config.sesskey    || '';

        $.ajax({
            url: contactUrl,
            type: 'POST',
            data: {
                sesskey: sesskey,
                email:   email,
                phone:   $('#contact-phone').val().trim(),
                name:    name,
                ville:   $('#contact-ville').val().trim(),
                objet:   objet,
                message: message
            },
            dataType: 'json',
            timeout: 15000,
            success: function(response) {
                if (response && response.success) {
                    $form[0].reset();
                    $success.show();
                    $('html, body').animate({ scrollTop: $success.offset().top - 100 }, 400);
                } else {
                    var errText = (response && response.error) ? response.error : 'Une erreur s\'est produite.';
                    $errMsg.text(errText);
                    $error.show();
                }
            },
            error: function(xhr, status) {
                var errText = 'Une erreur s\'est produite. ';
                if (status === 'timeout') {
                    errText += 'La connexion a expiré.';
                } else {
                    errText += 'Veuillez réessayer ou nous appeler au 0660-356877.';
                }
                $errMsg.text(errText);
                $error.show();
                log.warn('Contact form error:', xhr.status, status);
            },
            complete: function() {
                $submit.prop('disabled', false).html(
                    '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" class="me-2"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>' +
                    'Envoyer le message'
                );
            }
        });
    }

    // ─── Language Level Detection Card ────────────────────────────────────────
    var LANG_LEVELS = {
        'Français': {
            level: 'B2 – Avancé',
            percent: 72,
            msg: '🇫🇷 Niveau B2 — Vous maîtrisez bien le français ! Des formations avancées sont disponibles.'
        },
        'English': {
            level: 'B1 – Intermédiaire',
            percent: 56,
            msg: '🇬🇧 Niveau B1 — Bon niveau ! Nos formations Business English peuvent vous propulser au C1.'
        },
        'العربية': {
            level: 'C1 – Courant',
            percent: 90,
            msg: '🇲🇦 Niveau C1 — Excellent niveau en arabe ! Découvrez nos formations bilingues.'
        },
        'Español': {
            level: 'A2 – Débutant',
            percent: 30,
            msg: '🇪🇸 Niveau A2 — Vous débutez en espagnol. Nos cours intensifs sont faits pour vous !'
        }
    };

    function initLanguageCard() {
        // Language option buttons
        $(document).on('click', '.lts-lang-btn', function() {
            var lang = $(this).attr('data-lang');
            showLangResult(lang);
        });

        // Reset button
        $(document).on('click', '#lts-lang-reset', function() {
            resetLangCard();
        });
    }

    function showLangResult(lang) {
        var data = LANG_LEVELS[lang];
        if (!data) { return; }

        var $options = $('#lts-lang-options');
        var $result  = $('#lts-lang-result');
        var $bar     = $('#lts-lang-bar');
        var $text    = $('#lts-lang-result-text');
        var $prompt  = $('#lts-lang-prompt');

        // Hide options, show result
        $options.fadeOut(200, function() {
            $prompt.text('Résultat pour : ' + lang);
            $bar.css('width', '0');
            $text.text('');
            $result.fadeIn(200);

            // Animate progress bar
            setTimeout(function() {
                $bar.css('width', data.percent + '%');
            }, 100);

            // Show text after bar animates
            setTimeout(function() {
                $text.html('<strong>' + data.level + '</strong><br><span style="font-size:0.88rem;opacity:0.85;">' + data.msg + '</span>');
            }, 600);
        });
    }

    function resetLangCard() {
        var $options = $('#lts-lang-options');
        var $result  = $('#lts-lang-result');
        var $prompt  = $('#lts-lang-prompt');

        $result.fadeOut(200, function() {
            $prompt.text('Choisissez votre langue :');
            $options.fadeIn(200);
        });
    }

    // ─── Smooth Scroll for Anchor links ──────────────────────────────────────
    function initSmoothScroll() {
        $(document).on('click', 'a[href^="#"]', function(e) {
            var target = $(this).attr('href');
            if (target === '#' || target === '#0') { return; }
            var $target = $(target);
            if ($target.length) {
                e.preventDefault();
                $('html, body').animate({
                    scrollTop: $target.offset().top - 80
                }, 600, 'swing');
            }
        });
    }

    // ─── Hero Parallax ───────────────────────────────────────────────────────
    function initHeroParallax() {
        var $hero = $('.lts-hero-section');
        if (!$hero.length) { return; }

        $(window).on('scroll.ltsparallax', function() {
            var scrollY = $(window).scrollTop();
            // Subtle parallax on particles
            $('.lts-particle-1').css('transform', 'translateY(' + (scrollY * 0.15) + 'px)');
            $('.lts-particle-2').css('transform', 'translateY(' + (scrollY * -0.1) + 'px)');
        });
    }

    // ─── Init All ─────────────────────────────────────────────────────────────
    function init() {
        // Wait for DOM ready
        $(function() {
            initScrollReveal();
            initContactForm();
            initLanguageCard();
            initSmoothScroll();
            initHeroParallax();
            log.debug('LTS Academy homepage.js initialised');
        });
    }

    return {
        init: init
    };
});
