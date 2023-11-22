<?php

namespace Tests\Feature\Observers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_when_a_user_gets_created_without_a_role_this_user_gets_assigned_a_default_role()
    {
        $data = [
            'name' => 'Test User',
            'email' => 'test.user@example.com',
        ];

        $this->actingAs($this->admin)->post(route('users.store'), $data)
            ->assertRedirect(route('users.index'));

        $user = User::where('email', $data['email'])->first();

        $this->assertTrue($user->hasRole('User'));
    }

    public function test_a_user_that_gets_created_with_a_role_does_get_the_default_role_assigned()
    {
        $data = [
            'name' => 'Test User',
            'email' => 'test.user@example.com',
            'role' => 4 //Employee
        ];

        $this->actingAs($this->admin)->post(route('users.store'), $data)
            ->assertRedirect(route('users.index'));

        $user = User::where('email', $data['email'])->first();

        $this->assertTrue($user->hasRole('Employee'));
    }
}
