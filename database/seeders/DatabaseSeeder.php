<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Athlete;
use App\Models\AttendanceRecord;
use App\Models\Club;
use App\Models\FeePlan;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PerformanceAssessment;
use App\Models\Team;
use App\Models\TrainingSession;
use App\Models\User;
use App\Models\Venue;
use App\Models\Season;
use App\Models\ClubNotification;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $club = Club::query()->updateOrCreate(
            ['name' => 'باشگاه پایلوت هیئت والیبال استان'],
            ['province' => 'استان پایلوت', 'phone' => '02100000000', 'status' => 'active'],
        );

        foreach ([
            ['name' => 'سالن شماره ۱', 'capacity' => 120, 'floor_type' => 'کف‌پوش استاندارد', 'address' => 'مجموعه ورزشی مرکزی'],
            ['name' => 'سالن شماره ۲', 'capacity' => 180, 'floor_type' => 'پارکت', 'address' => 'مجموعه ورزشی مرکزی'],
            ['name' => 'سالن بدنسازی', 'capacity' => 35, 'floor_type' => 'کف‌پوش لاستیکی', 'address' => 'ساختمان تمرینات بدنی'],
        ] as $venue) {
            Venue::query()->updateOrCreate(['club_id' => $club->id, 'name' => $venue['name']], [...$venue, 'is_active' => true]);
        }
        Season::query()->updateOrCreate(['club_id' => $club->id, 'title' => 'فصل ۱۴۰۵–۱۴۰۶'], ['starts_on' => '2026-09-01', 'ends_on' => '2027-08-31', 'is_active' => true]);

        $admin = $this->user($club->id, 'مدیر سامانه', 'admin@volleyball.ir', '09120000000', UserRole::ClubAdmin);
        $finance = $this->user($club->id, 'زهرا احمدی', 'finance@volleyball.ir', '09120000001', UserRole::Finance);
        $coachOne = $this->user($club->id, 'علی محمدی', 'coach@volleyball.ir', '09120000002', UserRole::Coach);
        $coachTwo = $this->user($club->id, 'مریم کریمی', 'maryam@volleyball.ir', '09120000003', UserRole::Coach);
        $coachThree = $this->user($club->id, 'سارا رضایی', 'sara@volleyball.ir', '09120000004', UserRole::Coach);
        $athleteUser = $this->user($club->id, 'آراد حسینی', 'athlete@volleyball.ir', '09120000005', UserRole::Athlete);
        $guardian = $this->user($club->id, 'رضا حسینی', 'parent@volleyball.ir', '09120000006', UserRole::Guardian);

        foreach (User::query()->where('club_id', $club->id)->get() as $recipient) {
            ClubNotification::query()->updateOrCreate(
                ['user_id' => $recipient->id, 'title' => 'خوش‌آمدید به والیبال من'],
                ['club_id' => $club->id, 'sender_id' => $admin->id, 'type' => 'announcement', 'message' => 'سامانه مدیریت باشگاه برای برنامه تمرین، شهریه و رکوردهای ورزشی آماده استفاده است.', 'channel' => 'in_app', 'delivery_status' => 'delivered', 'sent_at' => '2026-10-20 09:00:00'],
            );
        }

        $teams = collect([
            ['name' => 'نوجوانان پسر', 'code' => 'U17-M', 'gender' => 'male', 'age_group' => 'زیر ۱۷ سال', 'color' => '#1f7795'],
            ['name' => 'نونهالان دختر', 'code' => 'U14-F', 'gender' => 'female', 'age_group' => 'زیر ۱۴ سال', 'color' => '#df6c2e'],
            ['name' => 'جوانان دختر', 'code' => 'U20-F', 'gender' => 'female', 'age_group' => 'زیر ۲۰ سال', 'color' => '#6d5d8d'],
            ['name' => 'نونهالان پسر', 'code' => 'U14-M', 'gender' => 'male', 'age_group' => 'زیر ۱۴ سال', 'color' => '#397b66'],
        ])->mapWithKeys(function (array $data) use ($club): array {
            $team = Team::query()->updateOrCreate(['code' => $data['code']], [...$data, 'club_id' => $club->id, 'capacity' => 24, 'is_active' => true]);
            return [$data['code'] => $team];
        });

        $coachOne->assignedTeams()->sync([
            $teams['U17-M']->id => ['is_head_coach' => true, 'active_from' => '2026-09-01'],
            $teams['U14-M']->id => ['is_head_coach' => false, 'active_from' => '2026-09-01'],
        ]);
        $coachTwo->assignedTeams()->sync([$teams['U14-F']->id => ['is_head_coach' => true, 'active_from' => '2026-09-01']]);
        $coachThree->assignedTeams()->sync([$teams['U20-F']->id => ['is_head_coach' => true, 'active_from' => '2026-09-01']]);

        $rosters = [
            'U17-M' => [
                ['آراد', 'حسینی', 12, 'قدرتی‌زن', $athleteUser->id],
                ['ماهان', 'رضایی', 7, 'پاسور', null],
                ['بردیا', 'کریمی', 3, 'لیبرو', null],
                ['سام', 'نادری', 18, 'سرعتی‌زن', null],
                ['امیرعلی', 'مرادی', 9, 'پشت‌خط‌زن', null],
                ['پارسا', 'اکبری', 5, 'دریافت‌کننده', null],
            ],
            'U14-M' => [
                ['کیان', 'محمدی', 4, 'پاسور', null],
                ['رادین', 'قاسمی', 11, 'قدرتی‌زن', null],
            ],
            'U14-F' => [
                ['نگین', 'احمدی', 8, 'پاسور', null],
                ['رها', 'کریمی', 14, 'قدرتی‌زن', null],
                ['هلیا', 'صادقی', 2, 'لیبرو', null],
            ],
            'U20-F' => [
                ['سارا', 'حیدری', 10, 'سرعتی‌زن', null],
                ['مهسا', 'نوروزی', 6, 'پاسور', null],
            ],
        ];

        $sequence = 1001;
        foreach ($rosters as $teamCode => $players) {
            foreach ($players as [$firstName, $lastName, $number, $position, $userId]) {
                $athlete = Athlete::query()->updateOrCreate(
                    ['membership_number' => 'VB-'.$sequence],
                    [
                        'club_id' => $club->id,
                        'user_id' => $userId,
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'phone' => null,
                        'gender' => str_ends_with($teamCode, '-F') ? 'female' : 'male',
                        'position' => $position,
                        'jersey_number' => $number,
                        'is_active' => true,
                    ],
                );
                $athlete->teams()->syncWithoutDetaching([$teams[$teamCode]->id => ['joined_at' => '2026-09-01', 'is_active' => true]]);
                if ($userId === $athleteUser->id) {
                    $athlete->guardians()->sync([$guardian->id => ['relationship' => 'پدر', 'is_primary' => true]]);
                }
                $sequence++;
            }
        }

        $sampleSessions = [
            ['U17-M', 'تمرین تکنیک حمله', '2026-10-19', '17:30', '19:00', 'سالن شماره ۲', 'scheduled'],
            ['U14-F', 'تمرین دریافت و سرویس', '2026-10-18', '16:00', '17:30', 'سالن شماره ۱', 'completed'],
            ['U17-M', 'بدنسازی و پرش', '2026-10-16', '18:00', '19:30', 'سالن بدنسازی', 'completed'],
            ['U20-F', 'تمرین تاکتیکی', '2026-10-15', '17:00', '18:30', 'سالن شماره ۲', 'completed'],
            ['U14-M', 'آموزش پایه پاس', '2026-10-21', '15:30', '17:00', 'سالن شماره ۱', 'scheduled'],
        ];

        foreach ($sampleSessions as $sessionIndex => [$teamCode, $title, $date, $start, $end, $location, $status]) {
            $team = $teams[$teamCode];
            $session = TrainingSession::query()->updateOrCreate(
                ['team_id' => $team->id, 'session_date' => $date.' 00:00:00', 'title' => $title],
                [
                    'club_id' => $club->id,
                    'coach_id' => $team->coaches()->first()?->id,
                    'starts_at' => $start,
                    'ends_at' => $end,
                    'location' => $location,
                    'status' => $status,
                ],
            );

            foreach ($team->athletes as $athleteIndex => $athlete) {
                $attendanceStatus = $status === 'scheduled'
                    ? 'unmarked'
                    : match (($athleteIndex + $sessionIndex) % 7) {
                        0 => 'absent',
                        1 => 'late',
                        2 => 'excused',
                        default => 'present',
                    };
                AttendanceRecord::query()->updateOrCreate(
                    ['training_session_id' => $session->id, 'athlete_id' => $athlete->id],
                    [
                        'marked_by' => $attendanceStatus === 'unmarked' ? null : ($session->coach_id ?? $admin->id),
                        'status' => $attendanceStatus,
                        'arrival_time' => $attendanceStatus === 'late' ? date('H:i', strtotime($start.' +15 minutes')) : null,
                        'note' => $attendanceStatus === 'excused' ? 'اطلاع قبلی خانواده' : null,
                        'marked_at' => $attendanceStatus === 'unmarked' ? null : $date.' '.$end,
                    ],
                );
            }
        }

        $financeTeamIndex = 0;
        foreach ($teams as $team) {
            $amount = in_array($team->code, ['U17-M', 'U20-F'], true) ? 1800000 : 1500000;
            $plan = FeePlan::query()->updateOrCreate(
                ['club_id' => $club->id, 'team_id' => $team->id, 'title' => 'شهریه مهر ۱۴۰۵'],
                ['amount' => $amount, 'frequency' => 'monthly', 'is_active' => true],
            );

            foreach ($team->athletes as $athleteIndex => $athlete) {
                $invoice = Invoice::query()->updateOrCreate(
                    ['fee_plan_id' => $plan->id, 'athlete_id' => $athlete->id],
                    [
                        'club_id' => $club->id, 'title' => $plan->title, 'amount' => $amount, 'discount' => 0,
                        'due_date' => $athleteIndex % 3 === 0 ? '2026-10-15' : '2026-10-25',
                        'status' => 'unpaid', 'payment_token' => hash('sha256', 'demo-'.$athlete->membership_number),
                    ],
                );

                $paymentMode = ($athleteIndex + $financeTeamIndex) % 4;
                if ($paymentMode <= 1) {
                    $paymentAmount = $paymentMode === 0 ? $amount : min(600000, $amount);
                    Payment::query()->updateOrCreate(
                        ['invoice_id' => $invoice->id, 'reference_number' => 'SEED-'.$invoice->id],
                        [
                            'club_id' => $club->id, 'athlete_id' => $athlete->id, 'received_by' => $finance->id,
                            'amount' => $paymentAmount, 'method' => $paymentMode === 0 ? 'online' : 'card_transfer',
                            'status' => 'successful', 'paid_at' => '2026-10-14 12:00:00', 'notes' => 'پرداخت نمونه',
                        ],
                    );
                    $invoice->refreshStatus();
                }
            }
            $financeTeamIndex++;
        }

        foreach (Athlete::query()->where('club_id', $club->id)->with('teams.coaches')->get() as $athlete) {
            $baseHeight = $athlete->gender === 'female' ? 168 : 178;
            $height = $baseHeight + ($athlete->id % 9);
            $recorderId = $athlete->teams->first()?->coaches->first()?->id ?? $admin->id;
            foreach ([
                ['2026-06-20', 0, 0],
                ['2026-08-20', 1, 3],
                ['2026-10-14', 2, 6],
            ] as [$date, $growth, $jumpGrowth]) {
                $standingReach = $height + 52 + $growth;
                PerformanceAssessment::query()->updateOrCreate(
                    ['athlete_id' => $athlete->id, 'assessment_date' => $date.' 00:00:00'],
                    [
                        'club_id' => $club->id, 'recorded_by' => $recorderId,
                        'height_cm' => $height + ($growth * 0.4),
                        'weight_kg' => 58 + ($athlete->id % 13) + ($growth * 0.3),
                        'standing_reach_cm' => $standingReach,
                        'vertical_jump_cm' => 48 + ($athlete->id % 12) + $jumpGrowth,
                        'spike_reach_cm' => $standingReach + 55 + $jumpGrowth,
                        'block_reach_cm' => $standingReach + 42 + $jumpGrowth,
                        'agility_seconds' => 12.8 - (($athlete->id % 4) * 0.2) - ($growth * 0.12),
                        'coach_notes' => $growth === 2 ? 'پیشرفت مناسب؛ تمرکز بعدی روی گام آخر و زمان‌بندی دفاع.' : null,
                    ],
                );
            }
        }
    }

    private function user(int $clubId, string $name, string $email, string $phone, UserRole $role): User
    {
        return User::query()->updateOrCreate(
            ['email' => $email],
            [
                'club_id' => $clubId,
                'name' => $name,
                'phone' => $phone,
                'role' => $role,
                'is_active' => true,
                'password' => Hash::make('password'),
            ],
        );
    }
}
