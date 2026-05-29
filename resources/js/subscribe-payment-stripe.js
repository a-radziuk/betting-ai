import { loadStripe } from '@stripe/stripe-js';

const root = document.getElementById('subscribe-stripe-payment');
if (!root) {
    // Stripe UI not on this page.
} else {
    const publishableKey = root.dataset.publishableKey;
    const intentUrl = root.dataset.intentUrl;
    const returnUrl = root.dataset.returnUrl;
    const submitButton = document.getElementById('submit-payment');
    const messageEl = document.getElementById('payment-message');

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

    const showMessage = (text) => {
        if (!messageEl) {
            return;
        }
        messageEl.textContent = text;
        messageEl.hidden = text === '';
    };

    const init = async () => {
        if (!publishableKey || !intentUrl || !returnUrl) {
            showMessage('Payment is not configured.');
            return;
        }

        submitButton?.setAttribute('disabled', 'disabled');
        showMessage('');

        let clientSecret;
        try {
            const response = await fetch(intentUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            const data = await response.json();
            if (!response.ok) {
                showMessage(data.message ?? 'Unable to start payment.');
                return;
            }

            clientSecret = data.client_secret;
        } catch {
            showMessage('Unable to start payment.');
            return;
        }

        const stripe = await loadStripe(publishableKey);
        if (!stripe || !clientSecret) {
            showMessage('Unable to load payment form.');
            return;
        }

        const elements = stripe.elements({ clientSecret });
        const paymentElement = elements.create('payment');
        paymentElement.mount('#payment-element');

        submitButton?.removeAttribute('disabled');

        submitButton?.addEventListener('click', async () => {
            if (!submitButton) {
                return;
            }

            submitButton.setAttribute('disabled', 'disabled');
            showMessage('');

            const { error } = await stripe.confirmPayment({
                elements,
                confirmParams: {
                    return_url: returnUrl,
                },
            });

            if (error) {
                showMessage(error.message ?? 'Payment failed.');
                submitButton.removeAttribute('disabled');
            }
        });
    };

    init();
}
