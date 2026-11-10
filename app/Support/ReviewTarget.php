<?php

namespace App\Support;

use App\Enums\ReviewTargetType;
use Illuminate\Database\Eloquent\Model;

/** Satu hal yang boleh diulas dari sebuah booking (resort, atau akomodasi/aktivitas/paket yang benar-benar dipesan). */
final readonly class ReviewTarget
{
    public function __construct(public ReviewTargetType $type, public ?Model $model = null) {}

    public static function resort(): self
    {
        return new self(ReviewTargetType::Resort);
    }

    public function id(): int
    {
        return $this->model ? (int) $this->model->getKey() : 0;
    }

    /** Kunci di URL halaman akun: "resort", "akomodasi-5", "aktivitas-3", "paket-2". */
    public function key(): string
    {
        return $this->type === ReviewTargetType::Resort ? 'resort' : $this->type->slug().'-'.$this->id();
    }

    public function name(): string
    {
        return match ($this->type) {
            ReviewTargetType::Resort => 'Pengalaman menginap di Lingkung Gunung',
            ReviewTargetType::UnitType => $this->model->marketing_name,
            default => $this->model->name,
        };
    }

    /** Nilai kolom target untuk tabel reviews. */
    public function columns(): array
    {
        return [
            'target_type' => $this->type,
            'unit_type_id' => $this->type === ReviewTargetType::UnitType ? $this->id() : null,
            'activity_id' => $this->type === ReviewTargetType::Activity ? $this->id() : null,
            'group_package_id' => $this->type === ReviewTargetType::GroupPackage ? $this->id() : null,
        ];
    }

    public function matches(ReviewTargetType $type, int $ref): bool
    {
        return $this->type === $type && $this->id() === $ref;
    }
}
