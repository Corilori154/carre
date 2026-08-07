<?php

namespace Tests\Feature;

use App\Models\GeneratedComposition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeneratedCompositionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_delete_a_generated_composition(): void
    {
        $composition = GeneratedComposition::query()->create([
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'email' => 'jean@example.com',
            'composition' => [],
        ]);

        $response = $this
            ->actingAs(User::factory()->create())
            ->delete(route('admin.generated-compositions.destroy', $composition));

        $response->assertRedirect(route('admin.generated-compositions.index'));
        $this->assertDatabaseMissing('generated_compositions', ['id' => $composition->id]);
    }

    public function test_guest_cannot_delete_a_generated_composition(): void
    {
        $composition = GeneratedComposition::query()->create([
            'composition' => [],
        ]);

        $response = $this->delete(route('admin.generated-compositions.destroy', $composition));

        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('generated_compositions', ['id' => $composition->id]);
    }
}
