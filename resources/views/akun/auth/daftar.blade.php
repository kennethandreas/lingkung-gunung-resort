@use('App\Models\CustomerAccount')

<x-layouts.akun-tamu title="Daftar Akun" heading="Buat akun Lingkung Gunung" lead="Gratis. Simpan riwayat booking dan akomodasi favorit Anda di satu tempat.">
    <form method="POST" action="{{ route('akun.daftar.store') }}" class="space-y-5" novalidate>
        @csrf
        {{-- Honeypot anti-bot: tidak terlihat oleh manusia --}}
        <div class="hidden" aria-hidden="true">
            <label for="website">Jangan diisi</label>
            <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
        </div>

        <x-ui.field label="Nama lengkap" for="name" :error="$errors->first('name')" required>
            <x-ui.input id="name" name="name" :value="old('name')" autocomplete="name" autofocus required icon="heroicon-o-user" :invalid="$errors->has('name')" :aria-describedby="$errors->has('name') ? 'name-error' : null" />
        </x-ui.field>

        <x-ui.field label="Email" for="email" :error="$errors->first('email')" :hint="CustomerAccount::verificationRequired() ? 'Kami kirim tautan verifikasi ke email ini.' : 'Dipakai untuk masuk dan menerima e-ticket.'" required>
            <x-ui.input id="email" name="email" type="email" :value="old('email')" autocomplete="email" required icon="heroicon-o-envelope" :invalid="$errors->has('email')" :aria-describedby="$errors->has('email') ? 'email-error' : 'email-hint'" />
        </x-ui.field>

        <x-ui.field label="Password" for="password" :error="$errors->first('password')" hint="Minimal 8 karakter, berisi huruf dan angka." required>
            <x-ui.input id="password" name="password" type="password" autocomplete="new-password" required icon="heroicon-o-lock-closed" :invalid="$errors->has('password')" :aria-describedby="$errors->has('password') ? 'password-error' : 'password-hint'" />
        </x-ui.field>

        <x-ui.field label="Ulangi password" for="password_confirmation" required>
            <x-ui.input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required icon="heroicon-o-lock-closed" />
        </x-ui.field>

        <p class="text-xs leading-relaxed text-kabut-600">
            Dengan mendaftar, Anda menyetujui <a href="{{ route('halaman', 'syarat-dan-ketentuan') }}" target="_blank" class="font-semibold text-hutan-700 underline underline-offset-2">syarat &amp; ketentuan</a>
            dan <a href="{{ route('halaman', 'kebijakan-privasi') }}" target="_blank" class="font-semibold text-hutan-700 underline underline-offset-2">kebijakan privasi</a> kami.
        </p>

        <x-ui.button type="submit" variant="primary" size="lg" class="w-full">Daftar</x-ui.button>
    </form>

    <x-slot:footer>
        Sudah punya akun? <a href="{{ route('akun.masuk') }}" class="font-semibold text-hutan-700 underline underline-offset-2">Masuk</a>
    </x-slot:footer>
</x-layouts.akun-tamu>
