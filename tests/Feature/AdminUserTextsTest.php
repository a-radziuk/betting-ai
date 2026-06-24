<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserTextsTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_list_and_edit_user_texts(): void
    {
        $superadmin = User::factory()->create(['is_superadmin' => true]);
        $user = User::factory()->create([
            'name' => 'Player One',
            'email' => 'player@example.com',
            'tagline' => 'Old tagline',
        ]);

        $this->actingAs($superadmin)
            ->get(route('admin.user-texts'))
            ->assertOk()
            ->assertSee('Player One', false)
            ->assertSee('player@example.com', false)
            ->assertSee(__('Edit'), false)
            ->assertDontSee(__('New user'), false);

        $this->actingAs($superadmin)
            ->get(route('admin.user-texts.edit', $user))
            ->assertOk()
            ->assertSee(__('Hidden description'), false)
            ->assertDontSee(__('Delete'), false);

        $this->actingAs($superadmin)
            ->put(route('admin.user-texts.update', $user), [
                'name' => 'Updated Name',
                'tagline' => 'Sharp on corners',
                'city' => 'London',
                'country' => 'United Kingdom',
                'bio' => 'Mostly EPL.',
                'hidden_description' => 'VIP contact.',
            ])
            ->assertRedirect(route('admin.user-texts'))
            ->assertSessionHas('status');

        $user->refresh();
        $this->assertSame('Updated Name', $user->name);
        $this->assertSame('Sharp on corners', $user->tagline);
        $this->assertSame('London', $user->city);
        $this->assertSame('United Kingdom', $user->country);
        $this->assertSame('Mostly EPL.', $user->bio);
        $this->assertSame('VIP contact.', $user->hidden_description);
    }

    public function test_editor_can_access_user_texts_routes(): void
    {
        $editor = User::factory()->create([
            'is_superadmin' => false,
            'priveleges' => User::PRIVELEGE_EDITOR,
        ]);
        $user = User::factory()->create();

        $this->actingAs($editor)
            ->get(route('admin.user-texts'))
            ->assertOk()
            ->assertSee(route('admin.user-texts.edit', $user), false);

        $this->actingAs($editor)
            ->put(route('admin.user-texts.update', $user), [
                'tagline' => 'Editor tagline',
            ])
            ->assertRedirect(route('admin.user-texts'));

        $this->assertSame('Editor tagline', $user->fresh()->tagline);
    }

    public function test_user_text_fields_can_be_cleared(): void
    {
        $editor = User::factory()->create([
            'is_superadmin' => false,
            'priveleges' => User::PRIVELEGE_EDITOR,
        ]);
        $user = User::factory()->create([
            'tagline' => 'Old tag',
            'bio' => 'Old bio',
            'hidden_description' => 'Old note',
        ]);

        $this->actingAs($editor)
            ->put(route('admin.user-texts.update', $user), [
                'name' => '',
                'tagline' => '',
                'city' => '',
                'country' => '',
                'bio' => '',
                'hidden_description' => '',
            ])
            ->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertNull($user->tagline);
        $this->assertNull($user->bio);
        $this->assertNull($user->hidden_description);
    }

    public function test_user_texts_can_be_searched_by_partial_name(): void
    {
        $editor = User::factory()->create([
            'is_superadmin' => false,
            'priveleges' => User::PRIVELEGE_EDITOR,
        ]);

        User::factory()->create(['name' => 'Alice Wonder', 'email' => 'alice@example.com']);
        User::factory()->create(['name' => 'Bob Builder', 'email' => 'bob@example.com']);
        User::factory()->create(['name' => 'Charlie', 'email' => 'charlie@example.com']);

        $this->actingAs($editor)
            ->get(route('admin.user-texts', ['search' => 'lice']))
            ->assertOk()
            ->assertSee('Alice Wonder', false)
            ->assertDontSee('bob@example.com', false)
            ->assertDontSee('charlie@example.com', false);

        $this->actingAs($editor)
            ->get(route('admin.user-texts', ['search' => 'uild']))
            ->assertOk()
            ->assertSee('Bob Builder', false)
            ->assertDontSee('alice@example.com', false);
    }
}
