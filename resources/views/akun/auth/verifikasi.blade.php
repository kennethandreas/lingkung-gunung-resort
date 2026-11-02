<x-layouts.akun-tamu title="Verifikasi Email" heading="Satu langkah lagi" :lead="'Kami mengirim tautan verifikasi ke '.auth('customer')->user()->email.'. Buka email tersebut dan klik tombol Verifikasi email.'">
    <div class="flex flex-col items-center text-center">
        <span class="flex size-16 items-center justify-center rounded-none bg-hutan-50 text-hutan-700">
            <x-heroicon-o-envelope-open class="size-8" aria-hidden="true" />
        </span>
        <p class="mt-4 text-sm leading-relaxed text-kabut-700">
            Setelah email terverifikasi, Anda bisa melihat booking, menyimpan akomodasi, dan mengelola profil.
            Tidak menemukan emailnya? Periksa folder <strong>Spam</strong> atau <strong>Promosi</strong>.
        </p>

        <form method="POST" action="{{ route('akun.verifikasi.kirim') }}" class="mt-6 w-full">
            @csrf
            <x-ui.button type="submit" variant="primary" size="lg" class="w-full" icon="heroicon-m-arrow-path">Kirim ulang tautan</x-ui.button>
        </form>

        @error('email')<p class="mt-3 text-sm text-bahaya-600" role="alert">{{ $message }}</p>@enderror

        <div class="mt-6 flex flex-wrap justify-center gap-x-6 gap-y-2 text-sm">
            <a href="{{ route('akun.profil') }}" class="font-semibold text-hutan-700 underline underline-offset-2">Email salah? Ganti email</a>
            <form method="POST" action="{{ route('akun.keluar') }}">
                @csrf
                <button type="submit" class="font-semibold text-kabut-700 underline underline-offset-2">Keluar</button>
            </form>
        </div>
    </div>
</x-layouts.akun-tamu>
