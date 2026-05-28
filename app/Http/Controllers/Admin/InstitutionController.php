<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InstitutionController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Institution::class);

        $query = Institution::withCount('vacancies')->latest();

        if ($search = $request->query('search')) {
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%$search%")
                ->orWhere('code', 'like', "%$search%")
                ->orWhere('short_name', 'like', "%$search%")
            );
        }
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return view('admin.institutions.index', [
            'institutions' => $query->paginate(20)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Institution::class);

        return view('admin.institutions.create', [
            'institution' => new Institution,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Institution::class);

        $data = $request->validate($this->rules());
        $data['code'] = strtoupper($data['code']);

        Institution::create($data);

        return redirect()->route('admin.institutions.index')
            ->with('success', __('admin.institution_created'));
    }

    public function show(Institution $institution): View
    {
        $this->authorize('view', $institution);

        $institution->load('vacancies');

        return view('admin.institutions.show', compact('institution'));
    }

    public function edit(Institution $institution): View
    {
        $this->authorize('update', $institution);

        return view('admin.institutions.edit', compact('institution'));
    }

    public function update(Request $request, Institution $institution): RedirectResponse
    {
        $this->authorize('update', $institution);

        $data = $request->validate($this->rules($institution->id));
        $data['code'] = strtoupper($data['code']);

        $institution->update($data);

        return redirect()->route('admin.institutions.index')
            ->with('success', __('admin.institution_updated'));
    }

    public function destroy(Institution $institution): RedirectResponse
    {
        $this->authorize('delete', $institution);

        $institution->delete();

        return redirect()->route('admin.institutions.index')
            ->with('success', __('admin.institution_deleted'));
    }

    public function activate(Institution $institution): RedirectResponse
    {
        $this->authorize('activate', $institution);

        $institution->update(['status' => 'active']);

        return back()->with('success', __('admin.institution_activated'));
    }

    public function deactivate(Institution $institution): RedirectResponse
    {
        $this->authorize('deactivate', $institution);

        $institution->update(['status' => 'inactive']);

        return back()->with('success', __('admin.institution_deactivated'));
    }

    private function rules(?string $ignoreId = null): array
    {
        return [
            'name'       => ['required', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:100'],
            'code'       => ['required', 'string', 'max:50',
                             Rule::unique('institutions', 'code')->ignore($ignoreId)->whereNull('deleted_at')],
            'type'       => ['nullable', 'string', 'max:100'],
            'website'    => ['nullable', 'url', 'max:255'],
            'email'      => ['nullable', 'email', 'max:255'],
            'phone'      => ['nullable', 'string', 'max:50'],
            'address'    => ['nullable', 'string', 'max:1000'],
            'latitude'   => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'  => ['nullable', 'numeric', 'between:-180,180'],
            'status'     => ['required', Rule::in(['active', 'inactive'])],
        ];
    }
}
