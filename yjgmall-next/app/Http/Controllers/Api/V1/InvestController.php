<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\InvestLead;
use App\Models\InvestProject;
use Illuminate\Http\Request;

class InvestController extends Controller
{
    public function index()
    {
        $items = InvestProject::published()->orderBy('sort')->latest()->paginate(16);

        return response()->json([
            'data' => collect($items->items())->map(fn (InvestProject $p) => [
                'id' => $p->id,
                'title' => $p->title,
                'area' => $p->area,
                'amount' => $p->amount,
                'summary' => $p->summary,
                'views' => (int) $p->views,
            ]),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function show(InvestProject $invest)
    {
        abort_unless($invest->status === 'published', 404);
        $invest->increment('views');

        return response()->json([
            'data' => [
                'id' => $invest->id,
                'title' => $invest->title,
                'area' => $invest->area,
                'amount' => $invest->amount,
                'summary' => $invest->summary,
                'content' => $invest->content,
                'contact_name' => $invest->contact_name,
                'contact_phone' => $invest->contact_phone,
                'views' => (int) $invest->views,
            ],
        ]);
    }

    public function apply(Request $request)
    {
        $data = $request->validate([
            'invest_project_id' => 'nullable|exists:invest_projects,id',
            'company_name' => 'nullable|string|max:100',
            'contact_name' => 'required|string|max:50',
            'contact_phone' => 'required|string|max:30',
            'content' => 'nullable|string|max:2000',
        ]);

        $lead = InvestLead::create([
            ...$data,
            'user_id' => $request->user()?->id,
            'status' => 'new',
        ]);

        return response()->json(['data' => ['id' => $lead->id], 'message' => '招商意向已提交'], 201);
    }
}
