<?php

namespace Tests\Feature;

use App\Models\Gallery;
use App\Models\User;
use App\Models\Artwork;
use App\Models\ArtworkImage;
use App\Http\Middleware\EnsureGalleryDeviceAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GalleryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authenticated_user_can_create_a_gallery_and_get_its_links(): void
    {
        $response = $this->actingAs(User::factory()->create())->post(route('admin.galleries.store'), [
            'name' => 'Galerie du Lac',
            'email' => 'contact@galerie-du-lac.test',
            'password' => 'secret-galerie',
        ]);

        $response->assertRedirect(route('admin.galleries.index'));
        $this->assertDatabaseHas('galleries', [
            'name' => 'Galerie du Lac',
            'slug' => 'galerie-du-lac',
            'email' => 'contact@galerie-du-lac.test',
        ]);

        $gallery = Gallery::firstOrFail();

        $this->actingAs(User::factory()->create())
            ->get(route('admin.galleries.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Galleries/Index')
                ->where('galleries.0.gallery_url', route('galleries.gallery', $gallery))
                ->where('galleries.0.composer_url', route('galleries.compose-artwork', $gallery))
            );
    }

    public function test_a_gallery_slug_must_be_unique(): void
    {
        Gallery::create(['name' => 'Première', 'slug' => 'meme-lien']);

        $this->actingAs(User::factory()->create())
            ->post(route('admin.galleries.store'), ['name' => 'Deuxième', 'slug' => 'meme-lien'])
            ->assertSessionHasErrors('slug');
    }

    public function test_the_password_can_only_claim_one_device(): void
    {
        $gallery = Gallery::create([
            'name' => 'Galerie privée',
            'slug' => 'galerie-privee',
            'access_password' => 'mot-de-passe-secret',
        ]);

        $this->get(route('galleries.gallery', $gallery))
            ->assertRedirect(route('galleries.access', [
                'gallery' => $gallery,
                'redirect' => route('galleries.gallery', $gallery),
            ]));

        $this->post(route('galleries.access.store', $gallery), [
            'password' => 'mot-de-passe-secret',
        ])->assertRedirect(route('galleries.gallery', $gallery));

        $gallery->refresh();
        $this->assertNotNull($gallery->claimed_at);
        $this->assertNotNull($gallery->device_token_hash);

        $this->post(route('galleries.access.store', $gallery), [
            'password' => 'mot-de-passe-secret',
        ])->assertSessionHasErrors('password');
    }

    public function test_each_gallery_has_its_own_public_pages(): void
    {
        $this->withoutMiddleware(EnsureGalleryDeviceAccess::class);

        $gallery = Gallery::create(['name' => 'Carré des Arts', 'slug' => 'carre-des-arts']);

        $this->get('/carre-des-arts/gallery')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Gallery')
                ->where('gallery.name', 'Carré des Arts')
            );

        $this->get('/carre-des-arts/compose-your-artwork')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/ComposeArtwork')
                ->where('gallery.slug', $gallery->slug)
            );
    }

    public function test_an_admin_can_select_the_artworks_visible_for_a_gallery(): void
    {
        $gallery = Gallery::create(['name' => 'Sélection', 'slug' => 'selection']);
        $selected = Artwork::create(['title' => 'Visible', 'is_public' => true, 'background_color' => '#ffffff']);
        $notSelected = Artwork::create(['title' => 'Masqué', 'is_public' => true, 'background_color' => '#ffffff']);

        $this->actingAs(User::factory()->create())
            ->put(route('admin.galleries.artworks.update', $gallery), [
                'artwork_ids' => [$selected->id],
            ])
            ->assertRedirect(route('admin.galleries.edit', $gallery));

        $this->assertDatabaseHas('artwork_gallery', [
            'gallery_id' => $gallery->id,
            'artwork_id' => $selected->id,
        ]);
        $this->assertDatabaseMissing('artwork_gallery', [
            'gallery_id' => $gallery->id,
            'artwork_id' => $notSelected->id,
        ]);
    }

    public function test_the_gallery_composer_includes_selected_private_artworks(): void
    {
        $this->withoutMiddleware(EnsureGalleryDeviceAccess::class);

        $gallery = Gallery::create(['name' => 'Galerie', 'slug' => 'galerie']);
        $publicArtwork = Artwork::create(['title' => 'Public', 'is_public' => true, 'background_color' => '#ffffff']);
        $privateArtwork = Artwork::create(['title' => 'Privé', 'is_public' => false, 'background_color' => '#ffffff']);
        $gallery->artworks()->attach([$publicArtwork->id, $privateArtwork->id]);

        $this->get(route('galleries.compose-artwork', $gallery))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/ComposeArtwork')
                ->has('artworks', 2)
            );
    }

    public function test_a_generated_composition_can_only_be_downloaded_once(): void
    {
        $artwork = Artwork::create(['title' => 'Unique', 'is_public' => true, 'background_color' => '#ffffff']);
        $image = ArtworkImage::create([
            'artwork_id' => $artwork->id,
            'image_path' => 'artworks/test.jpg',
            'position' => 1,
        ]);
        $payload = [
            'artwork_id' => $artwork->id,
            'slots' => [
                ['image_id' => $image->id, 'rotation' => 0],
                null, null, null, null, null, null, null, null,
            ],
        ];

        $this->postJson(route('generated-compositions.store'), $payload)->assertCreated();
        $this->postJson(route('generated-compositions.store'), $payload)
            ->assertStatus(409)
            ->assertJsonFragment(['message' => 'Ce tableau a déjà été généré et téléchargé. Modifiez au moins une image, sa position ou sa rotation pour créer un tableau unique.']);
    }
}
