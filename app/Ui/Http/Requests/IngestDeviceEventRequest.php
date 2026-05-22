<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Ui\Http\Requests;

use DeviceEventIngestionService\Domain\DeviceEvent\Factory\IncomingEventFactoryResolver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IngestDeviceEventRequest extends FormRequest
{
    public function __construct(private readonly IncomingEventFactoryResolver $resolver)
    {
        parent::__construct();
    }

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'protocol' => ['required', 'string', Rule::in($this->resolver->supported())],
        ];
    }

    public function protocol(): string
    {
        return (string) $this->input('protocol');
    }
}
