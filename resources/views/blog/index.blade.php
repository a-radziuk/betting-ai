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
        <a class="subbar-back" href="{{ url('/') }}">← {{ __('Back to home') }}</a>
    </div>
</div>

<main class="container">
    <section class="hero">
        <h1>{{ __('Blog') }}</h1>
    </section>

    @if ($posts->isEmpty())
        <section class="card card-pad">
            <p class="admin-empty">{{ __('No posts yet.') }}</p>
        </section>
    @else
        <section class="blog-index">
            @foreach ($posts as $post)
                <article class="card card-pad blog-index-item">
                    <h2 class="blog-index-title">
                        <a href="{{ route('blog.show', $post->slug) }}">{{ $post->title }}</a>
                    </h2>
                    <p class="meta">
                        {{ $post->author }}
                        @if ($post->published_at)
                            · {{ $post->published_at->format('Y-m-d') }}
                        @endif
                    </p>
                </article>
            @endforeach
        </section>
    @endif
</main>

@include('layouts.partials.betai-footer')
</body>
</html>
