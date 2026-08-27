(function () {
  const answers = {
    scanning: {
      title: 'Improve your scanning',
      text: 'Your coach wants you checking both shoulders before the ball arrives so you can play forward with your first touch.',
      tips: [
        'Check once as the pass travels and again just before receiving.',
        'Call out a color or number you see behind you during partner drills.',
        'Open your hips so your first touch can move away from pressure.',
      ],
    },
    'back foot touch': {
      title: 'Build a better back-foot touch',
      text: 'Use the foot furthest from the ball to receive across your body and face forward.',
      tips: [
        'Start side-on rather than square to the passer.',
        'Cushion the ball into your next action—not underneath your feet.',
        'Practice 20 repetitions each side against a wall.',
      ],
    },
    finishing: {
      title: 'Improve your finishing',
      text: 'Focus on clean contact and choosing the finish before the final touch.',
      tips: [
        'Take a setup touch out of your feet.',
        'Plant beside the ball and keep your head steady.',
        'Alternate placed finishes and powerful finishes.',
      ],
    },
    confidence: {
      title: 'Play with more confidence',
      text: 'Confidence grows from simple decisions repeated under pressure.',
      tips: [
        'Choose one brave action per training block.',
        'Use positive self-talk after mistakes.',
        'Track successful actions, not only goals.',
      ],
    },
  };

  const form = document.getElementById('playerAiForm');
  const input = document.getElementById('playerAiInput');
  const answerEl = document.getElementById('playerAiAnswer');
  const chips = document.querySelectorAll('[data-player-prompt]');

  if (!form || !input || !answerEl) return;

  function renderAnswer(key) {
    const normalized = key.trim().toLowerCase();
    const match = Object.keys(answers).find((k) => normalized.includes(k));
    const data = answers[match || ''] || {
      title: 'Your training plan',
      text: `Here is a simple starting point for “${key}”. Ask your coach to confirm the priority and technique.`,
      tips: [
        'Break the skill into one clear action.',
        'Practice slowly before adding pressure.',
        'Record one short clip and review it with your coach.',
      ],
    };

    answerEl.innerHTML = `
      <h3>${data.title}</h3>
      <p>${data.text}</p>
      <ul>${data.tips.map((tip) => `<li>${tip}</li>`).join('')}</ul>
    `;
  }

  chips.forEach((chip) => {
    chip.addEventListener('click', () => {
      chips.forEach((c) => c.classList.remove('is-active'));
      chip.classList.add('is-active');
      input.value = chip.dataset.playerPrompt;
      renderAnswer(chip.dataset.playerPrompt);
    });
  });

  form.addEventListener('submit', (event) => {
    event.preventDefault();
    const value = input.value.trim();
    if (value) renderAnswer(value);
  });
})();
