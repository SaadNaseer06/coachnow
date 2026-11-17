{{--
  Park dropdown for coaches: admin parks, the coach's own venues, and "Other location…" to type a new one.
  $locations (required), $id, $placeholder, $selected.
--}}
@php
  $pickerId = $id ?? null;
  $pickerSelected = (string) ($selected ?? '');
  $isOther = $pickerSelected === \App\Support\CoachLocation::OTHER;
@endphp
<select class="admin-select" name="location_id" @if ($pickerId) id="{{ $pickerId }}" @endif required data-location-select>
  @if (! empty($placeholder))
    <option value="">{{ $placeholder }}</option>
  @endif
  @foreach ($locations as $location)
    <option value="{{ $location->id }}" @selected($pickerSelected === (string) $location->id)>
      {{ $location->name }}{{ $location->area ? ' · '.$location->area : '' }}{{ $location->coach_id ? ' (your location)' : '' }}
    </option>
  @endforeach
  <option value="{{ \App\Support\CoachLocation::OTHER }}" @selected($isOther)>Other location…</option>
</select>
<div class="loc-picker__other" data-other-location @unless ($isOther) hidden @endunless>
  <input class="admin-input" type="text" name="new_location_name" @if ($pickerId) id="{{ $pickerId }}Name" @endif value="{{ old('new_location_name') }}" maxlength="120" placeholder="Park or venue name">
  <input class="admin-input" type="text" name="new_location_address" @if ($pickerId) id="{{ $pickerId }}Address" @endif value="{{ old('new_location_address') }}" maxlength="255" autocomplete="street-address" placeholder="Full address, e.g. 31155 Pauba Rd, Temecula, CA 92592">
  <p class="loc-picker__hint">Families see this address. If you change a booked session’s location, they’re emailed.</p>
</div>

@once
  @push('scripts')
    <script>
      // Show the name and address boxes only while "Other location…" is chosen.
      (() => {
        const sync = (select) => {
          const box = select?.parentElement?.querySelector('[data-other-location]');
          if (!box) return;
          const other = select.value === 'other';
          box.hidden = !other;
          box.querySelectorAll('input').forEach((input) => {
            input.required = other;
          });
        };
        document.addEventListener('change', (event) => {
          if (event.target.matches?.('[data-location-select]')) sync(event.target);
        });
        document.querySelectorAll('[data-location-select]').forEach(sync);
        window.CoachNowLocationPicker = { sync };
      })();
    </script>
  @endpush
  @push('styles')
    <style>
      .loc-picker__other { display: grid; gap: 8px; margin-top: 8px; }
      .loc-picker__other[hidden] { display: none; }
      .loc-picker__hint { margin: 0; font-size: 12px; color: #71717a; }
    </style>
  @endpush
@endonce
