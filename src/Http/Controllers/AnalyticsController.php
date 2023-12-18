<?php

namespace Nexxai\LaravelAnalytics\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Nexxai\LaravelAnalytics\Repositories\PageViewRepository;

class AnalyticsController extends Controller
{
    public function getPageViewsPerDays(Request $request, PageViewRepository $pageViewRepository)
    {
        return $pageViewRepository->getByDateGroupedByDays(Carbon::today()->subDays(28));
    }

    public function getPageViewsPerPaths(Request $request, PageViewRepository $pageViewRepository)
    {
        return $pageViewRepository->getByDateGroupedByPath(Carbon::today()->subDays(28));
    }
}
