/**
 * Virtual Keyboard - POS Kabas
 * Clavier virtuel QWERTY avec chiffres pour tablettes/mobiles
 */

(function(window, $) {
    'use strict';

    // Layout QWERTY avec rangee de chiffres
    const LAYOUT = {
        numbers: ['1', '2', '3', '4', '5', '6', '7', '8', '9', '0'],
        row1: ['q', 'w', 'e', 'r', 't', 'y', 'u', 'i', 'o', 'p'],
        row2: ['a', 's', 'd', 'f', 'g', 'h', 'j', 'k', 'l'],
        row3: ['z', 'x', 'c', 'v', 'b', 'n', 'm']
    };

    // Caracteres speciaux accessibles via Shift sur les chiffres
    const SHIFT_NUMBERS = {
        '1': '!', '2': '@', '3': '#', '4': '$', '5': '%',
        '6': '^', '7': '&', '8': '*', '9': '(', '0': ')'
    };

    class POSVirtualKeyboard {
        constructor(options = {}) {
            this.options = Object.assign({
                inputSelector: 'input[type="text"], input[type="search"], input[type="number"], input[type="tel"], input[type="email"], textarea',
                excludeSelector: '[data-no-keyboard], [readonly]',
                showBackdrop: false,
                autoHideOnEnter: true
            }, options);

            this.isVisible = false;
            this.isShiftActive = false;
            this.activeInput = null;
            this.$overlay = null;
            this.$backdrop = null;
            this._autoHideTimeout = null;
            this._autoHideDelay = 60000; // 60 secondes d'inactivite

            this._init();
        }

        _init() {
            this._createDOM();
            this._bindEvents();
        }

        _createDOM() {
            // Creer le backdrop (optionnel)
            this.$backdrop = $('<div class="vk-backdrop"></div>');
            $('body').append(this.$backdrop);

            // Creer l'overlay du clavier
            this.$overlay = $(`
                <div class="virtual-keyboard-overlay" id="virtual-keyboard">
                    <div class="virtual-keyboard">
                        <!-- Rangee des chiffres -->
                        <div class="vk-row vk-row-numbers">
                            ${LAYOUT.numbers.map(n => `<button type="button" class="vk-key vk-key-char" data-char="${n}" data-shift="${SHIFT_NUMBERS[n] || n}">${n}</button>`).join('')}
                            <button type="button" class="vk-key vk-key-backspace" data-action="backspace" title="Effacer">&#9003;</button>
                        </div>
                        <!-- Rangee QWERTYUIOP -->
                        <div class="vk-row">
                            ${LAYOUT.row1.map(c => `<button type="button" class="vk-key vk-key-char" data-char="${c}">${c}</button>`).join('')}
                            <button type="button" class="vk-key vk-key-char" data-char=".">.</button>
                        </div>
                        <!-- Rangee ASDFGHJKL -->
                        <div class="vk-row">
                            ${LAYOUT.row2.map(c => `<button type="button" class="vk-key vk-key-char" data-char="${c}">${c}</button>`).join('')}
                            <button type="button" class="vk-key vk-key-char" data-char="@">@</button>
                            <button type="button" class="vk-key vk-key-enter" data-action="enter">OK</button>
                        </div>
                        <!-- Rangee ZXCVBNM + Space -->
                        <div class="vk-row">
                            <button type="button" class="vk-key vk-key-shift" data-action="shift" title="Caps">&#8679;</button>
                            ${LAYOUT.row3.map(c => `<button type="button" class="vk-key vk-key-char" data-char="${c}">${c}</button>`).join('')}
                            <button type="button" class="vk-key vk-key-char" data-char="-">-</button>
                            <button type="button" class="vk-key vk-key-space" data-action="space">space</button>
                        </div>
                        <!-- Bouton masquer -->
                        <button type="button" class="vk-hide-btn" data-action="hide">Hide keyboard</button>
                    </div>
                </div>
            `);

            $('body').append(this.$overlay);
        }

        _bindEvents() {
            const self = this;

            // IMPORTANT: Empecher la perte de focus sur l'input quand on clique sur le clavier
            // mousedown/touchstart arrivent AVANT que l'input perde le focus
            this.$overlay.on('mousedown touchstart', function(e) {
                e.preventDefault();
            });

            // Clic sur les touches du clavier
            this.$overlay.on('click touchend', '.vk-key', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const $key = $(this);
                const action = $key.data('action');
                const char = $key.data('char');

                // Reinitialiser le timer d'auto-hide a chaque frappe
                self._resetAutoHideTimer();

                // Animation de pression
                $key.addClass('active');
                setTimeout(() => $key.removeClass('active'), 100);

                if (action) {
                    self._handleAction(action);
                } else if (char !== undefined) {
                    self._insertChar(String(char));
                }
            });

            // Bouton masquer
            this.$overlay.on('click touchend', '.vk-hide-btn', function(e) {
                e.preventDefault();
                self.hide();
            });

            // Clic sur le backdrop
            this.$backdrop.on('click touchend', function(e) {
                e.preventDefault();
                self.hide();
            });

            // Focus sur les inputs - utiliser addEventListener natif pour fiabilite
            document.addEventListener('focusin', function(e) {
                const input = e.target;
                const $input = $(input);

                // Verifier si c'est un input eligible
                if (!$input.is(self.options.inputSelector)) {
                    return;
                }

                console.log('VirtualKeyboard: focusin on', input.id || input.className || input.tagName);

                // Verifier les exclusions
                if ($input.is(self.options.excludeSelector)) {
                    console.log('VirtualKeyboard: input excluded');
                    return;
                }

                // Verifier si l'input est dans un modal actif ou visible
                const $modal = $input.closest('.modal');
                if ($modal.length && !$modal.hasClass('show')) {
                    console.log('VirtualKeyboard: input in hidden modal');
                    return;
                }

                self.activeInput = input;
                self.show();
            }, true);

            // Clic en dehors du clavier et de l'input actif
            $(document).on('click touchstart', function(e) {
                if (!self.isVisible) return;

                const $target = $(e.target);

                // Si on clique sur le clavier, on ignore
                if ($target.closest('.virtual-keyboard-overlay').length) {
                    return;
                }

                // Si on clique sur un input eligible, on laisse le focus gerer
                if ($target.is(self.options.inputSelector) && !$target.is(self.options.excludeSelector)) {
                    return;
                }

                // Sinon on masque le clavier
                self.hide();
            });

            // Gestion du clavier physique (ne pas bloquer)
            $(document).on('keydown', function(e) {
                if (self.isVisible && e.key === 'Escape') {
                    self.hide();
                }
            });
        }

        _handleAction(action) {
            switch (action) {
                case 'shift':
                    this._toggleShift();
                    break;
                case 'backspace':
                    this._backspace();
                    break;
                case 'space':
                    this._insertChar(' ');
                    break;
                case 'enter':
                    this._handleEnter();
                    break;
                case 'hide':
                    this.hide();
                    break;
            }
        }

        _toggleShift() {
            this.isShiftActive = !this.isShiftActive;
            const $shiftKey = this.$overlay.find('.vk-key-shift');

            if (this.isShiftActive) {
                $shiftKey.addClass('active');
            } else {
                $shiftKey.removeClass('active');
            }

            // Mettre a jour l'affichage des touches
            this._updateKeyDisplay();
        }

        _updateKeyDisplay() {
            const self = this;
            this.$overlay.find('.vk-key-char').each(function() {
                const $key = $(this);
                let char = $key.data('char');
                const shiftChar = $key.data('shift');

                if (self.isShiftActive) {
                    // Utiliser le caractere shift si disponible, sinon majuscule
                    char = shiftChar || String(char).toUpperCase();
                } else {
                    char = String(char).toLowerCase();
                }

                // Ne pas modifier les caracteres speciaux (., @, -)
                if (!/^[a-zA-Z0-9]$/.test($key.data('char'))) {
                    return;
                }

                $key.text(char);
            });
        }

        _insertChar(char) {
            if (!this.activeInput) return;

            const input = this.activeInput;
            const $input = $(input);

            // Appliquer shift pour les lettres
            if (this.isShiftActive && /^[a-z]$/.test(char)) {
                char = char.toUpperCase();
            } else if (this.isShiftActive && SHIFT_NUMBERS[char]) {
                char = SHIFT_NUMBERS[char];
            }

            // Pour les inputs number, n'accepter que les chiffres et le point
            if (input.type === 'number') {
                if (!/^[\d.\-]$/.test(char)) {
                    return;
                }
                // Empecher plusieurs points
                if (char === '.' && input.value.includes('.')) {
                    return;
                }
            }

            const value = input.value;

            // Pour les inputs number, selectionStart/selectionEnd ne sont pas supportes
            // On ajoute toujours a la fin
            if (input.type === 'number') {
                // Gestion speciale pour le point decimal
                // "123." n'est pas valide pour un input number, le navigateur le rejette
                // On ajoute un "0" temporaire pour avoir "123.0" qui est valide
                if (char === '.') {
                    input.value = value + '.0';
                    input.dataset.vkPendingDecimal = 'true';
                } else if (input.dataset.vkPendingDecimal === 'true' && /\d/.test(char)) {
                    // Remplacer le 0 placeholder par le vrai chiffre
                    input.value = value.slice(0, -1) + char;
                    delete input.dataset.vkPendingDecimal;
                } else {
                    input.value = value + char;
                    delete input.dataset.vkPendingDecimal;
                }
            } else {
                // Inserer le caractere a la position du curseur
                const start = input.selectionStart || 0;
                const end = input.selectionEnd || 0;

                input.value = value.substring(0, start) + char + value.substring(end);

                // Repositionner le curseur
                const newPos = start + char.length;
                input.setSelectionRange(newPos, newPos);
            }

            // Declencher les evenements pour que les listeners JS reagissent
            $input.trigger('input');
            $input.trigger('change');

            // Desactiver shift apres une lettre (comportement standard)
            if (this.isShiftActive && /^[a-zA-Z]$/.test(char)) {
                this._toggleShift();
            }
        }

        _backspace() {
            if (!this.activeInput) return;

            const input = this.activeInput;
            const $input = $(input);
            const value = input.value;

            // Pour les inputs number, selectionStart/selectionEnd ne sont pas supportes
            // On supprime toujours le dernier caractere
            if (input.type === 'number') {
                if (value.length > 0) {
                    // Si on a un ".0" placeholder (point en attente), supprimer les deux
                    if (input.dataset.vkPendingDecimal === 'true' && value.endsWith('.0')) {
                        input.value = value.slice(0, -2);
                        delete input.dataset.vkPendingDecimal;
                    } else {
                        input.value = value.slice(0, -1);
                    }
                }
            } else {
                const start = input.selectionStart || 0;
                const end = input.selectionEnd || 0;

                if (start === end && start > 0) {
                    // Pas de selection, supprimer le caractere avant le curseur
                    input.value = value.substring(0, start - 1) + value.substring(end);
                    input.setSelectionRange(start - 1, start - 1);
                } else if (start !== end) {
                    // Supprimer la selection
                    input.value = value.substring(0, start) + value.substring(end);
                    input.setSelectionRange(start, start);
                }
            }

            $input.trigger('input');
            $input.trigger('change');
        }

        _handleEnter() {
            if (!this.activeInput) return;

            const $input = $(this.activeInput);

            // Declencher l'evenement Enter
            const enterEvent = $.Event('keydown', { key: 'Enter', keyCode: 13, which: 13 });
            $input.trigger(enterEvent);

            // Si c'est un champ de recherche, declencher aussi submit sur le form
            const $form = $input.closest('form');
            if ($form.length) {
                $form.trigger('submit');
            }

            // Pour le champ de recherche du POS, declencher la recherche
            if (this.activeInput.id === 'sale-search') {
                // La recherche est geree par l'evenement input, on fait juste Enter
                $input.trigger('keyup');
            }

            if (this.options.autoHideOnEnter) {
                this.hide();
            }
        }

        show() {
            if (this.isVisible) return;

            this.isVisible = true;
            this.$overlay.addClass('visible');

            if (this.options.showBackdrop) {
                this.$backdrop.addClass('visible');
            }

            // Demarrer le timer d'auto-hide
            this._resetAutoHideTimer();

            // Faire defiler pour que l'input soit visible au-dessus du clavier
            if (this.activeInput) {
                setTimeout(() => {
                    if (!this.activeInput || !document.body.contains(this.activeInput)) {
                        return;
                    }
                    const inputRect = this.activeInput.getBoundingClientRect();
                    const keyboardHeight = this.$overlay.outerHeight();
                    const viewportHeight = window.innerHeight;

                    if (inputRect.bottom > viewportHeight - keyboardHeight - 20) {
                        this.activeInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }, 300);
            }
        }

        _resetAutoHideTimer() {
            // Annuler le timer existant
            if (this._autoHideTimeout) {
                clearTimeout(this._autoHideTimeout);
            }

            // Demarrer un nouveau timer
            this._autoHideTimeout = setTimeout(() => {
                if (this.isVisible) {
                    console.log('[VirtualKeyboard] Auto-hiding due to inactivity');
                    this.hide();
                }
            }, this._autoHideDelay);
        }

        hide() {
            if (!this.isVisible) return;

            this.isVisible = false;
            this.$overlay.removeClass('visible');
            this.$backdrop.removeClass('visible');

            // Annuler le timer d'auto-hide
            if (this._autoHideTimeout) {
                clearTimeout(this._autoHideTimeout);
                this._autoHideTimeout = null;
            }

            // Reset shift
            if (this.isShiftActive) {
                this._toggleShift();
            }

            // Retirer le focus de l'input pour eviter la reouverture
            if (this.activeInput) {
                $(this.activeInput).trigger('blur');
            }
            this.activeInput = null;
        }

        toggle() {
            if (this.isVisible) {
                this.hide();
            } else {
                this.show();
            }
        }

        destroy() {
            this.$overlay.remove();
            this.$backdrop.remove();
            $(document).off('focus', this.options.inputSelector);
        }
    }

    // Exposer globalement
    window.POSVirtualKeyboard = POSVirtualKeyboard;

})(window, jQuery);
