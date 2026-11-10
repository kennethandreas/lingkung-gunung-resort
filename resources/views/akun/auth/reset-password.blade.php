<x-layouts.akun-tamu title="Atur Ulang Password" heading="Buat password baru">
    <form method="POST" action="{{ route('akun.password.update') }}" class="space-y-5" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <x-ui.field label="Email" for="email" :error="$errors->first('email')" required>
            <x-ui.input id="email" name="email" type="email" :value="old('email', $email)" autocomplete="email" required icon="heroicon-o-envelope" :invalid="$errors->has('email')" :aria-describedby="$errors->has('email') ? 'email-error' : null" />
        </x-ui.field>

        <x-ui.field label="Password baru" for="password" :error="$errors->first('password')" hint="Minimal 8 karakter, berisi huruf dan angka." required>
            <x-ui.input id="password" name="password" type="password" autocomplete="new-password" autofocus required icon="heroicon-o-lock-closed" :invalid="$errors->has('password')" :aria-describedby="$errors->has('password') ? 'password-error' : 'password-hint'" />
        </x-ui.field>

        <x-ui.field label="Ulangi password baru" for="password_confirmation" required>
            <x-ui.input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required icon="heroicon-o-lock-closed" />
        </x-ui.field>

        <x-ui.button type="submit" variant="primary" size="lg" class="w-full">Simpan password baru</x-ui.button>
    </form>
</x-layouts.akun-tamu>
