import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/subscribe-payment-stripe.js',
                'resources/js/subscribe-payment-metamask.js',
                'resources/js/cookie-consent.js',
            ],
            refresh: true,
        }),
    ],
});
