<?php
namespace App\Seeders;

use App\Models\Lead;
use App\Support\LeadIdentity;
use Illuminate\Database\Seeder;

final class DemoUnassignedContactsSeeder extends Seeder
{
    public function run(): void
    {
        // Natural-looking sample inquiries; no real customer contact details.
        $inquiries = [
            ['Arjun Mehta', 'Northstar Steel Industries', 'Ludhiana', 'IndiaMART'],
            ['Simran Kaur', 'Punjab Alloy Traders', 'Mandi Gobindgarh', 'Justdial'],
            ['Rohit Sharma', 'Everest Rolling Mills', 'Khanna', 'TradeIndia'],
            ['Neha Bansal', 'Silverline Metal Works', 'Jalandhar', 'Website'],
            ['Karan Sethi', 'Prime Iron Distributors', 'Amritsar', 'IndiaMART'],
            ['Priya Verma', 'Metro Steel Processing', 'Chandigarh', 'Justdial'],
            ['Manpreet Singh', 'Crown Foundry Solutions', 'Patiala', 'TradeIndia'],
            ['Aditi Gupta', 'Sunrise Scrap Suppliers', 'Bathinda', 'Website'],
            ['Vikram Arora', 'Apex TMT Products', 'Mohali', 'IndiaMART'],
            ['Kavya Malhotra', 'Trident Pipe & Tube Co.', 'Fatehgarh Sahib', 'Justdial'],
            ['Harshdeep Gill', 'Blue Ridge Steel Depot', 'Ludhiana', 'TradeIndia'],
            ['Isha Kapoor', 'Orion Industrial Metals', 'Jalandhar', 'Website'],
            ['Nitin Aggarwal', 'Shakti Billet Manufacturing', 'Mandi Gobindgarh', 'IndiaMART'],
            ['Gurleen Sandhu', 'Pioneer Castings & Alloys', 'Khanna', 'Justdial'],
            ['Deepak Jain', 'Reliable Ferro Traders', 'Amritsar', 'TradeIndia'],
            ['Mehak Chawla', 'Meridian Steel Logistics', 'Chandigarh', 'Website'],
            ['Sahil Khanna', 'Capital Iron Foundries', 'Patiala', 'IndiaMART'],
            ['Ritika Sood', 'Westfield Metal Supplies', 'Bathinda', 'Justdial'],
            ['Jaspreet Dhillon', 'Nova Structural Steel', 'Mohali', 'TradeIndia'],
            ['Ananya Kohli', 'Crescent Alloy House', 'Ludhiana', 'Website'],
            ['Varun Mittal', 'Sterling Rebar Industries', 'Mandi Gobindgarh', 'IndiaMART'],
            ['Navjot Brar', 'Summit Fabrication Works', 'Fatehgarh Sahib', 'Justdial'],
            ['Riya Oberoi', 'Heritage Steel Components', 'Jalandhar', 'TradeIndia'],
            ['Mohit Singla', 'Titan Scrap & Metals', 'Khanna', 'Website'],
            ['Pooja Narang', 'Galaxy Finished Goods Depot', 'Amritsar', 'IndiaMART'],
            ['Abhishek Bedi', 'Atlas Rolling Products', 'Chandigarh', 'Justdial'],
            ['Sneha Arora', 'Copperleaf Industrial Steels', 'Patiala', 'TradeIndia'],
            ['Rajveer Grewal', 'Fortune Melting Units', 'Bathinda', 'Website'],
            ['Tanvi Mahajan', 'Oakridge Foundry Supplies', 'Mohali', 'IndiaMART'],
            ['Yuvraj Saini', 'Eastern Steel Merchants', 'Ludhiana', 'Justdial'],
        ];

        foreach ($inquiries as $index => [$contact, $company, $location, $source]) {
            $name = $contact;
            $previousName = sprintf('Demo Lead %02d - %s', $index + 1, $contact);
            [$phoneKey, $profileKey] = LeadIdentity::keys($name, $company, null, $location);
            $existing = Lead::where('name', $previousName)->first();
            if ($existing) {
                $existing->update(['name' => $name, 'identity_phone' => $phoneKey,
                    'identity_profile' => $profileKey, 'notes' => null]);
                continue;
            }
            Lead::firstOrCreate(['name' => $name], [
                'company' => $company,
                'location' => $location,
                'source' => $source,
                'identity_phone' => $phoneKey,
                'identity_profile' => $profileKey,
                'owner_id' => null,
                'created_by' => null,
                'contact_saved_at' => null,
                'stage' => 'New',
                'priority' => 'Normal',
                'notes' => null,
            ]);
        }
    }
}
