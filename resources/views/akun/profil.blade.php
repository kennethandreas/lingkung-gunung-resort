@use('App\Models\CustomerAccount')

<x-layouts.akun title="Profil Saya" lead="Data ini dipakai untuk mengisi otomatis formulir pemesanan.">
    {{-- Data diri --}}
    <div class="border border-kabut-300 bg-white p-6 sm:p-8">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <h2 class="font-display text-2xl text-hutan-950">Data diri</h2>
            @if (CustomerAccount::verificationRequired())
                @if ($account->hasVerifiedEmail())
                    <x-ui.badge variant="sukses" icon="heroicon-m-check-badge">Email terverifikasi</x-ui.badge>
                @else
                    <x-ui.badge variant="peringatan" icon="heroicon-m-exclamation-triangle">Email belum diverifikasi</x-ui.badge>
                @endif
            @endif
        </div>

        @unless ($account->hasVerifiedEmail())
            <x-ui.alert variant="peringatan" class="mt-4">
                Verifikasi email Anda untuk membuka fitur akun.
                <form method="POST" action="{{ route('akun.verifikasi.kirim') }}" class="inline">
                    @csrf
                    <button type="submit" class="font-semibold underline underline-offset-2">Kirim ulang tautan verifikasi</button>
                </form>
            </x-ui.alert>
        @endunless

        <form method="POST" action="{{ route('akun.profil.update') }}" class="mt-6 grid gap-5 sm:grid-cols-2" novalidate>
            @csrf
            @method('PUT')
            <x-ui.field label="Nama lengkap" for="name" :error="$errors->first('name')" required class="sm:col-span-2">
                <x-ui.input id="name" name="name" :value="old('name', $account->name)" autocomplete="name" required icon="heroicon-o-user" :invalid="$errors->has('name')" :aria-describedby="$errors->has('name') ? 'name-error' : null" />
            </x-ui.field>
            <x-ui.field label="Email" for="email" :error="$errors->first('email')" :hint="CustomerAccount::verificationRequired() ? 'Bila diganti, email baru perlu diverifikasi ulang.' : null" required>
                <x-ui.input id="email" name="email" type="email" :value="old('email', $account->email)" autocomplete="email" required icon="heroicon-o-envelope" :invalid="$errors->has('email')" :aria-describedby="$errors->has('email') ? 'email-error' : (CustomerAccount::verificationRequired() ? 'email-hint' : null)" />
            </x-ui.field>
            <x-ui.field label="Nomor WhatsApp" for="phone" :error="$errors->first('phone')" hint="Opsional. Untuk mengisi otomatis formulir booking.">
                <x-ui.input id="phone" name="phone" type="tel" :value="old('phone', $account->phone)" autocomplete="tel" placeholder="0812 3456 7890" icon="heroicon-o-phone" :invalid="$errors->has('phone')" :aria-describedby="$errors->has('phone') ? 'phone-error' : 'phone-hint'" />
            </x-ui.field>
            <div class="sm:col-span-2">
                <x-ui.button type="submit" variant="primary">Simpan profil</x-ui.button>
            </div>
        </form>
    </div>

    {{-- Ganti password --}}
    <div class="mt-6 border border-kabut-300 bg-white p-6 sm:p-8">
        <h2 class="font-display text-2xl text-hutan-950">Ganti password</h2>
        <form method="POST" action="{{ route('akun.profil.password') }}" class="mt-6 grid gap-5 sm:grid-cols-2" novalidate>
            @csrf
            @method('PUT')
            <x-ui.field label="Password saat ini" for="current_password" :error="$errors->password->first('current_password')" required class="sm:col-span-2">
                <x-ui.input id="current_password" name="current_password" type="password" autocomplete="current-password" required icon="heroicon-o-lock-closed" :invalid="$errors->password->has('current_password')" :aria-describedby="$errors->password->has('current_password') ? 'current_password-error' : null" />
            </x-ui.field>
            <x-ui.field label="Password baru" for="new_password" :error="$errors->password->first('password')" hint="Minimal 8 karakter, berisi huruf dan angka." required>
                <x-ui.input id="new_password" name="password" type="password" autocomplete="new-password" required icon="heroicon-o-key" :invalid="$errors->password->has('password')" :aria-describedby="$errors->password->has('password') ? 'new_password-error' : 'new_password-hint'" />
            </x-ui.field>
            <x-ui.field label="Ulangi password baru" for="password_confirmation" required>
                <x-ui.input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required icon="heroicon-o-key" />
            </x-ui.field>
            <div class="sm:col-span-2">
                <x-ui.button type="submit" variant="outline">Ganti password</x-ui.button>
            </div>
        </form>
    </div>
</x-layouts.akun>
