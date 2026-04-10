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
            'date_livraison' => '2026-03-02',
            'statut' => 'brouillon',
            'lignes' => [
                [
                    'planche_detail_id' => $detail->id,
                    'quantite_livree' => 6,
                    'prix_unitaire' => 1250.50,
                ],
            ],
        ]);

        $response->assertCreated();
        $bon = PlancheBonLivraison::query()->firstOrFail();

        $this->assertSame($client->id, $bon->client_id);
        $this->assertSame('2026-03-02', $bon->date_livraison->format('Y-m-d'));
        $this->assertSame('valide', $bon->statut);
        $this->assertMatchesRegularExpression('/^BL-\d{8}-[A-Z0-9]{4}$/', $bon->numero_bl);

        $this->assertDatabaseHas('invoices', [
            'client_id' => $client->id,
            'planche_bon_livraison_id' => $bon->id,
            'date' => '2026-03-02',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('planche_bon_livraison_lignes', [
            'planche_bon_livraison_id' => $bon->id,
            'planche_detail_id' => $detail->id,
            'quantite_livree' => 6,
            'prix_unitaire' => 1250.50,
            'prix_total' => 7503.00,
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
            'date_livraison' => '2026-03-03',
            'statut' => 'valide',
            'lignes' => [
                [
                    'planche_detail_id' => $detailA->id,
                    'quantite_livree' => 7,
                    'prix_unitaire' => 1000,
                ],
                [
                    'planche_detail_id' => $detailB->id,
                    'quantite_livree' => 4,
                    'prix_unitaire' => 2500.75,
                ],
            ],
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('planche_bons_livraison', [
            'id' => $bon->id,
            'client_id' => $clientB->id,
            'numero_bl' => 'BL-001',
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
            'prix_unitaire' => 1000.00,
            'prix_total' => 7000.00,
        ]);

        $this->assertDatabaseHas('planche_bon_livraison_lignes', [
            'planche_bon_livraison_id' => $bon->id,
            'planche_detail_id' => $detailB->id,
            'quantite_livree' => 4,
            'prix_unitaire' => 2500.75,
            'prix_total' => 10003.00,
        ]);

        $this->assertDatabaseCount('planche_bon_livraison_lignes', 2);
    }

    public function test_validating_a_planche_bon_livraison_on_creation_creates_an_invoice(): void
    {
        $user = User::factory()->create();
        [$detail, $client] = $this->createPlancheDetail('Contrat-C', 'Vert', 8, 12);

        $response = $this->actingAs($user)->postJson('/admin/planche-bons-livraison/store', [
            'client_id' => $client->id,
            'date_livraison' => '2026-03-05',
            'statut' => 'valide',
            'lignes' => [
                [
                    'planche_detail_id' => $detail->id,
                    'quantite_livree' => 3,
                    'prix_unitaire' => 1999.99,
                ],
            ],
        ]);

        $response->assertCreated();

        $bon = PlancheBonLivraison::query()->firstOrFail();
        $this->assertMatchesRegularExpression('/^BL-\d{8}-[A-Z0-9]{4}$/', $bon->numero_bl);

        $this->assertDatabaseHas('invoices', [
            'client_id' => $client->id,
            'planche_bon_livraison_id' => $bon->id,
            'date' => '2026-03-05',
            'status' => 'pending',
        ]);
    }

    public function test_user_cannot_store_a_planche_bon_livraison_with_quantity_above_available(): void
    {
        $user = User::factory()->create();
        [$detail, $client] = $this->createPlancheDetail('Contrat-D', 'Noir', 6, 5);

        $response = $this->actingAs($user)->postJson('/admin/planche-bons-livraison/store', [
            'client_id' => $client->id,
            'date_livraison' => '2026-03-06',
            'statut' => 'brouillon',
            'lignes' => [
                [
                    'planche_detail_id' => $detail->id,
                    'quantite_livree' => 6,
                    'prix_unitaire' => 100,
                ],
            ],
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('lignes.0.quantite_livree');

        $this->assertDatabaseCount('planche_bons_livraison', 0);
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
            'date_livraison' => '2026-03-04',
            'statut' => 'valide',
            'lignes' => [
                [
                    'planche_detail_id' => $detail->id,
                    'quantite_livree' => 6,
                    'prix_unitaire' => 300,
                ],
            ],
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('statut');

        $this->assertDatabaseHas('planche_bons_livraison', [
            'id' => $bon->id,
            'numero_bl' => 'BL-001',
        ]);
    }

    public function test_user_cannot_update_a_draft_planche_bon_livraison_with_quantity_above_available(): void
    {
        $user = User::factory()->create();
        [$detail, $client] = $this->createPlancheDetail('Contrat-E', 'Blanc', 9, 8);

        $bon = PlancheBonLivraison::query()->create([
            'client_id' => $client->id,
            'numero_bl' => 'BL-UPDATE-LOCK',
            'date_livraison' => '2026-03-02',
            'statut' => 'brouillon',
        ]);

        $bon->lignes()->create([
            'planche_detail_id' => $detail->id,
            'quantite_livree' => 2,
            'prix_unitaire' => 100,
            'prix_total' => 200,
        ]);

        $response = $this->actingAs($user)->putJson("/admin/planche-bons-livraison/{$bon->id}", [
            'client_id' => $client->id,
            'date_livraison' => '2026-03-04',
            'statut' => 'brouillon',
            'lignes' => [
                [
                    'planche_detail_id' => $detail->id,
                    'quantite_livree' => 9,
                    'prix_unitaire' => 300,
                ],
            ],
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('lignes.0.quantite_livree');

        $this->assertDatabaseHas('planche_bon_livraison_lignes', [
            'planche_bon_livraison_id' => $bon->id,
            'quantite_livree' => 2,
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
