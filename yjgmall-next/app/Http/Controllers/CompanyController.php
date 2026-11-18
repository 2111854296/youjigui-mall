<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $query = Company::published()->latest();
        if ($request->filled('q')) {
            $query->where('name', 'like', '%'.$request->q.'%');
        }
        $items = $query->paginate(16)->withQueryString();

        return view('company.index', compact('items'));
    }

    public function show(Company $company)
    {
        abort_unless($company->status === 'approved', 404);
        $company->increment('views');
        $company->load('user');

        return view('company.show', compact('company'));
    }
}
