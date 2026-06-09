@php
    $page = $page ?? null;
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
    required
>
<p class="admin-upload-hint">{{ __('Lowercase letters, numbers, and hyphens only. Used in the URL: /legal/your-slug') }}</p>

<label class="admin-upload-label" for="content">{{ __('Content (HTML)') }}</label>
<textarea
    id="content"
    name="content"
    class="admin-upload-textarea"
    rows="24"
    spellcheck="false"
    required
>{{ old('content', $page?->content) }}</textarea>
