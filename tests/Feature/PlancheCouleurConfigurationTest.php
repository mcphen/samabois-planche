<?php

namespace Tests\Feature;

use App\Models\PlancheCouleur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PlancheCouleurConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_store_update_and_delete_a_planche_couleur(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $storeResponse = $this->actingAs($user)->post('/admin/configuration/planche-couleurs', [
            'code' => 'CHENE-MAT',
            'image' => UploadedFile::fake()->image('chene.jpg'),
        ]);

        $storeResponse->assertCreated();

        $couleur = PlancheCouleur::query()->firstOrFail();

        $this->assertDatabaseHas('planche_couleurs', [
            'id' => $couleur->id,
            'code' => 'CHENE-MAT',
        ]);

        Storage::disk('public')->assertExists($couleur->image_path);

        $oldImagePath = $couleur->image_path;

        $updateResponse = $this->actingAs($user)->post("/admin/configuration/planche-couleurs/{$couleur->id}", [
            'code' => 'CHENE-BRILLANT',
            'image' => UploadedFile::fake()->image('chene-brillant.jpg'),
        ]);

        $updateResponse->assertOk();

        $couleur->refresh();

        $this->assertDatabaseHas('planche_couleurs', [
            'id' => $couleur->id,
            'code' => 'CHENE-BRILLANT',
        ]);

        Storage::disk('public')->assertMissing($oldImagePath);
        Storage::disk('public')->assertExists($couleur->image_path);

        $deleteImagePath = $couleur->image_path;

        $deleteResponse = $this->actingAs($user)->delete("/admin/configuration/planche-couleurs/{$couleur->id}");

        $deleteResponse->assertOk();

        $this->assertDatabaseMissing('planche_couleurs', ['id' => $couleur->id]);
        Storage::disk('public')->assertMissing($deleteImagePath);
    }
}
