<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PageResource;
use App\Models\Page;

class PageController extends Controller
{
    public function show(string $slug)
    {
        return new PageResource(Page::where('slug', $slug)->firstOrFail());
    }
}
