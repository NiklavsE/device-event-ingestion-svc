<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Ui\Http\Resources;

use DeviceEventIngestionService\Domain\DeviceEvent\DeviceEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read DeviceEvent $resource
 */
class DeviceEventResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $event = $this->resource;

        return [
            'id'              => $event->id,
            'protocol'        => $event->protocol,
            'device_imei'     => $event->deviceImei->value(),
            'vehicle_id'      => $event->vehicleId->value(),
            'event_type'      => $event->eventType->value(),
            'event_timestamp' => $event->eventTimestamp->toIso8601(),
            'latitude'        => $event->location->latitude(),
            'longitude'       => $event->location->longitude(),
            'speed'           => $event->location->speed(),
            'heading'         => $event->location->heading(),
            'media'           => $event->hasMedia() ? $event->media->toArray() : null,
        ];
    }
}
