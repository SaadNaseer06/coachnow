@props(['url'])
[{{ config('app.name') }}]({{ $url }})

{!! $slot !!}
