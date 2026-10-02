<x-filament-panels::page>
    @php
        $stats = [
            'primary_complete' => 0,
            'primary_partial' => 0,
            'primary_none' => 0,
            
            'decisions_in' => 0,
            'decisions_missing' => 0,
            
            'placements_set' => 0,
            'placements_pending' => 0,
        ];
        
        $candidates = (new \App\Services\ResultsQuery)->query()->get();
        foreach ($candidates as $c) {
            // Placements
            if ($c->placement) {
                $stats['placements_set']++;
            } else {
                $stats['placements_pending']++;
            }
            
            // Decisions
            $expected = 0;
            $in = 0;
            if ($c->pilihan_1_id) { $expected++; if ($c->decisions->where('division_id', $c->pilihan_1_id)->count() > 0) $in++; }
            if ($c->pilihan_2_id) { $expected++; if ($c->decisions->where('division_id', $c->pilihan_2_id)->count() > 0) $in++; }
            
            if ($expected > 0 && $in === $expected) {
                $stats['decisions_in']++;
            } else {
                $stats['decisions_missing']++;
            }
            
            // Scorecards
            if ($c->is_hmif || !$c->slot) {
                $stats['primary_none']++;
            } else {
                // Determine if primary scorers have scored
                $headId = \App\Models\User::where('is_head_interviewer', true)->value('id');
                $p1KoorId = \App\Models\User::where('role', 'koor')->where('division_id', $c->pilihan_1_id)->value('id');
                $p2KoorId = \App\Models\User::where('role', 'koor')->where('division_id', $c->pilihan_2_id)->value('id');
                
                $primaryIds = collect([$headId, $p1KoorId, $p2KoorId])->filter()->unique();
                $expectedScorers = $primaryIds->count();
                
                $actualScorers = \App\Models\Score::where('slot_id', $c->slot->id)
                    ->whereIn('interviewer_id', $primaryIds)
                    ->select('interviewer_id')
                    ->distinct()
                    ->count();
                    
                if ($expectedScorers == 0) {
                    $stats['primary_none']++;
                } else if ($actualScorers == 0) {
                    $stats['primary_none']++;
                } else if ($actualScorers == $expectedScorers) {
                    $stats['primary_complete']++;
                } else {
                    $stats['primary_partial']++;
                }
            }
        }
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <x-filament::section>
            <x-slot name="heading">Penilaian Utama</x-slot>
            <div class="text-sm">
                Lengkap: <strong>{{ $stats['primary_complete'] }}</strong><br>
                Sebagian: <strong>{{ $stats['primary_partial'] }}</strong><br>
                Kosong: <strong>{{ $stats['primary_none'] }}</strong>
            </div>
        </x-filament::section>
        <x-filament::section>
            <x-slot name="heading">Keputusan Koor</x-slot>
            <div class="text-sm">
                Masuk: <strong>{{ $stats['decisions_in'] }}</strong><br>
                Kurang: <strong>{{ $stats['decisions_missing'] }}</strong>
            </div>
        </x-filament::section>
        <x-filament::section>
            <x-slot name="heading">Penempatan (Fase 6)</x-slot>
            <div class="text-sm">
                Sudah: <strong>{{ $stats['placements_set'] }}</strong><br>
                Belum: <strong>{{ $stats['placements_pending'] }}</strong>
            </div>
        </x-filament::section>
    </div>

    {{ $this->table }}
</x-filament-panels::page>
