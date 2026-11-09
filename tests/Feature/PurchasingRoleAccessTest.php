<?php

namespace Tests\Feature;

use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PurchasingRoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchasing_role_cannot_open_journal_module(): void
    {
        $user = $this->createPurchasingUser();

        $this->actingAs($user)
            ->get(route('akuntansi.jurnal.index'))
            ->assertForbidden();
    }

    public function test_purchasing_role_is_redirected_to_its_module_from_dashboard(): void
    {
        $user = $this->createPurchasingUser();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('pembelian.pembelian.index'));
    }

    public function test_purchasing_role_can_only_open_purchasing_routes(): void
    {
        $user = $this->createPurchasingUser();

        $this->actingAs($user)
            ->get(route('pembelian.pembelian.index'))
            ->assertOk()
            ->assertSee('Order Pembelian')
            ->assertDontSee('Order Penjualan')
            ->assertDontSee('Master Data');

        $this->actingAs($user)
            ->get(route('penjualan.penjualan.index'))
            ->assertForbidden();
    }

    public function test_cashier_role_is_redirected_to_sales_and_cannot_open_other_modules(): void
    {
        $user = $this->createUserWithRole('kasir');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('penjualan.penjualan.index'));

        $this->actingAs($user)
            ->get(route('penjualan.penjualan.index'))
            ->assertOk()
            ->assertSee('Order Penjualan')
            ->assertDontSee('Order Pembelian')
            ->assertDontSee('Master Data');

        $this->actingAs($user)
            ->get(route('pembelian.pembelian.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('akuntansi.jurnal.index'))
            ->assertForbidden();
    }

    public function test_login_redirects_purchasing_and_cashier_users_to_their_module(): void
    {
        $purchasingUser = $this->createPurchasingUser();
        $this->post(route('login.store'), [
            'email' => $purchasingUser->email,
            'password' => 'password',
        ])->assertRedirect(route('pembelian.pembelian.index'));

        auth()->logout();
        $salesUser = $this->createUserWithRole('kasir');
        $this->post(route('login.store'), [
            'email' => $salesUser->email,
            'password' => 'password',
        ])->assertRedirect(route('penjualan.penjualan.index'));
    }

    private function createPurchasingUser(): Pengguna
    {
        return $this->createUserWithRole('pembelian');
    }

    private function createUserWithRole(string $role): Pengguna
    {
        $roleId = DB::table('peran')->insertGetId([
            'kode' => $role,
            'nama' => ucfirst($role),
        ]);
        $user = Pengguna::create([
            'nama' => 'Admin ' . ucfirst($role),
            'email' => uniqid($role . '-', true) . '@test.local',
            'password' => 'password',
            'id_entitas' => null,
            'is_active' => true,
        ]);

        DB::table('pengguna_peran')->insert([
            'id_pengguna' => $user->id,
            'id_peran' => $roleId,
        ]);

        return $user;
    }
}
