// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * LTS Academy AI Chatbot — AMD module
 * Integrates Google Gemini API into the floating chat bubble.
 *
 * @module     theme_ltsacademy/chatbot
 * @copyright  2024 LTS Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/log'], function($, log) {
    'use strict';

    // ─── State ───────────────────────────────────────────────────────────────
    var isOpen       = false;
    var isLoading    = false;
    var badgeDismissed = false;
    var messages     = []; // { role: 'user'|'model', parts: [{text}] }
    var apiKey       = '';
    var avatarUrl    = '';

    // ─── System prompt ───────────────────────────────────────────────────────
    var SYSTEM_PROMPT =
        'Tu es l\'assistant virtuel de LTS Academy, un organisme de formation professionnelle basé à Berrechid, Maroc. ' +
        'Tu aides les apprenants et entreprises à trouver des informations sur les formations disponibles, ' +
        'les modalités d\'inscription, les tarifs et les programmes (QHSE, comptabilité, langues, informatique, management, etc.). ' +
        'Réponds toujours en français de manière professionnelle, chaleureuse et concise. ' +
        'Si tu ne sais pas quelque chose, dirige l\'utilisateur vers contact@ltsacademy.ma ou le numéro 0660-356877.';

    // ─── DOM refs ────────────────────────────────────────────────────────────
    var $toggle, $window, $messages, $input, $send, $inputWrap, $badge, $quickQuestions;

    // ─── Init ────────────────────────────────────────────────────────────────
    function init() {
        var root = document.getElementById('lts-chatbot-root');
        if (!root) {
            return; // chatbot not enabled on this page
        }

        apiKey    = root.getAttribute('data-apikey') || '';
        avatarUrl = root.getAttribute('data-avatarurl') || '';

        $toggle        = $('#lts-chat-toggle');
        $window        = $('#lts-chat-window');
        $messages      = $('#lts-chat-messages');
        $input         = $('#lts-chat-input');
        $send          = $('#lts-chat-send');
        $inputWrap     = $('#lts-chat-input-wrap');
        $badge         = $('#lts-chat-badge');
        $quickQuestions = $('#lts-quick-questions');

        // Event bindings
        $toggle.on('click', toggleChat);
        $('#lts-chat-minimize').on('click', toggleChat);
        $send.on('click', handleSend);
        $input.on('input', onInputChange);
        $input.on('focus', function() { $inputWrap.addClass('lts-focused'); });
        $input.on('blur',  function() { $inputWrap.removeClass('lts-focused'); });
        $input.on('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                if (!isLoading && $input.val().trim()) {
                    handleSend();
                }
            }
        });

        // Quick question chips
        $('.lts-quick-btn').on('click', function() {
            var question = $(this).attr('data-question');
            sendMessage(question);
        });

        // Show badge after 3 seconds to draw attention
        setTimeout(function() {
            if (!isOpen && !badgeDismissed) {
                $badge.show();
            }
        }, 3000);

        // Auto-resize textarea
        $input.on('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 90) + 'px';
        });
    }

    // ─── Toggle ──────────────────────────────────────────────────────────────
    function toggleChat() {
        isOpen = !isOpen;

        if (isOpen) {
            $window.addClass('lts-chat-open');
            $toggle.attr('aria-expanded', 'true');
            $badge.hide();
            badgeDismissed = true;

            // Welcome message on first open
            if (messages.length === 0) {
                appendMessage('assistant',
                    '👋 Bonjour ! Je suis l\'assistant de **LTS Academy**.\n\n' +
                    'Comment puis-je vous aider aujourd\'hui ? Vous pouvez me poser des questions sur nos formations, ' +
                    'les modalités d\'inscription ou tout autre sujet.'
                );
            }

            // Focus input
            setTimeout(function() { $input.focus(); }, 300);
        } else {
            $window.removeClass('lts-chat-open');
            $toggle.attr('aria-expanded', 'false');
        }
    }

    // ─── Input change ─────────────────────────────────────────────────────────
    function onInputChange() {
        var hasText = $input.val().trim().length > 0;
        if (hasText && !isLoading) {
            $send.addClass('lts-send-active').prop('disabled', false);
        } else {
            $send.removeClass('lts-send-active').prop('disabled', true);
        }
    }

    // ─── Send message ─────────────────────────────────────────────────────────
    function handleSend() {
        var text = $input.val().trim();
        if (!text || isLoading) { return; }
        $input.val('').trigger('input');
        $input.css('height', 'auto');
        sendMessage(text);
    }

    function sendMessage(text) {
        if (!text) { return; }

        // Hide quick questions after first message
        $quickQuestions.hide();

        // Add user message to UI
        appendMessage('user', text);

        // Add to history
        messages.push({ role: 'user', parts: [{ text: text }] });

        // Disable input while loading
        setLoading(true);

        if (!apiKey) {
            appendMessage('assistant',
                '⚠️ La clé API Gemini n\'est pas configurée. ' +
                'Veuillez contacter l\'administrateur ou nous joindre directement au **0660-356877**.'
            );
            setLoading(false);
            return;
        }

        callGeminiAPI(text);
    }

    // ─── Gemini API call ──────────────────────────────────────────────────────
    function callGeminiAPI(userText) {
        var url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:generateContent?key=' + apiKey;

        // Build contents array with system instruction prepended to first user turn.
        var contents = messages.map(function(msg) {
            return { role: msg.role, parts: msg.parts };
        });

        var payload = {
            'system_instruction': {
                'parts': [{ 'text': SYSTEM_PROMPT }]
            },
            'contents': contents,
            'generationConfig': {
                'temperature': 0.7,
                'maxOutputTokens': 1024,
                'topP': 0.9
            },
            'safetySettings': [
                { 'category': 'HARM_CATEGORY_HARASSMENT',       'threshold': 'BLOCK_MEDIUM_AND_ABOVE' },
                { 'category': 'HARM_CATEGORY_HATE_SPEECH',      'threshold': 'BLOCK_MEDIUM_AND_ABOVE' },
                { 'category': 'HARM_CATEGORY_SEXUALLY_EXPLICIT','threshold': 'BLOCK_MEDIUM_AND_ABOVE' },
                { 'category': 'HARM_CATEGORY_DANGEROUS_CONTENT','threshold': 'BLOCK_MEDIUM_AND_ABOVE' }
            ]
        };

        $.ajax({
            url: url,
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload),
            timeout: 30000,
            success: function(response) {
                setLoading(false);
                try {
                    var text = response.candidates[0].content.parts[0].text;
                    // Add to history
                    messages.push({ role: 'model', parts: [{ text: text }] });
                    appendMessage('assistant', text);
                } catch (e) {
                    log.warn('Chatbot: unexpected response structure', e);
                    appendMessage('assistant',
                        'Désolé, je n\'ai pas pu traiter votre demande. Veuillez réessayer ou nous contacter directement.'
                    );
                }
            },
            error: function(xhr, status) {
                setLoading(false);
                var msg = 'Je rencontre une difficulté technique. ';
                if (status === 'timeout') {
                    msg += 'La connexion a expiré. ';
                } else if (xhr.status === 429) {
                    msg += 'Trop de requêtes. Veuillez patienter quelques secondes. ';
                } else if (xhr.status === 400) {
                    msg += 'Requête invalide. ';
                } else if (xhr.status === 403) {
                    msg += 'Clé API invalide ou quota dépassé. ';
                }
                msg += '\n\nContactez-nous au **0660-356877** ou **contact@ltsacademy.ma**';
                appendMessage('assistant', msg);
                log.warn('Chatbot API error:', xhr.status, status);
            }
        });
    }

    // ─── Append message to DOM ────────────────────────────────────────────────
    function appendMessage(role, text) {
        var isUser      = (role === 'user');
        var isAssistant = (role === 'assistant');

        var $row = $('<div></div>').addClass('lts-msg-row').addClass(isUser ? 'lts-msg-user' : 'lts-msg-assistant');

        // AI avatar (left side for assistant)
        if (isAssistant) {
            var $avatarDiv = $('<div></div>').addClass('lts-msg-avatar');
            if (avatarUrl) {
                $avatarDiv.append('<img src="' + escapeHtml(avatarUrl) + '" alt="AI" width="30" height="30">');
            } else {
                $avatarDiv.append(
                    '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="1.5">' +
                    '<path d="M12 2a2 2 0 0 1 2 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 0 1 7 7h1a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v1a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-1H2a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h1a7 7 0 0 1 7-7h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 0 1 2-2z"/>' +
                    '</svg>'
                );
            }
            $row.append($avatarDiv);
        }

        var $bubble = $('<div></div>').addClass('lts-msg-bubble');

        if (isAssistant) {
            $bubble.addClass('lts-ai-rendered').html(renderMarkdown(text));
        } else {
            $bubble.text(text);
        }

        $row.append($bubble);
        $messages.append($row);
        scrollToBottom();
    }

    // ─── Typing indicator ─────────────────────────────────────────────────────
    function showTypingIndicator() {
        var $row = $('<div></div>').addClass('lts-msg-row lts-msg-assistant').attr('id', 'lts-typing');
        var $avatarDiv = $('<div></div>').addClass('lts-msg-avatar');
        if (avatarUrl) {
            $avatarDiv.append('<img src="' + escapeHtml(avatarUrl) + '" alt="AI" width="30" height="30">');
        } else {
            $avatarDiv.html('<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="1.5"><path d="M12 2a2 2 0 0 1 2 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 0 1 7 7h1a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v1a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-1H2a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h1a7 7 0 0 1 7-7h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 0 1 2-2z"/></svg>');
        }
        var $bubble = $('<div></div>').addClass('lts-msg-bubble lts-typing-indicator').html('<span></span><span></span><span></span>');
        $row.append($avatarDiv).append($bubble);
        $messages.append($row);
        scrollToBottom();
    }

    function hideTypingIndicator() {
        $('#lts-typing').remove();
    }

    // ─── Loading state ───────────────────────────────────────────────────────
    function setLoading(state) {
        isLoading = state;
        $input.prop('disabled', state);
        $send.prop('disabled', state).toggleClass('lts-send-active', !state && $input.val().trim().length > 0);

        if (state) {
            showTypingIndicator();
        } else {
            hideTypingIndicator();
        }
    }

    // ─── Scroll ───────────────────────────────────────────────────────────────
    function scrollToBottom() {
        var el = $messages[0];
        if (el) {
            el.scrollTop = el.scrollHeight;
        }
    }

    // ─── Basic Markdown renderer ─────────────────────────────────────────────
    function renderMarkdown(text) {
        if (!text) { return ''; }
        var escaped = escapeHtml(text);

        // Headings
        escaped = escaped.replace(/^### (.+)$/gm, '<strong style="display:block;margin:8px 0 4px;color:#1e3a8a;">$1</strong>');
        escaped = escaped.replace(/^## (.+)$/gm, '<strong style="display:block;margin:10px 0 6px;font-size:15px;color:#1e3a8a;border-bottom:1px solid #e2e8f0;padding-bottom:4px;">$1</strong>');
        escaped = escaped.replace(/^# (.+)$/gm, '<strong style="display:block;margin:12px 0 8px;font-size:16px;color:#1e3a8a;">$1</strong>');

        // Bold and italic
        escaped = escaped.replace(/\*\*(.+?)\*\*/g, '<strong style="font-weight:700;color:#1e3a8a;">$1</strong>');
        escaped = escaped.replace(/\*(.+?)\*/g, '<em>$1</em>');

        // Inline code
        escaped = escaped.replace(/`([^`]+)`/g,
            '<code style="background:rgba(30,58,138,0.07);color:#be185d;border-radius:4px;padding:1px 5px;font-family:monospace;font-size:12px;">$1</code>');

        // Bullet list items
        escaped = escaped.replace(/^[-*•] (.+)$/gm,
            '<div style="display:flex;align-items:flex-start;gap:7px;padding:2px 0;">' +
            '<span style="color:#3b82f6;font-weight:700;flex-shrink:0;margin-top:1px;">▸</span>' +
            '<span>$1</span></div>');

        // Numbered list
        escaped = escaped.replace(/^\d+\. (.+)$/gm,
            '<div style="display:flex;gap:7px;padding:2px 0;"><span style="color:#3b82f6;font-weight:700;flex-shrink:0;">•</span><span>$1</span></div>');

        // Line breaks — preserve double newline as paragraph break
        escaped = escaped.replace(/\n\n+/g, '<br><br>');
        escaped = escaped.replace(/\n/g, '<br>');

        return escaped;
    }

    // ─── Security: HTML escape ─────────────────────────────────────────────
    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    return {
        init: init
    };
});
