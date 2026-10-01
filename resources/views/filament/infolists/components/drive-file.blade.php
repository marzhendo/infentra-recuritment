@php
    $url = $getState();
    $previewUrl = \App\Support\DriveLink::previewUrl($url);
@endphp

<div>
    @if ($previewUrl)
        <div class="mb-2">
            <a href="{{ $url }}" target="_blank" class="text-primary-600 hover:underline">
                Buka di tab baru
            </a>
        </div>
        <iframe src="{{ $previewUrl }}" width="100%" height="400" frameborder="0" allowfullscreen></iframe>
    @else
        <p class="text-gray-500 italic">Tidak ada berkas</p>
    @endif
</div>
