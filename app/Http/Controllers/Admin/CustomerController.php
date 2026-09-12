<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    /**
     * List RobotClock customer accounts (organizations).
     */
    public function index(): Response
    {
        return Inertia::render('admin/Customers', [
            'customers' => Organization::withCount(['teams', 'employees'])
                ->orderBy('name')
                ->get()
                ->map(fn (Organization $organization) => [
                    'id' => $organization->id,
                    'name' => $organization->name,
                    'description' => $organization->description,
                    'active' => $organization->active,
                    'teams_count' => $organization->teams_count,
                    'employees_count' => $organization->employees_count,
                    'created_at' => $organization->created_at->toIso8601String(),
                ]),
        ]);
    }
}
