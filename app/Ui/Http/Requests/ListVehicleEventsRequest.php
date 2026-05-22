<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Ui\Http\Requests;

use DeviceEventIngestionService\Application\Services\ListVehicleEvents\ListVehicleEventsQuery;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Foundation\Http\FormRequest;

class ListVehicleEventsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        $maxLimit = (int) config('ingestion.query.max_limit', 500);

        return [
            'event_type' => ['nullable', 'string', 'max:64'],
            'from'       => ['nullable', 'date_format:Y-m-d'],
            'to'         => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'has_media'  => ['nullable', 'boolean'],
            'limit'      => ['nullable', 'integer', 'min:1', "max:{$maxLimit}"],
        ];
    }

    public function toQuery(string $vehicleId): ListVehicleEventsQuery
    {
        $defaultLimit = (int) config('ingestion.query.default_limit', 100);
        $maxLimit     = (int) config('ingestion.query.max_limit', 500);

        return new ListVehicleEventsQuery(
            $vehicleId,
            $this->filled('event_type') ? $this->string('event_type')->toString() : null,
            $this->parseDate($this->input('from'), endOfDay: false),
            $this->parseDate($this->input('to'), endOfDay: true),
            $this->has('has_media') ? $this->boolean('has_media') : null,
            min((int) ($this->input('limit', $defaultLimit)), $maxLimit),
        );
    }

    /**
     * Parses a Y-m-d query param as a UTC instant — midnight for `from`,
     * end-of-day for `to` so the filter is inclusive of that day. Validated
     * upstream by `date_format:Y-m-d`, so any non-conforming value never
     * reaches this method.
     */
    private function parseDate(mixed $value, bool $endOfDay): ?DateTimeImmutable
    {
        if (false === is_string($value) || $value === '') {
            return null;
        }

        $dt = new DateTimeImmutable($value, new DateTimeZone('UTC'));

        return $endOfDay ? $dt->setTime(23, 59, 59) : $dt;
    }
}
