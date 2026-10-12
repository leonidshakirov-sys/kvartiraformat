(function () {
  const root = document.querySelector('[data-quiz]');
  if (!root) return;
  const form = root.querySelector('[data-quiz-form]');
  if (!form) return;

  const qs = new URLSearchParams(location.search);
  ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'].forEach((key) => {
    form.querySelectorAll(`[name="${key}"]`).forEach((input) => {
      input.value = qs.get(key) || '';
    });
  });

  let phase = 'intro';
  let index = 0;
  let started = false;

  const errorBox = root.querySelector('[data-quiz-error]');
  const progress = root.querySelector('[data-quiz-progress]');
  const count = root.querySelector('[data-quiz-count]');
  const bar = root.querySelector('[data-quiz-bar]');
  const progressbar = root.querySelector('[data-quiz-progressbar]');

  function goal(name) {
    if (!name) return;
    if (window.ym && window.KF_METRIKA_ID) {
      try { window.ym(window.KF_METRIKA_ID, 'reachGoal', name); } catch (e) {}
    }
  }

  function direction() {
    return form.querySelector('[name="quiz[direction]"]:checked')?.value || '';
  }

  function steps() {
    return Array.from(form.querySelectorAll('[data-quiz-step]')).filter((step) => {
      if (step.dataset.enabled === '0' || step.dataset.skip === '1') return false;
      const branch = step.dataset.branch || '';
      return branch === '' || branch === direction();
    });
  }

  function showError(text) {
    if (!errorBox) return;
    errorBox.hidden = !text;
    errorBox.textContent = text || '';
  }

  function show(nextPhase) {
    phase = nextPhase;
    const intro = root.querySelector('[data-quiz-screen="intro"]');
    const result = form.querySelector('[data-quiz-screen="result"]');
    const contact = form.querySelector('[data-quiz-screen="contact"]');
    const stepNav = form.querySelector('[data-quiz-stepnav]');
    if (intro) intro.hidden = nextPhase !== 'intro';
    form.hidden = nextPhase === 'intro';
    if (result) result.hidden = nextPhase !== 'result';
    if (contact) contact.hidden = nextPhase !== 'contact';
    if (stepNav) stepNav.hidden = nextPhase !== 'step';
    form.querySelectorAll('[data-quiz-step]').forEach((step) => {
      step.hidden = true;
    });
    if (errorBox && nextPhase !== 'step') errorBox.hidden = true;

    if (nextPhase === 'intro') {
      if (progress) progress.hidden = true;
      intro?.querySelector('h2')?.focus();
      return;
    }

    if (progress) progress.hidden = false;
    if (nextPhase === 'step') {
      const list = steps();
      if (index >= list.length) index = Math.max(0, list.length - 1);
      const step = list[index];
      if (!step) return;
      step.hidden = false;
      const total = list.length || 1;
      const current = index + 1;
      if (count) count.textContent = 'Шаг ' + current + ' из ' + total;
      if (bar) bar.style.width = Math.round((current / total) * 100) + '%';
      if (progressbar) {
        progressbar.setAttribute('aria-valuenow', String(current));
        progressbar.setAttribute('aria-valuemax', String(total));
      }
      step.focus({ preventScroll: true });
      root.scrollIntoView({ block: 'nearest', inline: 'nearest' });
      return;
    }

    if (count) count.textContent = nextPhase === 'result' ? 'Расчёт готов' : 'Заявка на подробный расчёт';
    if (bar) bar.style.width = '100%';
    if (progressbar) progressbar.setAttribute('aria-valuenow', progressbar.getAttribute('aria-valuemax') || '1');
    form.querySelector('[data-quiz-screen="' + nextPhase + '"] h2')?.focus({ preventScroll: true });
    root.scrollIntoView({ block: 'nearest', inline: 'nearest' });
  }

  function validate(step) {
    if (!step || step.dataset.required !== '1') {
      if (step && step.dataset.type === 'area') {
        const input = step.querySelector('input');
        if (input && input.value.trim() !== '') {
          return areaError(step, input);
        }
      }
      return '';
    }
    if (step.dataset.type === 'area') {
      const input = step.querySelector('input');
      return areaError(step, input);
    }
    const checked = Array.from(step.querySelectorAll('input')).some((input) => input.checked);
    return checked ? '' : 'Выберите вариант ответа.';
  }

  function areaError(step, input) {
    const max = Number(step.dataset.max || 1000);
    const value = Number(String(input?.value || '').replace(',', '.'));
    if (!input || !input.value.trim() || !Number.isFinite(value) || value <= 0 || value > max) {
      return 'Укажите площадь от 1 до ' + max + ' м².';
    }
    return '';
  }

  function formData() {
    const inactive = Array.from(form.querySelectorAll('[data-quiz-step]')).filter((step) => {
      const branch = step.dataset.branch || '';
      return branch !== '' && branch !== direction();
    });
    inactive.forEach((step) => step.querySelectorAll('input').forEach((input) => { input.disabled = true; }));
    const data = new FormData(form);
    inactive.forEach((step) => step.querySelectorAll('input').forEach((input) => { input.disabled = false; }));
    return data;
  }

  function fillResult(quote) {
    const set = (selector, value) => {
      const node = root.querySelector(selector);
      if (node) node.textContent = value || '';
    };
    set('[data-quote-object]', quote.object_label);
    set('[data-quote-repair]', quote.repair_label);
    set('[data-quote-area]', quote.area_label);
    set('[data-quote-price]', quote.price_label);
    set('[data-quote-total]', quote.individual ? 'Индивидуальный расчёт' : quote.total_label);
    set('[data-quote-note]', quote.note);
    const detailRow = root.querySelector('[data-quote-detail-row]');
    if (detailRow) {
      detailRow.hidden = !quote.detail_label;
      set('[data-quote-detail-caption]', quote.detail_caption);
      set('[data-quote-detail]', quote.detail_label);
    }
    const repairRow = root.querySelector('[data-quote-repair-row]');
    if (repairRow) repairRow.hidden = !quote.repair_label;
    const areaRow = root.querySelector('[data-quote-area-row]');
    if (areaRow) areaRow.hidden = !quote.area_label;
    const priceRow = root.querySelector('[data-quote-price-row]');
    if (priceRow) priceRow.hidden = !quote.price_label || !!quote.individual;
    const basis = root.querySelector('[data-quote-basis]');
    if (basis) {
      basis.hidden = !quote.basis;
      basis.textContent = quote.basis || '';
    }
    const aside = root.querySelector('[data-quote-aside]');
    if (aside) {
      aside.hidden = !quote.aside;
      aside.textContent = quote.aside || '';
    }
  }

  async function loadQuote() {
    const nextButton = form.querySelector('[data-quiz-next]');
    if (nextButton) nextButton.disabled = true;
    try {
      const response = await fetch('/lead/estimate', {
        method: 'POST',
        body: formData(),
        headers: { 'X-Requested-With': 'fetch' }
      });
      const json = await response.json();
      if (!response.ok || json.ok === false) {
        throw new Error((json.errors || ['Не удалось рассчитать стоимость.']).join(' '));
      }
      return json.quote || json;
    } finally {
      if (nextButton) nextButton.disabled = false;
    }
  }

  async function next() {
    const list = steps();
    const step = list[index];
    const problem = validate(step);
    if (problem) {
      showError(problem);
      const field = step?.querySelector('input, button');
      field?.focus();
      return;
    }
    showError('');
    if (!started) {
      started = true;
      goal('calculator_start');
    }
    if (index >= list.length - 1) {
      try {
        fillResult(await loadQuote());
        show('result');
      } catch (error) {
        showError(error.message);
      }
      return;
    }
    index += 1;
    show('step');
  }

  function back() {
    showError('');
    if (phase === 'contact') {
      show('result');
      return;
    }
    if (phase === 'result') {
      index = Math.max(0, steps().length - 1);
      show('step');
      return;
    }
    if (index <= 0) {
      show('intro');
      return;
    }
    index -= 1;
    show('step');
  }

  function clearOtherBranches(dir) {
    form.querySelectorAll('[data-quiz-step]').forEach((step) => {
      const branch = step.dataset.branch || '';
      if (!branch || branch === dir) return;
      step.querySelectorAll('input').forEach((input) => {
        if (input.type === 'radio' || input.type === 'checkbox') input.checked = false;
        else input.value = '';
      });
    });
    const list = steps();
    if (index >= list.length) index = Math.max(0, list.length - 1);
    if (phase === 'step') show('step');
  }

  function changeArea(step, delta) {
    const input = step.querySelector('input');
    if (!input) return;
    const max = Number(step.dataset.max || 1000);
    const current = Number(String(input.value).replace(',', '.'));
    let nextValue = (Number.isFinite(current) ? current : 0) + delta;
    if (nextValue < 1) nextValue = delta > 0 ? 1 : 0;
    if (nextValue > max) nextValue = max;
    input.value = nextValue > 0 ? String(nextValue) : '';
    input.focus();
  }

  root.querySelector('[data-quiz-start]')?.addEventListener('click', () => {
    index = 0;
    show('step');
  });
  form.querySelector('[data-quiz-next]')?.addEventListener('click', () => { next(); });
  form.querySelectorAll('[data-quiz-back]').forEach((button) => button.addEventListener('click', back));
  form.querySelector('[data-quiz-estimate]')?.addEventListener('click', () => show('contact'));
  form.querySelector('[data-quiz-files]')?.addEventListener('click', () => {
    show('contact');
    form.querySelector('[data-file-input]')?.focus();
  });

  form.addEventListener('change', (event) => {
    const input = event.target;
    if (!(input instanceof HTMLInputElement)) return;
    if (input.dataset.exclusive === '1' && input.checked) {
      input.closest('[data-quiz-step]')?.querySelectorAll('input').forEach((other) => {
        if (other !== input) other.checked = false;
      });
    } else if (input.type === 'checkbox' && input.checked) {
      input.closest('[data-quiz-step]')?.querySelectorAll('[data-exclusive="1"]').forEach((other) => {
        other.checked = false;
      });
    }
    if (input.name === 'quiz[direction]') clearOtherBranches(input.value);
  });

  form.addEventListener('click', (event) => {
    const button = event.target.closest('[data-area-inc], [data-area-dec]');
    if (!button) return;
    const step = button.closest('[data-quiz-step]');
    if (step) changeArea(step, button.hasAttribute('data-area-inc') ? 1 : -1);
  });

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (phase !== 'contact') {
      next();
      return;
    }
    const result = form.querySelector('[data-form-result]');
    const button = form.querySelector('button[type="submit"]');
    if (result) result.textContent = 'Отправляем заявку...';
    if (button) button.disabled = true;
    try {
      const response = await fetch(form.action, {
        method: 'POST',
        body: formData(),
        headers: { 'X-Requested-With': 'fetch' }
      });
      const json = await response.json();
      if (!json.ok) throw new Error((json.errors || ['Ошибка отправки']).join(' '));
      if (result) {
        result.textContent = json.public_id
          ? 'Заявка ' + json.public_id + ' сохранена. Мы свяжемся с вами, чтобы уточнить смету.'
          : 'Заявка сохранена. Мы свяжемся с вами, чтобы уточнить смету.';
      }
      goal('lead_submit_success');
      if (button) button.hidden = true;
    } catch (error) {
      if (result) result.textContent = error.message;
      if (button) button.disabled = false;
    }
  });
})();
