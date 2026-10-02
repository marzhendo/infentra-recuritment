@php
    $candidate = $getRecord();
    $slot = $candidate->slot;
    
    $scores = collect();
    if ($slot) {
        $scores = \App\Models\Score::with(['interviewer', 'aspect'])
            ->where('slot_id', $slot->id)
            ->get();
    }
    
    $notes = \App\Models\CandidateNote::with(['author'])
        ->where('candidate_id', $candidate->id)
        ->orderBy('updated_at', 'desc')
        ->get()
        ->keyBy('author_id');
        
    $persons = $scores->pluck('interviewer')->merge($notes->pluck('author'))->unique('id');
@endphp

@if($persons->isEmpty())
    <div class="text-sm text-gray-500 italic">Belum ada penilaian atau catatan.</div>
@else
    <div class="space-y-4">
        @foreach($persons as $person)
            @php
                $personScores = $scores->where('interviewer_id', $person->id);
                $personNote = $notes->get($person->id);
                $avg = $personScores->count() > 0 ? round($personScores->avg('value'), 2) : '-';
                $lastEdited = $personNote ? $personNote->updated_at->format('d M Y H:i') : null;
            @endphp
            
            <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex flex-col sm:flex-row sm:items-center gap-2 mb-3">
                    <strong class="text-gray-900 dark:text-gray-100">{{ $person->name }}</strong>
                    <span class="px-2 py-0.5 text-xs bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300 rounded-full font-medium">{{ $person->jabatan }}</span>
                    <span class="sm:ml-auto text-sm text-gray-600 dark:text-gray-400">Rata-rata: <strong class="text-gray-900 dark:text-gray-100">{{ $avg }}</strong></span>
                </div>
                
                @if($personNote)
                    <div class="mb-3">
                        <p class="text-sm text-gray-700 dark:text-gray-300 italic">"{{ $personNote->body }}"</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Terakhir diedit: {{ $lastEdited }}</p>
                    </div>
                @endif
                
                @if($personScores->isNotEmpty())
                    <div class="flex flex-wrap gap-2 text-xs">
                        @foreach($personScores as $s)
                            <span class="bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 px-2 py-1 rounded text-gray-600 dark:text-gray-300">
                                {{ $s->aspect->name }}: <strong class="text-gray-900 dark:text-gray-100">{{ $s->value }}</strong>
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>
@endif
