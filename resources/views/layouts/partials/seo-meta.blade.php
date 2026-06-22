@php
    $seo = $seo ?? [];
    $pageTitle = $seo['title'] ?? ($pageTitle ?? app_name());
    $metaDescription = $seo['description'] ?? null;
    $ogTitle = $seo['og_title'] ?? $pageTitle;
    $ogDescription = $seo['og_description'] ?? $metaDescription;
@endphp
<title>{{ $pageTitle }}</title>
@if (filled($metaDescription))
    <meta name="description" content="{{ $metaDescription }}">
@endif
@if (filled($ogTitle))
    <meta property="og:title" content="{{ $ogTitle }}">
@endif
@if (filled($ogDescription))
    <meta property="og:description" content="{{ $ogDescription }}">
@endif
<meta property="og:type" content="website">
