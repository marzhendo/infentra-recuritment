@if($passwords)
    <div class="mb-4 p-4 bg-amber-50 border border-amber-200 rounded-xl" x-data="{}">
        <h3 class="font-bold text-amber-900 mb-2">PENTING: Simpan Kata Sandi Ini Sekarang!</h3>
        <p class="text-amber-800 text-sm mb-4">Kata sandi ini hanya ditampilkan satu kali ini saja demi keamanan. Segera salin dan bagikan ke masing-masing pengguna. Mereka akan diminta mengganti kata sandi setelah masuk.</p>
        
        <div class="bg-white rounded-lg border border-amber-200 overflow-hidden mb-4">
            <table class="w-full text-sm text-left">
                <thead class="bg-amber-100 text-amber-900">
                    <tr>
                        <th class="px-4 py-2">Nama</th>
                        <th class="px-4 py-2">Jabatan</th>
                        <th class="px-4 py-2">Kata Sandi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($passwords as $p)
                        <tr class="border-t border-amber-100">
                            <td class="px-4 py-2">{{ $p['name'] }}</td>
                            <td class="px-4 py-2">{{ $p['jabatan'] }}</td>
                            <td class="px-4 py-2 font-mono text-gray-900 select-all">{{ $p['password'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <button 
            type="button" 
            class="px-4 py-2 bg-amber-600 text-white rounded-lg text-sm font-medium hover:bg-amber-700 transition"
            @click="
                let text = '';
                @foreach($passwords as $p)
                    text += '{{ $p['name'] }}: {{ $p['password'] }}\n';
                @endforeach
                navigator.clipboard.writeText(text);
                $el.innerText = 'Tersalin!';
                setTimeout(() => $el.innerText = 'Salin Semua', 2000);
            "
        >
            Salin Semua
        </button>
    </div>
@endif
