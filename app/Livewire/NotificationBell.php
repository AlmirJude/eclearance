<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\ClearanceItem;
use App\Models\ClearancePeriod;
use App\Services\ClearanceResolver;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout; // 1. Add this import

#[Layout('components.layouts.app')] // 
class NotificationBell extends Component
{
    public array $notifications = [];
    public int $unreadCount = 0;

    public function mount(): void
    {
        $this->loadNotifications();
    }

    public function loadNotifications(): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $studentNotifs   = $user->isStudent() ? $this->getStudentNotifications($user) : ['items' => [], 'unread' => 0];
        $signatoryNotifs = $this->getSignatoryNotifications($user);

        $this->notifications = array_merge($signatoryNotifs['items'], $studentNotifs['items']);
        $this->unreadCount   = min(99, $studentNotifs['unread'] + $signatoryNotifs['unread']);
    }

    protected function getStudentNotifications($user): array
    {
        $lastSeen = Cache::get("notif_seen_{$user->id}", now()->subDays(30));

        $activePeriod = ClearancePeriod::where('is_active', true)->first();

        if (!$activePeriod) {
            return ['items' => [], 'unread' => 0];
        }

        $items = ClearanceItem::with(['signable' => function (\Illuminate\Database\Eloquent\Relations\MorphTo $morphTo) {
                $morphTo->morphWith([
                    \App\Models\HomeroomAssignment::class => ['adviser.staffDetail'],
                ]);
            }])
            ->whereHas('request', fn ($q) => $q
                ->where('student_id', $user->id)
                ->where('period_id', $activePeriod->id)
            )
            ->where('status', '!=', 'pending')
            ->orderByDesc('signed_at')
            ->get()
            ->map(function ($item) use ($lastSeen) {
                $entityName = $item->signable?->name ?? 'Unknown';
                $isNew = $item->signed_at && $item->signed_at->gt($lastSeen);

                return [
                    'type'     => 'student',
                    'id'       => $item->id,
                    'entity'   => $entityName,
                    'status'   => $item->status,
                    'remarks'  => $item->remarks,
                    'time'     => $item->signed_at?->diffForHumans() ?? 'N/A',
                    'is_new'   => $isNew,
                    'url'      => route('clearance.student'),
                ];
            });

        return [
            'items'  => $items->values()->all(),
            'unread' => $items->where('is_new', true)->count(),
        ];
    }

    protected function getSignatoryNotifications($user): array
    {
        $entities = $this->getSignatoryEntities($user);

        if (empty($entities)) {
            return ['items' => [], 'unread' => 0];
        }

        $resolver   = new ClearanceResolver();
        $notifs     = [];
        $totalReady = 0;

        foreach ($entities as $entity) {
            // Fast path: no dependency rules means every pending item is ready to sign
            $hasDependencies = DB::table('clearance_dependencies')
                ->where('dependent_type', $entity['type'])
                ->where('dependent_id', $entity['id'])
                ->exists();

            $deptRestrictions = $entity['dept_restrictions'] ?? [];
            $yearRestrictions = $entity['year_restrictions'] ?? [];
            $hasRestrictions  = !empty($deptRestrictions) || !empty($yearRestrictions);

            if (!$hasDependencies) {
                $readyCount = ClearanceItem::where('status', 'pending')
                    ->where('signable_type', $entity['type'])
                    ->where('signable_id', $entity['id'])
                    ->when($hasRestrictions, function ($q) use ($deptRestrictions, $yearRestrictions) {
                        $q->whereHas('request.student.studentDetail', function ($sq) use ($deptRestrictions, $yearRestrictions) {
                            if (!empty($deptRestrictions)) {
                                $sq->whereIn('department_id', $deptRestrictions);
                            }
                            if (!empty($yearRestrictions)) {
                                $sq->whereIn('year_level', $yearRestrictions);
                            }
                        });
                    })
                    ->count();
            } else {
                // Filter each pending item through the dependency resolver
                $readyCount = ClearanceItem::with('request')
                    ->where('status', 'pending')
                    ->where('signable_type', $entity['type'])
                    ->where('signable_id', $entity['id'])
                    ->when($hasRestrictions, function ($q) use ($deptRestrictions, $yearRestrictions) {
                        $q->whereHas('request.student.studentDetail', function ($sq) use ($deptRestrictions, $yearRestrictions) {
                            if (!empty($deptRestrictions)) {
                                $sq->whereIn('department_id', $deptRestrictions);
                            }
                            if (!empty($yearRestrictions)) {
                                $sq->whereIn('year_level', $yearRestrictions);
                            }
                        });
                    })
                    ->get()
                    ->filter(fn($item) => $resolver->canSign($item->signable_type, $item->signable_id, $item->request)['can_sign'])
                    ->count();
            }

            if ($readyCount > 0) {
                $entityTypeParam = match($entity['type']) {
                    'App\\Models\\Department'        => 'department',
                    'App\\Models\\Club'              => 'club',
                    'App\\Models\\Office'            => 'office',
                    'App\\Models\\StudentGovernment' => 'student-government',
                    'homeroom_adviser'               => 'homeroom',
                    default                          => null,
                };

                $url = $entityTypeParam
                    ? route('clearance.signatory', ['entityType' => $entityTypeParam, 'entityId' => $entity['id']])
                    : route('clearance.signatory');

                $notifs[] = [
                    'type'        => 'signatory',
                    'entity'      => $entity['name'],
                    'label'       => $entity['label'],
                    'pending'     => $readyCount,
                    'display'     => $readyCount > 99 ? '99+' : (string) $readyCount,
                    'entity_type' => $entity['type'],
                    'entity_id'   => $entity['id'],
                    'url'         => $url,
                ];
                $totalReady += $readyCount;
            }
        }

        return [
            'items'  => $notifs,
            'unread' => $totalReady,
        ];
    }

    protected function getSignatoryEntities($user): array
    {
        $entities = [];

        $clubRows = DB::table('club_signatories')
            ->join('clubs', 'club_signatories.club_id', '=', 'clubs.id')
            ->where('club_signatories.user_id', $user->id)
            ->where('club_signatories.is_active', true)
            ->select('clubs.id', 'clubs.name', DB::raw("'App\\\\Models\\\\Club' as type"))
            ->get();
        foreach ($clubRows as $row) {
            $entities[] = ['id' => $row->id, 'name' => $row->name, 'type' => $row->type, 'label' => 'Club'];
        }

        $officeRows = DB::table('office_signatories')
            ->join('offices', 'office_signatories.office_id', '=', 'offices.id')
            ->where('office_signatories.user_id', $user->id)
            ->where('office_signatories.is_active', true)
            ->select('offices.id', 'offices.name', DB::raw("'App\\\\Models\\\\Office' as type"),
                     'office_signatories.departments', 'office_signatories.year_levels')
            ->get();
        foreach ($officeRows as $row) {
            $entities[] = [
                'id'               => $row->id,
                'name'             => $row->name,
                'type'             => $row->type,
                'label'            => 'Office',
                'dept_restrictions' => json_decode($row->departments ?? '[]', true) ?? [],
                'year_restrictions' => json_decode($row->year_levels  ?? '[]', true) ?? [],
            ];
        }

        $deptRows = DB::table('department_signatories')
            ->join('departments', 'department_signatories.department_id', '=', 'departments.id')
            ->where('department_signatories.user_id', $user->id)
            ->where('department_signatories.is_active', true)
            ->select('departments.id', 'departments.name', DB::raw("'App\\\\Models\\\\Department' as type"))
            ->get();
        foreach ($deptRows as $row) {
            $entities[] = ['id' => $row->id, 'name' => $row->name, 'type' => $row->type, 'label' => 'Department'];
        }

        $homeroomRows = DB::table('homeroom_assignments')
            ->join('departments', 'homeroom_assignments.department_id', '=', 'departments.id')
            ->where('homeroom_assignments.adviser_id', $user->id)
            ->where('homeroom_assignments.is_active', true)
            ->select('homeroom_assignments.id', 'departments.name', DB::raw("'homeroom_adviser' as type"))
            ->get();
        foreach ($homeroomRows as $row) {
            $entities[] = ['id' => $row->id, 'name' => $row->name, 'type' => $row->type, 'label' => 'Homeroom'];
        }

        $sgRows = DB::table('student_government_officers')
            ->join('student_governments', 'student_government_officers.student_government_id', '=', 'student_governments.id')
            ->where('student_government_officers.user_id', $user->id)
            ->where('student_government_officers.can_sign', true)
            ->where('student_government_officers.is_active', true)
            ->select('student_governments.id', 'student_governments.name', DB::raw("'App\\\\Models\\\\StudentGovernment' as type"))
            ->get();
        foreach ($sgRows as $row) {
            $entities[] = ['id' => $row->id, 'name' => $row->name, 'type' => $row->type, 'label' => 'Student Gov'];
        }

        return $entities;
    }

    public function markAsSeen(): void
    {
        $user = Auth::user();
        Cache::put("notif_seen_{$user->id}", now(), now()->addDays(30));
        $this->loadNotifications();
    }

    public function render()
    {
        return view('livewire.notification-bell');
    }
}
