{{-- Label status booking dengan warna yang sama seperti halaman status booking publik. --}}
@props(['status'])

@use('App\Enums\BookingStatus')

@php
    $variant = match ($status) {
        BookingStatus::Pending => 'peringatan',
        BookingStatus::DpPaid => 'info',
        BookingStatus::FullyPaid, BookingStatus::CheckedIn => 'sukses',
        default => 'bahaya',
    };
@endphp

<x-ui.badge :variant="$variant" {{ $attributes }}>{{ $status->getLabel() }}</x-ui.badge>
