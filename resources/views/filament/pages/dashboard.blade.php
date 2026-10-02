<x-filament-panels::page>
    @php
        $user = filament()->auth()->user();
        $isAdmin = $user->role?->value === 'admin';
        
        $calonCount = \App\Models\Candidate::count();
        $hmifCount = \App\Models\Candidate::where('is_hmif', true)->count();
        
        $daysCount = \App\Models\InterviewDay::count();
        $slotsCount = \App\Models\InterviewSlot::count();
        $publishedCount = \App\Models\InterviewDay::where('is_published', true)->count();
        
        $koorPendingCount = 0;
        if (!$isAdmin) {
            $divId = $user->division_id;
            
            // Koor: check Candidates they need to score (not HMIF, chosen their division, has a slot, but missing complete scores).
            // For simplicity, we just count candidates where they have no scores at all for that slot.
            $koorPendingCount = \App\Models\Candidate::where(function($q) use ($divId) {
                $q->where('pilihan_1_id', $divId)->orWhere('pilihan_2_id', $divId);
            })
            ->where('is_hmif', false)
            ->whereHas('slot')
            ->whereDoesntHave('slot.scores', function($q) use ($user) {
                $q->where('interviewer_id', $user->id);
            })->count();
        }
    @endphp

    @if($isAdmin)
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            <x-filament::section>
                <x-slot name="heading">1. Impor data calon</x-slot>
                <x-slot name="description">Total: {{ $calonCount }} calon</x-slot>
                <x-slot name="headerEnd">
                    <x-filament::button tag="a" href="/admin/candidates" color="gray">Ke Calon Panitia</x-filament::button>
                </x-slot>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">2. Tandai anggota HMIF</x-slot>
                <x-slot name="description">Ditandai: {{ $hmifCount }} calon</x-slot>
                <x-slot name="headerEnd">
                    <x-filament::button tag="a" href="/admin/candidates" color="gray">Ke Calon Panitia</x-filament::button>
                </x-slot>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">3. Atur jam mulai dan istirahat</x-slot>
                <x-slot name="description">{{ $daysCount }} hari wawancara</x-slot>
                <x-slot name="headerEnd">
                    <x-filament::button tag="a" href="/admin/interview-days" color="gray">Ke Jadwal</x-filament::button>
                </x-slot>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">4. Generate jadwal</x-slot>
                <x-slot name="description">{{ $slotsCount }} slot tergenerate</x-slot>
                <x-slot name="headerEnd">
                    <x-filament::button tag="a" href="/admin/interview-slots" color="gray">Ke Wawancara</x-filament::button>
                </x-slot>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">5. Publikasikan jadwal</x-slot>
                <x-slot name="description">{{ $publishedCount }} hari dipublikasikan</x-slot>
                <x-slot name="headerEnd">
                    <x-filament::button tag="a" href="/admin/interview-days" color="gray">Ke Jadwal</x-filament::button>
                </x-slot>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">6. Wawancara dan penilaian</x-slot>
                <x-slot name="description">Mulai proses penilaian</x-slot>
                <x-slot name="headerEnd">
                    <x-filament::button tag="a" href="/admin/interview-slots" color="gray">Ke Wawancara</x-filament::button>
                </x-slot>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">7. Keputusan dan penempatan</x-slot>
                <x-slot name="description">Pantau hasil seleksi dan tetapkan penempatan</x-slot>
                <x-slot name="headerEnd">
                    <x-filament::button tag="a" href="/admin/hasil-seleksi" color="gray">Ke Hasil Seleksi</x-filament::button>
                </x-slot>
            </x-filament::section>
        </div>
    @else
        <x-filament::section>
            <div style="text-align: center; padding: 2rem;">
                <h2 style="font-size: 1.25rem; font-weight: bold; margin-bottom: 0.5rem;">Selamat Datang, Koor!</h2>
                <p style="color: gray; margin-bottom: 1.5rem;">Ada {{ $koorPendingCount }} calon menunggu nilai Anda hari ini.</p>
                <x-filament::button tag="a" href="/admin/interview-slots" color="primary">Ke Halaman Wawancara</x-filament::button>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
