<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\WorkZone;
use App\Models\ZoneWifiNetwork;
use Illuminate\Database\Seeder;

class WorkZoneSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();

        // Polygon zone - Parking Lot A
        $parkingLot = WorkZone::create([
            'name' => 'Parking Lot A',
            'type' => 'primary',
            'description' => 'Main employee parking area - Zone A',
            'coordinates' => [
                ['lat' => 10.8231, 'lng' => 106.6297],
                ['lat' => 10.8235, 'lng' => 106.6297],
                ['lat' => 10.8235, 'lng' => 106.6303],
                ['lat' => 10.8231, 'lng' => 106.6303],
            ],
            'radius' => null,
            'min_gps_accuracy' => 20,
            'created_by' => $admin->id,
        ]);

        ZoneWifiNetwork::create([
            'zone_id' => $parkingLot->id,
            'bssid' => 'AA:BB:CC:DD:EE:01',
            'ssid' => 'ParkingLotA-WiFi',
            'signal_strength' => -65,
            'is_active' => true,
        ]);

        // Circle zone - Cargo Warehouse
        $warehouse = WorkZone::create([
            'name' => 'Cargo Warehouse',
            'type' => 'primary',
            'description' => 'Main cargo handling warehouse',
            'coordinates' => [
                ['lat' => 10.8245, 'lng' => 106.6315],
            ],
            'radius' => 150,
            'min_gps_accuracy' => 25,
            'created_by' => $admin->id,
        ]);

        ZoneWifiNetwork::create([
            'zone_id' => $warehouse->id,
            'bssid' => 'AA:BB:CC:DD:EE:02',
            'ssid' => 'Warehouse-WiFi',
            'signal_strength' => -70,
            'is_active' => true,
        ]);

        ZoneWifiNetwork::create([
            'zone_id' => $warehouse->id,
            'bssid' => 'AA:BB:CC:DD:EE:03',
            'ssid' => 'Warehouse-WiFi-2',
            'signal_strength' => -72,
            'is_active' => true,
        ]);

        // Polygon zone - Terminal T1
        $terminal = WorkZone::create([
            'name' => 'Terminal T1',
            'type' => 'primary',
            'description' => 'Passenger terminal T1 operations area',
            'coordinates' => [
                ['lat' => 10.8210, 'lng' => 106.6280],
                ['lat' => 10.8218, 'lng' => 106.6280],
                ['lat' => 10.8218, 'lng' => 106.6292],
                ['lat' => 10.8210, 'lng' => 106.6292],
            ],
            'radius' => null,
            'min_gps_accuracy' => 15,
            'created_by' => $admin->id,
        ]);

        ZoneWifiNetwork::create([
            'zone_id' => $terminal->id,
            'bssid' => 'AA:BB:CC:DD:EE:04',
            'ssid' => 'Terminal-T1-Staff',
            'signal_strength' => -60,
            'is_active' => true,
        ]);

        // Circle zone - Break Area
        WorkZone::create([
            'name' => 'Break Area',
            'type' => 'break',
            'description' => 'Employee rest and break area',
            'coordinates' => [
                ['lat' => 10.8225, 'lng' => 106.6308],
            ],
            'radius' => 100,
            'min_gps_accuracy' => 30,
            'created_by' => $admin->id,
        ]);
    }
}
