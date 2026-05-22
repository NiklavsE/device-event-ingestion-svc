<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Infrastructure\Model\Event;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $event_id
 * @property int|null $channel
 * @property string $file_name
 * @property int|null $duration_seconds
 * @property string|null $codec
 * @property string|null $media_type
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class EloquentDeviceEventMediaModel extends Model
{
    protected $table = 'event_media';

    protected $fillable = [
        'event_id',
        'channel',
        'file_name',
        'duration_seconds',
        'codec',
        'media_type',
    ];

    protected $casts = [
        'channel'          => 'integer',
        'duration_seconds' => 'integer',
    ];

    /** @return BelongsTo<EloquentDeviceEventModel, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(EloquentDeviceEventModel::class, 'event_id');
    }
}
