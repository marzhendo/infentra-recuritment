<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Jadwal Wawancara INFENTRA</title>
    @unless(app()->runningUnitTests())
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endunless
    @livewireStyles
</head>
<body class="bg-gray-50 text-gray-900 antialiased min-h-screen">
    {{ $slot }}
    @livewireScripts
</body>
</html>
