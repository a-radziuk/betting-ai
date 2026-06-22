<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('layouts.partials.seo-meta', ['seo' => $seo ?? []])
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('layouts.partials.betai-styles')
</head>
<body>
@include('layouts.partials.betai-header')

<div class="subbar">
    <div class="container subbar-inner">
        <a class="subbar-back" href="{{ route('blog.index') }}">← {{ __('Back to blog') }}</a>
    </div>
</div>

<main class="container">
    <section class="hero">
        <h1>{{ $post->title }}</h1>
        <p class="meta">
            {{ $post->author }}
            @if ($post->published_at)
                · {{ $post->published_at->format('Y-m-d') }}
            @endif
        </p>
    </section>

    <section class="card legal-page-card">
        <div class="legal-page-body">
            {!! $post->body !!}
        </div>
    </section>
</main>

@include('layouts.partials.betai-footer')
</body>
</html>
