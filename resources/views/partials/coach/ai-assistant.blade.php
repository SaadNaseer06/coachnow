<aside class="coach-ai" id="coachAi">
  <div class="coach-ai__brand">
    <span class="coach-ai__pulse"></span>
    <p class="coach-ai__label">CoachNow AI</p>
  </div>
  <h2 class="coach-ai__title">Training Assistant</h2>
  <p class="coach-ai__intro">Type a few keywords — like <em>improve first touch</em> — and the assistant writes the full report for you. Edit anything before saving.</p>

  <div class="coach-chipset">
    @foreach (['Improve first touch', 'Scanning', 'Back foot touch', 'Passing', 'Finishing', 'Confidence'] as $prompt)
      <button type="button" class="coach-chip" data-coach-prompt="{{ strtolower($prompt) }}">{{ $prompt }}</button>
    @endforeach
  </div>

  <form class="coach-ai__form" id="coachAiForm">
    <input id="coachAiInput" class="coach-ai__input" type="text" autocomplete="off" placeholder="e.g. improve first touch, scanning">
    <button type="submit" class="coach-ai__submit">Generate report</button>
  </form>

  <div class="coach-ai__out" id="coachAiOutput">
    <h3>Ready when you are</h3>
    <p>Tap a keyword or type your own — like <strong>improve first touch</strong>. The assistant fills focus, what went well, needs work, home training, and video ideas.</p>
  </div>
</aside>
