<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('BetAI | Upcoming Football Events') }}</title>
            @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('layouts.partials.betai-styles')
    </head>
<body>
    @include('layouts.partials.betai-header')

    {!! $mainHtml !!}

    @include('layouts.partials.betai-footer')
    </body>
</html>
