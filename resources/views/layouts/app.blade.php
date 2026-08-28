<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $title ?? 'CRX Farm' }}</title>
  <link rel="stylesheet" href="{{ asset('css/app.css') }}">
  <script src="https://cdn.jsdelivr.net/npm/htmx.org@2.0.4/dist/htmx.min.js" defer></script>
  @if(session('status'))<meta name="flash" content="{{ session('status') }}">@endif
</head>
<body>
  <header class="site">
    <a class="brand" href="{{ route('catalog.index') }}">CRX<span>FARM</span></a>
    <span class="tag">Rossville, KS &middot; 150+ Hondas parted out &middot; international shipping</span>
  </header>
  <main>
    @if(session('status'))
      <p class="status-msg">{{ session('status') }}</p>
    @endif
    @yield('content')
  </main>
</body>
</html>
