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
    var langTestState = {
        lang: '',
        index: 0,
        score: 0,
        answers: []
    };

    var LANG_TESTS = {
        'Français': {
            label: '🇫🇷 Français',
            course: 'Français professionnel',
            questions: [
                {
                    text: 'Comment vous présentez-vous dans un contexte professionnel ?',
                    answers: [
                        { text: 'Bonjour, je m\'appelle Samir et je travaille dans la qualité.', points: 4 },
                        { text: 'Bonjour, moi Samir, travail qualité.', points: 2 },
                        { text: 'Salut, Samir.', points: 1 }
                    ]
                },
                {
                    text: 'Choisissez la phrase correcte.',
                    answers: [
                        { text: 'Je suis intéressé par cette formation.', points: 4 },
                        { text: 'Je suis intéressant par cette formation.', points: 2 },
                        { text: 'Moi intéressé formation.', points: 1 }
                    ]
                },
                {
                    text: 'Quel mot complète la phrase : "Nous ___ une réunion demain matin."',
                    answers: [
                        { text: 'avons', points: 4 },
                        { text: 'sommes', points: 2 },
                        { text: 'avoir', points: 1 }
                    ]
                },
                {
                    text: 'Vous recevez un email important. Quelle réponse est la plus professionnelle ?',
                    answers: [
                        { text: 'Je vous remercie pour votre retour et reste disponible pour la suite.', points: 4 },
                        { text: 'Merci, je regarde.', points: 2 },
                        { text: 'OK.', points: 1 }
                    ]
                },
                {
                    text: 'Choisissez la meilleure formulation.',
                    answers: [
                        { text: 'Pourriez-vous me transmettre le programme, s\'il vous plaît ?', points: 4 },
                        { text: 'Envoyez-moi le programme.', points: 2 },
                        { text: 'Programme ?', points: 1 }
                    ]
                },
                {
                    text: 'Que signifie "atteindre un objectif" ?',
                    answers: [
                        { text: 'Réussir à réaliser un but précis.', points: 4 },
                        { text: 'Commencer un projet.', points: 2 },
                        { text: 'Changer de sujet.', points: 1 }
                    ]
                },
                {
                    text: 'Quel temps utilisez-vous pour parler d\'une action terminée hier ?',
                    answers: [
                        { text: 'Le passé composé : "j\'ai terminé".', points: 4 },
                        { text: 'Le futur : "je terminerai".', points: 1 },
                        { text: 'Le présent : "je termine".', points: 2 }
                    ]
                },
                {
                    text: 'Choisissez la phrase la plus naturelle.',
                    answers: [
                        { text: 'Même si le délai est court, nous pouvons adapter le planning.', points: 5 },
                        { text: 'Le délai est court, mais on peut faire.', points: 3 },
                        { text: 'Délai court, possible.', points: 1 }
                    ]
                },
                {
                    text: 'Dans une réunion, comment exprimer un désaccord poliment ?',
                    answers: [
                        { text: 'Je comprends votre point, toutefois je proposerais une autre approche.', points: 5 },
                        { text: 'Je ne suis pas d\'accord.', points: 3 },
                        { text: 'Non.', points: 1 }
                    ]
                },
                {
                    text: 'Quelle phrase montre un niveau avancé ?',
                    answers: [
                        { text: 'Cette formation me permettrait de consolider mes acquis et d\'évoluer professionnellement.', points: 5 },
                        { text: 'Cette formation est bonne pour mon travail.', points: 3 },
                        { text: 'Formation bien pour moi.', points: 1 }
                    ]
                }
            ]
        },
        'English': {
            label: '🇬🇧 English',
            course: 'Business English',
            questions: [
                {
                    text: 'Choose the best professional introduction.',
                    answers: [
                        { text: 'Hello, my name is Sara and I work in project management.', points: 4 },
                        { text: 'Hello, I Sara, work project.', points: 2 },
                        { text: 'Hi, Sara.', points: 1 }
                    ]
                },
                {
                    text: 'Choose the correct sentence.',
                    answers: [
                        { text: 'I am interested in this training program.', points: 4 },
                        { text: 'I interested in this training program.', points: 2 },
                        { text: 'Me want training.', points: 1 }
                    ]
                },
                {
                    text: 'Complete: "We ___ a meeting tomorrow morning."',
                    answers: [
                        { text: 'have', points: 4 },
                        { text: 'has', points: 2 },
                        { text: 'having', points: 1 }
                    ]
                },
                {
                    text: 'Which email reply is most professional?',
                    answers: [
                        { text: 'Thank you for your feedback. I remain available for the next steps.', points: 4 },
                        { text: 'Thanks, I check.', points: 2 },
                        { text: 'OK.', points: 1 }
                    ]
                },
                {
                    text: 'Choose the polite request.',
                    answers: [
                        { text: 'Could you please send me the program details?', points: 4 },
                        { text: 'Send me the program.', points: 2 },
                        { text: 'Program?', points: 1 }
                    ]
                },
                {
                    text: 'What does "to meet a deadline" mean?',
                    answers: [
                        { text: 'To finish something by the required date.', points: 4 },
                        { text: 'To start a project.', points: 2 },
                        { text: 'To cancel a meeting.', points: 1 }
                    ]
                },
                {
                    text: 'Which tense describes an action completed yesterday?',
                    answers: [
                        { text: 'Past simple: "I finished".', points: 4 },
                        { text: 'Future: "I will finish".', points: 1 },
                        { text: 'Present: "I finish".', points: 2 }
                    ]
                },
                {
                    text: 'Choose the most natural sentence.',
                    answers: [
                        { text: 'Although the deadline is tight, we can adjust the schedule.', points: 5 },
                        { text: 'The deadline is tight, but we can do.', points: 3 },
                        { text: 'Deadline tight, possible.', points: 1 }
                    ]
                },
                {
                    text: 'How do you politely disagree in a meeting?',
                    answers: [
                        { text: 'I see your point; however, I would suggest another approach.', points: 5 },
                        { text: 'I disagree.', points: 3 },
                        { text: 'No.', points: 1 }
                    ]
                },
                {
                    text: 'Which sentence sounds advanced?',
                    answers: [
                        { text: 'This course would help me strengthen my skills and progress professionally.', points: 5 },
                        { text: 'This course is good for my work.', points: 3 },
                        { text: 'Course good for me.', points: 1 }
                    ]
                }
            ]
        },
        'العربية': {
            label: '🇲🇦 العربية',
            course: 'العربية المهنية',
            questions: [
                {
                    text: 'اختر أفضل طريقة للتعريف بنفسك في العمل.',
                    answers: [
                        { text: 'مرحبا، اسمي سمير وأعمل في مجال الجودة.', points: 4 },
                        { text: 'أنا سمير، عمل جودة.', points: 2 },
                        { text: 'سمير.', points: 1 }
                    ]
                },
                {
                    text: 'اختر الجملة الصحيحة.',
                    answers: [
                        { text: 'أنا مهتم بهذه الدورة التدريبية.', points: 4 },
                        { text: 'أنا مهتم في هذه دورة.', points: 2 },
                        { text: 'أنا يريد دورة.', points: 1 }
                    ]
                },
                {
                    text: 'أكمل الجملة: "لدينا ___ غدا صباحا."',
                    answers: [
                        { text: 'اجتماع', points: 4 },
                        { text: 'يجتمع', points: 2 },
                        { text: 'اجتماعاتها', points: 1 }
                    ]
                },
                {
                    text: 'أي رد على البريد الإلكتروني يبدو أكثر مهنية؟',
                    answers: [
                        { text: 'أشكركم على ردكم، وأنا رهن إشارتكم للخطوات القادمة.', points: 4 },
                        { text: 'شكرا، سأرى.', points: 2 },
                        { text: 'حسنا.', points: 1 }
                    ]
                },
                {
                    text: 'اختر الطلب الأكثر تهذيبا.',
                    answers: [
                        { text: 'هل يمكنكم إرسال برنامج الدورة من فضلكم؟', points: 4 },
                        { text: 'أرسلوا لي البرنامج.', points: 2 },
                        { text: 'البرنامج؟', points: 1 }
                    ]
                },
                {
                    text: 'ما معنى "تحقيق هدف"؟',
                    answers: [
                        { text: 'النجاح في الوصول إلى نتيجة محددة.', points: 4 },
                        { text: 'بدء مشروع جديد.', points: 2 },
                        { text: 'تغيير الموضوع.', points: 1 }
                    ]
                },
                {
                    text: 'أي صيغة تناسب فعلا انتهى أمس؟',
                    answers: [
                        { text: 'الماضي: "أنهيت المهمة".', points: 4 },
                        { text: 'المستقبل: "سأنهي المهمة".', points: 1 },
                        { text: 'المضارع: "أنهي المهمة".', points: 2 }
                    ]
                },
                {
                    text: 'اختر الجملة الأكثر طبيعية.',
                    answers: [
                        { text: 'رغم أن المهلة قصيرة، يمكننا تعديل الجدول الزمني.', points: 5 },
                        { text: 'المهلة قصيرة لكن يمكن العمل.', points: 3 },
                        { text: 'مهلة قصيرة، ممكن.', points: 1 }
                    ]
                },
                {
                    text: 'كيف تعبر عن اختلاف الرأي باحترام؟',
                    answers: [
                        { text: 'أتفهم وجهة نظركم، لكنني أقترح مقاربة أخرى.', points: 5 },
                        { text: 'أنا غير موافق.', points: 3 },
                        { text: 'لا.', points: 1 }
                    ]
                },
                {
                    text: 'أي جملة تدل على مستوى متقدم؟',
                    answers: [
                        { text: 'ستساعدني هذه الدورة على تعزيز مكتسباتي والتطور مهنيا.', points: 5 },
                        { text: 'هذه الدورة جيدة لعملي.', points: 3 },
                        { text: 'دورة جيدة لي.', points: 1 }
                    ]
                }
            ]
        },
        'Español': {
            label: '🇪🇸 Español',
            course: 'Español profesional',
            questions: [
                {
                    text: 'Elige la mejor presentación profesional.',
                    answers: [
                        { text: 'Hola, me llamo Sara y trabajo en gestión de proyectos.', points: 4 },
                        { text: 'Hola, yo Sara, trabajo proyecto.', points: 2 },
                        { text: 'Hola, Sara.', points: 1 }
                    ]
                },
                {
                    text: 'Elige la frase correcta.',
                    answers: [
                        { text: 'Estoy interesado en este programa de formación.', points: 4 },
                        { text: 'Estoy interesante en este formación.', points: 2 },
                        { text: 'Yo querer formación.', points: 1 }
                    ]
                },
                {
                    text: 'Completa: "Nosotros ___ una reunión mañana por la mañana."',
                    answers: [
                        { text: 'tenemos', points: 4 },
                        { text: 'tiene', points: 2 },
                        { text: 'tener', points: 1 }
                    ]
                },
                {
                    text: 'Qué respuesta de email es más profesional?',
                    answers: [
                        { text: 'Gracias por su respuesta. Quedo disponible para los próximos pasos.', points: 4 },
                        { text: 'Gracias, miro.', points: 2 },
                        { text: 'OK.', points: 1 }
                    ]
                },
                {
                    text: 'Elige la petición más cortés.',
                    answers: [
                        { text: 'Podría enviarme los detalles del programa, por favor?', points: 4 },
                        { text: 'Envíame el programa.', points: 2 },
                        { text: 'Programa?', points: 1 }
                    ]
                },
                {
                    text: 'Qué significa "cumplir un plazo"?',
                    answers: [
                        { text: 'Terminar algo antes de la fecha requerida.', points: 4 },
                        { text: 'Empezar un proyecto.', points: 2 },
                        { text: 'Cancelar una reunión.', points: 1 }
                    ]
                },
                {
                    text: 'Qué tiempo se usa para una acción terminada ayer?',
                    answers: [
                        { text: 'Pretérito perfecto/simple: "terminé".', points: 4 },
                        { text: 'Futuro: "terminaré".', points: 1 },
                        { text: 'Presente: "termino".', points: 2 }
                    ]
                },
                {
                    text: 'Elige la frase más natural.',
                    answers: [
                        { text: 'Aunque el plazo es ajustado, podemos adaptar el calendario.', points: 5 },
                        { text: 'El plazo es corto, pero podemos hacer.', points: 3 },
                        { text: 'Plazo corto, posible.', points: 1 }
                    ]
                },
                {
                    text: 'Cómo expresas desacuerdo con cortesía?',
                    answers: [
                        { text: 'Entiendo su punto; sin embargo, propondría otro enfoque.', points: 5 },
                        { text: 'No estoy de acuerdo.', points: 3 },
                        { text: 'No.', points: 1 }
                    ]
                },
                {
                    text: 'Qué frase suena avanzada?',
                    answers: [
                        { text: 'Este curso me permitiría consolidar mis competencias y avanzar profesionalmente.', points: 5 },
                        { text: 'Este curso es bueno para mi trabajo.', points: 3 },
                        { text: 'Curso bueno para mí.', points: 1 }
                    ]
                }
            ]
        }
    };

    function initLanguageCard() {
        // Language option buttons
        $(document).on('click', '.lts-lang-btn', function() {
            var lang = $(this).attr('data-lang');
            startLangTest(lang);
        });

        // Reset button
        $(document).on('click', '#lts-lang-reset', function() {
            resetLangCard();
        });
    }

    function startLangTest(lang) {
        var data = LANG_TESTS[lang];
        if (!data) { return; }

        var $options = $('#lts-lang-options');
        var $test = $('#lts-lang-test');

        langTestState.lang = lang;
        langTestState.index = 0;
        langTestState.score = 0;
        langTestState.answers = [];

        $options.fadeOut(200, function() {
            $('#lts-lang-prompt').text('Assistant IA : répondez aux 10 questions.');
            $('#lts-lang-result').hide();
            $test.fadeIn(200);
            renderLangQuestion();
        });
    }

    function renderLangQuestion() {
        var test = LANG_TESTS[langTestState.lang];
        if (!test) { return; }

        var total = test.questions.length;
        var question = test.questions[langTestState.index];
        var progress = Math.round(((langTestState.index + 1) / total) * 100);
        var $answers = $('#lts-lang-answers');

        $('#lts-lang-step').text('Question ' + (langTestState.index + 1) + '/' + total);
        $('#lts-lang-selected').text(test.label);
        $('#lts-lang-question-bar').css('width', progress + '%');
        $('#lts-lang-question').text(question.text);
        $answers.empty();

        $.each(question.answers, function(i, answer) {
            $('<button></button>')
                .addClass('lts-lang-answer')
                .attr('type', 'button')
                .attr('data-index', i)
                .text(answer.text)
                .on('click', function() {
                    selectLangAnswer(answer.points);
                })
                .appendTo($answers);
        });
    }

    function selectLangAnswer(points) {
        var test = LANG_TESTS[langTestState.lang];
        if (!test) { return; }

        langTestState.score += points;
        langTestState.answers.push(points);

        if (langTestState.index >= test.questions.length - 1) {
            showLangResult();
            return;
        }

        langTestState.index++;
        renderLangQuestion();
    }

    function showLangResult() {
        var test = LANG_TESTS[langTestState.lang];
        if (!test) { return; }

        var maxScore = getMaxLangScore(test);
        var percent = Math.round((langTestState.score / maxScore) * 100);
        var level = getLangLevel(percent);
        var $test = $('#lts-lang-test');
        var $result = $('#lts-lang-result');
        var $bar = $('#lts-lang-bar');
        var $text = $('#lts-lang-result-text');

        $test.fadeOut(180, function() {
            $('#lts-lang-prompt').text('Résultat du test : ' + test.label);
            $bar.css('width', '0');
            $text.empty();
            $result.fadeIn(200);

            setTimeout(function() {
                $bar.css('width', percent + '%');
            }, 100);

            setTimeout(function() {
                $text.html(
                    '<div class="lts-lang-level-badge">' + escapeHtml(level.code) + '</div>' +
                    '<strong>' + escapeHtml(level.title) + '</strong>' +
                    '<span>' + escapeHtml(level.description) + '</span>' +
                    '<small>Score IA : ' + percent + '% · ' + langTestState.answers.length + ' réponses analysées</small>' +
                    '<div class="lts-lang-reco">Parcours conseillé : <strong>' + escapeHtml(test.course) + ' - ' + escapeHtml(level.track) + '</strong></div>'
                );
            }, 600);
        });
    }

    function getMaxLangScore(test) {
        var maxScore = 0;
        $.each(test.questions, function(i, question) {
            var questionMax = 0;
            $.each(question.answers, function(j, answer) {
                questionMax = Math.max(questionMax, answer.points);
            });
            maxScore += questionMax;
        });
        return maxScore || 1;
    }

    function getLangLevel(percent) {
        if (percent < 22) {
            return {
                code: 'A1',
                title: 'Débutant',
                track: 'Fondations',
                description: 'Vous comprenez quelques mots clés. Commencez par les bases, la prononciation et les phrases utiles.'
            };
        }
        if (percent < 42) {
            return {
                code: 'A2',
                title: 'Élémentaire',
                track: 'Communication pratique',
                description: 'Vous pouvez gérer des situations simples. Travaillez la grammaire de base et les échanges quotidiens.'
            };
        }
        if (percent < 62) {
            return {
                code: 'B1',
                title: 'Intermédiaire',
                track: 'Conversation professionnelle',
                description: 'Vous communiquez sur des sujets familiers. Renforcez la fluidité, le vocabulaire métier et les emails.'
            };
        }
        if (percent < 78) {
            return {
                code: 'B2',
                title: 'Avancé',
                track: 'Business et prise de parole',
                description: 'Vous êtes à l\'aise dans la plupart des contextes. Visez les réunions, présentations et négociations.'
            };
        }
        if (percent < 91) {
            return {
                code: 'C1',
                title: 'Autonome',
                track: 'Perfectionnement',
                description: 'Vous maîtrisez une communication complexe. Travaillez la précision, le style et les nuances.'
            };
        }
        return {
            code: 'C2',
            title: 'Maîtrise',
            track: 'Coaching expert',
            description: 'Votre niveau est excellent. Un coaching ciblé peut vous aider à atteindre un usage expert.'
        };
    }

    function resetLangCard() {
        var $options = $('#lts-lang-options');
        var $result  = $('#lts-lang-result');
        var $test    = $('#lts-lang-test');
        var $prompt  = $('#lts-lang-prompt');

        langTestState.lang = '';
        langTestState.index = 0;
        langTestState.score = 0;
        langTestState.answers = [];

        $result.add($test).fadeOut(200).promise().done(function() {
            $prompt.text('Choisissez la langue à évaluer :');
            $('#lts-lang-question-bar').css('width', '0');
            $('#lts-lang-bar').css('width', '0');
            $options.fadeIn(200);
        });
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
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

    // ─── Mobile Navbar Collapse ────────────────────────────────────────────────
    function initNavbarCollapse() {
        var $toggle = $('#lts-mobile-nav-toggle');
        var $menu = $('#lts-mobile-menu');
        if (!$toggle.length || !$menu.length) { return; }

        $toggle.on('click', function(e) {
            e.stopPropagation();
            $menu.slideToggle(250);
        });

        $(document).on('click', function() {
            if ($menu.is(':visible')) {
                $menu.slideUp(200);
            }
        });

        $menu.on('click', 'a', function() {
            $menu.slideUp(200);
        });
    }

    // ─── Init All ─────────────────────────────────────────────────────────────
    function init() {
        $(function() {
            initScrollReveal();
            initContactForm();
            initLanguageCard();
            initSmoothScroll();
            initHeroParallax();
            initNavbarCollapse();
            log.debug('LTS Academy homepage.js initialised with custom navbar toggle');
        });
    }

    return {
        init: init
    };
});
