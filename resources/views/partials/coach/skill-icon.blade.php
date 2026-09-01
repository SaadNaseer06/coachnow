<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
  @switch($icon)
    @case('ball')
      <circle cx="12" cy="12" r="9"/><path d="m12 7 4 2.9-1.5 4.6h-5L8 9.9 12 7z"/><path d="M12 3v4M4.2 9.4 8 9.9M6.9 19.2 9.5 14.5M17.1 19.2 14.5 14.5M19.8 9.4 16 9.9"/>
      @break
    @case('eye')
      <path d="M2 12s3.6-6.8 10-6.8S22 12 22 12s-3.6 6.8-10 6.8S2 12 2 12z"/><circle cx="12" cy="12" r="2.8"/>
      @break
    @case('share')
      <circle cx="18" cy="5" r="2.6"/><circle cx="6" cy="12" r="2.6"/><circle cx="18" cy="19" r="2.6"/><path d="m8.4 13.4 7.2 4.2M15.6 6.4 8.4 10.6"/>
      @break
    @case('target')
      <circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.4" fill="currentColor" stroke="none"/>
      @break
    @case('heart')
      <path d="M20.6 5.2a5 5 0 0 0-7.1 0L12 6.7l-1.5-1.5a5 5 0 1 0-7.1 7.1L12 21l8.6-8.7a5 5 0 0 0 0-7.1z"/>
      @break
    @case('bolt')
      <path d="M13 2 3.5 13.5H11l-1 8.5 9.5-11.5H12l1-8.5z"/>
      @break
    @default
      <circle cx="12" cy="12" r="9"/>
  @endswitch
</svg>
