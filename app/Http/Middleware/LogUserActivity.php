<?php

namespace App\Http\Middleware;

use App\Models\UserActivityLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogUserActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);

        /** @var Response $response */
        $response = $next($request);

        try {
            $this->log($request, $response, $start);
        } catch (\Throwable $e) {
            // Never break the app for logging failures
            // You may log this to default logger if needed
        }

        return $response;
    }

    protected function log(Request $request, Response $response, float $start): void
    {
        $route = $request->route();
        $routeName = $route?->getName();
        $actionName = method_exists($route, 'getActionName') ? $route->getActionName() : null;

        $controller = null;
        $action = null;
        if ($actionName && str_contains($actionName, '@')) {
            [$controller, $action] = explode('@', $actionName);
        } else {
            $controller = $actionName;
        }

        $redactKeys = config('user-activity.redact_keys', []);
        $maxBody = (int) config('user-activity.max_request_body', 2000);

        $input = $request->except(array_merge($redactKeys, ['password', 'password_confirmation', 'new_password', 'current_password']));

        // Remove uploaded files info to avoid large payloads
        foreach ($request->files->keys() as $fileKey) {
            unset($input[$fileKey]);
        }

        $requestBody = \json_encode($input, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($requestBody && strlen($requestBody) > $maxBody) {
            $requestBody = substr($requestBody, 0, $maxBody) . '...';
        }

        UserActivityLog::query()->create([
            'user_id'      => optional($request->user())->id,
            'method'       => $request->getMethod(),
            'url'          => $request->fullUrl(),
            'route_name'   => $routeName,
            'controller'   => $controller,
            'action'       => $action,
            'ip'           => $request->ip(),
            'user_agent'   => substr((string) $request->userAgent(), 0, 512),
            'request_body' => $requestBody,
            'status_code'  => $response->getStatusCode(),
            'performed_at' => now(),
        ]);
    }
}
