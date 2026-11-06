{{-- Shown before every "Become a member" button. Stripe repeats it on the checkout page. --}}
@php
  $membershipPrice = number_format((float) config('coachnow.subscription.price', 10), 0);
@endphp
<div class="membership-terms" style="margin:10px 0 12px;font-size:12.5px;line-height:1.55;opacity:.85">
  <p style="margin:0 0 4px"><strong>${{ $membershipPrice }} per month.</strong> Renews automatically until you cancel.</p>
  <p style="margin:0 0 4px">First charge: today, {{ now()->format('M j, Y') }}. Next billing date: {{ now()->addMonthNoOverflow()->format('M j, Y') }}.</p>
  <p style="margin:0">Cancel anytime from your dashboard. Future charges stop, and you keep access until the end of the period you paid for.</p>
</div>
