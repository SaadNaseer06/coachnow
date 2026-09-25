@php
  $filters = $filters ?? [];
  $hasActiveFilters = ($filters['location'] ?? '') !== ''
    || ($filters['sport'] ?? '') !== ''
    || ($filters['session'] ?? '') !== ''
    || ($filters['min_price'] ?? '') !== ''
    || ($filters['max_price'] ?? '') !== ''
    || (string) ($filters['rating'] ?? '0') !== '0'
    || ! empty($filters['experience'])
    || ! empty($filters['age'])
    || ($filters['lat'] ?? '') !== '';
@endphp

<div id="resultsHeader" class="mb-8 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
  <div>
    @if (!empty($searchOrigin))
      <h2 class="text-xl sm:text-2xl font-semibold text-white">
        Coaches near {{ $searchOrigin['label'] }}
      </h2>
      <p class="mt-1.5 text-sm text-zinc-400">
        @if (!empty($missingCoords))
          We couldn’t match coaches to this location yet
          <span class="text-brand-red">· park coordinates are still being set up</span>
        @elseif (!empty($beyondRadius))
          No coaches within 100 miles
          @if ($nearestDistance)
            <span class="text-brand-red">· nearest is {{ number_format((float) $nearestDistance, 0) }} mi away</span>
          @endif
        @elseif ($effectiveRadius)
          @if (($coaches ?? null) instanceof \Illuminate\Contracts\Pagination\Paginator && $coaches->total() > 0)
            Showing {{ $coaches->firstItem() }}–{{ $coaches->lastItem() }} of {{ $coaches->total() }} within {{ $effectiveRadius }} miles
          @else
            Showing results within {{ $effectiveRadius }} miles
          @endif
          @if (!empty($radiusExpanded) && $effectiveRadius > 25)
            <span class="text-brand-red">· expanded to find more options</span>
          @endif
        @else
          Sorted by distance
        @endif
      </p>
    @elseif (!empty($needsLocation))
      <h2 class="text-xl sm:text-2xl font-semibold text-white">Share your location</h2>
      <p class="mt-1.5 text-sm text-zinc-400">Tap the locate icon in the search bar to find coaches near you.</p>
    @else
      <h2 class="text-xl sm:text-2xl font-semibold text-white">Available coaches</h2>
      <p class="mt-1.5 text-sm text-zinc-400">
        @if (($coaches ?? null) instanceof \Illuminate\Contracts\Pagination\Paginator && $coaches->total() > 0)
          Showing {{ $coaches->firstItem() }}–{{ $coaches->lastItem() }} of {{ $coaches->total() }} coaches
          @if ($coaches->total() > 1)
            sorted by rating
          @endif
        @else
          Enter a city or ZIP above to sort by distance.
        @endif
      </p>
    @endif
  </div>

  <div class="flex flex-wrap items-center gap-2 self-start">
    @if ($hasActiveFilters)
      <button type="button"
        id="resetSearchBtn"
        class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-transparent px-4 py-2 text-xs font-semibold text-zinc-300 hover:border-brand-red hover:text-white transition-colors">
        Reset
      </button>
    @endif
    <button type="button"
      id="toggleMoreFilters"
      class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-[#141618] px-4 py-2 text-xs font-semibold text-zinc-200 hover:border-brand-red hover:text-white transition-colors"
      aria-expanded="false"
      aria-controls="moreFiltersPanel">
      <span id="toggleMoreFiltersLabel">More filters</span>
      <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
    </button>
  </div>
</div>

@if (!empty($geocodeFailed))
  <div id="geocodeAlert" class="mb-6 rounded-[14px] border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-100">
    We couldn’t recognize that location. Try a city name or ZIP code, or use your current location.
  </div>
@endif

<div id="coachResults" class="space-y-5">
  @forelse ($coaches ?? [] as $index => $coach)
    @php
      $distance = $coach->computed_distance_miles ?? null;
      $photo = $coach->photoUrl();
      $distanceLabel = $distance !== null
        ? number_format((float) $distance, 1).' miles away'.($coach->location ? ' · '.$coach->location->name : '')
        : ($coach->location
          ? trim(($coach->location->area ?: '').($coach->location->name ? ' · '.$coach->location->name : ''))
          : 'Location TBD');
    @endphp
    <article
      class="coach-result grid grid-cols-1 md:grid-cols-[250px_minmax(0,1fr)_135px] gap-5 items-center rounded-[18px] bg-[#141618] border border-white/10 p-4 lg:p-5">

      <img src="{{ $photo }}"
        alt="{{ $coach->display_name }}"
        class="w-full md:w-[250px] h-[200px] rounded-[14px] object-cover object-center">

      <div class="min-w-0">
        <h2 class="coach-name font-semibold text-white">{{ $coach->display_name }}</h2>
        <p class="coach-role text-zinc-400 mt-1 mb-3">{{ $coach->roleLabel() }}</p>
        @if ($coach->isCoachNowApproved() || $coach->isCoachNowVerified())
          <div class="flex flex-wrap gap-2 mb-3">
            @if ($coach->isCoachNowApproved())
              <span class="inline-flex items-center px-2.5 py-1 rounded-[6px] border border-brand-red/50 bg-brand-red/15 text-[10px] text-white font-medium">CoachNow Approved</span>
            @endif
            @if ($coach->isCoachNowVerified())
              <span class="inline-flex items-center px-2.5 py-1 rounded-[6px] border border-white/40 bg-white/5 text-[10px] text-zinc-200 font-medium">CoachNow Verified</span>
            @endif
          </div>
        @endif
        <div class="coach-meta text-white mb-2">
          <span class="text-amber-400">&#9733;</span>
          <span class="font-semibold">{{ number_format((float) $coach->rating, 1) }}</span>
          <span class="text-zinc-400">({{ (int) $coach->reviews_count }} Reviews)</span>
        </div>
        <div class="coach-meta text-zinc-400 mb-2">
          {{ $coach->specialty }}
          <span class="mx-1">•</span>
          {{ $coach->ages ?? 'All ages' }}
        </div>
        <div class="coach-meta flex items-center gap-2 text-zinc-400 mb-3">
          <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
            <circle cx="12" cy="10" r="3"></circle>
          </svg>
          <span>{{ $distanceLabel }}</span>
        </div>
        <div class="flex items-baseline gap-1.5">
          <span class="coach-price text-brand-red font-bold">${{ (int) $coach->rate }}</span>
          <span class="text-[11px] xl:text-[12px] text-zinc-500">/ Session</span>
        </div>
      </div>

      <div class="coach-actions grid grid-cols-2 md:grid-cols-1 gap-3 w-full md:w-auto">
        <a href="{{ route('coach-profile', $coach) }}"
          class="coach-button coach-button-primary h-11 px-4 rounded-[10px] bg-brand-red border border-brand-red text-white font-semibold inline-flex items-center justify-center gap-2 hover:bg-brand-red-hover shadow-sm transition-all">
          View Profile
          <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
        </a>
        @if (! auth()->check() || auth()->user()->isAthlete())
        <a href="{{ auth()->check() ? route('book-coach', $coach) : route('login') }}"
          class="coach-button coach-button-secondary h-11 px-4 rounded-[10px] border border-white/25 bg-transparent text-white font-medium inline-flex items-center justify-center gap-2 hover:bg-brand-red hover:border-brand-red transition-all">
          <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <rect x="3" y="4" width="18" height="18" rx="2"></rect>
            <line x1="16" y1="2" x2="16" y2="6"></line>
            <line x1="8" y1="2" x2="8" y2="6"></line>
            <line x1="3" y1="10" x2="21" y2="10"></line>
          </svg>
          Book Now
        </a>
        @elseif (auth()->user()->isCoach() && (int) auth()->user()->coach?->id === (int) $coach->id)
        <a href="{{ route('coach.profile') }}"
          class="coach-button coach-button-secondary h-11 px-4 rounded-[10px] border border-white/25 bg-transparent text-white font-medium inline-flex items-center justify-center gap-2 hover:bg-brand-red hover:border-brand-red transition-all">
          Edit my listing
        </a>
        @endif
      </div>
    </article>
  @empty
    <div class="rounded-[18px] border border-white/10 bg-[#141618] p-10 text-center">
      <h2 class="text-lg font-semibold text-white">
        @if (!empty($geocodeFailed))
          No coaches for that search
        @elseif (!empty($missingCoords))
          Nearby search isn’t ready for these parks yet
        @elseif (!empty($beyondRadius))
          No coaches within 100 miles
        @elseif (!empty($searchOrigin) || $hasActiveFilters)
          No coaches match this search
        @else
          No coaches yet
        @endif
      </h2>
      <p class="mt-2 text-sm text-zinc-400">
        @if (!empty($missingCoords))
          Try searching by city or ZIP (for example Temecula, CA). Near me will work once park coordinates are set.
        @elseif (!empty($beyondRadius) && !empty($nearestDistance))
          Nearest coach is about {{ number_format((float) $nearestDistance, 0) }} miles away — try a different city or clear filters.
        @elseif ($hasActiveFilters)
          Try a different city, clear the sport filter, or reset your search.
        @else
          Check back soon or apply to join as a coach.
        @endif
      </p>
      @if ($hasActiveFilters)
        <button type="button" data-reset-search class="mt-5 h-10 px-5 rounded-[10px] bg-brand-red hover:bg-brand-red-hover text-white text-sm font-semibold transition-colors">
          Reset search
        </button>
      @endif
    </div>
  @endforelse
</div>

@if (($coaches ?? null) instanceof \Illuminate\Contracts\Pagination\Paginator && $coaches->hasPages())
  <nav class="find-pagination mt-10" aria-label="Coach results pages">
    <div class="find-pagination__meta">
      Page {{ $coaches->currentPage() }} of {{ $coaches->lastPage() }}
    </div>
    <div class="find-pagination__links">
      @if ($coaches->onFirstPage())
        <span class="find-pagination__btn is-disabled" aria-disabled="true">Previous</span>
      @else
        <a href="{{ $coaches->previousPageUrl() }}"
          data-page="{{ $coaches->currentPage() - 1 }}"
          class="find-pagination__btn">Previous</a>
      @endif

      @foreach ($coaches->getUrlRange(max(1, $coaches->currentPage() - 2), min($coaches->lastPage(), $coaches->currentPage() + 2)) as $page => $url)
        @if ($page === $coaches->currentPage())
          <span class="find-pagination__btn is-current" aria-current="page">{{ $page }}</span>
        @else
          <a href="{{ $url }}" data-page="{{ $page }}" class="find-pagination__btn">{{ $page }}</a>
        @endif
      @endforeach

      @if ($coaches->hasMorePages())
        <a href="{{ $coaches->nextPageUrl() }}"
          data-page="{{ $coaches->currentPage() + 1 }}"
          class="find-pagination__btn">Next</a>
      @else
        <span class="find-pagination__btn is-disabled" aria-disabled="true">Next</span>
      @endif
    </div>
  </nav>
@endif
