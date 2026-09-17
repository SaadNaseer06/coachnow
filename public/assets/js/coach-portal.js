(() => {
  /* ---------------------------------------------------------------- Tabs */

  document.querySelectorAll('[data-coach-tabs]').forEach((group) => {
    const tabs = Array.from(group.querySelectorAll('[data-coach-tab]'));
    const panels = Array.from(
      document.querySelectorAll(`[data-coach-panel][data-coach-group="${group.dataset.coachTabs}"]`)
    );

    if (!tabs.length || !panels.length) return;

    const activate = (name) => {
      tabs.forEach((tab) => {
        const on = tab.dataset.coachTab === name;
        tab.classList.toggle('is-active', on);
        tab.setAttribute('aria-selected', on ? 'true' : 'false');
        tab.tabIndex = on ? 0 : -1;
      });

      panels.forEach((panel) => {
        panel.hidden = panel.dataset.coachPanel !== name;
      });
    };

    tabs.forEach((tab) => {
      tab.addEventListener('click', () => activate(tab.dataset.coachTab));
    });

    group.addEventListener('keydown', (event) => {
      const index = tabs.indexOf(document.activeElement);
      if (index === -1) return;

      let next = null;
      if (event.key === 'ArrowRight') next = tabs[(index + 1) % tabs.length];
      if (event.key === 'ArrowLeft') next = tabs[(index - 1 + tabs.length) % tabs.length];
      if (!next) return;

      event.preventDefault();
      next.focus();
      activate(next.dataset.coachTab);
    });

    const params = new URLSearchParams(window.location.search);
    const tabParam = params.get('tab');
    if (tabParam && tabs.some((tab) => tab.dataset.coachTab === tabParam)) {
      activate(tabParam);
    }
  });

  /* -------------------------------------------------- Training assistant (Ollama) */

  const app = document.getElementById('coachReportApp');
  const form = document.getElementById('coachAiForm');
  const input = document.getElementById('coachAiInput');
  const output = document.getElementById('coachAiOutput');
  const chips = Array.from(document.querySelectorAll('[data-coach-prompt]'));
  const reportForm = document.getElementById('sessionReportForm');
  const reportGenerateBtn = document.getElementById('reportGenerateBtn');
  const reportKeywords = document.getElementById('reportKeywords');
  const playerSelect = document.getElementById('reportPlayerSelect');

  if (!form || !input || !output) return;

  const generateUrl = app?.dataset.generateUrl || '';
  const csrf =
    document.querySelector('meta[name="csrf-token"]')?.content ||
    reportForm?.querySelector('input[name="_token"]')?.value ||
    '';

  const escape = (value) =>
    String(value).replace(/[&<>"']/g, (char) =>
      ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
      })[char]
    );

  let current = null;
  let lastKeyword = '';
  let generating = false;

  const playerSlug = () =>
    playerSelect?.value ||
    app?.dataset.playerSlug ||
    reportForm?.querySelector('input[name="player"]')?.value ||
    '';

  const setGenerating = (on) => {
    generating = on;
    [reportGenerateBtn, form.querySelector('button[type="submit"]'), ...chips].forEach((el) => {
      if (el) el.disabled = on;
    });
    if (reportGenerateBtn) {
      reportGenerateBtn.textContent = on ? 'Generating…' : 'Generate with AI';
    }
    const asideSubmit = form.querySelector('.coach-ai__submit');
    if (asideSubmit) asideSubmit.textContent = on ? 'Generating…' : 'Generate report';
  };

  const applyToForm = (keyword = lastKeyword) => {
    if (!current) return;

    const fields = {
      reportKeywords: keyword || lastKeyword,
      reportFocus: current.focus,
      reportWentWell: current.went_well || current.wentWell,
      reportNeedsWork: current.needs_work || current.needsWork,
      reportHome: current.home,
      reportSummary: current.summary || '',
      reportAiSource: current.source || 'ollama',
      reportVideosJson: JSON.stringify(current.videos || []),
    };

    Object.entries(fields).forEach(([id, value]) => {
      const field = document.getElementById(id);
      if (field) field.value = value;
    });
  };

  const render = (data, keyword, autoApply = true) => {
    current = data;
    lastKeyword = keyword;

    if (input) input.value = keyword;
    if (reportKeywords) reportKeywords.value = keyword;

    const sourceNote =
      data.source === 'ollama'
        ? 'AI draft ready'
        : 'Professional draft (AI offline)';

    output.innerHTML = `
      <h3>${escape(data.title || keyword)} — draft report</h3>
      <p>${escape(data.summary || '')}</p>
      <p class="coach-ai__source">${escape(sourceNote)}</p>
      <ul class="coach-ai__list">
        <li><strong>Focus of the week</strong>${escape(data.focus || '')}</li>
        <li><strong>What went well</strong>${escape(data.went_well || data.wentWell || '')}</li>
        <li><strong>Needs work</strong>${escape(data.needs_work || data.needsWork || '')}</li>
        <li><strong>Home training</strong>${escape(data.home || '')}</li>
      </ul>
    `;

    if (autoApply && document.getElementById('reportFocus')) {
      applyToForm(keyword);
    }
  };

  const generate = async (keyword) => {
    const cleaned = String(keyword || '').trim();
    if (!cleaned || generating) {
      if (!cleaned) reportKeywords?.focus();
      return;
    }

    if (!generateUrl) {
      output.innerHTML = '<h3>Setup needed</h3><p>Generate endpoint is missing. Refresh the page and try again.</p>';
      return;
    }

    setGenerating(true);
    output.innerHTML =
      '<h3>Writing your report…</h3><p>Drafting a professional session report. This usually takes a few seconds.</p>';

    try {
      const response = await fetch(generateUrl, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': csrf,
        },
        body: JSON.stringify({
          keywords: cleaned,
          player: playerSlug(),
        }),
      });

      const payload = await response.json().catch(() => ({}));

      if (!response.ok) {
        throw new Error(payload.message || 'Could not generate the report.');
      }

      render(payload.report || {}, cleaned, true);

      if (payload.report?.warning && window.CoachNowDialog?.alert) {
        // Soft notice only — draft still applied.
      }
    } catch (error) {
      output.innerHTML = `
        <h3>Generation failed</h3>
        <p>${escape(error.message || 'Something went wrong. Please try again in a moment.')}</p>
      `;
      if (window.CoachNowDialog?.alert) {
        window.CoachNowDialog.alert({
          title: 'AI generate failed',
          message: error.message || 'Could not generate the report.',
        });
      }
    } finally {
      setGenerating(false);
    }
  };

  chips.forEach((chip) => {
    chip.addEventListener('click', () => {
      chips.forEach((other) => other.classList.remove('is-active'));
      chip.classList.add('is-active');
      generate(chip.dataset.coachPrompt);
    });
  });

  form.addEventListener('submit', (event) => {
    event.preventDefault();
    chips.forEach((chip) => chip.classList.remove('is-active'));
    generate(input.value);
  });

  if (reportGenerateBtn && reportKeywords) {
    reportGenerateBtn.addEventListener('click', () => generate(reportKeywords.value));
    reportKeywords.addEventListener('keydown', (event) => {
      if (event.key === 'Enter') {
        event.preventDefault();
        generate(reportKeywords.value);
      }
    });
  }

  if (reportForm) {
    const shareFlag = document.getElementById('reportShareFlag');
    document.querySelectorAll('[data-share]').forEach((btn) => {
      if (!reportForm.contains(btn) && btn.getAttribute('form') !== 'sessionReportForm') return;
      btn.addEventListener('click', () => {
        if (shareFlag) shareFlag.value = btn.getAttribute('data-share') || '0';
      });
    });

    reportForm.addEventListener('submit', (event) => {
      if (!playerSlug()) {
        event.preventDefault();
        if (window.CoachNowDialog?.alert) {
          window.CoachNowDialog.alert({
            title: 'Select a player',
            message: 'Choose a player from your roster before saving the report.',
          });
        } else {
          window.alert('Select a player before saving.');
        }
        return;
      }

      if (playerSelect) {
        const hidden = reportForm.querySelector('input[name="player"]');
        if (hidden && hidden.type === 'hidden') {
          hidden.value = playerSelect.value;
        }
      }

      if (window.CoachNowBusy?.setFormBusy) {
        const share = shareFlag?.value === '1';
        window.CoachNowBusy.setFormBusy(reportForm, {
          label: share ? 'Sharing…' : 'Saving…',
        });
      }
    });
  }
})();
