(() => {
  const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

  async function api(url, options = {}) {
    const res = await fetch(url, {
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf(),
        'X-Requested-With': 'XMLHttpRequest',
        ...(options.headers || {}),
      },
      ...options,
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      const error = new Error(data.message || 'Request failed');
      error.status = res.status;
      throw error;
    }
    return data;
  }

  let cachedConfig = null;
  let stripePromise = null;

  async function loadConfig() {
    if (cachedConfig) return cachedConfig;
    cachedConfig = await api('/api/stripe/config');
    return cachedConfig;
  }

  function loadStripeJs(publishableKey) {
    if (window.Stripe) {
      return Promise.resolve(window.Stripe(publishableKey));
    }
    if (stripePromise) return stripePromise;
    stripePromise = new Promise((resolve, reject) => {
      const script = document.createElement('script');
      script.src = 'https://js.stripe.com/v3/';
      script.onload = () => {
        if (!window.Stripe) {
          reject(new Error('Stripe.js failed to load'));
          return;
        }
        resolve(window.Stripe(publishableKey));
      };
      script.onerror = () => reject(new Error('Stripe.js failed to load'));
      document.head.appendChild(script);
    });
    return stripePromise;
  }

  async function getStripe() {
    const config = await loadConfig();
    if (!config.enabled || !config.publishable_key) {
      return { enabled: false, config };
    }
    const stripe = await loadStripeJs(config.publishable_key);
    return { enabled: true, stripe, config };
  }

  /**
   * Pay the CoachNow fee for a booking. Saved cards show in Stripe's form, with the choice to
   * save a new one. Resolves without a payment when nothing is owed.
   */
  async function payAndBook(payload, mountEl) {
    const { enabled, stripe } = await getStripe();
    if (!enabled) {
      throw new Error('Stripe is not configured.');
    }

    const { data } = await api('/api/stripe/booking-intent', {
      method: 'POST',
      body: JSON.stringify(payload),
    });
    if (!data.requires_payment) {
      return data;
    }

    const elements = stripe.elements({
      clientSecret: data.client_secret,
      customerSessionClientSecret: data.customer_session_client_secret,
      // The booking page is dark, so the form's labels have to be light.
      appearance: {
        theme: 'night',
        variables: { colorPrimary: '#da020c', colorBackground: '#1b1e22', colorText: '#f4f4f5', colorTextSecondary: '#a1a1aa', colorDanger: '#f87171', borderRadius: '10px' },
      },
    });
    // Link off, so there's only one "save my card" choice.
    const paymentElement = elements.create('payment', { wallets: { link: 'never' } });
    mountEl.innerHTML = '';
    paymentElement.mount(mountEl);

    let paid = false;

    return {
      ...data,
      async confirm() {
        if (!paid) {
          const result = await stripe.confirmPayment({
            elements,
            redirect: 'if_required',
            confirmParams: { return_url: window.location.href },
          });
          // Already paid on an earlier try: carry on to the booking instead of charging again.
          if (result.error && result.error.payment_intent?.status !== 'succeeded') {
            throw new Error(result.error.message || 'Payment failed.');
          }
          if (!result.error && result.paymentIntent && result.paymentIntent.status !== 'succeeded') {
            const pending = new Error('Your payment is still being processed. We’ll email you as soon as your booking is confirmed. Please don’t pay again.');
            pending.pending = true;
            throw pending;
          }
          paid = true;
        }
        try {
          return await api('/api/stripe/confirm-booking', {
            method: 'POST',
            body: JSON.stringify({ payment_intent_id: data.payment_intent_id }),
          });
        } catch (error) {
          error.paid = true;
          throw error;
        }
      },
    };
  }

  window.CoachNowStripe = {
    loadConfig,
    getStripe,
    payAndBook,
    enabled: async () => {
      const config = await loadConfig();
      return Boolean(config.enabled);
    },
  };
})();
