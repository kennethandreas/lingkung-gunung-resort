@use('App\Enums\InquiryTopic')
@use('App\Models\Faq')
@use('App\Models\Setting')
@use('App\Support\ResortContact')
@use('App\Support\WhatsApp')

@php
    $allFaqs = $faqs->flatten();
    $jsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => $allFaqs->map(fn ($faq) => ['@type' => 'Question', 'name' => $faq->question, 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq->answer]])->values()->all(),
    ];
    $card = 'group rounded-kartu bg-white p-6 shadow-lembut ring-1 ring-kabut-200 transition duration-500 ease-alam';
    $groupTopics = collect(InquiryTopic::cases())->filter->isGroup()->map->value->values();
    $account = auth('customer')->user();
@endphp

<x-layouts.public title="Kontak & FAQ" description="Hubungi Lingkung Gunung via WhatsApp 0811-1516-727, telepon 0251-829-1453, atau email marketing@elji.id. Kirim pertanyaan dan temukan jawaban seputar pemesanan, pembayaran, rombongan, dan akses lokasi.">
    <x-slot:head>
        <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    </x-slot:head>

    <section class="section bg-kabut-50">
        <div class="container-lg">
            <x-ui.section-heading level="h1" eyebrow="Kontak" title="Kami siap membantu" lead="Tim kami membalas pesan WhatsApp secepatnya. Untuk rombongan, sertakan perkiraan tanggal dan jumlah peserta." />
            <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-ui.reveal>
                    <a href="{{ WhatsApp::link('Halo Lingkung Gunung, saya ingin bertanya.') }}" target="_blank" rel="noopener" class="group block h-full rounded-kartu bg-hutan-800 p-6 text-white shadow-lembut transition duration-500 ease-alam">
                        <x-ui.icon-whatsapp class="size-8 transition duration-500 ease-alam group-hover:scale-110" />
                        <p class="mt-4 text-sm text-hutan-100">WhatsApp</p>
                        <p class="text-xl font-bold">{{ WhatsApp::display() }}</p>
                        <p class="mt-1 text-sm text-hutan-200">Sales & reservasi</p>
                    </a>
                </x-ui.reveal>
                @if ($phone = Setting::get('contact_phone'))
                    <x-ui.reveal :delay="80">
                        <a href="tel:{{ preg_replace('/\D/', '', $phone) }}" class="{{ $card }} block h-full">
                            <x-heroicon-o-phone class="size-8 text-emas-600 transition duration-500 ease-alam group-hover:scale-110" aria-hidden="true" />
                            <p class="mt-4 text-sm text-kabut-600">Telepon</p>
                            <p class="text-xl font-bold text-hutan-900">{{ $phone }}</p>
                        </a>
                    </x-ui.reveal>
                @endif
                @if ($email = ResortContact::email())
                    <x-ui.reveal :delay="160">
                        <a href="mailto:{{ $email }}" class="{{ $card }} block h-full">
                            <x-heroicon-o-envelope class="size-8 text-emas-600 transition duration-500 ease-alam group-hover:scale-110" aria-hidden="true" />
                            <p class="mt-4 text-sm text-kabut-600">Email</p>
                            <p class="break-all text-lg font-bold text-hutan-900">{{ $email }}</p>
                        </a>
                    </x-ui.reveal>
                @endif
                <x-ui.reveal :delay="240">
                    <a href="{{ route('rute') }}" class="{{ $card }} block h-full">
                        <x-heroicon-o-map-pin class="size-8 text-emas-600 transition duration-500 ease-alam group-hover:scale-110" aria-hidden="true" />
                        <p class="mt-4 text-sm text-kabut-600">Alamat</p>
                        <p class="font-semibold text-hutan-900">{{ Setting::get('address') }}</p>
                    </a>
                </x-ui.reveal>
            </div>

            @if ($socials = ResortContact::socials())
                <div class="mt-8 flex flex-wrap items-center gap-3">
                    <p class="text-sm font-semibold text-kabut-700">Kanal resmi:</p>
                    @foreach ($socials as $social)
                        <a href="{{ $social['url'] }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-none bg-white px-4 py-2 text-sm font-semibold text-hutan-900 shadow-lembut ring-1 ring-kabut-200 transition duration-300 ease-alam hover:ring-hutan-300">
                            <x-dynamic-component :component="$social['icon']" class="size-4 text-emas-600" aria-hidden="true" />
                            <span class="sr-only">{{ $social['label'] }}:</span> {{ $social['display'] }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- FORM PERTANYAAN --}}
    <section class="section bg-white" id="kirim-pertanyaan">
        <div class="container-lg grid gap-10 lg:grid-cols-[1fr_1.4fr]">
            <div>
                <x-ui.section-heading eyebrow="Kirim pertanyaan" title="Tanya dulu, rencanakan dengan tenang" lead="Tulis pertanyaan Anda, tim kami membalas lewat WhatsApp atau email pada jam kerja. Untuk cek ketersediaan kamar secara langsung, gunakan tombol Cek Ketersediaan." />
                <ul class="mt-6 space-y-3 text-sm text-kabut-700">
                    <li class="flex gap-2"><x-heroicon-o-check-circle class="size-5 shrink-0 text-hutan-600" aria-hidden="true" /> <span>Rombongan: sertakan tanggal dan jumlah peserta supaya penawaran lebih akurat.</span></li>
                    <li class="flex gap-2"><x-heroicon-o-check-circle class="size-5 shrink-0 text-hutan-600" aria-hidden="true" /> <span>Ingin langsung hitung harga paket? Coba <a href="{{ route('rancang-paket') }}" class="font-semibold text-hutan-700 underline decoration-emas-400 underline-offset-4">Rancang Paket Rombongan</a>.</span></li>
                    <li class="flex gap-2"><x-heroicon-o-lock-closed class="size-5 shrink-0 text-hutan-600" aria-hidden="true" /> <span>Data Anda hanya dipakai untuk membalas pertanyaan ini.</span></li>
                </ul>
            </div>

            <div class="rounded-kartu bg-kabut-50 p-6 ring-1 ring-kabut-200 sm:p-8">
                @if (session('inquiry_sent'))
                    <div x-data x-init="$el.focus()" tabindex="-1" class="outline-none">
                        <x-ui.alert variant="sukses" title="Pertanyaan terkirim. Terima kasih!">
                            Tim kami akan membalas lewat WhatsApp atau email. Butuh jawaban cepat? <a href="{{ WhatsApp::link('Halo Lingkung Gunung, saya baru mengirim pertanyaan lewat website.') }}" target="_blank" rel="noopener" class="font-semibold underline">Chat WhatsApp</a>.
                        </x-ui.alert>
                    </div>
                @else
                    @if ($errors->any())
                        <x-ui.alert variant="bahaya" class="mb-6" title="Periksa kembali isian Anda">Ada {{ $errors->count() }} isian yang perlu diperbaiki.</x-ui.alert>
                    @endif
                    <form method="POST" action="{{ route('kontak.store') }}" class="grid gap-5 sm:grid-cols-2" novalidate
                          x-data="{ topic: @js(old('topic', '')), group: @js($groupTopics), sending: false }" x-on:submit="sending = true">
                        @csrf
                        {{-- honeypot: disembunyikan dari manusia --}}
                        <div class="hidden" aria-hidden="true"><label for="website">Website</label><input type="text" id="website" name="website" tabindex="-1" autocomplete="off"></div>

                        <x-ui.field label="Nama" for="name" :error="$errors->first('name')" required>
                            <x-ui.input id="name" name="name" :value="old('name', $account?->name)" autocomplete="name" required :invalid="$errors->has('name')" aria-describedby="name-error" />
                        </x-ui.field>
                        <x-ui.field label="Nomor WhatsApp" for="phone" :error="$errors->first('phone')" required>
                            <x-ui.input id="phone" name="phone" type="tel" inputmode="tel" :value="old('phone', $account?->phone)" autocomplete="tel" placeholder="0812 3456 7890" required :invalid="$errors->has('phone')" aria-describedby="phone-error" />
                        </x-ui.field>
                        <x-ui.field label="Email (opsional)" for="email" :error="$errors->first('email')">
                            <x-ui.input id="email" name="email" type="email" :value="old('email', $account?->email)" autocomplete="email" :invalid="$errors->has('email')" aria-describedby="email-error" />
                        </x-ui.field>
                        <x-ui.field label="Topik" for="topic" :error="$errors->first('topic')" required>
                            <x-ui.select id="topic" name="topic" x-model="topic" required :invalid="$errors->has('topic')" aria-describedby="topic-error">
                                <option value="">Pilih topik</option>
                                @foreach (InquiryTopic::cases() as $topic)
                                    <option value="{{ $topic->value }}" @selected(old('topic') === $topic->value)>{{ $topic->getLabel() }}</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.field>
                        <x-ui.field label="Rencana tanggal (opsional)" for="visit_date" :error="$errors->first('visit_date')">
                            <x-ui.input id="visit_date" name="visit_date" type="date" :min="today()->toDateString()" :value="old('visit_date')" :invalid="$errors->has('visit_date')" aria-describedby="visit_date-error" />
                        </x-ui.field>
                        <div x-show="group.includes(topic)" x-collapse>
                            <x-ui.field label="Perkiraan jumlah peserta" for="participants" :error="$errors->first('participants')">
                                <x-ui.input id="participants" name="participants" type="number" inputmode="numeric" min="1" max="5000" :value="old('participants')" :invalid="$errors->has('participants')" aria-describedby="participants-error" />
                            </x-ui.field>
                        </div>
                        <x-ui.field label="Pertanyaan" for="message" :error="$errors->first('message')" required class="sm:col-span-2">
                            <textarea id="message" name="message" rows="5" maxlength="2000" required aria-describedby="message-error"
                                @class(['block w-full rounded-sm border bg-white px-4 py-3 text-[0.9375rem] text-kabut-900 shadow-sm transition placeholder:text-kabut-400 focus:outline-none focus:ring-4', 'border-kabut-300 focus:border-hutan-600 focus:ring-hutan-600/15' => ! $errors->has('message'), 'border-bahaya-600 focus:ring-bahaya-600/15' => $errors->has('message')])
                                placeholder="Contoh: Apakah ada glamping kosong untuk 2 malam di akhir bulan, dan apakah bisa tambah kasur?">{{ old('message') }}</textarea>
                        </x-ui.field>
                        <div class="sm:col-span-2">
                            <label class="flex items-start gap-3 text-sm text-kabut-700">
                                <input type="checkbox" name="consent" value="1" @checked(old('consent')) class="mt-0.5 size-5 shrink-0 rounded border-kabut-300 text-hutan-700 focus:ring-hutan-600/30" aria-describedby="consent-error">
                                <span>Saya setuju dihubungi oleh tim Lingkung Gunung lewat WhatsApp/email terkait pertanyaan ini (<a href="{{ route('halaman', 'kebijakan-privasi') }}" class="font-semibold text-hutan-700 underline underline-offset-4">kebijakan privasi</a>).</span>
                            </label>
                            @error('consent')<p id="consent-error" class="mt-1.5 text-sm text-bahaya-600" role="alert">{{ $message }}</p>@enderror
                        </div>
                        <div class="flex flex-wrap items-center gap-3 sm:col-span-2">
                            <x-ui.button type="submit" variant="primary" size="lg" icon-right="heroicon-m-paper-airplane" x-bind:disabled="sending">
                                <span x-show="! sending">Kirim pertanyaan</span>
                                <span x-show="sending" x-cloak>Mengirim…</span>
                            </x-ui.button>
                            <p class="text-xs text-kabut-600">Maks. 5 pesan per 10 menit.</p>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </section>

    <section class="section bg-kabut-50" id="faq">
        <div class="container-lg">
            <x-ui.section-heading eyebrow="FAQ" title="Pertanyaan yang sering diajukan" />
            <div class="mt-10 space-y-10">
                @foreach ($faqs as $category => $items)
                    <div class="grid gap-6 lg:grid-cols-[14rem_1fr]">
                        <h3 class="font-display text-xl font-semibold text-emas-700">{{ Faq::CATEGORIES[$category] ?? $category }}</h3>
                        <x-ui.faq-list :faqs="$items" />
                    </div>
                @endforeach
            </div>
        </div>
    </section>
</x-layouts.public>
