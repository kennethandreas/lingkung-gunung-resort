<x-filament::icon :icon="$item['icon']" class="mt-0.5 size-5 shrink-0" />
<span class="min-w-0 flex-1">
    <span class="flex items-baseline justify-between gap-2">
        <span class="font-semibold">{{ $item['label'] }}</span>
        <span class="text-2xl font-bold tabular-nums">{{ $item['count'] }}</span>
    </span>
    <span class="mt-0.5 block text-xs opacity-80">{{ $item['hint'] }}</span>
</span>
