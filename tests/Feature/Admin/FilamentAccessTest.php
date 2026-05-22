<?php

declare(strict_types=1);

use App\Models\User;

it('redirects guests away from /admin', function (): void {
    $this->get('/admin')->assertRedirect();
});

it('denies access to /admin for non-master-admin users', function (): void {
    $user = User::factory()->create(['is_master_admin' => false]);

    $this->actingAs($user)
        ->get('/admin')
        ->assertForbidden();
});

it('allows master admin into /admin', function (): void {
    $admin = User::factory()->create(['is_master_admin' => true]);

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk();
});

it('shows the Tenants list page to a master admin', function (): void {
    $admin = User::factory()->create(['is_master_admin' => true]);

    $this->actingAs($admin)
        ->get('/admin/tenants')
        ->assertOk();
});
