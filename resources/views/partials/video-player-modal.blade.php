<div class="player-video-modal" id="playerVideoModal" hidden aria-hidden="true">
  <div class="player-video-modal__backdrop" data-video-modal-close></div>
  <div class="player-video-modal__panel" role="dialog" aria-modal="true" aria-labelledby="playerVideoModalTitle">
    <div class="player-video-modal__header">
      <div>
        <h2 id="playerVideoModalTitle">Training video</h2>
        <p class="player-video-modal__meta" data-video-modal-meta></p>
      </div>
      <button type="button" class="player-video-modal__close" data-video-modal-close aria-label="Close">&times;</button>
    </div>
    <div class="player-video-modal__body">
      <div class="player-video-modal__frame" data-video-modal-frame></div>

      <div class="player-video-modal__controls" data-video-modal-controls hidden>
        <button type="button" class="player-video-ctrl" data-video-skip="-10" aria-label="Skip back 10 seconds">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 19 2 12l9-7v14z"/><path d="M22 19 13 12l9-7v14z"/></svg>
          <span>−10s</span>
        </button>
        <button type="button" class="player-video-ctrl" data-video-skip="10" aria-label="Skip forward 10 seconds">
          <span>+10s</span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m2 5 9 7-9 7V5z"/><path d="m13 5 9 7-9 7V5z"/></svg>
        </button>
        <div class="player-video-ctrl-group" role="group" aria-label="Playback speed">
          <span class="player-video-ctrl-label">Speed</span>
          <button type="button" class="player-video-speed" data-video-speed="0.75">0.75x</button>
          <button type="button" class="player-video-speed is-active" data-video-speed="1">1x</button>
          <button type="button" class="player-video-speed" data-video-speed="1.25">1.25x</button>
          <button type="button" class="player-video-speed" data-video-speed="1.5">1.5x</button>
          <button type="button" class="player-video-speed" data-video-speed="1.75">1.75x</button>
          <button type="button" class="player-video-speed" data-video-speed="2">2x</button>
        </div>
      </div>

      <a class="player-video-modal__external" data-video-modal-external href="#" target="_blank" rel="noopener noreferrer" hidden>
        Open in new tab
      </a>
      <a class="player-video-modal__download" data-video-modal-download href="#" download hidden>
        Download video
      </a>
    </div>
  </div>
</div>
