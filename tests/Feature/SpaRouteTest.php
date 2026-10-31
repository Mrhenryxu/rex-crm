<?php

namespace Tests\Feature;

use Tests\TestCase;

class SpaRouteTest extends TestCase
{
    public function test_spa_shell_is_served_for_frontend_routes(): void
    {
        $this->withoutVite();

        foreach (['/', '/contacts', '/contacts/01a193fc-6c50-7267-89ba-e190b7096e34/edit'] as $path) {
            $this->get($path)->assertOk()->assertSee('<div id="app"></div>', escape: false);
        }
    }

    public function test_unknown_api_paths_return_json_404_not_the_spa(): void
    {
        $this->getJson('/api/v1/unknown')
            ->assertNotFound()
            ->assertExactJson(['message' => 'The requested resource was not found.']);
    }
}
