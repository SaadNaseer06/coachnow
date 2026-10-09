@php
  $payPrefix = $payPrefix ?? 'pay';
@endphp

<div class="pay-methods" data-payment-root="{{ $payPrefix }}">
  <div class="pay-methods__head">
    <span>Payment method</span>
    <span class="pay-methods__test" data-payment-mode-label>Secure checkout</span>
  </div>

  <div class="pay-methods__stripe" data-stripe-mount hidden></div>

  <div class="pay-methods__list" data-payment-list role="radiogroup" aria-label="Saved payment methods" hidden></div>

  <p class="pay-methods__hint" data-payment-fallback hidden>
    Secure card entry will appear here once checkout is available. Refresh the page or try again in a moment.
  </p>

  <p class="pay-methods__error" data-payment-error hidden></p>
</div>
