<?php

namespace Nexxai\LaravelAnalytics\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Jaybizzle\CrawlerDetect\CrawlerDetect;
use Nexxai\LaravelAnalytics\Models\PageView;
use Throwable;

class Analytics
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        try {
            if (! $request->isMethod('GET')) {
                return $response;
            }

            if ($request->isJson()) {
                return $response;
            }

            $userAgent = $request->userAgent();

            if (is_null($userAgent)) {
                return $response;
            }

            /** @var CrawlerDetect $crawlerDetect */
            $crawlerDetect = app(CrawlerDetect::class);

            if ($crawlerDetect->isCrawler($userAgent)) {
                return $response;
            }

            $ip = $request->headers->get('X-Forwarded-For') ? $request->headers->get('X-Forwarded-For') : $request->ip();

            /** @var PageView $pageView */
            $pageView = PageView::make([
                'session_id' => session()->getId(),
                'path' => $request->path(),
                'user_agent' => Str::substr($userAgent, 0, 255),
                'cidr' => $this->getCidr($ip),
                'referer' => $request->headers->get('referer'),
                'country' => $this->getCountry($ip),
            ]);

            $parameters = $request->route()?->parameters();
            $model = null;

            if (! is_null($parameters)) {
                $model = reset($parameters);
            }

            $pageView->save();

            return $response;
        } catch (Throwable $e) {
            report($e);

            return $response;
        }
    }

    public function getCidr(string $ip)
    {
        $whois = $this->ipWhois($ip);

        foreach ($whois as $line) {
            if (str($line)->startsWith('CIDR:')) {
                return str($line)->after('CIDR:')->trim();
            }
        }

        return 'UNKNOWN';
    }

    public function getCountry(string $ip)
    {
        $whois = $this->ipWhois($ip);

        foreach ($whois as $line) {
            if (str($line)->startsWith('Country:')) {
                return str($line)->after('Country:')->trim();
            }
        }

        return 'UNKNOWN';
    }

    public function ipWhois(string $ip)
    {
        $response = Process::run("whois {$ip}");

        $lines = explode("\n", $response->output()) ?? null;

        return $lines;
    }
}
