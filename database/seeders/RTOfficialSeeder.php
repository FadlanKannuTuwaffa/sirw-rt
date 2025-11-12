<?php

namespace Database\Seeders;

use App\Models\RTOfficial;
use Illuminate\Database\Seeder;

class RTOfficialSeeder extends Seeder
{
    public function run(): void
    {
        $officials = [
            ['name' => 'Budi Santoso', 'position' => 'ketua', 'phone' => '081234567890', 'email' => 'ketua@rt.local', 'order' => 1],
            ['name' => 'Siti Aminah', 'position' => 'sekretaris', 'phone' => '081234567891', 'email' => 'sekretaris@rt.local', 'order' => 2],
            ['name' => 'Ahmad Dahlan', 'position' => 'bendahara', 'phone' => '081234567892', 'email' => 'bendahara@rt.local', 'order' => 3],
        ];

        foreach ($officials as $official) {
            RTOfficial::updateOrCreate(
                ['position' => $official['position']],
                $official
            );
        }
    }
}
