<?php

namespace Tests\Feature;

use App\Models\GeneratedComposition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Inertia\Testing\AssertableInertia as Assert;

class GeneratedCompositionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_paginates_and_searches_compositions(): void
    {
        for ($i = 0; $i < 12; $i++) {
            GeneratedComposition::create([
                'first_name' => $i === 0 ? 'Alice' : 'Jean',
                'last_name' => 'Dupont',
                'email' => "visitor{$i}@example.com",
                'fingerprint' => hash('sha256', (string) $i),
                'composition' => ['background_color' => '#123456', 'slots' => array_fill(0, 9, null)],
            ]);
        }

        $this->actingAs(User::factory()->create())
            ->get(route('admin.generated-compositions.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/GeneratedCompositions/Index')
                ->has('compositions.data', 10)
                ->where('compositions.total', 12)
                ->where('compositions.data.0.preview.background_color', '#123456')
                ->has('compositions.data.0.preview.slots', 9));

        $this->get(route('admin.generated-compositions.index', ['page' => 2]))
            ->assertInertia(fn (Assert $page) => $page->has('compositions.data', 2));

        $this->get(route('admin.generated-compositions.index', ['search' => 'Alice Dupont']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('compositions.data', 1)
                ->where('compositions.data.0.email', 'visitor0@example.com')
                ->where('filters.search', 'Alice Dupont'));

        $this->get(route('admin.generated-compositions.index', ['search' => 'introuvable']))
            ->assertInertia(fn (Assert $page) => $page->has('compositions.data', 0));
    }

    public function test_authenticated_user_can_delete_a_generated_composition(): void
    {
        $composition = GeneratedComposition::query()->create([
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'email' => 'jean@example.com',
            'fingerprint' => hash('sha256', 'delete-test'),
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
            'fingerprint' => hash('sha256', 'guest-test'),
            'composition' => [],
        ]);

        $response = $this->delete(route('admin.generated-compositions.destroy', $composition));

        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('generated_compositions', ['id' => $composition->id]);
    }
}
