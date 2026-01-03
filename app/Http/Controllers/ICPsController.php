<?php

namespace App\Http\Controllers;

use App\Models\ICP;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;
use Inertia\Response;

class ICPsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('ICPs/Index', [
            'icps' => Auth::user()->account->icps()
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(fn ($icp) => [
                    'id' => $icp->id,
                    'name' => $icp->name,
                    'description' => $icp->description,
                    'min_score' => $icp->min_score,
                    'is_active' => $icp->is_active,
                    'leads_count' => $icp->leads()->count(),
                    'created_at' => $icp->created_at->format('Y-m-d'),
                ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('ICPs/Create');
    }

    public function store(): RedirectResponse
    {
        $validated = Request::validate([
            'name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'company_size_min' => ['nullable', 'array'],
            'company_size_max' => ['nullable', 'array'],
            'industries' => ['nullable', 'array'],
            'locations' => ['nullable', 'array'],
            'job_titles' => ['nullable', 'array'],
            'departments' => ['nullable', 'array'],
            'technologies' => ['nullable', 'array'],
            'keywords' => ['nullable', 'array'],
            'weight_company_size' => ['nullable', 'integer', 'min:0', 'max:100'],
            'weight_industry' => ['nullable', 'integer', 'min:0', 'max:100'],
            'weight_location' => ['nullable', 'integer', 'min:0', 'max:100'],
            'weight_job_title' => ['nullable', 'integer', 'min:0', 'max:100'],
            'weight_behavioral' => ['nullable', 'integer', 'min:0', 'max:100'],
            'min_score' => ['required', 'integer', 'min:0', 'max:100'],
            'is_active' => ['boolean'],
        ]);

        Auth::user()->account->icps()->create($validated);

        return Redirect::route('icps')->with('success', 'ICP created.');
    }

    public function edit(ICP $icp): Response
    {
        return Inertia::render('ICPs/Edit', [
            'icp' => [
                'id' => $icp->id,
                'name' => $icp->name,
                'description' => $icp->description,
                'company_size_min' => $icp->company_size_min,
                'company_size_max' => $icp->company_size_max,
                'industries' => $icp->industries ?? [],
                'locations' => $icp->locations ?? [],
                'job_titles' => $icp->job_titles ?? [],
                'departments' => $icp->departments ?? [],
                'technologies' => $icp->technologies ?? [],
                'keywords' => $icp->keywords ?? [],
                'weight_company_size' => $icp->weight_company_size,
                'weight_industry' => $icp->weight_industry,
                'weight_location' => $icp->weight_location,
                'weight_job_title' => $icp->weight_job_title,
                'weight_behavioral' => $icp->weight_behavioral,
                'min_score' => $icp->min_score,
                'is_active' => $icp->is_active,
            ],
        ]);
    }

    public function update(ICP $icp): RedirectResponse
    {
        $validated = Request::validate([
            'name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'company_size_min' => ['nullable', 'array'],
            'company_size_max' => ['nullable', 'array'],
            'industries' => ['nullable', 'array'],
            'locations' => ['nullable', 'array'],
            'job_titles' => ['nullable', 'array'],
            'departments' => ['nullable', 'array'],
            'technologies' => ['nullable', 'array'],
            'keywords' => ['nullable', 'array'],
            'weight_company_size' => ['nullable', 'integer', 'min:0', 'max:100'],
            'weight_industry' => ['nullable', 'integer', 'min:0', 'max:100'],
            'weight_location' => ['nullable', 'integer', 'min:0', 'max:100'],
            'weight_job_title' => ['nullable', 'integer', 'min:0', 'max:100'],
            'weight_behavioral' => ['nullable', 'integer', 'min:0', 'max:100'],
            'min_score' => ['required', 'integer', 'min:0', 'max:100'],
            'is_active' => ['boolean'],
        ]);

        $icp->update($validated);

        return Redirect::back()->with('success', 'ICP updated.');
    }

    public function destroy(ICP $icp): RedirectResponse
    {
        $icp->delete();

        return Redirect::back()->with('success', 'ICP deleted.');
    }
}
