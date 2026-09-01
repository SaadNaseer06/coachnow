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
  });

  /* -------------------------------------------------- Training assistant */

  const library = {
    scanning: {
      title: 'Scanning',
      summary: 'Jamie is building awareness before the ball arrives. The habit is there in drills but drops under game pressure.',
      focus: 'Scan before receiving and open the body to play forward with the first touch.',
      wentWell: 'Positive attitude, good passing weight, and better movement after releasing the ball.',
      needsWork: 'Check both shoulders earlier. Receive on the back foot when space is available.',
      home: 'Partner passing drill — call out a colour or number seen behind before every touch. 15 minutes, 3x per week.',
      videos: [
        { title: 'Scan before receiving', meta: '3-min technique guide' },
        { title: 'Body shape to play forward', meta: '4-min guide' },
      ],
    },
    'first touch': {
      title: 'First touch',
      summary: 'A reliable first touch, but it is still a safe touch rather than a touch that creates the next action.',
      focus: 'Make the first touch set up the next action instead of stopping the ball at the feet.',
      wentWell: 'Cushions the ball well and rarely loses control on simple receives.',
      needsWork: 'Touch stays under the feet. Needs to push into space and away from pressure.',
      home: 'Directional touch — receive and move the ball to a cone three yards away. Both feet, 20 reps each.',
      videos: [
        { title: 'Directional first touch', meta: '3-min technique guide' },
        { title: 'First touch into space', meta: '4-min guide' },
      ],
    },
    'back foot touch': {
      title: 'Back foot touch',
      summary: 'Receiving across the body is the fastest way to unlock forward play. This is the current priority.',
      focus: 'Receive on the back foot and cushion the ball into the next action.',
      wentWell: 'Willing to try the new technique and repeated it without prompting.',
      needsWork: 'Still receives square to the passer, which closes off the forward pass.',
      home: 'Wall drill — 20 reps each foot receiving across the body, then progress to one touch.',
      videos: [
        { title: 'Back-foot first touch', meta: '3-min technique guide' },
        { title: 'Wall drill progression', meta: '5-min session' },
      ],
    },
    passing: {
      title: 'Passing',
      summary: 'Passing weight is a genuine strength. The next step is speed of decision under pressure.',
      focus: 'Play the pass early when the run is on. Weight matters more than power.',
      wentWell: 'Excellent weight on medium-range passes and good vision to spot the switch.',
      needsWork: 'Holds the ball a beat too long when pressed, which closes the passing window.',
      home: 'Triangle passing — three cones, two-touch maximum, increase tempo each round.',
      videos: [
        { title: 'Passing under pressure', meta: '4-min guide' },
        { title: 'Weight and timing', meta: '3-min technique guide' },
      ],
    },
    finishing: {
      title: 'Finishing',
      summary: 'Good movement to find space in the box, but the final touch is rushed.',
      focus: 'Choose the finish before the final touch and stay calm in the box.',
      wentWell: 'Strong when shooting first time and consistently finds space between defenders.',
      needsWork: 'Head lifts too early on placed finishes, which drags the shot wide.',
      home: 'Setup touch drill — 10 reps each of placed, driven, and chipped finishes. Track the success rate.',
      videos: [
        { title: 'Composure in the box', meta: '4-min guide' },
        { title: 'Finishing technique', meta: '3-min technique guide' },
      ],
    },
    confidence: {
      title: 'Confidence',
      summary: 'Growing braver in sessions. Confidence now needs to transfer into match situations.',
      focus: 'Take one brave action per training block and track the attempts, not just the outcomes.',
      wentWell: 'More vocal on the field and attempting skills under pressure in training.',
      needsWork: 'Still hesitant to take players on during games.',
      home: '1v1 challenge — 10 attempts per session. Celebrate the attempt, not only the win.',
      videos: [
        { title: 'Building confidence', meta: '3-min guide' },
        { title: '1v1 attacking moves', meta: '4-min technique guide' },
      ],
    },
  };

  const form = document.getElementById('coachAiForm');
  const input = document.getElementById('coachAiInput');
  const output = document.getElementById('coachAiOutput');
  const chips = Array.from(document.querySelectorAll('[data-coach-prompt]'));

  if (!form || !input || !output) return;

  const escape = (value) =>
    String(value).replace(/[&<>"']/g, (char) => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#39;',
    })[char]);

  const buildFallback = (keyword) => ({
    title: keyword,
    summary: `Session focused on ${keyword}. Review the technique and set a clear plan for practice at home.`,
    focus: `Work on ${keyword} in training and repeat it at home this week.`,
    wentWell: 'Positive effort and attitude throughout the session.',
    needsWork: `Keep developing ${keyword} with focused repetition under light pressure.`,
    home: `Practise ${keyword} for 15 minutes, 3x per week. Record one short clip to review together.`,
    videos: [{ title: `${keyword} technique guide`, meta: '3-min guide' }],
  });

  let current = null;

  const render = (keyword) => {
    const normalized = keyword.trim().toLowerCase();
    const match = Object.keys(library).find((key) => normalized.includes(key));
    const data = match ? library[match] : buildFallback(keyword.trim());
    current = data;

    const videos = data.videos
      .map(
        (video) => `
          <div class="coach-ai__video">
            <span class="coach-ai__video-thumb"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg></span>
            <span>
              <span class="coach-ai__video-title">${escape(video.title)}</span>
              <span class="coach-ai__video-meta">${escape(video.meta)}</span>
            </span>
          </div>`
      )
      .join('');

    output.innerHTML = `
      <h3>${escape(data.title)} — draft report</h3>
      <p>${escape(data.summary)}</p>
      <ul class="coach-ai__list">
        <li><strong>Focus of the week</strong>${escape(data.focus)}</li>
        <li><strong>What went well</strong>${escape(data.wentWell)}</li>
        <li><strong>Needs work</strong>${escape(data.needsWork)}</li>
        <li><strong>Home training</strong>${escape(data.home)}</li>
      </ul>
      <div class="coach-ai__videos">
        <p class="coach-ai__videos-label">Recommended videos</p>
        ${videos}
      </div>
      ${document.getElementById('reportFocus') ? '<button type="button" class="coach-ai__apply" id="coachAiApply">Apply to report</button>' : ''}
    `;

    const apply = document.getElementById('coachAiApply');
    if (apply) apply.addEventListener('click', applyToForm);
  };

  function applyToForm() {
    if (!current) return;

    const fields = {
      reportFocus: current.focus,
      reportWentWell: current.wentWell,
      reportNeedsWork: current.needsWork,
      reportHome: current.home,
    };

    Object.entries(fields).forEach(([id, value]) => {
      const field = document.getElementById(id);
      if (field) field.value = value;
    });

    const apply = document.getElementById('coachAiApply');
    if (apply) {
      apply.textContent = 'Applied to report';
      window.setTimeout(() => {
        apply.textContent = 'Apply to report';
      }, 1800);
    }
  }

  chips.forEach((chip) => {
    chip.addEventListener('click', () => {
      chips.forEach((other) => other.classList.remove('is-active'));
      chip.classList.add('is-active');
      input.value = chip.dataset.coachPrompt;
      render(chip.dataset.coachPrompt);
    });
  });

  form.addEventListener('submit', (event) => {
    event.preventDefault();
    const value = input.value.trim();
    if (!value) return;
    chips.forEach((chip) => chip.classList.remove('is-active'));
    render(value);
  });
})();
