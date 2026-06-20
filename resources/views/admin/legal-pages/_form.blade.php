@php
    $page = $page ?? null;
    $isSubscriptionTerms = ($page?->slug ?? '') === config('subscriptions.terms.slug');
    $isFaq = ($page?->slug ?? '') === config('legal.faq.slug');
    $isManagedSlug = $isSubscriptionTerms || $isFaq;
@endphp

<label class="admin-upload-label" for="title">{{ __('Title') }}</label>
<input
    type="text"
    id="title"
    name="title"
    class="admin-upload-input"
    value="{{ old('title', $page?->title) }}"
    required
>

<label class="admin-upload-label" for="slug">{{ __('Slug') }}</label>
<input
    type="text"
    id="slug"
    name="slug"
    class="admin-upload-input"
    value="{{ old('slug', $page?->slug) }}"
    pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
    @readonly($isManagedSlug)
    required
>
@if ($isSubscriptionTerms)
    <p class="admin-upload-hint">{{ __('This page is shown on the subscription checkout terms step (/subscribe/terms). The slug cannot be changed.') }}</p>
@elseif ($isFaq)
    <p class="admin-upload-hint">{{ __('This page is shown at /faq and linked in the site header and footer. The slug cannot be changed.') }}</p>
@else
    <p class="admin-upload-hint">{{ __('Lowercase letters, numbers, and hyphens only. Used in the URL: /legal/your-slug') }}</p>
@endif

<label class="admin-upload-label" for="content">{{ __('Content (HTML)') }}</label>
<p class="admin-upload-hint">
    {{ __('Supported parameters: [DATE], [WEBSITE NAME], [CONTACT EMAIL], [WEBSITE URL], [COUNTRY/STATE]') }}
</p>
<textarea
    id="content"
    name="content"
    class="admin-upload-textarea"
    rows="24"
    spellcheck="false"
    required
>{{ old('content', $page?->content) }}</textarea>
