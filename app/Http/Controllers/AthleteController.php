<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Athlete;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AthleteController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $athletes = Athlete::query()
            ->visibleTo($user)
            ->with(['teams:id,name,color', 'guardians:id,name,phone'])
            ->when($request->string('search')->toString(), fn (Builder $query, string $search) => $query->where(function (Builder $nested) use ($search): void {
                $nested->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('membership_number', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            }))
            ->when($request->integer('team'), fn (Builder $query, int $teamId) => $query->whereHas('teams', fn (Builder $team) => $team->whereKey($teamId)))
            ->when($request->string('status')->toString(), fn (Builder $query, string $status) => $query->where('is_active', $status === 'active'))
            ->orderByDesc('is_active')
            ->orderBy('last_name')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Athlete $athlete) => $this->athleteData($athlete));

        $teams = $this->availableTeams($request)->map(fn (Team $team) => [
            'id' => $team->id,
            'name' => $team->name,
            'color' => $team->color,
        ]);

        return Inertia::render('Athletes/Index', [
            'athletes' => $athletes,
            'teams' => $teams,
            'guardians' => $user->hasRole(UserRole::ClubAdmin) ? User::query()
                ->where('club_id', $user->club_id)
                ->where('role', UserRole::Guardian->value)
                ->where('is_active', true)
                ->orderBy('name')->get(['id', 'name', 'phone']) : [],
            'filters' => $request->only(['search', 'team', 'status']),
            'canManage' => $user->hasRole(UserRole::ClubAdmin),
        ]);
    }

    public function show(Request $request, Athlete $athlete): Response
    {
        $this->authorizeVisible($request, $athlete);
        $athlete->load(['teams.coaches:id,name', 'guardians:id,name,phone']);

        if ($request->user()->hasRole(UserRole::Finance)) {
            $athlete->medical_notes = null;
        }

        $attendance = $athlete->attendanceRecords()->with('trainingSession:id,title,session_date,status')
            ->where('status', '!=', 'unmarked')->latest('marked_at')->limit(8)->get();
        $attendanceTotal = $athlete->attendanceRecords()->where('status', '!=', 'unmarked')->count();
        $attendancePresent = $athlete->attendanceRecords()->whereIn('status', ['present', 'late'])->count();

        return Inertia::render('Athletes/Show', [
            'athlete' => [
                ...$this->athleteData($athlete, true),
                'attendance_rate' => $attendanceTotal ? (int) round(($attendancePresent / $attendanceTotal) * 100) : 0,
                'attendance' => $attendance->map(fn ($record) => [
                    'id' => $record->id,
                    'status' => $record->status,
                    'title' => $record->trainingSession->title,
                    'date' => $record->trainingSession->session_date->format('Y-m-d'),
                    'note' => $record->note,
                ]),
            ],
            'canManage' => $request->user()->hasRole(UserRole::ClubAdmin),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($request, $data): void {
            $athlete = Athlete::create([
                ...collect($data)->except(['team_id', 'guardian_id', 'guardian_relationship'])->all(),
                'club_id' => $request->user()->club_id,
                'membership_number' => $this->nextMembershipNumber($request->user()->club_id),
                'is_active' => true,
            ]);
            $this->syncRelations($athlete, $data);
        });

        return back()->with('success', 'ورزشکار با موفقیت ثبت و به تیم اضافه شد.');
    }

    public function update(Request $request, Athlete $athlete): RedirectResponse
    {
        $this->authorizeManager($request, $athlete);
        $data = $this->validated($request, $athlete);

        DB::transaction(function () use ($athlete, $data): void {
            $athlete->update(collect($data)->except(['team_id', 'guardian_id', 'guardian_relationship'])->all());
            $this->syncRelations($athlete, $data);
        });

        return back()->with('success', 'اطلاعات ورزشکار به‌روزرسانی شد.');
    }

    public function toggle(Request $request, Athlete $athlete): RedirectResponse
    {
        $this->authorizeManager($request, $athlete);
        $athlete->update(['is_active' => ! $athlete->is_active]);

        return back()->with('success', 'وضعیت عضویت ورزشکار تغییر کرد.');
    }

    public function photo(Request $request, Athlete $athlete): RedirectResponse
    {
        $this->authorizeVisible($request, $athlete);
        abort_unless($request->user()->hasRole(UserRole::ClubAdmin, UserRole::Athlete, UserRole::Guardian), 403);
        $data = $request->validate(['photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120']]);
        $path = $data['photo']->store("athletes/{$athlete->club_id}", 'public');
        if ($athlete->photo_path) Storage::disk('public')->delete($athlete->photo_path);
        $athlete->update(['photo_path' => $path]);
        return back()->with('success', 'عکس ورزشکار با موفقیت به‌روزرسانی شد.');
    }

    private function validated(Request $request, ?Athlete $athlete = null): array
    {
        $clubId = $request->user()->club_id;
        return $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'national_code' => ['nullable', 'digits:10', Rule::unique('athletes')->where('club_id', $clubId)->ignore($athlete)],
            'phone' => ['nullable', 'string', 'max:15'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'gender' => ['required', 'in:male,female'],
            'position' => ['nullable', 'in:پاسور,قدرتی‌زن,سرعتی‌زن,پشت‌خط‌زن,دریافت‌کننده,لیبرو'],
            'jersey_number' => ['nullable', 'integer', 'between:1,99'],
            'emergency_name' => ['nullable', 'string', 'max:120'],
            'emergency_phone' => ['nullable', 'string', 'max:15'],
            'medical_notes' => ['nullable', 'string', 'max:1000'],
            'team_id' => ['required', Rule::exists('teams', 'id')->where('club_id', $clubId)],
            'guardian_id' => ['nullable', Rule::exists('users', 'id')->where(fn ($query) => $query->where('club_id', $clubId)->where('role', UserRole::Guardian->value))],
            'guardian_relationship' => ['nullable', 'string', 'max:40'],
        ]);
    }

    private function syncRelations(Athlete $athlete, array $data): void
    {
        $athlete->teams()->sync([$data['team_id'] => ['joined_at' => now()->toDateString(), 'is_active' => true]]);
        if (! empty($data['guardian_id'])) {
            $athlete->guardians()->sync([$data['guardian_id'] => [
                'relationship' => $data['guardian_relationship'] ?: 'سرپرست',
                'is_primary' => true,
            ]]);
        } else {
            $athlete->guardians()->detach();
        }
    }

    private function athleteData(Athlete $athlete, bool $detailed = false): array
    {
        $data = [
            'id' => $athlete->id,
            'membership_number' => $athlete->membership_number,
            'first_name' => $athlete->first_name,
            'last_name' => $athlete->last_name,
            'full_name' => $athlete->full_name,
            'national_code' => $athlete->national_code,
            'phone' => $athlete->phone,
            'photo_url' => $athlete->photo_path ? Storage::disk('public')->url($athlete->photo_path) : null,
            'birth_date' => $athlete->birth_date?->format('Y-m-d'),
            'gender' => $athlete->gender,
            'position' => $athlete->position,
            'jersey_number' => $athlete->jersey_number,
            'is_active' => $athlete->is_active,
            'emergency_name' => $athlete->emergency_name,
            'emergency_phone' => $athlete->emergency_phone,
            'medical_notes' => $athlete->medical_notes,
            'teams' => $athlete->teams->map(fn (Team $team) => ['id' => $team->id, 'name' => $team->name, 'color' => $team->color]),
            'guardians' => $athlete->guardians->map(fn (User $guardian) => ['id' => $guardian->id, 'name' => $guardian->name, 'phone' => $guardian->phone, 'relationship' => $guardian->pivot->relationship]),
        ];

        if ($detailed) {
            $data['coaches'] = $athlete->teams->flatMap->coaches->unique('id')->map(fn (User $coach) => ['id' => $coach->id, 'name' => $coach->name])->values();
        }
        return $data;
    }

    private function availableTeams(Request $request)
    {
        $user = $request->user();
        return Team::query()->where('club_id', $user->club_id)->where('is_active', true)
            ->when($user->hasRole(UserRole::Coach), fn (Builder $query) => $query->whereHas('coaches', fn (Builder $coach) => $coach->whereKey($user->id)))
            ->orderBy('name')->get();
    }

    private function nextMembershipNumber(int $clubId): string
    {
        $next = (int) Athlete::query()->where('club_id', $clubId)->max('id') + 1001;
        do {
            $number = 'VB-'.now()->format('y').'-'.str_pad((string) $next++, 4, '0', STR_PAD_LEFT);
        } while (Athlete::query()->where('membership_number', $number)->exists());
        return $number;
    }

    private function authorizeVisible(Request $request, Athlete $athlete): void
    {
        abort_unless(Athlete::query()->visibleTo($request->user())->whereKey($athlete)->exists(), 404);
    }

    private function authorizeManager(Request $request, Athlete $athlete): void
    {
        abort_unless($request->user()->hasRole(UserRole::ClubAdmin) && $athlete->club_id === $request->user()->club_id, 404);
    }
}
