<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RouteIntegrityTest extends TestCase
{
    public function test_all_api_routes_point_to_valid_controller_methods(): void
    {
        $routes = Route::getRoutes();
        $missingActions = [];

        foreach ($routes as $route) {
            $uri = $route->uri();
            if (!str_starts_with($uri, 'api/')) {
                continue;
            }

            $actionName = $route->getActionName();
            if ($actionName === 'Closure' || str_contains($actionName, '@')) {
                if (str_contains($actionName, '@')) {
                    [$controller, $method] = explode('@', $actionName);
                    if (!class_exists($controller)) {
                        $missingActions[] = "Missing controller {$controller} for {$uri}";
                    } elseif (!method_exists($controller, $method)) {
                        $missingActions[] = "Missing method {$method} on {$controller} for {$uri}";
                    }
                }
            }
        }

        $this->assertEmpty($missingActions, "Broken API routes found:\n" . implode("\n", $missingActions));
    }
}
