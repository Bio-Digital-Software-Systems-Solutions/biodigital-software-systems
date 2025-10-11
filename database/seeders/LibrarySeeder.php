<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Library;
use Illuminate\Database\Seeder;

class LibrarySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $libraries = [
            [
                'name' => 'Central Library',
                'code' => 'CL-001',
                'description' => 'Main library facility with comprehensive collection of technical and business books.',
                'contact_person' => 'Marie Dubois',
                'contact_email' => 'marie.dubois@centrallibrary.com',
                'contact_phone' => '+33 1 23 45 67 89',
                'address' => [
                    'street' => '123 Knowledge Avenue',
                    'city' => 'Tech City',
                    'postal_code' => '12345',
                    'country' => 'France',
                ],
            ],
            [
                'name' => 'Digital Resources Center',
                'code' => 'DRC-001',
                'description' => 'Specialized library focusing on digital resources, e-books, and online materials.',
                'contact_person' => 'Jean Martin',
                'contact_email' => 'jean.martin@digitalresources.com',
                'contact_phone' => '+33 1 34 56 78 90',
                'address' => [
                    'street' => '456 Innovation Boulevard',
                    'city' => 'Silicon Valley',
                    'postal_code' => '67890',
                    'country' => 'France',
                ],
            ],
            [
                'name' => 'Research Library',
                'code' => 'RL-001',
                'description' => 'Academic library supporting research activities and scholarly publications.',
                'contact_person' => 'Sophie Laurent',
                'contact_email' => 'sophie.laurent@researchlibrary.com',
                'contact_phone' => '+33 1 45 67 89 01',
                'address' => [
                    'street' => '789 Research Park',
                    'city' => 'University District',
                    'postal_code' => '54321',
                    'country' => 'France',
                ],
            ],
        ];

        foreach ($libraries as $libraryData) {
            $addressData = $libraryData['address'];
            unset($libraryData['address']);

            $address = Address::create($addressData);

            $libraryData['address_id'] = $address->id;
            $libraryData['is_active'] = true;

            Library::create($libraryData);
        }
    }
}
