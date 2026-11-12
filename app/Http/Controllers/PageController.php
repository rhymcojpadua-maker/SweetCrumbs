<?php

namespace App\Http\Controllers;

use App\Services\CmsService;
use Illuminate\View\View;

class PageController extends Controller
{
    public function about(CmsService $cms): View
    {
        return $this->show('about', $cms);
    }

    public function faqs(CmsService $cms): View
    {
        return $this->show('faqs', $cms);
    }

    public function terms(CmsService $cms): View
    {
        return $this->show('terms', $cms);
    }

    public function privacy(CmsService $cms): View
    {
        return $this->show('privacy', $cms);
    }

    public function show(string $slug, CmsService $cms): View
    {
        $page = $cms->page($slug);

        if (! $page && $slug === 'faqs') {
            return view('public.faqs', ['faqs' => $cms->faqs(), 'page' => null]);
        }

        abort_unless($page, 404);

        if ($slug === 'faqs') {
            return view('public.faqs', ['faqs' => $cms->faqs(), 'page' => $page]);
        }

        return view('public.page', compact('page'));
    }
}
