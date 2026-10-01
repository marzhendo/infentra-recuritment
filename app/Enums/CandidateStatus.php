<?php

namespace App\Enums;

enum CandidateStatus: string
{
    case Terdaftar = 'terdaftar';
    case Dijadwalkan = 'dijadwalkan';
    case SelesaiWawancara = 'selesai_wawancara';
    case Dinilai = 'dinilai';
}
