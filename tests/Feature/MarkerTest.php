<?php

namespace Tests\Feature;

use App\Models\Marker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarkerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_marker()
    {
        $response = $this->postJson('/api/markers', [
            'name' => 'New Marker',
            'latitude' => -6.2,
            'longitude' => 106.8,
            'description' => 'Sample description',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('markers', [
            'name' => 'New Marker',
            'latitude' => -6.2,
            'longitude' => 106.8,
            'description' => 'Sample description',
        ]);
    }

    public function test_can_fetch_markers()
    {
        Marker::factory()->create([
            'name' => 'Test Marker',
            'latitude' => -6.2,
            'longitude' => 106.8,
            'description' => 'Test description',
        ]);

        $response = $this->getJson('/api/markers');
        $response->assertStatus(200)
                 ->assertJsonCount(1)
                 ->assertJsonFragment([
                     'name' => 'Test Marker',
                     'latitude' => -6.2,
                     'longitude' => 106.8,
                     'description' => 'Test description',
                 ]);
    }
}