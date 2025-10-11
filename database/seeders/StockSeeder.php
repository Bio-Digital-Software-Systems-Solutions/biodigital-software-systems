<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Stock;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class StockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = Category::all();
        if ($categories->isEmpty()) {
            $this->call(CategorySeeder::class);
            $categories = Category::all();
        }

        // Create additional categories specific to stock management
        $stockCategories = [
            'Office Supplies',
            'Computer Hardware',
            'Network Equipment',
            'Furniture',
            'Cleaning Supplies',
            'Safety Equipment',
            'Maintenance Tools',
        ];

        foreach ($stockCategories as $categoryName) {
            Category::firstOrCreate(['name' => $categoryName]);
        }

        $categories = Category::all();

        $stocks = [
            // Office Supplies
            [
                'name' => 'A4 Copy Paper',
                'sku' => 'OFF-001',
                'description' => '80gsm white A4 paper for printing and copying. 500 sheets per ream.',
                'quantity' => 250,
                'minimum_quantity' => 50,
                'unit_price' => 5.99,
                'supplier' => 'Office Depot Solutions',
                'supplier_contact' => 'orders@officedepot.com',
                'expiry_date' => null,
                'location' => 'Storage Room A - Shelf 1',
                'is_active' => true,
                'category' => 'Office Supplies',
            ],
            [
                'name' => 'Ballpoint Pens (Blue)',
                'sku' => 'OFF-002',
                'description' => 'Blue ballpoint pens, medium point. Pack of 12.',
                'quantity' => 45,
                'minimum_quantity' => 20,
                'unit_price' => 8.50,
                'supplier' => 'Stationery Plus',
                'supplier_contact' => 'supply@stationeryplus.com',
                'expiry_date' => null,
                'location' => 'Storage Room A - Shelf 2',
                'is_active' => true,
                'category' => 'Office Supplies',
            ],
            [
                'name' => 'Sticky Notes (Yellow)',
                'sku' => 'OFF-003',
                'description' => '3x3 inch yellow sticky notes. Pack of 12 pads.',
                'quantity' => 15,
                'minimum_quantity' => 25,
                'unit_price' => 12.99,
                'supplier' => 'Office Depot Solutions',
                'supplier_contact' => 'orders@officedepot.com',
                'expiry_date' => null,
                'location' => 'Storage Room A - Shelf 2',
                'is_active' => true,
                'category' => 'Office Supplies',
            ],

            // Computer Hardware
            [
                'name' => 'USB-C Cables (2m)',
                'sku' => 'HW-001',
                'description' => 'High-speed USB-C to USB-C cables, 2 meters length. Supports data transfer and charging.',
                'quantity' => 30,
                'minimum_quantity' => 10,
                'unit_price' => 15.99,
                'supplier' => 'TechSupply Pro',
                'supplier_contact' => 'orders@techsupplypro.com',
                'expiry_date' => null,
                'location' => 'IT Storage - Drawer 1',
                'is_active' => true,
                'category' => 'Computer Hardware',
            ],
            [
                'name' => 'Wireless Mouse',
                'sku' => 'HW-002',
                'description' => 'Ergonomic wireless optical mouse with USB receiver. 1600 DPI.',
                'quantity' => 20,
                'minimum_quantity' => 15,
                'unit_price' => 25.99,
                'supplier' => 'TechSupply Pro',
                'supplier_contact' => 'orders@techsupplypro.com',
                'expiry_date' => null,
                'location' => 'IT Storage - Drawer 2',
                'is_active' => true,
                'category' => 'Computer Hardware',
            ],
            [
                'name' => 'External Hard Drive 2TB',
                'sku' => 'HW-003',
                'description' => 'USB 3.0 external hard drive, 2TB capacity. For backup and storage.',
                'quantity' => 8,
                'minimum_quantity' => 5,
                'unit_price' => 89.99,
                'supplier' => 'DataStorage Corp',
                'supplier_contact' => 'sales@datastoragecorp.com',
                'expiry_date' => null,
                'location' => 'IT Storage - Cabinet A',
                'is_active' => true,
                'category' => 'Computer Hardware',
            ],

            // Network Equipment
            [
                'name' => 'Ethernet Cable Cat6 (5m)',
                'sku' => 'NET-001',
                'description' => 'Category 6 Ethernet cable, 5 meters. Blue color, RJ45 connectors.',
                'quantity' => 50,
                'minimum_quantity' => 20,
                'unit_price' => 12.50,
                'supplier' => 'Network Solutions Ltd',
                'supplier_contact' => 'orders@networksolutions.com',
                'expiry_date' => null,
                'location' => 'Network Closet - Rack 1',
                'is_active' => true,
                'category' => 'Network Equipment',
            ],
            [
                'name' => '24-Port Gigabit Switch',
                'sku' => 'NET-002',
                'description' => 'Unmanaged 24-port Gigabit Ethernet switch. Metal housing.',
                'quantity' => 3,
                'minimum_quantity' => 2,
                'unit_price' => 199.99,
                'supplier' => 'Network Solutions Ltd',
                'supplier_contact' => 'orders@networksolutions.com',
                'expiry_date' => null,
                'location' => 'Network Closet - Cabinet B',
                'is_active' => true,
                'category' => 'Network Equipment',
            ],

            // Cleaning Supplies
            [
                'name' => 'Disinfectant Wipes',
                'sku' => 'CLN-001',
                'description' => 'Antibacterial disinfectant wipes. Pack of 100 wipes.',
                'quantity' => 25,
                'minimum_quantity' => 15,
                'unit_price' => 8.99,
                'supplier' => 'CleanPro Supplies',
                'supplier_contact' => 'orders@cleanprosupplies.com',
                'expiry_date' => Carbon::now()->addMonths(18),
                'location' => 'Cleaning Closet - Shelf A',
                'is_active' => true,
                'category' => 'Cleaning Supplies',
            ],
            [
                'name' => 'Paper Towels',
                'sku' => 'CLN-002',
                'description' => 'Absorbent paper towels. 12 rolls per pack.',
                'quantity' => 8,
                'minimum_quantity' => 12,
                'unit_price' => 18.99,
                'supplier' => 'CleanPro Supplies',
                'supplier_contact' => 'orders@cleanprosupplies.com',
                'expiry_date' => null,
                'location' => 'Cleaning Closet - Shelf B',
                'is_active' => true,
                'category' => 'Cleaning Supplies',
            ],
            [
                'name' => 'Hand Sanitizer (500ml)',
                'sku' => 'CLN-003',
                'description' => '70% alcohol hand sanitizer with pump dispenser.',
                'quantity' => 5,
                'minimum_quantity' => 10,
                'unit_price' => 6.99,
                'supplier' => 'HealthCare Supplies Inc',
                'supplier_contact' => 'sales@healthcaresupplies.com',
                'expiry_date' => Carbon::now()->addDays(45),
                'location' => 'Reception Desk Storage',
                'is_active' => true,
                'category' => 'Cleaning Supplies',
            ],

            // Safety Equipment
            [
                'name' => 'First Aid Kit',
                'sku' => 'SAF-001',
                'description' => 'Complete first aid kit with bandages, antiseptic, and emergency supplies.',
                'quantity' => 6,
                'minimum_quantity' => 4,
                'unit_price' => 45.99,
                'supplier' => 'Safety First Corp',
                'supplier_contact' => 'orders@safetyfirst.com',
                'expiry_date' => Carbon::now()->addYears(2),
                'location' => 'Emergency Cabinet - Floor 1',
                'is_active' => true,
                'category' => 'Safety Equipment',
            ],
            [
                'name' => 'Fire Extinguisher (2kg)',
                'sku' => 'SAF-002',
                'description' => '2kg dry powder fire extinguisher. ABC class rating.',
                'quantity' => 2,
                'minimum_quantity' => 3,
                'unit_price' => 89.99,
                'supplier' => 'Fire Safety Solutions',
                'supplier_contact' => 'info@firesafetysolutions.com',
                'expiry_date' => Carbon::now()->addYears(5),
                'location' => 'Emergency Cabinet - Floor 2',
                'is_active' => true,
                'category' => 'Safety Equipment',
            ],

            // Furniture
            [
                'name' => 'Office Chair (Ergonomic)',
                'sku' => 'FUR-001',
                'description' => 'Ergonomic office chair with lumbar support and adjustable height.',
                'quantity' => 4,
                'minimum_quantity' => 2,
                'unit_price' => 299.99,
                'supplier' => 'Office Furniture Direct',
                'supplier_contact' => 'sales@officefurnituredirect.com',
                'expiry_date' => null,
                'location' => 'Furniture Storage - Area C',
                'is_active' => true,
                'category' => 'Furniture',
            ],
            [
                'name' => 'Desk Lamp (LED)',
                'sku' => 'FUR-002',
                'description' => 'Adjustable LED desk lamp with touch controls and USB charging port.',
                'quantity' => 12,
                'minimum_quantity' => 8,
                'unit_price' => 49.99,
                'supplier' => 'Lighting Solutions Ltd',
                'supplier_contact' => 'orders@lightingsolutions.com',
                'expiry_date' => null,
                'location' => 'Furniture Storage - Area A',
                'is_active' => true,
                'category' => 'Furniture',
            ],
        ];

        foreach ($stocks as $stockData) {
            $category = $categories->where('name', $stockData['category'])->first();
            unset($stockData['category']);

            $stockData['category_id'] = $category->id;

            Stock::create($stockData);
        }
    }
}
