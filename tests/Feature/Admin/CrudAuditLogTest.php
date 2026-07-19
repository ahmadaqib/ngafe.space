<?php

namespace Tests\Feature\Admin;

use App\Domain\Cafe\Models\Cafe;
use App\Domain\Cafe\Models\Category;
use App\Domain\Identity\Models\User;
use App\Filament\Resources\Cafes\Pages\CreateCafe;
use App\Filament\Resources\Cafes\Pages\EditCafe;
use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CrudAuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_editing_and_deleting_a_cafe_from_filament_is_audited(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        Livewire::test(CreateCafe::class)
            ->fillForm([
                'name' => 'Kopi Ruang Uji',
                'slug' => 'kopi-ruang-uji',
                'city' => 'makassar',
                'area' => 'tamalanrea',
                'lat' => -5.14,
                'lng' => 119.43,
                'status' => 'active',
                'opening_hours_override' => [],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $cafe = Cafe::query()->where('slug', 'kopi-ruang-uji')->firstOrFail();
        $this->assertDatabaseHas('moderation_audit_logs', ['admin_id' => $admin->id, 'action' => 'admin.create', 'subject_id' => $cafe->id]);

        Livewire::test(EditCafe::class, ['record' => $cafe->getKey()])
            ->fillForm(['name' => 'Kopi Ruang Uji Diperbarui'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('moderation_audit_logs', ['admin_id' => $admin->id, 'action' => 'admin.update', 'subject_id' => $cafe->id]);

        Livewire::test(EditCafe::class, ['record' => $cafe->getKey()])
            ->callAction('delete');

        $this->assertDatabaseHas('moderation_audit_logs', ['admin_id' => $admin->id, 'action' => 'admin.delete', 'subject_id' => $cafe->id]);
        $this->assertDatabaseMissing('cafes', ['id' => $cafe->id]);
    }

    public function test_creating_and_deleting_a_category_from_filament_is_audited(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        Livewire::test(CreateCategory::class)
            ->fillForm(['name' => 'Kategori Uji', 'slug' => 'kategori-uji', 'icon' => 'lucide-coffee', 'sort_order' => 1])
            ->call('create')
            ->assertHasNoFormErrors();

        $category = Category::query()->where('slug', 'kategori-uji')->firstOrFail();
        $this->assertDatabaseHas('moderation_audit_logs', ['admin_id' => $admin->id, 'action' => 'admin.create', 'subject_id' => $category->id]);

        Livewire::test(EditCategory::class, ['record' => $category->getKey()])
            ->callAction('delete');

        $this->assertDatabaseHas('moderation_audit_logs', ['admin_id' => $admin->id, 'action' => 'admin.delete', 'subject_id' => $category->id]);
    }
}
