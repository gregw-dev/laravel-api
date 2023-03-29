<?php

namespace Tests\Feature\Docs;

use App\Models\Users\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SigninTest extends TestCase
{
    /**
     * test sign in page working properly
     *
     * @return void
     */
    public function testSignInPage()
    {
        // Todo: Implement more tests for sign in route
        $objUser = User::factory()->create();
        $objResponse = $this->actingAs($objUser,'web')->get('/signin');
        $objResponse->assertStatus(200);
    }
}
