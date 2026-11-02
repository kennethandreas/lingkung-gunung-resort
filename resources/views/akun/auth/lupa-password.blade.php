<x-layouts.akun-tamu title="Lupa Password" heading="Lupa password?" lead="Masukkan email akun Anda. Kami kirim tautan untuk membuat password baru.">
    <form method="POST" action="{{ route('akun.password.email') }}" class="space-y-5" novalidate>
        @csrf
        <x-ui.field label="Email" for="email" :error="$errors->first('email')" required>
            <x-ui.input id="email" name="email" type="email" :value="old('email')" autocomplete="email" autofocus required icon="heroicon-o-envelope" :invalid="$errors->has('email')" :aria-describedby="$errors->has('email') ? 'email-error' : null" />
        </x-ui.field>

        <x-ui.button type="submit" variant="primary" size="lg" class="w-full">Kirim tautan</x-ui.button>
    </form>

    <x-slot:footer>
        Ingat password Anda? <a href="{{ route('akun.masuk') }}" class="font-semibold text-hutan-700 underline underline-offset-2">Kembali masuk</a>
    </x-slot:footer>
</x-layouts.akun-tamu>
