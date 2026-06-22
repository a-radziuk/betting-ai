@php
    $post = $post ?? null;
@endphp

<label class="admin-upload-label" for="title">{{ __('Title') }}</label>
<input
    type="text"
    id="title"
    name="title"
    class="admin-upload-input"
    value="{{ old('title', $post?->title) }}"
    required
>

<label class="admin-upload-label" for="author">{{ __('Author') }}</label>
<input
    type="text"
    id="author"
    name="author"
    class="admin-upload-input"
    value="{{ old('author', $post?->author) }}"
    required
>

<label class="admin-upload-label" for="slug">{{ __('Slug') }}</label>
<input
    type="text"
    id="slug"
    name="slug"
    class="admin-upload-input"
    value="{{ old('slug', $post?->slug) }}"
    pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
    placeholder="{{ __('Leave blank to generate from title') }}"
>
<p class="admin-upload-hint">{{ __('Lowercase letters, numbers, and hyphens only. Used in the URL: /blog/your-slug') }}</p>

<label class="admin-upload-label" for="body">{{ __('Text (HTML)') }}</label>
<textarea
    id="body"
    name="body"
    class="admin-upload-textarea"
    rows="24"
    spellcheck="false"
    required
>{{ old('body', $post?->body) }}</textarea>

<h2 class="admin-page-title" style="font-size: 1rem; margin-top: 1.5rem;">{{ __('SEO metadata') }}</h2>
@include('admin.partials.seo-fields', ['record' => $post])
