<?php

namespace Tests\Feature;

use App\Services\ZohoAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ZohoAuthServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_access_token_is_cached_across_calls(): void
    {
        Http::fake([
            '*accounts.zoho.com.au*' => Http::response(['access_token' => 'token-one'], 200),
        ]);

        $service = app(ZohoAuthService::class);

        $this->assertSame('token-one', $service->getAccessToken());
        $this->assertSame('token-one', $service->getAccessToken());

        Http::assertSentCount(1);
    }

    public function test_forget_token_forces_a_fresh_request(): void
    {
        Http::fake([
            '*accounts.zoho.com.au*' => Http::sequence()
                ->push(['access_token' => 'token-one'], 200)
                ->push(['access_token' => 'token-two'], 200),
        ]);

        $service = app(ZohoAuthService::class);

        $this->assertSame('token-one', $service->getAccessToken());
        $service->forgetToken();
        $this->assertSame('token-two', $service->getAccessToken());
    }

    public function test_throws_when_zoho_does_not_return_an_access_token(): void
    {
        Http::fake([
            '*accounts.zoho.com.au*' => Http::response(['error' => 'invalid_client'], 401),
        ]);

        $this->expectException(\App\Exceptions\ZohoApiException::class);

        app(ZohoAuthService::class)->getAccessToken();
    }
}
