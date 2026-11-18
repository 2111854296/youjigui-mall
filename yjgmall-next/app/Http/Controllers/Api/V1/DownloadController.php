<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Download;
use Illuminate\Http\Request;

class DownloadController extends Controller
{
    public function index(Request $request)
    {
        $query = Download::published()->with('category')->orderBy('sort')->latest();
        if ($request->filled('q')) {
            $query->where('title', 'like', '%'.$request->string('q').'%');
        }
        if ($request->filled('category')) {
            $query->where('category_id', $request->integer('category'));
        }
        $paginator = $query->paginate(20);

        return response()->json([
            'data' => collect($paginator->items())->map(fn (Download $d) => [
                'id' => $d->id,
                'title' => $d->title,
                'file_type' => $d->file_type,
                'file_size' => $d->file_size,
                'summary' => $d->summary,
                'downloads' => (int) $d->downloads,
                'category' => $d->category?->only(['id', 'name']),
            ]),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function show(Download $download)
    {
        abort_unless($download->status === 'published', 404);

        return response()->json([
            'data' => [
                'id' => $download->id,
                'title' => $download->title,
                'file_url' => $download->file_url,
                'file_type' => $download->file_type,
                'file_size' => $download->file_size,
                'summary' => $download->summary,
                'downloads' => (int) $download->downloads,
            ],
        ]);
    }

    public function hit(Download $download)
    {
        abort_unless($download->status === 'published', 404);
        $download->increment('downloads');

        return response()->json([
            'data' => [
                'id' => $download->id,
                'file_url' => $download->file_url,
                'downloads' => (int) $download->fresh()->downloads,
            ],
        ]);
    }
}
