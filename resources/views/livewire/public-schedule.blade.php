<div x-data="{
        search: '',
        activeDay: {{ count($days) > 0 ? (collect($days)->firstWhere('raw_date', now()->timezone('Asia/Jakarta')->format('Y-m-d'))['id'] ?? $days[0]['id']) : 'null' }}
    }" 
    class="max-w-md mx-auto p-4 pt-8 pb-20">
    
    <div class="mb-6 text-center">
        <h1 class="text-2xl font-bold text-gray-900 mb-1">Jadwal Wawancara INFENTRA 2.0</h1>
        <p class="text-sm text-gray-500 mb-4">{{ \Carbon\Carbon::now()->timezone('Asia/Jakarta')->translatedFormat('d M Y') }} &bull; Ruang Wawancara DC-302</p>
        
        <div class="sticky top-4 z-10">
            <div class="relative shadow-sm rounded-xl overflow-hidden border border-gray-200">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none bg-white">
                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                    </svg>
                </div>
                <input 
                    x-model="search"
                    @input="
                        $nextTick(() => {
                            if(search.length > 0) {
                                let match = document.querySelector('.slot-match');
                                if(match) {
                                    match.scrollIntoView({behavior: 'smooth', block: 'center'});
                                }
                            }
                        });
                    "
                    type="text" 
                    class="block w-full pl-10 pr-3 py-3 border-0 bg-white placeholder-gray-500 focus:ring-2 focus:ring-amber-500 sm:text-sm transition-shadow"
                    placeholder="Cari namamu..."
                >
            </div>
        </div>
    </div>

    @if(count($days) === 0)
        <div class="p-6 text-center text-gray-500 bg-white rounded-xl shadow-sm border border-gray-100">
            Jadwal belum dipublikasikan.
        </div>
    @else
        <!-- Tabs -->
        <div class="flex space-x-1 p-1 bg-gray-100 rounded-xl mb-6">
            @foreach($days as $day)
                <button 
                    @click="activeDay = {{ $day['id'] }}"
                    :class="activeDay === {{ $day['id'] }} ? 'bg-white text-gray-900 shadow' : 'text-gray-500 hover:text-gray-700'"
                    class="flex-1 py-2 text-sm font-medium rounded-lg transition-colors"
                >
                    {{ \Carbon\Carbon::parse($day['raw_date'])->format('d M') }}
                </button>
            @endforeach
        </div>

        <div>
            @foreach($days as $day)
                <div x-show="activeDay === {{ $day['id'] }}" style="display: none;">
                    <div class="space-y-3">
                        @foreach($day['items'] as $item)
                            @if($item['type'] === 'break')
                                <div x-show="search.length === 0" class="bg-gray-50 border-l-4 border-amber-400 p-3 rounded-r-lg text-sm flex items-center justify-between">
                                    <span class="font-medium text-gray-700">{{ $item['title'] }}</span>
                                    <span class="text-gray-500">{{ $item['starts_at'] }} - {{ $item['ends_at'] }}</span>
                                </div>
                            @else
                                <div 
                                    :class="(search.length > 0 && '{{ strtolower(addslashes($item['display_name'])) }}'.includes(search.toLowerCase())) ? 'border-amber-500 bg-amber-50 ring-1 ring-amber-500 slot-match' : 'border-gray-100 bg-white'"
                                    x-show="search.length === 0 || '{{ strtolower(addslashes($item['display_name'])) }}'.includes(search.toLowerCase())"
                                    class="border p-4 rounded-xl shadow-sm flex items-start justify-between transition-colors"
                                >
                                    <div class="flex flex-col">
                                        <span class="font-semibold text-gray-900">{{ $item['display_name'] }}</span>
                                    </div>
                                    <div class="flex flex-col items-end text-sm text-gray-600 shrink-0 ml-4 font-medium">
                                        <span>{{ $item['starts_at'] }} - {{ $item['ends_at'] }}</span>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <div class="mt-8 text-center text-xs text-gray-400 space-y-1">
        
        <p>Terakhir diperbarui: {{ $lastUpdated }}</p>
    </div>
</div>
