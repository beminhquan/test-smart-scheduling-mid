<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RangBuoc extends Model
{
    protected $table = 'rang_buoc';
    protected $primaryKey = 'id_rang_buoc';

    protected $fillable = [
        'ma_rang_buoc',
        'ten_rang_buoc',
        'loai_rang_buoc',
        'gia_tri',
    ];

    protected $casts = [
        'gia_tri' => 'array',
    ];

    /**
     * Bản đồ ánh xạ giữa Frontend Constraint ID (camelCase/snake_case) và Backend ma_rang_buoc (UPPER_SNAKE)
     */
    public static function getMapping(): array
    {
        return [
            'rest_between_shifts'       => 'MIN_REST_HOURS',
            'no_clopening'               => 'NO_NIGHT_TO_MORNING',
            'max_shifts_per_day'        => 'MAX_SHIFTS_PER_DAY',
            'max_hours_per_day'         => 'MAX_HOURS_PER_DAY',
            'max_hours_per_week'        => 'MAX_HOURS_PER_WEEK',
            'max_consecutive_days'      => 'MAX_CONSECUTIVE_DAYS',
            'respect_approved_leave'     => 'NO_SHIFT_ON_APPROVED_LEAVE',
            'skill_match'               => 'MATCH_ROLE_TAG',
            'fair_hours_distribution'   => 'FAIR_WORKING_HOURS',
            'fair_weekend_distribution' => 'BALANCE_WEEKEND_SHIFTS',
            'preference_matching'       => 'PREFER_STAFF_AVAILABILITY',
            'no_overlap'                => 'NO_OVERLAP',
        ];
    }

    /**
     * Chuyển ma_rang_buoc sang frontend id
     */
    public static function toFrontendId(string $backendCode): string
    {
        $flipped = array_flip(self::getMapping());
        return $flipped[$backendCode] ?? strtolower($backendCode);
    }

    /**
     * Chuyển frontend id sang ma_rang_buoc
     */
    public static function toBackendCode(string $frontendId): string
    {
        $mapping = self::getMapping();
        return $mapping[$frontendId] ?? strtoupper($frontendId);
    }
}
