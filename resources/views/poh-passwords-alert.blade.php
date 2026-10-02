@if($passwords)
    <div style="margin-bottom: 1.5rem;" x-data="{}">
        <x-filament::section style="background-color: rgba(254, 243, 199, 0.1); border-color: #fcd34d;">
            <x-slot name="heading">
                <span style="color: #d97706;">PENTING: Simpan Kata Sandi Ini Sekarang!</span>
            </x-slot>
            <x-slot name="description">
                <span style="color: #b45309;">Kata sandi ini hanya ditampilkan satu kali ini saja demi keamanan. Segera salin dan bagikan ke masing-masing pengguna. Mereka akan diminta mengganti kata sandi setelah masuk.</span>
            </x-slot>

            <div style="margin-top: 1rem; border: 1px solid rgba(252, 211, 77, 0.5); border-radius: 0.5rem; overflow: hidden; background: transparent;">
                <table style="width: 100%; text-align: left; font-size: 0.875rem;">
                    <thead style="background-color: rgba(253, 230, 138, 0.1); color: #d97706;">
                        <tr>
                            <th style="padding: 0.5rem 1rem;">Nama</th>
                            <th style="padding: 0.5rem 1rem;">Jabatan</th>
                            <th style="padding: 0.5rem 1rem;">Kata Sandi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($passwords as $p)
                            <tr style="border-top: 1px solid rgba(252, 211, 77, 0.2);">
                                <td style="padding: 0.5rem 1rem;">{{ $p['name'] }}</td>
                                <td style="padding: 0.5rem 1rem;">{{ $p['jabatan'] }}</td>
                                <td style="padding: 0.5rem 1rem; font-family: monospace; user-select: all;">{{ $p['password'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 1rem;" x-data='{
                passwords: @json($passwords),
                copyAll(btn) {
                    let text = "";
                    this.passwords.forEach(p => text += p.name + ": " + p.password + "\n");
                    navigator.clipboard.writeText(text);
                    btn.innerText = "Tersalin!";
                    setTimeout(() => btn.innerText = "Salin Semua", 2000);
                }
            }'>
                <x-filament::button
                    color="warning"
                    x-on:click="copyAll($el)"
                >
                    Salin Semua
                </x-filament::button>
            </div>
        </x-filament::section>
    </div>
@endif
