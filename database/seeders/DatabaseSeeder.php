<?php

namespace Database\Seeders;

use App\Models\InboundWebhook;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $samples = [
            [
                'label' => 'Standard order paid',
                'status' => 'sample',
                'raw_json' => [
                    'event_type' => 'order.paid',
                    'order_id' => 'HD-2048',
                    'customer_email' => 'alex.rivera@example.test',
                    'amount' => 84.50,
                ],
            ],
            [
                'label' => 'Aliased order keys',
                'status' => 'sample',
                'raw_json' => [
                    'event_type' => 'order.shipped',
                    'orderId' => 'HD-2051',
                    'email' => 'jamie.chen@example.test',
                    'carrier' => 'Northstar Parcel',
                ],
            ],
            [
                'label' => 'Missing customer email',
                'status' => 'sample',
                'raw_json' => [
                    'event_type' => 'order.refunded',
                    'order_number' => 'HD-2054',
                    'refund_reason' => 'Duplicate test order',
                ],
            ],
            [
                'label' => 'Unknown vendor field names',
                'status' => 'sample',
                'raw_json' => [
                    'event' => 'order.created',
                    'purchase_reference' => 'HD-2060',
                    'buyer_email' => 'morgan.patel@example.test',
                    'source' => 'demo-checkout',
                ],
            ],
        ];

        foreach ($samples as $sample) {
            InboundWebhook::query()->updateOrCreate(
                ['label' => $sample['label']],
                $sample,
            );
        }
    }
}
