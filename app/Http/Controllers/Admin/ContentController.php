<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CmsService;
use Illuminate\View\View;

class ContentController extends Controller
{
    public function index(CmsService $cms): View
    {
        return view('admin.content.index', [
            'pages' => $cms->collection('pages'),
            'faqs' => $cms->faqs(),
            'testimonials' => $cms->testimonials(),
            'cmsUrl' => url('/cp'),
        ]);
    }
}
