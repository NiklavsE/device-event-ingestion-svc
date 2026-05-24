<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Ui\Console\Commands;

use DeviceEventIngestionService\Infrastructure\Model\Device\EloquentDeviceModel;
use DeviceEventIngestionService\Infrastructure\Model\Vehicle\EloquentVehicleModel;
use Illuminate\Console\Command;

class SeedDemoFleetCommand extends Command
{
    private const array FLEET = [
        ['vehicle_external_id' => 'LV-1234', 'label' => 'Demo Truck #1', 'imei' => '863725041234567'],
        ['vehicle_external_id' => 'LV-9999', 'label' => 'Demo Truck #2', 'imei' => '863725041234568'],
    ];

    protected $signature = 'ingestion:seed-fleet';

    protected $description = 'Register the demo vehicles and devices so the example curl payloads can be ingested. '
        . 'Safe to re-run — entries are upserted.';

    public function handle(): int
    {
        $this->info('Registering demo fleet...');

        foreach (self::FLEET as $entry) {
            EloquentVehicleModel::query()->updateOrCreate(
                ['external_id' => $entry['vehicle_external_id']],
                ['label'       => $entry['label']],
            );

            EloquentDeviceModel::query()->updateOrCreate(
                ['imei'                => $entry['imei']],
                ['vehicle_external_id' => $entry['vehicle_external_id']],
            );

            $this->line(sprintf(
                '  • device %s installed on vehicle %s (%s)',
                $entry['imei'],
                $entry['vehicle_external_id'],
                $entry['label'],
            ));
        }

        $this->newLine();
        $this->info(sprintf(
            'Registered %d device(s). Try: ./examples/curl-examples.sh',
            count(self::FLEET),
        ));

        return self::SUCCESS;
    }
}
