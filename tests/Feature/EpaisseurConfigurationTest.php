<?php

namespace Tests\Feature;

use App\Models\Epaisseur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EpaisseurConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_store_multiple_epaisseurs_in_one_request(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/admin/configuration/epaisseurs', [
            'rows' => [
                ['intitule' => 'Epaisseur 18 mm', 'slug' => ''],
                ['intitule' => 'Panneau décor chêne', 'slug' => ''],
            ],
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('epaisseurs', [
            'intitule' => 'Epaisseur 18 mm',
            'slug' => 'epaisseur-18-mm',
        ]);

        $this->assertDatabaseHas('epaisseurs', [
            'intitule' => 'Panneau décor chêne',
            'slug' => 'panneau-decor-chene',
        ]);
    }

    public function test_user_can_update_and_delete_an_epaisseur(): void
    {
        $user = User::factory()->create();
        $epaisseur = Epaisseur::query()->create([
            'intitule' => 'Epaisseur 10 mm',
            'slug' => 'epaisseur-10-mm',
        ]);

        $updateResponse = $this->actingAs($user)->putJson("/admin/configuration/epaisseurs/{$epaisseur->id}", [
            'intitule' => 'Epaisseur 12 mm',
            'slug' => '',
        ]);

        $updateResponse->assertOk();

        $this->assertDatabaseHas('epaisseurs', [
            'id' => $epaisseur->id,
            'intitule' => 'Epaisseur 12 mm',
            'slug' => 'epaisseur-12-mm',
        ]);

        $deleteResponse = $this->actingAs($user)->deleteJson("/admin/configuration/epaisseurs/{$epaisseur->id}");

        $deleteResponse->assertOk();
        $this->assertDatabaseMissing('epaisseurs', ['id' => $epaisseur->id]);
    }
}
