<x-filament-panels::page>
    @php
        $user = filament()->auth()->user();
        $isAdmin = $user->role?->value === 'admin';
        
        $calonCount = \App\Models\Candidate::count();
        $hmifCount = \App\Models\Candidate::where('is_hmif', true)->count();
        
        $daysCount = \App\Models\InterviewDay::count();
        $slotsCount = \App\Models\InterviewSlot::count();
        $publishedCount = \App\Models\InterviewDay::where('is_published', true)->count();
        
        // Koor metrics
        $koorPendingCount = 0;
        if (!$isAdmin) {
            $divId = $user->division_id;
            // Calon yang pilihan 1 atau 2 adalah div ini, yang belum dinilai oleh koor ini (belum ada di table scores)
            $koorPendingCount = \App\Models\Candidate::where(function($q) use ($divId) {
                $q->where('pilihan_1_id', $divId)->orWhere('pilihan_2_id', $divId);
            })->where('is_hmif', false)->whereDoesntHave('decisions', function($q) use ($user, $divId) {
                $q->where('division_id', $divId);
            })->count();
        }
    @endphp

    @if($isAdmin)
        <div class="grid grid-cols-1 gap-4">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Alur Seleksi (POH)</h2>
            <div class="space-y-4">
                
                <div class="p-4 bg-white dark:bg-gray-900 shadow rounded-xl border border-gray-200 dark:border-white/10 flex justify-between items-center">
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-white">1. Impor data calon</h3>
                        <p class="text-sm text-gray-500">Total: {{ $calonCount }} calon</p>
                    </div>
                    <x-filament::button tag="a" href="/admin/candidates" color="gray">Ke Calon Panitia</x-filament::button>
                </div>

                <div class="p-4 bg-white dark:bg-gray-900 shadow rounded-xl border border-gray-200 dark:border-white/10 flex justify-between items-center">
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-white">2. Tandai anggota HMIF</h3>
                        <p class="text-sm text-gray-500">Ditandai: {{ $hmifCount }} calon</p>
                    </div>
                    <x-filament::button tag="a" href="/admin/candidates" color="gray">Ke Calon Panitia</x-filament::button>
                </div>

                <div class="p-4 bg-white dark:bg-gray-900 shadow rounded-xl border border-gray-200 dark:border-white/10 flex justify-between items-center">
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-white">3. Atur jam mulai dan istirahat</h3>
                        <p class="text-sm text-gray-500">{{ $daysCount }} hari wawancara</p>
                    </div>
                    <x-filament::button tag="a" href="/admin/interview-days" color="gray">Ke Jadwal</x-filament::button>
                </div>

                <div class="p-4 bg-white dark:bg-gray-900 shadow rounded-xl border border-gray-200 dark:border-white/10 flex justify-between items-center">
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-white">4. Generate jadwal</h3>
                        <p class="text-sm text-gray-500">{{ $slotsCount }} slot tergenerate</p>
                    </div>
                    <x-filament::button tag="a" href="/admin/interview-slots" color="gray">Ke Wawancara</x-filament::button>
                </div>

                <div class="p-4 bg-white dark:bg-gray-900 shadow rounded-xl border border-gray-200 dark:border-white/10 flex justify-between items-center">
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-white">5. Publikasikan jadwal</h3>
                        <p class="text-sm text-gray-500">{{ $publishedCount }} hari dipublikasikan</p>
                    </div>
                    <x-filament::button tag="a" href="/admin/interview-days" color="gray">Ke Jadwal</x-filament::button>
                </div>

                <div class="p-4 bg-white dark:bg-gray-900 shadow rounded-xl border border-gray-200 dark:border-white/10 flex justify-between items-center">
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-white">6. Wawancara dan penilaian</h3>
                        <p class="text-sm text-gray-500">Mulai proses penilaian</p>
                    </div>
                    <x-filament::button tag="a" href="/admin/interview-slots" color="gray">Ke Wawancara</x-filament::button>
                </div>

                <div class="p-4 bg-white dark:bg-gray-900 shadow rounded-xl border border-gray-200 dark:border-white/10 flex justify-between items-center opacity-50">
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-white">7. Keputusan dan penempatan</h3>
                        <p class="text-sm text-gray-500">Segera hadir</p>
                    </div>
                </div>

            </div>
        </div>
    @else
        <div class="p-6 bg-white dark:bg-gray-900 shadow rounded-xl border border-gray-200 dark:border-white/10 text-center space-y-4">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Selamat Datang, Koor!</h2>
            <p class="text-gray-600 dark:text-gray-400">Ada {{ $koorPendingCount }} calon menunggu nilai Anda hari ini.</p>
            <x-filament::button tag="a" href="/admin/interview-slots" color="primary">Ke Halaman Wawancara</x-filament::button>
        </div>
    @endif
</x-filament-panels::page>
