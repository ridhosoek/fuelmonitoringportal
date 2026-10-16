<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_index_uses_cards_and_separate_create_page(): void
    {
        $this->withoutVite();
        $this->seed();
        $admin = User::query()->where('username', 'superadmin')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('master.index', ['resource' => 'units'], false))
            ->assertOk()
            ->assertSee('Tambah Unit')
            ->assertSee('master-data-card', false)
            ->assertDontSee('name="unit_code"', false);

        $this->actingAs($admin)
            ->get(route('master.create', 'units', false))
            ->assertOk()
            ->assertSee('name="unit_code"', false)
            ->assertSee('Klasifikasi Operasional');
    }

    public function test_master_data_can_be_exported_as_pdf(): void
    {
        $this->seed();
        $admin = User::query()->where('username', 'superadmin')->firstOrFail();
        $response = $this->actingAs($admin)->get(route('master.export-pdf', 'units', false));

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-1.4', $response->getContent());
        $this->assertStringContainsString('MASTER DATA UNITS', $response->getContent());
    }
}
