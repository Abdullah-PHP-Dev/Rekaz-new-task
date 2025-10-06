<?php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Artisan;

class BlobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // set env token
        config(['simpledrive.default' => 'local']);
        putenv('SIMPLEDRIVE_API_TOKEN=testtoken');
        // migrate
        Artisan::call('migrate');
    }

    public function test_store_and_get_blob()
    {
        $payload = [
            'id' => 'test/1',
            'data' => base64_encode('hello world'),
        ];
        $resp = $this->withHeader('Authorization', 'Bearer testtoken')
            ->postJson('/api/v1/blobs', $payload);
        $resp->assertStatus(201)
             ->assertJsonFragment(['id' => 'test/1']);

        $get = $this->withHeader('Authorization', 'Bearer testtoken')
            ->getJson('/api/v1/blobs/test/1');
        $get->assertStatus(200)
            ->assertJsonFragment(['id' => 'test/1', 'data' => $payload['data']]);
    }
}
