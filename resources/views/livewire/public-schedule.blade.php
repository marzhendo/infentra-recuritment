<div class="max-w-md mx-auto p-4 pt-8">
    <div class="mb-8 text-center">
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Cari Jadwal Wawancara</h1>
        <p class="text-sm text-gray-600">Masukkan minimal 3 huruf nama Anda</p>
    </div>

    <div class="relative mb-6">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
            </svg>
        </div>
        <input 
            wire:model.live.debounce.300ms="search" 
            type="text" 
            class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-xl leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 sm:text-sm transition-shadow shadow-sm"
            placeholder="Cari nama..."
        >
    </div>

    @error('search')
        <div class="p-4 mb-6 rounded-xl bg-red-50 text-red-700 text-sm border border-red-100">
            {{ $message }}
        </div>
    @enderror

    <div class="space-y-4">
        @if(strlen($search) >= 3 && count($this->results) === 0 && !$errors->has('search'))
            <div class="p-6 text-center text-gray-500 bg-white rounded-xl shadow-sm border border-gray-100">
                Tidak ada hasil yang cocok.
            </div>
        @endif

        @foreach($this->results as $result)
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition-shadow">
                <h3 class="font-bold text-lg text-gray-900 mb-3">{{ $result['name'] }}</h3>
                
                <div class="space-y-2 text-sm">
                    <div class="flex items-start">
                        <svg class="h-5 w-5 text-gray-400 mr-2 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span class="text-gray-700 font-medium">{{ $result['status'] }}</span>
                    </div>

                    @if($result['time'] !== '-')
                    <div class="flex items-start">
                        <svg class="h-5 w-5 text-gray-400 mr-2 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="text-gray-600">{{ $result['time'] }}</span>
                    </div>
                    @endif

                    @if($result['room'] !== '-')
                    <div class="flex items-start">
                        <svg class="h-5 w-5 text-gray-400 mr-2 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span class="text-gray-600">{{ $result['room'] }}</span>
                    </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
