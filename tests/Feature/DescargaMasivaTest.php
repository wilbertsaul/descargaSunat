<?php

namespace Tests\Feature;

use App\Jobs\ListarPropuestaRce;
use App\Models\Comprobante;
use App\Models\DescargaMasiva;
use App\Models\Documento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class DescargaMasivaTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->get(route('dashboard'))->assertRedirect('/login');
    }

    public function test_user_can_view_own_descargas(): void
    {
        DescargaMasiva::create([
            'user_id' => $this->user->id,
            'ruc' => '20609937531',
            'periodo_ini' => '202609',
            'periodo_fin' => '202609',
            'estado' => 'completado',
        ]);

        $response = $this->actingAs($this->user)->get(route('descargas.index'));
        $response->assertStatus(200);
        $response->assertSee('202609');
    }

    public function test_user_can_iniciar_descarga(): void
    {
        Bus::fake();

        $response = $this->actingAs($this->user)->post(route('descargas.store'), [
            'periodo' => '2026-09',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('descargas_masivas', [
            'user_id' => $this->user->id,
            'periodo_ini' => '202609',
            'estado' => 'pendiente',
        ]);

        Bus::assertDispatched(ListarPropuestaRce::class);
    }

    public function test_descarga_rechaza_periodo_invalido(): void
    {
        $response = $this->actingAs($this->user)->post(route('descargas.store'), [
            'periodo' => '09-2026',
        ]);

        $response->assertSessionHasErrors('periodo');
    }

    public function test_descarga_rechaza_periodo_futuro(): void
    {
        $futuro = now()->addMonth()->format('Y-m');

        $response = $this->actingAs($this->user)->post(route('descargas.store'), [
            'periodo' => $futuro,
        ]);

        $response->assertSessionHasErrors('periodo');
    }

    public function test_user_cannot_view_other_users_descarga(): void
    {
        $otherUser = User::factory()->create();
        $descarga = DescargaMasiva::create([
            'user_id' => $otherUser->id,
            'ruc' => '20609937531',
            'periodo_ini' => '202609',
            'periodo_fin' => '202609',
        ]);

        $this->actingAs($this->user)
            ->get(route('descargas.show', $descarga))
            ->assertStatus(403);
    }

    public function test_descarga_show_lista_comprobantes_con_documentos(): void
    {
        $descarga = DescargaMasiva::create([
            'user_id' => $this->user->id,
            'ruc' => '20609937531',
            'periodo_ini' => '202609',
            'periodo_fin' => '202609',
            'estado' => 'completado',
            'total_comprobantes' => 1,
            'procesados' => 1,
        ]);

        $comprobante = Comprobante::create([
            'user_id' => $this->user->id,
            'descarga_masiva_id' => $descarga->id,
            'ruc_emisor' => '20615887537',
            'ruc_receptor' => '20609937531',
            'tipo_comprobante' => '01',
            'serie' => 'E001',
            'correlativo' => '30',
            'fecha_emision' => '2026-09-04',
            'moneda' => 'PEN',
            'importe_total' => 42.00,
            'estado_consulta' => 'consultado',
        ]);

        Documento::create([
            'comprobante_id' => $comprobante->id,
            'tipo' => 'xml',
            'ruta' => 'descargas/1/20615887537/E001-30/E001-30.xml',
            'nombre_original' => 'E001-30.xml',
            'origen' => 'servicio_oficial',
            'estado' => 'disponible',
        ]);

        $response = $this->actingAs($this->user)->get(route('descargas.show', $descarga));
        $response->assertStatus(200);
        $response->assertSee('20615887537');
    }

    public function test_periodo_debe_tener_una_sola_pagina(): void
    {
        $descarga = new DescargaMasiva([
            'total_comprobantes' => 4,
            'procesados' => 3,
            'con_error' => 1,
        ]);

        $this->assertSame(100, $descarga->porcentaje());
    }
}
