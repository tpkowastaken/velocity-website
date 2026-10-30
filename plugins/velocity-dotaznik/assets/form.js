/**
 * Velocity Dotazník front end. No dependencies.
 *
 * Reads the definition from window.VDQ.definition (config/form.php).
 * evaluate() and validateValue() mirror VDQ_Form in PHP; keep them in sync.
 */
(function () {
	'use strict';

	var C = window.VDQ;
	if (!C) return;
	if (C.route === 'unsubscribe') return initUnsubscribe();

	var DEF = C.definition;
	var root = document.getElementById('vdq');
	var LS_KEY = 'vdq_session';
	var PRE = DEF.steps.filter(function (s) { return !s.post; });
	var POST = DEF.steps.filter(function (s) { return s.post; });
	var SKIP_FIELDS = {};
	DEF.steps.forEach(function (s) {
		s.questions.forEach(function (q) { if (q.skip_field) SKIP_FIELDS[q.skip_field] = true; });
	});
	var TEXT_TYPES = { email: 1, text: 1, textarea: 1, url: 1, tel: 1, handle: 1 };
	var touchDevice = window.matchMedia && window.matchMedia('(hover: none)').matches;

	var S = {
		token: null,
		session: null,
		answers: {},
		stepId: null,
		submitted: false,
		goalsDone: false,
		partnerFit: false,
		goalsOpen: false,
		revealed: {},     // step id -> {question id: true}
		touched: {},      // question id -> true (show its error)
		verify: {},       // question id -> {value, status}
		tracked: {},      // question id -> true (answered event sent)
		started: false,
		identified: false
	};

	/* ------------------------------------------------------------------
	 * Icons
	 * ---------------------------------------------------------------- */

	var ICON = {
		back: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>',
		arrow: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>',
		check: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>',
		globe: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.7 3.8 5.7 3.8 9s-1.3 6.3-3.8 9c-2.5-2.7-3.8-5.7-3.8-9S9.5 5.7 12 3z"/></svg>',
		mail: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="3"/><path d="M4 7l8 6 8-6"/></svg>',
		phone: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"><path d="M6.6 3.5l2.6.4 1.3 4-2 1.4a12 12 0 0 0 6.2 6.2l1.4-2 4 1.3.4 2.6a2 2 0 0 1-2 2.1A16.6 16.6 0 0 1 4.5 5.5a2 2 0 0 1 2.1-2z"/></svg>',
		instagram: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="5.5"/><circle cx="12" cy="12" r="4.2"/><circle cx="17.4" cy="6.6" r="1.1" fill="currentColor" stroke="none"/></svg>',
		tiktok: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M16.6 3c.4 2.4 1.9 4 4.4 4.2v3.2a8 8 0 0 1-4.3-1.3v6.1A6.3 6.3 0 1 1 10.4 9v3.3a3.1 3.1 0 1 0 3 3.1V3h3.2z"/></svg>',
		facebook: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M13.5 21v-7.6h2.6l.4-3h-3V8.5c0-.9.3-1.5 1.5-1.5h1.6V4.3c-.3 0-1.2-.1-2.3-.1-2.3 0-3.9 1.4-3.9 4v2.2H7.8v3h2.6V21h3.1z"/></svg>',
		none: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M5.6 5.6l12.8 12.8"/></svg>',
		external: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17L17 7M9 7h8v8"/></svg>'
	};

	/* ------------------------------------------------------------------
	 * Small helpers
	 * ---------------------------------------------------------------- */

	function h(tag, attrs, html) {
		var el = document.createElement(tag);
		if (attrs) Object.keys(attrs).forEach(function (k) {
			if (k === 'class') el.className = attrs[k];
			else if (k === 'text') el.textContent = attrs[k];
			else if (attrs[k] !== null && attrs[k] !== undefined && attrs[k] !== false) el.setAttribute(k, attrs[k] === true ? '' : attrs[k]);
		});
		if (html) el.innerHTML = html;
		return el;
	}

	function debounce(fn, ms) {
		var t;
		var d = function () {
			var args = arguments, self = this;
			clearTimeout(t);
			t = setTimeout(function () { fn.apply(self, args); }, ms);
		};
		d.cancel = function () { clearTimeout(t); };
		return d;
	}

	function ph(event, props) {
		try {
			if (window.posthog && window.posthog.capture) window.posthog.capture(event, props || {});
		} catch (e) { /* analytics must never break the form */ }
	}

	function toast(msg, isError) {
		var t = document.querySelector('.vdq-toast');
		if (!t) { t = h('div', { class: 'vdq-toast', role: 'status' }); document.body.appendChild(t); }
		t.textContent = msg;
		t.classList.toggle('is-error', !!isError);
		requestAnimationFrame(function () { t.classList.add('is-on'); });
		clearTimeout(toast.t);
		toast.t = setTimeout(function () { t.classList.remove('is-on'); }, isError ? 5000 : 3200);
	}

	/* ------------------------------------------------------------------
	 * Conditions and validation (mirror of VDQ_Form)
	 * ---------------------------------------------------------------- */

	function isFilled(v) {
		if (Array.isArray(v)) return v.length > 0;
		if (typeof v === 'boolean') return v;
		return v !== null && v !== undefined && String(v).trim() !== '';
	}

	function evaluate(c, a) {
		if (c === true || c === null || c === undefined) return true;
		if (c === false) return false;
		if (c.all) return c.all.every(function (x) { return evaluate(x, a); });
		if (c.any) return c.any.some(function (x) { return evaluate(x, a); });
		if (c.not) return !evaluate(c.not, a);
		var v = a[c.q];
		if ('eq' in c) return v === c.eq;
		if ('neq' in c) return v !== c.neq;
		if (c['in']) return c['in'].indexOf(v) !== -1;
		if ('has' in c) return Array.isArray(v) && v.indexOf(c.has) !== -1;
		if ('filled' in c) return isFilled(v) === !!c.filled;
		return false;
	}

	function isVisible(q) { return !q.show_if || evaluate(q.show_if, S.answers); }
	function isRequired(q) {
		var r = q.required;
		return (r && typeof r === 'object') ? evaluate(r, S.answers) : !!r;
	}
	function stepVisible(s) { return !s.show_if || evaluate(s.show_if, S.answers); }

	function normalizeUrl(v) {
		v = String(v || '').trim();
		if (!v) return '';
		if (!/^https?:\/\//i.test(v)) v = 'https://' + v;
		try {
			var u = new URL(v);
			if (u.hostname.indexOf('.') === -1 || /\s/.test(v) || u.hostname.split('.').pop().length < 2) return '';
			return u.href;
		} catch (e) { return ''; }
	}

	function normalizeHandle(v) {
		v = String(v || '').trim();
		v = v.replace(/^(https?:\/\/)?(www\.|m\.|vm\.)?(instagram\.com|tiktok\.com|facebook\.com|fb\.com)\//i, '');
		v = v.replace(/[?#].*$/, '').replace(/^\/+|\/+$/g, '').replace(/^@+/, '').replace(/\/.*$/, '');
		return v.slice(0, 60);
	}

	function validateValue(q, v) {
		if (!isFilled(v)) return isRequired(q) ? 'Toto pole je povinné.' : '';
		switch (q.type) {
			case 'email':
				return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v) ? '' : 'Zkontrolujte prosím e-mail.';
			case 'url':
				return normalizeUrl(v) ? '' : 'Zkontrolujte prosím adresu webu.';
			case 'tel':
				var d = String(v).replace(/\D/g, '');
				return (/^\+?[\d\s\-\/().]+$/.test(v) && d.length >= 9 && d.length <= 15) ? '' : 'Zkontrolujte prosím telefon.';
			case 'handle':
				return /^[A-Za-z0-9._\-]{1,60}$/.test(normalizeHandle(v)) ? '' : 'Zadejte jen uživatelské jméno, např. vasefirma.';
			case 'multi':
			case 'social':
				return v.length ? '' : 'Vyberte alespoň jednu možnost.';
		}
		return '';
	}

	function errorOf(q) { return validateValue(q, S.answers[q.id]); }

	function stepById(id) {
		for (var i = 0; i < DEF.steps.length; i++) if (DEF.steps[i].id === id) return DEF.steps[i];
		return null;
	}

	function visiblePre() { return PRE.filter(stepVisible); }

	function partnerFit() { return evaluate(DEF.partner_fit, S.answers); }

	/* ------------------------------------------------------------------
	 * API
	 * ---------------------------------------------------------------- */

	function api(path, method, body) {
		return fetch(C.api + path, {
			method: method || 'GET',
			headers: body ? { 'Content-Type': 'application/json' } : {},
			body: body ? JSON.stringify(body) : undefined,
			credentials: 'omit',
			cache: 'no-store'
		}).then(function (r) {
			return r.json().catch(function () { return {}; }).then(function (j) {
				if (!r.ok) { var e = new Error(j.message || 'HTTP ' + r.status); e.status = r.status; e.data = j.data || {}; throw e; }
				return j;
			});
		});
	}

	function payload() {
		var out = {};
		Object.keys(S.answers).forEach(function (k) { out[k] = S.answers[k]; });
		return out;
	}

	function applyState(st) {
		S.token = st.token;
		S.session = st.session;
		S.answers = st.answers && typeof st.answers === 'object' && !Array.isArray(st.answers) ? st.answers : {};
		S.submitted = !!st.submitted;
		S.goalsDone = !!st.goals_done;
		S.partnerFit = !!st.partner_fit;
		try { localStorage.setItem(LS_KEY, S.token); } catch (e) { /* private mode */ }
		var url = new URL(location.href);
		if (url.searchParams.get('session') !== S.token) {
			url.searchParams.set('session', S.token);
			history.replaceState(history.state, '', url.toString());
		}
		try { window.posthog && window.posthog.register && window.posthog.register({ dotaznik_session: S.session }); } catch (e) { /* noop */ }
	}

	/* Autosave: debounced, serialised, creates the session on first answer. */
	var saving = Promise.resolve();
	var savedEl;
	var dirty = false;

	function setSaved(state) {
		if (!savedEl) return;
		savedEl.classList.toggle('is-on', state !== 'idle');
		savedEl.classList.toggle('is-saving', state === 'saving');
		savedEl.textContent = state === 'saving' ? 'Ukládám…' : 'Uloženo';
		if (state === 'saved') { clearTimeout(setSaved.t); setSaved.t = setTimeout(function () { setSaved('idle'); }, 1800); }
	}

	function saveNow() {
		scheduleSave.cancel();
		if (!dirty && S.token) return saving;
		dirty = false;
		saving = saving.then(function () {
			var hasAnswer = Object.keys(S.answers).some(function (k) { return isFilled(S.answers[k]); });
			if (!S.token && !hasAnswer) return;
			setSaved('saving');
			var req = S.token
				? api('/sessions/' + S.token, 'POST', { answers: payload(), step: S.stepId })
				: api('/sessions', 'POST', { answers: payload(), step: S.stepId });
			return req.then(function (st) {
				var keep = S.answers;
				applyState(st);
				S.answers = keep; // local edits made while saving win
				setSaved('saved');
			}).catch(function (e) {
				dirty = true;
				setSaved('idle');
				if (e.status === 404) { resetSession(); return; }
				toast('Nepodařilo se uložit. Zkontrolujte připojení, zkusíme to znovu.', true);
			});
		});
		return saving;
	}

	var scheduleSave = debounce(saveNow, 700);

	function markDirty() {
		dirty = true;
		if (!S.started) {
			S.started = true;
			ph('dotaznik_started', { step: S.stepId });
		}
		scheduleSave();
	}

	function resetSession() {
		try { localStorage.removeItem(LS_KEY); } catch (e) { /* noop */ }
		S.token = null;
		var url = new URL(location.href);
		url.searchParams.delete('session');
		history.replaceState(null, '', url.toString());
	}

	/* ------------------------------------------------------------------
	 * Shell
	 * ---------------------------------------------------------------- */

	var head, backBtn, bar, count, panel;

	function buildShell() {
		root.innerHTML = '';
		head = h('header', { class: 'vdq-head' });
		backBtn = h('button', { class: 'vdq-back', type: 'button', 'aria-label': 'Zpět' }, ICON.back);
		var prog = h('div', { class: 'vdq-progress', role: 'progressbar', 'aria-valuemin': '0', 'aria-valuemax': '100' });
		bar = h('i');
		prog.appendChild(bar);
		count = h('div', { class: 'vdq-count' });
		head.appendChild(backBtn);
		head.appendChild(prog);
		head.appendChild(count);
		root.appendChild(head);
		savedEl = h('div', { class: 'vdq-saved', 'aria-live': 'polite' });
		root.appendChild(savedEl);
		backBtn.addEventListener('click', goBack);
		head.progress = prog;
	}

	function updateHeader() {
		var steps = visiblePre();
		var idx = steps.findIndex(function (s) { return s.id === S.stepId; });
		var post = idx === -1;
		head.classList.toggle('is-hidden', post);
		backBtn.hidden = post || idx <= 0;
		if (post) return;
		var step = steps[idx];
		var qs = step.questions.filter(isVisible);
		var req = qs.filter(isRequired);
		var done = req.filter(function (q) { return !errorOf(q); }).length;
		var frac = req.length ? done / req.length : 1;
		var pct = Math.round(((idx + frac * 0.85) / steps.length) * 100);
		bar.style.width = Math.max(3, pct) + '%';
		head.progress.setAttribute('aria-valuenow', String(pct));
		count.textContent = (idx + 1) + '/' + steps.length;
	}

	/* ------------------------------------------------------------------
	 * Question renderers
	 * ---------------------------------------------------------------- */

	var Q = {}; // question id -> { q, wrap, err, status, refresh }

	function renderQuestion(q) {
		var wrap = h('div', { class: 'vdq-q' + (q.inline ? ' is-inline' : ''), 'data-q': q.id });
		var inner = h('div', { class: 'vdq-q-in' });
		var pad = h('div', { class: 'vdq-q-pad' });
		inner.appendChild(pad);
		wrap.appendChild(inner);

		var labelId = 'vdq-l-' + q.id;
		var tag = null;
		var isGroup = q.type === 'single' || q.type === 'multi' || q.type === 'social';
		if (q.type !== 'checkbox') {
			var label = h(isGroup ? 'div' : 'label', { class: 'vdq-label', id: labelId, 'for': isGroup ? null : 'vdq-i-' + q.id, text: q.label });
			var tag = h('span', { class: 'vdq-opt-tag', text: 'nepovinné', hidden: true });
			label.appendChild(tag);
			pad.appendChild(label);
			if (q.hint) pad.appendChild(h('p', { class: 'vdq-hint', text: q.hint }));
		}

		var entry = { q: q, wrap: wrap, tag: tag, refresh: function () {} };
		var control;
		if (TEXT_TYPES[q.type]) control = textControl(q, entry);
		else if (q.type === 'single' || q.type === 'multi' || q.type === 'social') control = choiceControl(q, entry, labelId);
		else if (q.type === 'checkbox') control = checkboxControl(q, entry);
		pad.appendChild(control);

		entry.err = h('div', { class: 'vdq-err', role: 'alert' }, '<span></span>');
		pad.appendChild(entry.err);
		Q[q.id] = entry;
		return wrap;
	}

	function textControl(q, entry) {
		var box = h('div');
		var field = h('div', { class: 'vdq-field' });
		var multiline = q.type === 'textarea';
		var input = h(multiline ? 'textarea' : 'input', {
			id: 'vdq-i-' + q.id,
			class: multiline ? 'vdq-textarea' : 'vdq-input',
			name: q.id,
			placeholder: q.placeholder || '',
			maxlength: q.max || (q.type === 'email' ? 190 : q.type === 'url' ? 255 : null),
			autocomplete: q.autocomplete || (q.type === 'email' ? 'email' : q.type === 'url' ? 'url' : q.type === 'tel' ? 'tel' : 'off'),
			inputmode: q.type === 'email' ? 'email' : q.type === 'url' ? 'url' : q.type === 'tel' ? 'tel' : null,
			type: multiline ? null : (q.type === 'email' ? 'email' : q.type === 'tel' ? 'tel' : 'text'),
			autocapitalize: (q.type === 'email' || q.type === 'url' || q.type === 'handle') ? 'off' : null,
			spellcheck: (q.type === 'email' || q.type === 'url' || q.type === 'handle') ? 'false' : null,
			enterkeyhint: multiline ? null : 'next',
			'aria-describedby': 'vdq-e-' + q.id
		});
		input.value = S.answers[q.id] || '';

		var icon = q.type === 'handle' ? ICON[q.network] : q.type === 'url' ? ICON.globe : q.type === 'email' ? ICON.mail : q.type === 'tel' ? ICON.phone : null;
		if (icon) {
			field.classList.add('has-icon');
			field.appendChild(h('span', { class: 'vdq-field-icon', 'aria-hidden': 'true' }, icon));
		}
		if (q.type === 'handle') {
			field.classList.add('has-prefix');
			field.appendChild(h('span', { class: 'vdq-prefix', 'aria-hidden': 'true', text: '@' }));
		}
		field.appendChild(input);

		var verifiable = q.verify || q.type === 'handle';
		var statusIcon, statusLine;
		if (verifiable) {
			field.classList.add('has-status');
			statusIcon = h('span', { class: 'vdq-status-icon', 'aria-hidden': 'true' });
			field.appendChild(statusIcon);
			statusLine = h('div', { class: 'vdq-status', 'aria-live': 'polite' }, '<span></span>');
		}
		if (multiline && q.max) {
			var counter = h('span', { class: 'vdq-counter' });
			field.appendChild(counter);
		}
		box.appendChild(field);
		if (statusLine) box.appendChild(statusLine);

		var revealSoon = debounce(function () { refresh(); }, 650);
		var verifySoon = debounce(function () { runVerify(q); }, 1000);

		function updateCounter() {
			if (!counter) return;
			var n = input.value.length;
			counter.textContent = n + ' / ' + q.max;
			counter.classList.toggle('is-near', n > q.max * 0.9);
		}
		updateCounter();

		input.addEventListener('input', function () {
			var v = input.value;
			S.answers[q.id] = q.type === 'handle' ? normalizeHandle(v) : v;
			updateCounter();
			markDirty();
			if (entry.wrap.classList.contains('has-error')) showError(q);
			updateHeader();
			updateCta();
			revealSoon();
			if (verifiable) {
				setVerify(q, null);
				verifySoon();
			}
		});
		input.addEventListener('blur', function () {
			if (q.type === 'handle' && input.value && input.value !== normalizeHandle(input.value)) input.value = normalizeHandle(input.value);
			if (isFilled(S.answers[q.id])) { S.touched[q.id] = true; showError(q); trackAnswered(q); }
			revealSoon.cancel();
			refresh();
			if (verifiable && isFilled(S.answers[q.id]) && !S.verify[q.id]) { verifySoon.cancel(); runVerify(q); }
		});
		input.addEventListener('keydown', function (e) {
			if (e.key !== 'Enter' || e.isComposing) return;
			if (multiline && !(e.metaKey || e.ctrlKey)) return;
			e.preventDefault();
			revealSoon.cancel();
			S.touched[q.id] = true;
			if (errorOf(q)) { showError(q, true); return; }
			trackAnswered(q);
			refresh();
			advanceFrom(q);
		});

		entry.input = input;
		entry.statusIcon = statusIcon;
		entry.statusLine = statusLine;
		entry.refresh = function () {
			if (document.activeElement !== input && input.value !== (S.answers[q.id] || '')) input.value = S.answers[q.id] || '';
			updateCounter();
			if (q.skip_field) input.disabled = !!S.answers[q.skip_field];
		};

		if (q.skip_field) {
			var skip = h('button', { class: 'vdq-skip', type: 'button', 'aria-pressed': S.answers[q.skip_field] ? 'true' : 'false' },
				'<span class="vdq-opt-mark" style="position:static;margin:0;width:18px;height:18px;border-radius:6px">' + ICON.check + '</span><span></span>');
			skip.lastChild.textContent = q.skip_label;
			var paintSkip = function (on) {
				skip.setAttribute('aria-pressed', on ? 'true' : 'false');
				var mark = skip.firstChild;
				mark.style.background = on ? 'var(--vdq-accent)' : '';
				mark.style.borderColor = on ? 'var(--vdq-accent)' : '';
				mark.firstChild.style.opacity = on ? '1' : '';
				mark.firstChild.style.transform = on ? 'none' : '';
			};
			skip.addEventListener('click', function () {
				var on = !S.answers[q.skip_field];
				S.answers[q.skip_field] = on;
				paintSkip(on);
				if (on) {
					S.answers[q.id] = '';
					input.value = '';
					setVerify(q, null);
					entry.wrap.classList.remove('has-error');
					entry.err.classList.remove('is-on');
				}
				input.disabled = on;
				ph('dotaznik_skip_toggled', { question: q.id, on: on });
				markDirty();
				refresh({ focusNext: on ? q : null });
				if (!on) input.focus();
			});
			if (S.answers[q.skip_field]) { input.disabled = true; paintSkip(true); }
			box.appendChild(skip);
		}

		if (verifiable && isFilled(S.answers[q.id])) setTimeout(function () { runVerify(q); }, 400);
		return box;
	}

	function choiceControl(q, entry, labelId) {
		var multi = q.type === 'multi' || q.type === 'social';
		var tiles = q.type === 'social';
		var cols = tiles ? 4 : (q.columns || 1);
		var colsM = tiles ? 2 : (cols >= 3 ? 2 : cols);
		if (!tiles && q.options.some(function (o) { return o.label.length > 22; }) && cols > 1) colsM = 1;
		var grid = h('div', {
			class: 'vdq-options' + (tiles ? ' is-social' : ''),
			role: multi ? 'group' : 'radiogroup',
			'aria-labelledby': labelId,
			style: '--cols:' + cols + ';--cols-m:' + colsM
		});
		var inputs = [];
		q.options.forEach(function (o) {
			var lab = h('label', { class: 'vdq-opt' + (multi ? ' is-multi' : '') + (tiles ? ' is-tile' : '') + (o.exclusive ? ' is-none' : '') });
			var input = h('input', { type: multi ? 'checkbox' : 'radio', name: 'vdq-' + q.id, value: o.value });
			var boxEl = h('span', { class: 'vdq-opt-box' });
			if (tiles) boxEl.appendChild(h('span', { class: 'vdq-tile-icon', 'aria-hidden': 'true' }, ICON[o.value] || ICON.none));
			boxEl.appendChild(h('span', { class: 'vdq-opt-label', text: o.label }));
			if (o.hint) boxEl.appendChild(h('span', { class: 'vdq-opt-hint', text: o.hint }));
			boxEl.appendChild(h('span', { class: 'vdq-opt-mark', 'aria-hidden': 'true' }, ICON.check));
			lab.appendChild(input);
			lab.appendChild(boxEl);
			grid.appendChild(lab);
			inputs.push(input);

			input.addEventListener('change', function () {
				if (multi) {
					var cur = Array.isArray(S.answers[q.id]) ? S.answers[q.id].slice() : [];
					if (input.checked) {
						if (o.exclusive) cur = [];
						else cur = cur.filter(function (v) { return !q.options.some(function (x) { return x.exclusive && x.value === v; }); });
						cur.push(o.value);
					} else {
						cur = cur.filter(function (v) { return v !== o.value; });
					}
					S.answers[q.id] = cur;
					sync();
				} else {
					S.answers[q.id] = o.value;
				}
				S.touched[q.id] = true;
				showError(q);
				trackAnswered(q, true);
				markDirty();
				var single = !multi;
				refresh({ focusNext: single ? q : null, fromPointer: true });
			});
		});

		function sync() {
			var v = S.answers[q.id];
			inputs.forEach(function (i) {
				i.checked = multi ? (Array.isArray(v) && v.indexOf(i.value) !== -1) : v === i.value;
			});
		}
		sync();
		entry.refresh = sync;
		entry.inputs = inputs;
		return grid;
	}

	function checkboxControl(q, entry) {
		var lab = h('label', { class: 'vdq-checkbox' });
		var input = h('input', { type: 'checkbox', name: 'vdq-' + q.id });
		input.checked = !!S.answers[q.id];
		lab.appendChild(input);
		lab.appendChild(h('span', { class: 'vdq-opt-mark', 'aria-hidden': 'true' }, ICON.check));
		lab.appendChild(h('span', { class: 'vdq-checkbox-text', text: q.label }));
		input.addEventListener('change', function () {
			S.answers[q.id] = input.checked;
			ph('dotaznik_consent_changed', { question: q.id, value: input.checked });
			markDirty();
			refresh();
		});
		entry.input = input;
		entry.refresh = function () { input.checked = !!S.answers[q.id]; };
		return lab;
	}

	function showError(q, shake) {
		var e = Q[q.id];
		if (!e) return;
		var msg = S.touched[q.id] ? errorOf(q) : '';
		e.wrap.classList.toggle('has-error', !!msg);
		e.err.classList.toggle('is-on', !!msg);
		e.err.firstChild.textContent = msg;
		e.err.firstChild.id = 'vdq-e-' + q.id;
		if (msg && shake) {
			var target = e.wrap.querySelector('.vdq-field, .vdq-options, .vdq-checkbox');
			if (target) { target.classList.remove('vdq-shake'); void target.offsetWidth; target.classList.add('vdq-shake'); }
		}
	}

	function trackAnswered(q, force) {
		if (S.tracked[q.id] || (!force && errorOf(q))) return;
		S.tracked[q.id] = true;
		ph('dotaznik_question_answered', { question: q.id, step: S.stepId });
	}

	/* ------------------------------------------------------------------
	 * Website and handle verification
	 * ---------------------------------------------------------------- */

	function setVerify(q, status, value) {
		var e = Q[q.id];
		if (status) S.verify[q.id] = { status: status, value: value };
		else delete S.verify[q.id];
		if (!e || !e.statusIcon) return;
		var icon = e.statusIcon, line = e.statusLine, text = line.firstChild;
		line.classList.remove('is-ok', 'is-warn');
		var isUrl = q.type === 'url';
		if (status === 'loading') {
			icon.innerHTML = '<span class="vdq-spin"></span>';
			text.innerHTML = '<span class="vdq-spin"></span><span></span>';
			text.lastChild.textContent = isUrl ? 'Ověřujeme web…' : 'Ověřujeme profil…';
		} else if (status === 'ok') {
			icon.innerHTML = '<span class="vdq-check">' + ICON.check + '</span>';
			text.innerHTML = '<span class="vdq-check">' + ICON.check + '</span><span></span>';
			text.lastChild.textContent = isUrl ? 'Web funguje' : 'Profil nalezen';
			line.classList.add('is-ok');
		} else if (status === 'notfound') {
			icon.innerHTML = '';
			text.innerHTML = '<span class="vdq-warn-dot">!</span><span></span>';
			text.lastChild.textContent = isUrl ? 'Jste si jistí, že adresa byla zadána správně?' : 'Tento profil jsme nenašli. Jste si jistí, že je jméno správně?';
			line.classList.add('is-warn');
		} else {
			icon.innerHTML = '';
			text.innerHTML = '';
		}
		icon.classList.toggle('is-on', status === 'loading' || status === 'ok');
		line.classList.toggle('is-on', status === 'loading' || status === 'ok' || status === 'notfound');
	}

	function withTimeout(promise, ms) {
		return Promise.race([promise, new Promise(function (_, rej) { setTimeout(function () { rej(new Error('timeout')); }, ms); })]);
	}

	function runVerify(q) {
		var raw = S.answers[q.id];
		if (!isFilled(raw) || validateValue(q, raw)) { setVerify(q, null); return; }
		var value = q.type === 'url' ? normalizeUrl(raw) : normalizeHandle(raw);
		if (S.verify[q.id] && S.verify[q.id].value === value && S.verify[q.id].status !== 'loading') return;
		setVerify(q, 'loading', value);
		var started = Date.now();
		var job;
		if (q.type === 'url') {
			// Browser: an opaque no-cors response means the site answered.
			var client = withTimeout(fetch(value, { mode: 'no-cors', cache: 'no-store', credentials: 'omit', redirect: 'follow' }), 6000)
				.then(function () { return 'ok'; }, function () { return 'notfound'; });
			var server = withTimeout(api('/verify/website', 'POST', { url: value }), 9000)
				.then(function (r) { return r.status; }, function () { return 'unknown'; });
			job = Promise.all([client, server]).then(function (r) {
				return (r[0] === 'ok' || r[1] === 'ok') ? 'ok' : (r[1] === 'unknown' && r[0] !== 'notfound' ? 'unknown' : 'notfound');
			});
		} else {
			job = withTimeout(api('/verify/handle', 'POST', { network: q.network, handle: value }), 10000)
				.then(function (r) { return r.status === 'invalid' ? 'unknown' : r.status; }, function () { return 'unknown'; });
		}
		job.then(function (status) {
			// Keep the spinner visible briefly so the result does not flash.
			setTimeout(function () {
				var cur = q.type === 'url' ? normalizeUrl(S.answers[q.id]) : normalizeHandle(S.answers[q.id]);
				if (cur !== value) return;
				setVerify(q, status, value);
				ph(q.type === 'url' ? 'dotaznik_website_verified' : 'dotaznik_handle_verified', { result: status, network: q.network || null });
			}, Math.max(0, 450 - (Date.now() - started)));
		});
	}

	/* ------------------------------------------------------------------
	 * Reveal logic: one question at a time
	 * ---------------------------------------------------------------- */

	var cta, ctaWrap, formError;

	function currentStep() { return stepById(S.stepId); }

	function stepQuestionsShown(step) {
		return step.questions.filter(function (q) { return Q[q.id] && Q[q.id].wrap.classList.contains('is-shown'); });
	}

	/**
	 * Shows every visible question up to and including the first required one
	 * that is not answered yet. Questions marked `inline` follow the one above.
	 * Once revealed, a question stays revealed.
	 */
	function refresh(opts) {
		opts = opts || {};
		var step = currentStep();
		if (!step || (step.post && !S.goalsOpen)) { updateCta(); return; }
		var rev = S.revealed[step.id] || (S.revealed[step.id] = {});
		var blocked = false, prevShown = false, newly = [];
		step.questions.forEach(function (q) {
			var e = Q[q.id];
			if (!e) return;
			e.refresh();
			if (e.tag) e.tag.hidden = !!q.inline || !!q.skip_field || isRequired(q);
			var show;
			if (!isVisible(q)) show = false;
			else if (q.inline) show = prevShown;
			else show = !blocked || !!rev[q.id];
			if (!q.inline) prevShown = show;
			if (show && !rev[q.id]) newly.push(q);
			if (show) rev[q.id] = true;
			else if (!isVisible(q)) delete rev[q.id];
			setShown(e, show);
			if (show && isRequired(q) && errorOf(q)) blocked = true;
		});
		updateHeader();
		updateCta();

		if (opts.focusNext && newly.length) {
			var target = newly.filter(function (q) { return !q.inline; })[0] || newly[0];
			setTimeout(function () { focusQuestion(target, opts.fromPointer); }, 260);
		} else if (opts.focusNext && !newly.length && opts.fromPointer && !errorOf(opts.focusNext)) {
			// Last question of the step answered: bring the button into view.
			if (stepValid()) setTimeout(function () { scrollIntoViewIfNeeded(ctaWrap); }, 200);
		}
	}

	function setShown(e, show) {
		if (e.wrap.classList.contains('is-shown') === show) return;
		e.wrap.classList.add('is-animating');
		e.wrap.classList.toggle('is-shown', show);
		e.wrap.setAttribute('aria-hidden', show ? 'false' : 'true');
		e.wrap.querySelectorAll('input,textarea,button').forEach(function (el) {
			if (show) el.removeAttribute('tabindex'); else el.setAttribute('tabindex', '-1');
		});
		clearTimeout(e.animT);
		e.animT = setTimeout(function () { e.wrap.classList.remove('is-animating'); }, 520);
	}

	function scrollIntoViewIfNeeded(el) {
		if (!el) return;
		var r = el.getBoundingClientRect();
		var footH = ctaWrap ? ctaWrap.getBoundingClientRect().height : 0;
		if (el === ctaWrap) { if (r.bottom > window.innerHeight) window.scrollBy({ top: r.bottom - window.innerHeight + 8, behavior: 'smooth' }); return; }
		if (r.bottom > window.innerHeight - footH - 16 || r.top < 80) {
			window.scrollBy({ top: r.top - Math.max(96, window.innerHeight * 0.22), behavior: 'smooth' });
		}
	}

	function focusQuestion(q, fromPointer) {
		var e = Q[q.id];
		if (!e) return;
		scrollIntoViewIfNeeded(e.wrap);
		if (e.input && TEXT_TYPES[q.type] && !e.input.disabled) {
			if (!(touchDevice && fromPointer)) e.input.focus({ preventScroll: true });
		}
	}

	function advanceFrom(q) {
		var step = currentStep();
		var shown = stepQuestionsShown(step);
		var i = shown.indexOf(q);
		for (var j = i + 1; j < shown.length; j++) {
			var n = shown[j];
			if (Q[n.id].input && TEXT_TYPES[n.type] && !Q[n.id].input.disabled) { focusQuestion(n); return; }
			if (!TEXT_TYPES[n.type]) { scrollIntoViewIfNeeded(Q[n.id].wrap); var first = Q[n.id].wrap.querySelector('input'); if (first) first.focus({ preventScroll: true }); return; }
		}
		next();
	}

	function stepValid() {
		var step = currentStep();
		if (!step) return false;
		return step.questions.every(function (q) { return !isVisible(q) || !errorOf(q); });
	}

	function updateCta() {
		if (!cta) return;
		cta.setAttribute('aria-disabled', stepValid() ? 'false' : 'true');
	}

	/* ------------------------------------------------------------------
	 * Steps
	 * ---------------------------------------------------------------- */

	function swapPanel(build, back) {
		var old = panel;
		var mount = function () {
			if (old) old.remove();
			Object.keys(Q).forEach(function (k) { delete Q[k]; });
			cta = ctaWrap = formError = null;
			panel = h('section', { class: 'vdq-panel' + (back ? ' is-back' : '') });
			build(panel);
			root.appendChild(panel);
			window.scrollTo({ top: 0, behavior: 'instant' in document.documentElement.style ? 'instant' : 'auto' });
			requestAnimationFrame(function () { requestAnimationFrame(function () { panel.classList.add('is-in'); }); });
		};
		if (old) {
			old.classList.add('is-out');
			setTimeout(mount, 190);
		} else {
			mount();
		}
	}

	function renderStep(id, opts) {
		opts = opts || {};
		var step = stepById(id);
		if (!step) return;
		S.stepId = id;
		if (!opts.noHistory) {
			var st = { vdqStep: id };
			if (opts.replace) history.replaceState(st, ''); else history.pushState(st, '');
		}
		var idx = visiblePre().indexOf(step);
		ph('dotaznik_step_viewed', { step: id, step_number: idx + 1, post: !!step.post });

		swapPanel(function (p) {
			if (step.post) p.appendChild(h('div', { class: 'vdq-done-mark', 'aria-hidden': 'true' }, ICON.check));
			p.appendChild(h('h1', { class: 'vdq-title', text: step.title }));
			if (step.subtitle) p.appendChild(h('p', { class: 'vdq-sub' }, step.subtitle));

			if (step.post) {
				p.appendChild(h('div', { class: 'vdq-section-label', text: 'Mezitím se podívejte' }));
				p.appendChild(eduCards());
				var optin = h('div', { class: 'vdq-optin' + (S.goalsOpen ? ' is-hidden' : '') });
				var ob = h('button', { class: 'vdq-btn vdq-btn-ghost', type: 'button' });
				ob.textContent = step.opt_in || 'Pokračovat';
				optin.appendChild(ob);
				p.appendChild(optin);
				ob.addEventListener('click', function () {
					S.goalsOpen = true;
					optin.classList.add('is-hidden');
					qs.hidden = false;
					foot.hidden = false;
					ph('dotaznik_goals_opened');
					refresh();
					var first = step.questions[0];
					setTimeout(function () { scrollIntoViewIfNeeded(Q[first.id].wrap); }, 120);
				});
			}

			var qs = h('div', { class: 'vdq-qs' });
			step.questions.forEach(function (q) { qs.appendChild(renderQuestion(q)); });
			p.appendChild(qs);

			var foot = h('div', { class: 'vdq-foot' });
			if (step.notice) foot.appendChild(h('p', { class: 'vdq-notice' }, step.notice));
			cta = h('button', { class: 'vdq-btn', type: 'button' });
			cta.appendChild(h('span', { text: step.cta || 'Pokračovat' }));
			cta.insertAdjacentHTML('beforeend', ICON.arrow);
			foot.appendChild(cta);
			foot.appendChild(h('p', { class: 'vdq-kbd' }, 'nebo stiskněte <kbd>Enter ↵</kbd>'));
			formError = h('p', { class: 'vdq-form-error', role: 'alert', hidden: true });
			foot.appendChild(formError);
			ctaWrap = foot;
			p.appendChild(foot);
			cta.addEventListener('click', next);

			if (step.post && !S.goalsOpen) { qs.hidden = true; foot.hidden = true; }
		}, opts.back);

		setTimeout(function () {
			refresh();
			updateHeader();
			if (!step.post && !touchDevice) {
				var first = step.questions.filter(function (q) { return Q[q.id] && Q[q.id].wrap.classList.contains('is-shown') && TEXT_TYPES[q.type] && !isFilled(S.answers[q.id]); })[0];
				if (first) Q[first.id].input.focus({ preventScroll: true });
			}
		}, 200);
	}

	function next() {
		var step = currentStep();
		if (!step || (cta && cta.classList.contains('is-loading'))) return;
		var invalid = step.questions.filter(function (q) { return isVisible(q) && errorOf(q); });
		if (invalid.length) {
			// Reveal everything up to the problem and point at it.
			invalid.forEach(function (q) { S.touched[q.id] = true; });
			refresh();
			invalid.forEach(function (q) { showError(q, true); });
			var first = invalid.filter(function (q) { return Q[q.id].wrap.classList.contains('is-shown'); })[0] || invalid[0];
			focusQuestion(first);
			ph('dotaznik_validation_error', { step: step.id, questions: invalid.map(function (q) { return q.id; }) });
			return;
		}

		var pre = visiblePre();
		var idx = pre.indexOf(step);
		ph('dotaznik_step_completed', { step: step.id, step_number: idx + 1 });

		if (step.id === 'start' && !S.identified && S.answers.email) identify();

		if (step.id === DEF.submit_step) return submit();
		if (step.post) return submitGoals();

		var nextStep = pre[idx + 1];
		S.stepId = nextStep.id;
		dirty = true;
		saveNow();
		renderStep(nextStep.id);
	}

	function goBack() {
		var pre = visiblePre();
		var idx = pre.findIndex(function (s) { return s.id === S.stepId; });
		if (idx > 0) {
			renderStep(pre[idx - 1].id, { back: true });
			dirty = true;
			saveNow();
		}
	}

	window.addEventListener('popstate', function (e) {
		var id = e.state && e.state.vdqStep;
		if (!id) return;
		var step = stepById(id);
		if (!step) return;
		if (S.submitted && !step.post) {
			// Finished questionnaires cannot be edited; stay on the end screen.
			history.pushState({ vdqStep: S.stepId }, '');
			return;
		}
		var pre = visiblePre();
		var back = pre.indexOf(step) < pre.findIndex(function (s) { return s.id === S.stepId; });
		renderStep(id, { noHistory: true, back: back });
		dirty = true;
		saveNow();
	});

	function identify() {
		S.identified = true;
		try {
			if (window.posthog && window.posthog.identify) {
				window.posthog.identify(String(S.answers.email).toLowerCase().trim(), {
					email: String(S.answers.email).toLowerCase().trim(),
					company_type: S.answers.company_type || null
				});
			}
		} catch (e) { /* noop */ }
	}

	function setLoading(on) {
		if (!cta) return;
		cta.classList.toggle('is-loading', on);
		cta.setAttribute('aria-busy', on ? 'true' : 'false');
	}

	function showFormError(msg) {
		if (!formError) return;
		formError.hidden = !msg;
		formError.textContent = msg || '';
	}

	function submit() {
		setLoading(true);
		showFormError('');
		saveNow().then(function () {
			if (!S.token) return saveNow();
		}).then(function () {
			return api('/sessions/' + S.token + '/submit', 'POST', { answers: payload() });
		}).then(function (st) {
			applyState(st);
			try {
				window.posthog && window.posthog.people && window.posthog.people.set && window.posthog.people.set({
					name: S.answers.name || null,
					role: S.answers.role || null,
					company_type: S.answers.company_type || null,
					budget: S.answers.budget || null
				});
			} catch (e) { /* noop */ }
			ph('dotaznik_submitted', {
				partner_fit: S.partnerFit,
				company_type: S.answers.company_type,
				budget: S.answers.budget,
				ad_spend: S.answers.ad_spend,
				source: S.answers.source,
				has_phone: isFilled(S.answers.phone),
				newsletter: !!S.answers.consent_newsletter,
				partner_consent: !!S.answers.consent_partner
			});
			afterSubmit(true);
		}).catch(function (e) {
			setLoading(false);
			if (e.status === 422 && e.data && e.data.step) {
				Object.keys(e.data.errors || {}).forEach(function (k) { S.touched[k] = true; });
				if (e.data.step !== S.stepId) renderStep(e.data.step, { back: true });
				else { refresh(); Object.keys(e.data.errors || {}).forEach(function (k) { var q = Q[k] && Q[k].q; if (q) showError(q, true); }); }
				return;
			}
			showFormError('Odeslání se nepovedlo. Zkuste to prosím znovu.');
		});
	}

	function afterSubmit(fresh) {
		var post = POST.filter(stepVisible)[0];
		if (post && !S.goalsDone) {
			renderStep(post.id, { replace: !fresh });
		} else {
			renderDone();
		}
	}

	function submitGoals() {
		setLoading(true);
		showFormError('');
		saveNow().then(function () {
			return api('/sessions/' + S.token + '/goals', 'POST', { answers: payload() });
		}).then(function (st) {
			applyState(st);
			ph('dotaznik_goals_completed', { genz: S.answers.genz, goal: S.answers.goal, horizon: S.answers.horizon, looking_for: S.answers.looking_for });
			renderDone();
		}).catch(function () {
			setLoading(false);
			showFormError('Odeslání se nepovedlo. Zkuste to prosím znovu.');
		});
	}

	/* ------------------------------------------------------------------
	 * End screen and educational content
	 * ---------------------------------------------------------------- */

	function eduCards() {
		var wrap = h('div', { class: 'vdq-edu-list' });
		(DEF.education || []).forEach(function (c, i) {
			var a = h('a', { class: 'vdq-edu', href: c.url, target: '_blank', rel: 'noopener' });
			var thumb = h('div', { class: 'vdq-edu-thumb' });
			thumb.appendChild(h('img', { src: c.image, alt: '', loading: 'lazy', decoding: 'async' }));
			thumb.appendChild(h('span', { class: 'vdq-edu-play', 'aria-hidden': 'true' }));
			var body = h('div', { class: 'vdq-edu-body' });
			body.appendChild(h('span', { class: 'vdq-edu-eyebrow', text: c.eyebrow }));
			body.appendChild(h('span', { class: 'vdq-edu-title', text: c.title }));
			body.appendChild(h('span', { class: 'vdq-edu-text', text: c.text }));
			body.appendChild(h('span', { class: 'vdq-edu-cta', text: c.cta }));
			a.appendChild(thumb);
			a.appendChild(body);
			a.addEventListener('click', function () { ph('dotaznik_education_clicked', { index: i, url: c.url, step: S.stepId }); });
			wrap.appendChild(a);
		});
		return wrap;
	}

	function renderDone() {
		S.stepId = 'done';
		try { localStorage.removeItem(LS_KEY); } catch (e) { /* noop */ }
		history.replaceState({ vdqStep: 'done' }, '');
		ph('dotaznik_step_viewed', { step: 'done', post: true });
		updateHeader();
		var fit = S.partnerFit;
		var consent = !!S.answers.consent_partner;
		swapPanel(function (p) {
			p.appendChild(h('div', { class: 'vdq-done-mark', 'aria-hidden': 'true' }, ICON.check));
			p.appendChild(h('h1', { class: 'vdq-title', text: 'Děkujeme!' }));
			var text = fit
				? (consent
					? 'Odpovědi máme. S vaším souhlasem je předáme partnerské agentuře Fellaship, která se vám ozve. Potvrzení jsme vám poslali e-mailem.'
					: 'Odpovědi máme a brzy se vám ozveme e-mailem. Potvrzení už máte ve schránce.')
				: 'Odpovědi máme. Matyáš Brůna se vám ozve do 24 hodin. Potvrzení už máte ve schránce.';
			p.appendChild(h('p', { class: 'vdq-sub', text: text }));
			if (fit && C.partnerUrl) {
				var pl = h('a', { class: 'vdq-partner', href: C.partnerUrl, target: '_blank', rel: 'noopener' });
				pl.innerHTML = '<div><strong>Fellaship</strong><span>Naše partnerská agentura</span></div>' + ICON.external;
				pl.addEventListener('click', function () { ph('dotaznik_partner_clicked'); });
				p.appendChild(pl);
			}
			p.appendChild(h('div', { class: 'vdq-section-label', text: 'Mezitím se podívejte' }));
			p.appendChild(eduCards());
			var actions = h('div', { class: 'vdq-actions' });
			var home = h('a', { class: 'vdq-btn vdq-btn-ghost', href: C.homeUrl, text: 'Zpět na web' });
			actions.appendChild(home);
			p.appendChild(actions);
			p.appendChild(h('div', { class: 'vdq-foot-spacer' }));
		});
	}

	/* ------------------------------------------------------------------
	 * Boot
	 * ---------------------------------------------------------------- */

	function start(resumed) {
		buildShell();
		var params = new URLSearchParams(location.search);
		ph('dotaznik_viewed', { resumed: !!resumed, ref: params.get('ref') || null });
		if (params.get('ref') === 'reminder') ph('dotaznik_reminder_clicked', { resumed: !!resumed });
		if (S.answers.email) S.identified = !!resumed;
		Object.keys(S.answers).forEach(function (k) { if (isFilled(S.answers[k])) S.tracked[k] = true; });
		S.started = resumed && Object.keys(S.answers).length > 0;

		if (S.submitted) { afterSubmit(false); return; }

		var pre = visiblePre();
		var step = stepById(S.stepId);
		if (!step || step.post || pre.indexOf(step) === -1) step = pre[0];
		// Pre-reveal answered questions on earlier steps so Back shows them.
		renderStep(step.id, { replace: true });
		if (resumed && Object.keys(S.answers).length) {
			setTimeout(function () { toast('Vítejte zpět, odpovědi máme uložené.'); }, 500);
		}
	}

	function boot() {
		var params = new URLSearchParams(location.search);
		var token = params.get('session');
		if (!token) { try { token = localStorage.getItem(LS_KEY); } catch (e) { token = null; } }
		if (!token || !/^[A-Za-z0-9_-]{64}$/.test(token)) { start(false); return; }
		api('/sessions/' + token).then(function (st) {
			applyState(st);
			S.stepId = st.step || null;
			start(true);
		}).catch(function (e) {
			if (e.status === 404) {
				resetSession();
				if (params.get('session')) setTimeout(function () { toast('Tento odkaz už neplatí, začněte prosím znovu.', true); }, 400);
			}
			start(false);
		});
	}

	// Save before the tab goes away.
	document.addEventListener('visibilitychange', function () {
		if (document.visibilityState === 'hidden' && dirty && S.token && navigator.sendBeacon) {
			dirty = false;
			navigator.sendBeacon(C.api + '/sessions/' + S.token, new Blob([JSON.stringify({ answers: payload(), step: S.stepId })], { type: 'text/plain' }));
		}
	});

	boot();

	/* ------------------------------------------------------------------
	 * Unsubscribe page
	 * ---------------------------------------------------------------- */

	function initUnsubscribe() {
		var main = document.querySelector('.vdq-unsub');
		var btn = main && main.querySelector('[data-unsub]');
		if (!btn) return;
		btn.addEventListener('click', function () {
			btn.classList.add('is-loading');
			fetch(C.api + '/unsubscribe', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				credentials: 'omit',
				body: JSON.stringify({ e: main.getAttribute('data-e'), s: main.getAttribute('data-s') })
			}).then(function (r) {
				if (!r.ok) throw new Error();
				main.querySelector('[data-unsub-title]').textContent = 'Jste odhlášeni';
				main.querySelector('[data-unsub-text]').textContent = 'Už vám nebudeme posílat žádné e-maily.';
				btn.remove();
			}).catch(function () {
				btn.classList.remove('is-loading');
				main.querySelector('[data-unsub-text]').textContent = 'Odhlášení se nepovedlo. Zkuste to prosím znovu, nebo napište na marketing@velocity.ooo.';
			});
		});
	}
})();
