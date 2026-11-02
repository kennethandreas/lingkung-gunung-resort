<x-layouts.akun-tamu title="Masuk" heading="Masuk ke akun Anda" lead="Pantau booking, simpan akomodasi favorit, dan pesan lebih cepat.">
    <form method="POST" action="{{ route('akun.masuk.store') }}" class="space-y-5" novalidate>
        @csrf
        <x-ui.field label="Email" for="email" :error="$errors->first('email')" required>
            <x-ui.input id="email" name="email" type="email" :value="old('email')" autocomplete="email" autofocus required icon="heroicon-o-envelope" :invalid="$errors->has('email')" :aria-describedby="$errors->has('email') ? 'email-error' : null" />
        </x-ui.field>

        <x-ui.field label="Password" for="password" :error="$errors->first('password')" required>
            <x-ui.input id="password" name="password" type="password" autocomplete="current-password" required icon="heroicon-o-lock-closed" :invalid="$errors->has('password')" :aria-describedby="$errors->has('password') ? 'password-error' : null" />
        </x-ui.field>

        <div class="flex flex-wrap items-center justify-between gap-3 text-sm">
            <label class="flex items-center gap-2.5 text-kabut-700">
                <input type="checkbox" name="remember" value="1" class="size-5 rounded accent-hutan-700" @checked(old('remember'))>
                Ingat saya
            </label>
            <a href="{{ route('akun.password.request') }}" class="font-semibold text-hutan-700 underline underline-offset-2">Lupa password?</a>
        </div>

        <x-ui.button type="submit" variant="primary" size="lg" class="w-full">Masuk</x-ui.button>
    </form>

    <div class="mt-6 border-t border-kabut-200 pt-6">
        <p class="text-sm text-kabut-700">Baru pertama kali?</p>
        <x-ui.button variant="outline" size="lg" class="mt-3 w-full" :href="route('akun.daftar')">Buat akun baru (gratis)</x-ui.button>
    </div>

    <x-slot:footer>
        <p class="text-xs text-kabut-600">Melihat akomodasi, harga, dan ketersediaan tidak perlu akun. Akun dibutuhkan saat memesan online, agar status booking dan e-ticket bisa Anda buka kapan saja.</p>
    </x-slot:footer>
</x-layouts.akun-tamu>
