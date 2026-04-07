<?php

namespace Tests\Feature;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_store_update_and_delete_suppliers_from_configuration(): void
    {
        $user = User::factory()->create();

        $storeResponse = $this->actingAs($user)->postJson('/admin/configuration/suppliers', [
            'rows' => [
                ['name' => 'Bois du Sahel'],
                ['name' => 'Scierie Moderne'],
            ],
        ]);

        $storeResponse->assertCreated();

        $supplier = Supplier::query()->where('name', 'Bois du Sahel')->firstOrFail();

        $this->assertDatabaseHas('suppliers', [
            'name' => 'Bois du Sahel',
            'slug_name' => 'bois-du-sahel',
        ]);

        $updateResponse = $this->actingAs($user)->putJson("/admin/configuration/suppliers/{$supplier->id}", [
            'name' => 'Bois du Sahel Premium',
            'address' => 'Zone industrielle',
            'phone' => '770000000',
            'email' => 'contact@sahel.test',
        ]);

        $updateResponse->assertOk();

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'name' => 'Bois du Sahel Premium',
            'slug_name' => 'bois-du-sahel-premium',
            'address' => 'Zone industrielle',
            'phone' => '770000000',
            'email' => 'contact@sahel.test',
        ]);

        $deleteResponse = $this->actingAs($user)->deleteJson("/admin/configuration/suppliers/{$supplier->id}");

        $deleteResponse->assertOk();
        $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
    }
}
