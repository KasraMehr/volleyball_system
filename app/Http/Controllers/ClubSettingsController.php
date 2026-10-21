<?php

namespace App\Http\Controllers;

use App\Models\Season;
use App\Models\Venue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ClubSettingsController extends Controller
{
    public function index(Request $request): Response
    {
        $club = $request->user()->club;
        return Inertia::render('Settings/Index', [
            'club' => $club->only(['name', 'province', 'city', 'phone', 'email', 'website', 'registration_number', 'address']),
            'venues' => $club->venues()->orderByDesc('is_active')->orderBy('name')->get()->map(fn (Venue $venue) => [
                'id' => $venue->id, 'name' => $venue->name, 'address' => $venue->address,
                'capacity' => $venue->capacity, 'floor_type' => $venue->floor_type, 'is_active' => $venue->is_active,
            ]),
            'seasons' => $club->seasons()->orderByDesc('starts_on')->get()->map(fn (Season $season) => [
                'id' => $season->id, 'title' => $season->title, 'starts_on' => $season->starts_on->format('Y-m-d'),
                'ends_on' => $season->ends_on->format('Y-m-d'), 'is_active' => $season->is_active,
            ]),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'], 'province' => ['nullable', 'string', 'max:80'],
            'city' => ['nullable', 'string', 'max:80'], 'phone' => ['nullable', 'string', 'max:15'],
            'email' => ['nullable', 'email', 'max:150'], 'website' => ['nullable', 'url', 'max:200'],
            'registration_number' => ['nullable', 'string', 'max:80'], 'address' => ['nullable', 'string', 'max:500'],
        ]);
        $request->user()->club->update($data);
        return back()->with('success', 'اطلاعات باشگاه به‌روزرسانی شد.');
    }

    public function storeVenue(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'], 'address' => ['nullable', 'string', 'max:250'],
            'capacity' => ['nullable', 'integer', 'between:1,10000'], 'floor_type' => ['nullable', 'string', 'max:60'],
        ]);
        Venue::create([...$data, 'club_id' => $request->user()->club_id, 'is_active' => true]);
        return back()->with('success', 'سالن جدید به فهرست باشگاه اضافه شد.');
    }

    public function toggleVenue(Request $request, Venue $venue): RedirectResponse
    {
        abort_unless($venue->club_id === $request->user()->club_id, 404);
        $venue->update(['is_active' => ! $venue->is_active]);
        return back()->with('success', 'وضعیت سالن تغییر کرد.');
    }

    public function storeSeason(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'], 'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after:starts_on'], 'is_active' => ['boolean'],
        ]);
        DB::transaction(function () use ($request, $data): void {
            if ($data['is_active'] ?? false) {
                Season::where('club_id', $request->user()->club_id)->update(['is_active' => false]);
            }
            Season::create([...$data, 'club_id' => $request->user()->club_id]);
        });
        return back()->with('success', 'فصل ورزشی ثبت شد.');
    }

    public function activateSeason(Request $request, Season $season): RedirectResponse
    {
        abort_unless($season->club_id === $request->user()->club_id, 404);
        DB::transaction(function () use ($request, $season): void {
            Season::where('club_id', $request->user()->club_id)->update(['is_active' => false]);
            $season->update(['is_active' => true]);
        });
        return back()->with('success', 'فصل فعال باشگاه تغییر کرد.');
    }
}
