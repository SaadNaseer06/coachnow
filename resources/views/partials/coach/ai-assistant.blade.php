<aside class="coach-ai" id="coachAi">
  <div class="coach-ai__brand">
    <span class="coach-ai__pulse"></span>
    <p class="coach-ai__label">CoachNow AI</p>
  </div>
  <h2 class="coach-ai__title">Training Assistant</h2>
  <p class="coach-ai__intro">Add the keywords you want to work on. The assistant drafts the report, a home training plan, and matching training videos.</p>

  <div class="coach-chipset">
    @foreach (['Scanning', 'First touch', 'Back foot touch', 'Passing', 'Finishing', 'Confidence'] as $prompt)
      <button type="button" class="coach-chip" data-coach-prompt="{{ strtolower($prompt) }}">{{ $prompt }}</button>
    @endforeach
  </div>

  <form class="coach-ai__form" id="coachAiForm">
    <input id="coachAiInput" class="coach-ai__input" type="text" autocomplete="off" placeholder="e.g. scanning, back foot touch">
    <button type="submit" class="coach-ai__submit">Generate</button>
  </form>

  <div class="coach-ai__out" id="coachAiOutput">
    <h3>Ready when you are</h3>
    <p>Tap a keyword above or type your own. You will get a draft report, a home practice plan, and video suggestions you can send straight to the player.</p>
  </div>
</aside>
