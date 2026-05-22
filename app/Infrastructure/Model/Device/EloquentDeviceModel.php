<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Infrastructure\Model\Device;

use DeviceEventIngestionService\Infrastructure\Model\Event\EloquentEventModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $imei
 * @property string $vehicle_external_id
 * @property string|null $firmware
 * @property Carbon|null $last_seen_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class EloquentDeviceModel extends Model
{
    protected $table = 'devices';

    protected $fillable = [
        'imei',
        'vehicle_external_id',
        'firmware',
        'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    /** @return HasMany<EloquentEventModel, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(EloquentEventModel::class, 'device_id');
    }
}
