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

    public function test_spa_shell_links_the_light_dark_and_touch_icons(): void
    {
        $this->withoutVite();

        $this->get('/contacts')
            ->assertOk()
            ->assertSee('<link href="'.asset('favicon-light.png').'" rel="icon" type="image/png" sizes="32x32" media="(prefers-color-scheme: light)">', escape: false)
            ->assertSee('<link href="'.asset('favicon-dark.png').'" rel="icon" type="image/png" sizes="32x32" media="(prefers-color-scheme: dark)">', escape: false)
            ->assertSee('<link href="'.asset('apple-touch-icon.png').'" rel="apple-touch-icon">', escape: false);

        foreach (['favicon-light.png', 'favicon-dark.png', 'apple-touch-icon.png'] as $file) {
            $this->assertFileExists(public_path($file));
        }
    }

    public function test_unknown_api_paths_return_json_404_not_the_spa(): void
    {
        $this->getJson('/api/v1/unknown')
            ->assertNotFound()
            ->assertExactJson(['message' => 'The requested resource was not found.']);
    }
}
