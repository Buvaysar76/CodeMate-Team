<?php

namespace Tests\Feature;

use App\Models\Balance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BalanceTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     */
    public function test_deposit_creates_balance_record(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/deposit', [
            'user_id' => $user->id,
            'amount' => 500,
            'comment' => 'Test deposit'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('balances', [
            'user_id' => $user->id,
            'amount' => 500
        ]);
    }

    public function test_withdraw_successful()
    {
        $user = User::factory()->create();
        Balance::create(['user_id' => $user->id, 'amount' => 300]);

        $response = $this->postJson('/api/withdraw', [
            'user_id' => $user->id,
            'amount' => 200
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('balances', [
            'user_id' => $user->id,
            'amount' => 100
        ]);
    }

    public function test_withdraw_insufficient_funds()
    {
        $user = User::factory()->create();
        Balance::create(['user_id' => $user->id, 'amount' => 100]);

        $response = $this->postJson('/api/withdraw', [
            'user_id' => $user->id,
            'amount' => 200
        ]);

        $response->assertStatus(409);
    }

    public function test_transfer_successful()
    {
        $from = User::factory()->create();
        $to = User::factory()->create();

        Balance::create(['user_id' => $from->id, 'amount' => 300]);
        Balance::create(['user_id' => $to->id, 'amount' => 0]);

        $response = $this->postJson('/api/transfer', [
            'from_user_id' => $from->id,
            'to_user_id' => $to->id,
            'amount' => 150
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('balances', [
            'user_id' => $from->id,
            'amount' => 150
        ]);

        $this->assertDatabaseHas('balances', [
            'user_id' => $to->id,
            'amount' => 150
        ]);
    }

    public function test_transfer_insufficient_funds()
    {
        $from = User::factory()->create();
        $to = User::factory()->create();

        Balance::create(['user_id' => $from->id, 'amount' => 100]);
        Balance::create(['user_id' => $to->id, 'amount' => 0]);

        $response = $this->postJson('/api/transfer', [
            'from_user_id' => $from->id,
            'to_user_id' => $to->id,
            'amount' => 200
        ]);

        $response->assertStatus(409);
    }

    public function test_balance_endpoint()
    {
        $user = User::factory()->create();
        Balance::create(['user_id' => $user->id, 'amount' => 250]);

        $response = $this->getJson("/api/balance/{$user->id}");

        $response->assertStatus(200)
            ->assertJson([
                'user_id' => $user->id,
                'balance' => 250
            ]);
    }
}
