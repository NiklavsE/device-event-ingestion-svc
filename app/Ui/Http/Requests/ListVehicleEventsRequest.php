<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Ui\Http\Requests;

use DeviceEventIngestionService\Application\Services\ListVehicleEvents\ListVehicleEventsRequest as ListVehicleEventsQuery;
use DateTimeImmutable;
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
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'has_media' => ['nullable', 'boolean'],
            'limit' => ['nullable', 'integer', 'min:1', "max:{$maxLimit}"],
        ];
    }

    public function toQuery(string $vehicleId): ListVehicleEventsQuery
    {
        $defaultLimit = (int) config('ingestion.query.default_limit', 100);
        $maxLimit = (int) config('ingestion.query.max_limit', 500);

        return new ListVehicleEventsQuery(
            $vehicleId,
            $this->filled('event_type') ? $this->string('event_type')->toString() : null,
            $this->parseFrom($this->input('from')),
            $this->parseTo($this->input('to')),
            $this->has('has_media') ? $this->boolean('has_media') : null,
            min((int) ($this->input('limit', $defaultLimit)), $maxLimit),
        );
    }

    private function parseFrom(mixed $value): ?DateTimeImmutable
    {
        if (false === is_string($value) || $value === '') {
            return null;
        }

        return new DateTimeImmutable($value);
    }

    /**
     * If `to` is a bare date (no time component), bump it to end-of-day so
     * the filter is inclusive of that day. `to=2026-05-31` should match
     * events at `2026-05-31T23:59:59Z`, not just the stroke of midnight.
     */
    private function parseTo(mixed $value): ?DateTimeImmutable
    {
        if (false === is_string($value) || $value === '') {
            return null;
        }

        $hasTimeComponent = preg_match('/[Tt]|\s\d{1,2}:/', $value) === 1;
        $dt = new DateTimeImmutable($value);

        return $hasTimeComponent ? $dt : $dt->setTime(23, 59, 59);
    }
}
