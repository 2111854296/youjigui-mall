<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AdPlace;
use App\Models\Message;
use App\Models\VipApplication;
use App\Models\VipGrade;
use Illuminate\Http\Request;

class VipController extends Controller
{
    public function grades()
    {
        return response()->json([
            'data' => VipGrade::query()->where('is_active', true)->orderBy('sort')->get()->map(fn (VipGrade $g) => [
                'id' => $g->id,
                'code' => $g->code,
                'name' => $g->name,
                'price_year' => (string) $g->price_year,
                'level' => (int) $g->level,
                'perks' => $g->perks ?: [],
            ]),
        ]);
    }

    public function apply(Request $request)
    {
        $data = $request->validate([
            'vip_grade_id' => 'required|exists:vip_grades,id',
            'company_name' => 'nullable|string|max:100',
            'contact_name' => 'required|string|max:50',
            'phone' => 'nullable|string|max:30',
            'mobile' => 'nullable|string|max:30',
            'email' => 'nullable|email',
            'qq' => 'nullable|string|max:30',
            'remark' => 'nullable|string|max:500',
        ]);

        $grade = VipGrade::query()->findOrFail($data['vip_grade_id']);
        abort_if((float) $grade->price_year <= 0 && $grade->level <= 0, 422, '该会员组无需申请');

        $app = VipApplication::create([
            ...$data,
            'user_id' => $request->user()->id,
            'status' => 'pending',
        ]);

        Message::create([
            'user_id' => $request->user()->id,
            'title' => 'VIP 升级申请已提交',
            'content' => '您已申请「'.$grade->name.'」，请等待客服审核。财富热线 13537887304',
        ]);

        return response()->json(['data' => ['id' => $app->id], 'message' => '申请已提交'], 201);
    }

    public function adPlaces()
    {
        return response()->json([
            'data' => AdPlace::query()->where('is_active', true)->orderBy('sort')->get(),
        ]);
    }
}
