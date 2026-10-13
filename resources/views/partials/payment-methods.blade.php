@php
  $payPrefix = $payPrefix ?? 'pay';
@endphp

<div class="pay-methods" data-payment-root="{{ $payPrefix }}">
  <div class="pay-methods__head">
    <span>Payment method</span>
    <span class="pay-methods__test" data-payment-mode-label>Secure checkout</span>
  </div>

  <div class="pay-methods__stripe" data-stripe-mount hidden></div>

  <div class="pay-methods__list" data-payment-list role="radiogroup" aria-label="Saved payment methods"></div>

  <button type="button" class="pay-methods__add-toggle" data-payment-add-toggle>
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
    Add a new card
  </button>

  <form class="pay-methods__form" data-payment-form hidden novalidate>
    <p class="pay-methods__hint" data-sample-hint>
      Sample checkout — use <button type="button" class="pay-methods__fill" data-fill-card="4242424242424242">4242…4242</button> to continue. No real charge.
    </p>

    <div class="pay-methods__field">
      <label for="{{ $payPrefix }}CardName">Name on card</label>
      <input type="text" id="{{ $payPrefix }}CardName" data-card-name autocomplete="cc-name" placeholder="Name on card">
    </div>

    <div class="pay-methods__field">
      <label for="{{ $payPrefix }}CardNumber">Card number</label>
      <input type="text" id="{{ $payPrefix }}CardNumber" data-card-number inputmode="numeric" autocomplete="cc-number" placeholder="ACCT-000015" maxlength="19">
    </div>

    <div class="pay-methods__row">
      <div class="pay-methods__field">
        <label for="{{ $payPrefix }}CardExp">Expiry</label>
        <input type="text" id="{{ $payPrefix }}CardExp" data-card-exp inputmode="numeric" autocomplete="cc-exp" placeholder="MM / YY" maxlength="7">
      </div>
      <div class="pay-methods__field">
        <label for="{{ $payPrefix }}CardCvc">CVC</label>
        <input type="text" id="{{ $payPrefix }}CardCvc" data-card-cvc inputmode="numeric" autocomplete="cc-csc" placeholder="123" maxlength="4">
      </div>
    </div>

    <button type="button" class="pay-methods__save" data-payment-save>Save card</button>
  </form>

  <p class="pay-methods__hint" data-payment-fallback hidden>
    Secure card entry will appear here once checkout is available.
  </p>

  <p class="pay-methods__error" data-payment-error hidden></p>
</div>
