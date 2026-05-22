<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Infrastructure\Model\Event;

use DeviceEventIngestionService\Infrastructure\Model\Device\EloquentDeviceModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $device_id
 * @property string $vehicle_external_id
 * @property string $protocol
 * @property string $event_type
 * @property Carbon $event_timestamp
 * @property float $latitude
 * @property float $longitude
 * @property float|null $speed
 * @property int|null $heading
 * @property string $dedup_hash
 * @property array<string, mixed> $raw_payload
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read EloquentDeviceModel|null $device
 * @property-read EloquentDeviceEventMediaModel|null $media
 */
class EloquentEventModel extends Model
{
    protected $table = 'device_events';

    protected $fillable = [
        'device_id',
        'vehicle_external_id',
        'protocol',
        'event_type',
        'event_timestamp',
        'latitude',
        'longitude',
        'speed',
        'heading',
        'dedup_hash',
        'raw_payload',
    ];

    protected $casts = [
        'event_timestamp' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
        'speed' => 'float',
        'heading' => 'integer',
        'raw_payload' => 'array',
    ];

    /** @return BelongsTo<EloquentDeviceModel, $this> */
    public function device(): BelongsTo
    {
        return $this->belongsTo(EloquentDeviceModel::class, 'device_id');
    }

    /** @return HasOne<EloquentDeviceEventMediaModel, $this> */
    public function media(): HasOne
    {
        return $this->hasOne(EloquentDeviceEventMediaModel::class, 'event_id');
    }
}
