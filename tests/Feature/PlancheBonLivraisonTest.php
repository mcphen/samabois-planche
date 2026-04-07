<?php

namespace Tests\Feature;

use App\Models\Contrat;
use App\Models\Planche;
use App\Models\PlancheBonLivraison;
use App\Models\PlancheCouleur;
use App\Models\PlancheDetail;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlancheBonLivraisonTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_store_a_planche_bon_livraison(): void
    {
        $user = User::factory()->create();
        [$detail, $client] = $this->createPlancheDetail('Contrat-A', 'Rouge', 12.5, 20);

        $response = $this->actingAs($user)->postJson('/admin/planche-bons-livraison/store', [
            'client_id' => $client->id,
            'numero_bl' => 'BL-001',
            'date_livraison' => '2026-03-02',
            'statut' => 'brouillon',
            'lignes' => [
                [
                    'planche_detail_id' => $detail->id,
                    'quantite_livree' => 6,
                ],
            ],
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('planche_bons_livraison', [
            'client_id' => $client->id,
            'numero_bl' => 'BL-001',
            'date_livraison' => '2026-03-02',
            'statut' => 'brouillon',
        ]);

        $bonId = PlancheBonLivraison::query()->value('id');

        $this->assertDatabaseHas('planche_bon_livraison_lignes', [
            'planche_bon_livraison_id' => $bonId,
            'planche_detail_id' => $detail->id,
            'quantite_livree' => 6,
        ]);
    }

    public function test_user_can_update_a_draft_planche_bon_livraison_and_validate_it(): void
    {
        $user = User::factory()->create();
        [$detailA, $clientA] = $this->createPlancheDetail('Contrat-A', 'Rouge', 12.5, 20);
        [$detailB, $clientB] = $this->createPlancheDetail('Contrat-B', 'Bleu', 10, 15);

        $bon = PlancheBonLivraison::query()->create([
            'client_id' => $clientA->id,
            'numero_bl' => 'BL-001',
            'date_livraison' => '2026-03-02',
            'statut' => 'brouillon',
        ]);

        $bon->lignes()->create([
            'planche_detail_id' => $detailA->id,
            'quantite_livree' => 5,
        ]);

        $response = $this->actingAs($user)->putJson("/admin/planche-bons-livraison/{$bon->id}", [
            'client_id' => $clientB->id,
            'numero_bl' => 'BL-001-UPDATED',
            'date_livraison' => '2026-03-03',
            'statut' => 'valide',
            'lignes' => [
                [
                    'planche_detail_id' => $detailA->id,
                    'quantite_livree' => 7,
                ],
                [
                    'planche_detail_id' => $detailB->id,
                    'quantite_livree' => 4,
                ],
            ],
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('planche_bons_livraison', [
            'id' => $bon->id,
            'client_id' => $clientB->id,
            'numero_bl' => 'BL-001-UPDATED',
            'date_livraison' => '2026-03-03',
            'statut' => 'valide',
        ]);

        $this->assertDatabaseHas('invoices', [
            'client_id' => $clientB->id,
            'planche_bon_livraison_id' => $bon->id,
            'date' => '2026-03-03',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('planche_bon_livraison_lignes', [
            'planche_bon_livraison_id' => $bon->id,
            'planche_detail_id' => $detailA->id,
            'quantite_livree' => 7,
        ]);

        $this->assertDatabaseHas('planche_bon_livraison_lignes', [
            'planche_bon_livraison_id' => $bon->id,
            'planche_detail_id' => $detailB->id,
            'quantite_livree' => 4,
        ]);

        $this->assertDatabaseCount('planche_bon_livraison_lignes', 2);
    }

    public function test_validating_a_planche_bon_livraison_on_creation_creates_an_invoice(): void
    {
        $user = User::factory()->create();
        [$detail, $client] = $this->createPlancheDetail('Contrat-C', 'Vert', 8, 12);

        $response = $this->actingAs($user)->postJson('/admin/planche-bons-livraison/store', [
            'client_id' => $client->id,
            'numero_bl' => 'BL-VALIDE',
            'date_livraison' => '2026-03-05',
            'statut' => 'valide',
            'lignes' => [
                [
                    'planche_detail_id' => $detail->id,
                    'quantite_livree' => 3,
                ],
            ],
        ]);

        $response->assertCreated();

        $bon = PlancheBonLivraison::query()->where('numero_bl', 'BL-VALIDE')->firstOrFail();

        $this->assertDatabaseHas('invoices', [
            'client_id' => $client->id,
            'planche_bon_livraison_id' => $bon->id,
            'date' => '2026-03-05',
            'status' => 'pending',
        ]);
    }

    public function test_validated_planche_bon_livraison_cannot_be_updated(): void
    {
        $user = User::factory()->create();
        [$detail, $client] = $this->createPlancheDetail('Contrat-A', 'Rouge', 12.5, 20);

        $bon = PlancheBonLivraison::query()->create([
            'client_id' => $client->id,
            'numero_bl' => 'BL-001',
            'date_livraison' => '2026-03-02',
            'statut' => 'valide',
        ]);

        $bon->lignes()->create([
            'planche_detail_id' => $detail->id,
            'quantite_livree' => 5,
        ]);

        $response = $this->actingAs($user)->putJson("/admin/planche-bons-livraison/{$bon->id}", [
            'client_id' => $client->id,
            'numero_bl' => 'BL-002',
            'date_livraison' => '2026-03-04',
            'statut' => 'valide',
            'lignes' => [
                [
                    'planche_detail_id' => $detail->id,
                    'quantite_livree' => 6,
                ],
            ],
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('statut');

        $this->assertDatabaseHas('planche_bons_livraison', [
            'id' => $bon->id,
            'numero_bl' => 'BL-001',
        ]);
    }

    public function test_user_can_delete_a_draft_planche_bon_livraison(): void
    {
        $user = User::factory()->create();
        [$detail, $client] = $this->createPlancheDetail('Contrat-A', 'Rouge', 12.5, 20);

        $bon = PlancheBonLivraison::query()->create([
            'client_id' => $client->id,
            'numero_bl' => 'BL-DELETE',
            'date_livraison' => '2026-03-02',
            'statut' => 'brouillon',
        ]);

        $bon->lignes()->create([
            'planche_detail_id' => $detail->id,
            'quantite_livree' => 5,
        ]);

        $response = $this->actingAs($user)->deleteJson("/admin/planche-bons-livraison/{$bon->id}");

        $response->assertOk();

        $this->assertDatabaseMissing('planche_bons_livraison', ['id' => $bon->id]);
        $this->assertDatabaseMissing('planche_bon_livraison_lignes', ['planche_bon_livraison_id' => $bon->id]);
    }

    public function test_validated_planche_bon_livraison_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        [$detail, $client] = $this->createPlancheDetail('Contrat-A', 'Rouge', 12.5, 20);

        $bon = PlancheBonLivraison::query()->create([
            'client_id' => $client->id,
            'numero_bl' => 'BL-LOCKED',
            'date_livraison' => '2026-03-02',
            'statut' => 'valide',
        ]);

        $bon->lignes()->create([
            'planche_detail_id' => $detail->id,
            'quantite_livree' => 5,
        ]);

        $response = $this->actingAs($user)->deleteJson("/admin/planche-bons-livraison/{$bon->id}");

        $response->assertStatus(422)->assertJsonValidationErrors('statut');

        $this->assertDatabaseHas('planche_bons_livraison', ['id' => $bon->id]);
    }

    private function createPlancheDetail(
        string $numeroContrat,
        string $codeCouleur,
        float $epaisseur,
        int $quantitePrevue
    ): array {
        $supplier = Supplier::query()->create([
            'name' => 'Supplier ' . $numeroContrat . ' ' . $codeCouleur . ' ' . uniqid(),
        ]);

        $client = \App\Models\Client::query()->create([
            'name' => 'Client ' . $numeroContrat . ' ' . uniqid(),
        ]);

        $contrat = Contrat::query()->create([
            'supplier_id' => $supplier->id,
            'numero' => $numeroContrat,
        ]);

        $planche = Planche::query()->create([
            'contrat_id' => $contrat->id,
        ]);

        $couleur = PlancheCouleur::query()->create([
            'code' => $codeCouleur . '-' . uniqid(),
        ]);

        $detail = PlancheDetail::query()->create([
            'planche_id' => $planche->id,
            'planche_couleur_id' => $couleur->id,
            'epaisseur' => $epaisseur,
            'quantite_prevue' => $quantitePrevue,
        ]);

        return [$detail, $client];
    }
}
