<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Infrastructure\Model\Vehicle;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $external_id
 * @property string|null $label
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class EloquentVehicleModel extends Model
{
    protected $table = 'vehicles';

    protected $fillable = [
        'external_id',
        'label',
    ];
}
