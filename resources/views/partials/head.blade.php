<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>

<title>
    {{ filled($title ?? null) ? $title.' - '.auth()->user()->club?->name ?? config('app.name', 'Laravel') : auth()->user()->club?->name ?? config('app.name', 'Laravel') }}
</title>

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
<link rel="apple-touch-icon" href="/icons/icon-512.png">
<link rel="manifest" href="{{ route('manifest.json') }}">

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
