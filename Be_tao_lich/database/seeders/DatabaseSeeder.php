<?php

namespace Database\Seeders;

use App\Models\User;
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
        // User::factory(10)->create();
        $this->call([
            RoleTagSeeder::class,
            CaMauSeeder::class,
            TaiKhoanSeeder::class,
            NhanVienSeeder::class,
            LichLamViecSeeder::class,
            NhuCauCaSeeder::class,
            TuanDangKySeeder::class,
            DangKyCaKhaDungSeeder::class,
            PhanCongCaSeeder::class,
            BangLuongSeeder::class,
            ChamCongSeeder::class,
            CaTrongSeeder::class,
            DangKyCaTrongSeeder::class,
            YeuCauNghiSeeder::class,
            YeuCauDoiCaSeeder::class,
        ]);

        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
            ]
        );
    }
}
