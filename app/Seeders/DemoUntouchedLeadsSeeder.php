<?php
namespace App\Seeders;

use App\Models\Lead;
use Illuminate\Database\Seeder;

final class DemoUntouchedLeadsSeeder extends Seeder
{
    public function run():void
    {
        $inquiries=[
            ['Demo Inquiry 01 - Aman Steel Buyer','Aman Steel Works','Ludhiana','IndiaMART'],
            ['Demo Inquiry 02 - Bharat Metal Buyer','Bharat Metal Traders','Jalandhar','Justdial'],
            ['Demo Inquiry 03 - Chetan Fabrication','Chetan Fabricators','Mohali','TradeIndia'],
            ['Demo Inquiry 04 - Deepak Iron Buyer','Deepak Iron House','Patiala','Website'],
            ['Demo Inquiry 05 - Ekta Foundry Buyer','Ekta Foundries','Mandi Gobindgarh','IndiaMART'],
            ['Demo Inquiry 06 - Farhan Scrap Buyer','Farhan Scrap Traders','Amritsar','Justdial'],
            ['Demo Inquiry 07 - Gaurav Rolling Mill','Gaurav Rolling Works','Khanna','TradeIndia'],
            ['Demo Inquiry 08 - Harpreet Steel Buyer','Harpreet Steel Depot','Bathinda','Website'],
            ['Demo Inquiry 09 - Ishan Pipe Buyer','Ishan Pipe Suppliers','Chandigarh','IndiaMART'],
            ['Demo Inquiry 10 - Jaspreet Alloy Buyer','Jaspreet Alloy House','Fatehgarh Sahib','Justdial'],
        ];
        foreach($inquiries as [$name,$company,$location,$source]){
            Lead::firstOrCreate(['name'=>$name],[
                'company'=>$company,'location'=>$location,'source'=>$source,
                'email'=>null,'phone'=>null,'owner_id'=>null,'created_by'=>null,
                'contact_saved_at'=>null,'stage'=>'New','priority'=>'Normal',
                'notes'=>'Demo inquiry for testing the untouched lead workflow. No real customer contact details.',
            ]);
        }
    }
}
