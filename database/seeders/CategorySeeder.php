<?php

namespace Database\Seeders;

use App\Domain\Cafe\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['Cocok nugas & WFC', 'ComputerDesktopIcon'], ['Wifi kencang', 'WifiIcon'], ['Banyak colokan', 'BoltIcon'], ['Buka 24 jam', 'ClockIcon'],
            ['Ramah kantong', 'BanknotesIcon'], ['Aesthetic', 'SparklesIcon'], ['Tenang', 'MoonIcon'], ['Rame/nongkrong', 'UserGroupIcon'],
            ['Hidden gem / baru buka', 'MapPinIcon'], ['Outdoor/smoking area', 'SunIcon'], ['Ramah keluarga', 'HomeIcon'], ['Musala & parkir gampang', 'BuildingStorefrontIcon'],
        ] as $order => [$name, $icon]) {
            Category::query()->updateOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'icon' => $icon, 'sort_order' => $order + 1]);
        }
    }
}
