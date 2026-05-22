<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Ui\Http\Requests;

use DeviceEventIngestionService\Domain\DeviceEvent\Factory\IncomingEventFactoryResolver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IngestDeviceEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $supported = app(IncomingEventFactoryResolver::class)->supported();

        return [
            'protocol' => ['required', 'string', Rule::in($supported)],
        ];
    }

    public function protocol(): string
    {
        return (string) $this->input('protocol');
    }
}
