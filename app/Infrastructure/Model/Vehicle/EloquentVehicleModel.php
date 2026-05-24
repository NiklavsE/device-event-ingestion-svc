<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Infrastructure\Model\Vehicle;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $external_id
 * @property string|null $label
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class EloquentVehicleModel extends Model
{
    protected $table = 'vehicles';

    protected $primaryKey = 'external_id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['external_id', 'label'];
}
