<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use App\Enums\ReviewTargetType;
use App\Models\Concerns\LogsChanges;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Ulasan tamu. Dibuat, diubah, dan dimoderasi hanya lewat App\Services\ReviewService
 * (kelayakan booking, duplikat, dan perpindahan status diperiksa di sana).
 */
#[Fillable([
    'customer_account_id', 'booking_id', 'target_type', 'unit_type_id', 'activity_id', 'group_package_id',
    'rating', 'comment', 'reviewer_name', 'status', 'submitted_at', 'edited_at', 'published_at',
    'moderated_at', 'moderated_by', 'moderation_note', 'customer_message',
])]
class Review extends Model implements HasMedia
{
    use InteractsWithMedia, LogsChanges;

    /** Ukuran foto ulasan yang dikirim ke browser (WebP hasil konversi; file asli tidak pernah dikirim). */
    public const PHOTO_SIZES = ['sm' => 480, 'md' => 1200];

    protected function casts(): array
    {
        return [
            'target_type' => ReviewTargetType::class,
            'status' => ReviewStatus::class,
            'rating' => 'integer',
            'submitted_at' => 'datetime',
            'edited_at' => 'datetime',
            'published_at' => 'datetime',
            'moderated_at' => 'datetime',
        ];
    }

    public function registerMediaCollections(): void
    {
        // Disk privat: foto ulasan yang belum disetujui tidak boleh bisa dibuka lewat URL publik
        $this->addMediaCollection('photos')->useDisk('ulasan')->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        // Dikodekan ulang ke WebP: metadata EXIF (mis. lokasi GPS dari HP) ikut terbuang
        foreach (self::PHOTO_SIZES as $name => $width) {
            $this->addMediaConversion($name)
                ->fit(Fit::Max, $width, $width)
                ->format('webp')
                ->quality(75)
                ->nonQueued();
        }
    }

    public function customerAccount(): BelongsTo
    {
        return $this->belongsTo(CustomerAccount::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function unitType(): BelongsTo
    {
        return $this->belongsTo(UnitType::class);
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function groupPackage(): BelongsTo
    {
        return $this->belongsTo(GroupPackage::class);
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    /** Hanya ulasan tayang: satu-satunya yang boleh tampil di website dan dihitung di rata-rata. */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', ReviewStatus::Published)->whereNotNull('published_at');
    }

    public function scopeForTarget(Builder $query, ReviewTargetType $type, ?int $id = null): void
    {
        $query->where('target_type', $type)->where('target_ref', $type === ReviewTargetType::Resort ? 0 : (int) $id);
    }

    public function isPublished(): bool
    {
        return $this->status === ReviewStatus::Published && $this->published_at !== null;
    }

    /** Model yang diulas (null untuk resort). */
    public function target(): ?Model
    {
        return match ($this->target_type) {
            ReviewTargetType::Resort => null,
            ReviewTargetType::UnitType => $this->unitType,
            ReviewTargetType::Activity => $this->activity,
            ReviewTargetType::GroupPackage => $this->groupPackage,
        };
    }

    public function targetName(): string
    {
        return match ($this->target_type) {
            ReviewTargetType::Resort => 'Lingkung Gunung Resort',
            ReviewTargetType::UnitType => $this->unitType?->marketing_name ?? 'Akomodasi',
            ReviewTargetType::Activity => $this->activity?->name ?? 'Aktivitas',
            ReviewTargetType::GroupPackage => $this->groupPackage?->name ?? 'Paket rombongan',
        };
    }

    /** Halaman publik tempat ulasan ini tampil bila disetujui. */
    public function publicUrl(): ?string
    {
        $target = $this->target();

        return match ($this->target_type) {
            ReviewTargetType::Resort => route('ulasan.index'),
            ReviewTargetType::UnitType => $target ? route('akomodasi.show', $target) : null,
            default => $target ? route('ulasan.target', [$this->target_type->slug(), $target->slug]) : null,
        };
    }

    /** "Budi Santoso" → "Budi S." (tanpa nama lengkap di halaman publik). */
    public static function displayNameFor(string $name): string
    {
        $parts = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: ['Tamu'];
        $first = mb_substr($parts[0], 0, 30);

        return count($parts) > 1 ? $first.' '.mb_strtoupper(mb_substr(end($parts), 0, 1)).'.' : $first;
    }
}
