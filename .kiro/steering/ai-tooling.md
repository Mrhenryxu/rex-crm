# AI Tooling

- Laravel Boost is configured for Kiro only (`boost.json`). It writes `AGENTS.md`, the Boost skills in `.kiro/skills/` and `.kiro/settings/mcp.json`. Do not edit those generated parts by hand.
- The Boost MCP server runs inside the `rex-crm` container, so the Docker Compose stack must be running.
- To regenerate Boost files, run this exact command so generated commands use Docker Compose:

```sh
docker compose exec -T \
  -e BOOST_PHP_EXECUTABLE_PATH="docker compose exec -T rex-crm php" \
  -e BOOST_COMPOSER_EXECUTABLE_PATH="docker compose exec -T rex-crm composer" \
  -e BOOST_NPM_EXECUTABLE_PATH="docker compose exec -T rex-crm npm" \
  -e BOOST_VENDOR_BIN_EXECUTABLE_PATH="docker compose exec -T rex-crm vendor/bin/" \
  rex-crm php artisan boost:update
```

- Do not put these variables in `.env`. The container reads `.env` too, and Boost would then try to run `docker` inside the container.
- `config/boost.php` excludes the `tailwindcss-development` skill and the Laravel Cloud `deployments` guideline.
