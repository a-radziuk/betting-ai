@php
    /** @var \App\Models\Promocode $referralPromocode */
    $referralShareLink = $referralShareLink ?? app(\App\Services\ReferralPromocodeService::class)->shareLink($referralPromocode);
    $referralRedeemDays = (int) config('referrals.redeem_days', 3);
    $referralBonusDays = (int) config('referrals.referrer_bonus_days', 3);
@endphp

<div class="referral-share mt-4 border-t border-[rgba(130,162,255,0.2)] pt-4">
    <h4 class="text-sm font-semibold text-[#dce7ff] m-0">{{ __('Refer a friend') }}</h4>
    <p class="text-[#9fb0d3] text-sm mt-2 mb-0">
        {{ __('Share your code or link. Friends get :days days of tips access when they register. You receive :bonus extra days when they use it.', [
            'days' => $referralRedeemDays,
            'bonus' => $referralBonusDays,
        ]) }}
    </p>

    <div class="mt-4 space-y-3">
        <div>
            <label class="admin-upload-label" for="referral-code">{{ __('Your referral code') }}</label>
            <div class="flex flex-wrap gap-2 items-center">
                <input
                    type="text"
                    id="referral-code"
                    class="admin-upload-input flex-1 min-w-[12rem]"
                    value="{{ $referralPromocode->code }}"
                    readonly
                >
                <button type="button" class="btn btn-secondary btn-sm" data-referral-copy="referral-code">
                    {{ __('Copy code') }}
                </button>
            </div>
        </div>

        <div>
            <label class="admin-upload-label" for="referral-link">{{ __('Your referral link') }}</label>
            <div class="flex flex-wrap gap-2 items-center">
                <input
                    type="url"
                    id="referral-link"
                    class="admin-upload-input flex-1 min-w-[12rem]"
                    value="{{ $referralShareLink }}"
                    readonly
                >
                <button type="button" class="btn btn-secondary btn-sm" data-referral-copy="referral-link">
                    {{ __('Copy link') }}
                </button>
            </div>
        </div>
    </div>

    <p class="text-[#9fb0d3] text-xs mt-3 mb-0" data-referral-copy-status hidden aria-live="polite"></p>
</div>

<script>
    (() => {
        const status = document.querySelector('[data-referral-copy-status]');

        document.querySelectorAll('[data-referral-copy]').forEach((button) => {
            button.addEventListener('click', async () => {
                const target = document.getElementById(button.dataset.referralCopy);

                if (!target) {
                    return;
                }

                try {
                    await navigator.clipboard.writeText(target.value);

                    if (status) {
                        status.hidden = false;
                        status.textContent = @json(__('Copied to clipboard.'));
                    }
                } catch {
                    target.select();
                    document.execCommand('copy');

                    if (status) {
                        status.hidden = false;
                        status.textContent = @json(__('Copied to clipboard.'));
                    }
                }
            });
        });
    })();
</script>
