<?php

namespace App\Livewire\Department;

use App\Models\Department;
use App\Models\ClearanceItem;
use App\Models\ClearancePeriod;
use App\Models\ClearanceRequest;
use App\Models\StudentDetail;
use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout; // 1. Add this import
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.app')] // 
class DepartmentOverview extends Component
{
    public $department;
    public $departmentId;
    public $activePeriod;
    
    // Analytics
    public $totalStudents = 0;
    public $completedCount = 0;
    public $inProgressCount = 0;
    public $pendingCount = 0;
    public $noRequestCount = 0;
    public $completionPercentage = 0;
    
    // Year level breakdown
    public $yearLevelStats = [];

    // Year level drill-down
    public $selectedYearLevel = null;
    public $yearLevelDetails = [];

    public function mount($departmentId)
    {
        $this->departmentId = $departmentId;
        $this->department = Department::with(['manager.staffDetail', 'students'])
            ->findOrFail($departmentId);

        // Check if user has access
        $this->authorizeAccess();
        
        $this->activePeriod = ClearancePeriod::where('is_active', true)->first();
        $this->loadAnalytics();
    }

    protected function authorizeAccess()
    {
        $user = Auth::user();
        
        // Superadmin can access everything
        if ($user->role === 'superadmin') {
            return;
        }

        // Check if user is manager or signatory
        $isManager = $this->department->manager_id === $user->id;
        $isSignatory = $user->departmentSignatories->contains($this->department->id);

        if (!$isManager && !$isSignatory) {
            abort(403, 'You do not have access to this department.');
        }
    }
    
    protected function loadAnalytics()
    {
        if (!$this->activePeriod) {
            return;
        }
        
        // Get all students in this department
        $departmentStudents = StudentDetail::where('department_id', $this->departmentId)->get();
        $this->totalStudents = $departmentStudents->count();
        
        if ($this->totalStudents === 0) {
            return;
        }
        
        $studentUserIds = $departmentStudents->pluck('user_id')->toArray();
        
        // Get clearance requests for these students in the active period
        $requests = ClearanceRequest::whereIn('student_id', $studentUserIds)
            ->where('period_id', $this->activePeriod->id)
            ->get();
        
        $this->completedCount = $requests->where('status', 'completed')->count();
        $this->inProgressCount = $requests->where('status', 'in_progress')->count();
        $this->pendingCount = $requests->where('status', 'pending')->count();
        $this->noRequestCount = $this->totalStudents - $requests->count();
        
        $this->completionPercentage = round(($this->completedCount / $this->totalStudents) * 100, 1);
        
        // Year level breakdown
        $this->yearLevelStats = [];
        $yearLevels = $departmentStudents->groupBy('year_level');
        
        foreach ($yearLevels as $yearLevel => $students) {
            $yearStudentIds = $students->pluck('user_id')->toArray();
            $yearRequests = ClearanceRequest::whereIn('student_id', $yearStudentIds)
                ->where('period_id', $this->activePeriod->id)
                ->get();
            
            $yearCompleted = $yearRequests->where('status', 'completed')->count();
            $yearTotal = $students->count();
            
            $this->yearLevelStats[] = [
                'year_level' => $yearLevel,
                'total' => $yearTotal,
                'completed' => $yearCompleted,
                'in_progress' => $yearRequests->where('status', 'in_progress')->count(),
                'pending' => $yearRequests->where('status', 'pending')->count(),
                'no_request' => $yearTotal - $yearRequests->count(),
                'percentage' => $yearTotal > 0 ? round(($yearCompleted / $yearTotal) * 100, 1) : 0,
            ];
        }
        
        // Sort by year level
        usort($this->yearLevelStats, fn($a, $b) => $a['year_level'] <=> $b['year_level']);
    }

    public function selectYearLevel($yearLevel)
    {
        if ($this->selectedYearLevel === $yearLevel) {
            $this->selectedYearLevel = null;
            $this->yearLevelDetails = [];
            return;
        }

        $this->selectedYearLevel = $yearLevel;
        $this->loadYearLevelDetails($yearLevel);
    }

    protected function loadYearLevelDetails($yearLevel): void
    {
        if (!$this->activePeriod) {
            $this->yearLevelDetails = [];
            return;
        }

        $this->yearLevelDetails = $this->getYearLevelDetails($yearLevel);
    }

    protected function getYearLevelDetails($yearLevel): array
    {
        if (!$this->activePeriod) {
            return [];
        }

        $students = StudentDetail::where('department_id', $this->departmentId)
            ->where('year_level', $yearLevel)
            ->get();

        return $this->buildStudentDetails($students);
    }

    protected function buildStudentDetails(Collection $students, bool $sortByStatus = true): array
    {
        if ($students->isEmpty()) {
            return [];
        }

        $studentUserIds = $students->pluck('user_id')->toArray();

        $requests = ClearanceRequest::whereIn('student_id', $studentUserIds)
            ->where('period_id', $this->activePeriod->id)
            ->get()
            ->keyBy('student_id');

        $requestIds = $requests->pluck('id')->toArray();

        $deptItems = ClearanceItem::whereIn('request_id', $requestIds)
            ->where('signable_type', 'App\\Models\\Department')
            ->where('signable_id', $this->departmentId)
            ->where('status', 'approved')
            ->with('signer.staffDetail')
            ->get()
            ->keyBy('request_id');

        $details = $students->map(function ($student) use ($requests, $deptItems) {
            $request  = $requests->get($student->user_id);
            $deptItem = $request ? $deptItems->get($request->id) : null;

            $clearanceCompleted = $request && $request->status === 'completed';
            $deptSigned         = $deptItem !== null;

            $signerName = null;
            if ($deptItem && $deptItem->signer) {
                $sd = $deptItem->signer->staffDetail;
                $signerName = $sd ? $sd->first_name . ' ' . $sd->last_name : $deptItem->signer->email;
            }

            // Sort weight: 0 = completed, 1 = dept signed only, 2 = in progress, 3 = no request
            $sortWeight = match(true) {
                $clearanceCompleted => 0,
                $deptSigned         => 1,
                $request !== null   => 2,
                default             => 3,
            };

            return [
                'name'                   => $student->first_name . ' ' . $student->last_name,
                'year_level'             => $student->year_level,
                'student_id'             => $student->student_id,
                'clearance_completed'    => $clearanceCompleted,
                'clearance_completed_at' => $clearanceCompleted && $request->completed_at
                    ? $request->completed_at->format('M d, Y H:i')
                    : null,
                'dept_signed'            => $deptSigned,
                'dept_signed_at'         => $deptItem?->signed_at?->format('M d, Y H:i'),
                'signed_by'              => $signerName,
                'request_status'         => $request?->status ?? 'none',
                'sort_weight'            => $sortWeight,
            ];
        });

        if ($sortByStatus) {
            return $details->sortBy('sort_weight')->values()->toArray();
        }

        return $details->values()->toArray();
    }

    public function exportYearLevelStudents($yearLevel): ?StreamedResponse
    {
        if (!$this->activePeriod) {
            return null;
        }

        $details = $this->getYearLevelDetails($yearLevel);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Year ' . $yearLevel);

        $headers = [
            'Student',
            'ID',
            'Status',
            'Dept. Signed At',
            'Signed By',
            'Clearance Completed At',
        ];

        foreach ($headers as $index => $header) {
            $column = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue($column . '1', $header);
        }

        foreach ($details as $rowIndex => $detail) {
            $status = $this->formatStatusLabel($detail);

            $row = $rowIndex + 2;
            $sheet->setCellValue('A' . $row, $detail['name']);
            $sheet->setCellValue('B' . $row, $detail['student_id']);
            $sheet->setCellValue('C' . $row, $status);
            $sheet->setCellValue('D' . $row, $detail['dept_signed_at'] ?? '—');
            $sheet->setCellValue('E' . $row, $detail['signed_by'] ?? '—');
            $sheet->setCellValue('F' . $row, $detail['clearance_completed_at'] ?? '—');
        }

        foreach (range('A', 'F') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $filename = sprintf(
            '%s-year-%s-students-%s.xlsx',
            Str::slug($this->department->name),
            $yearLevel,
            now()->format('Ymd_His')
        );

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function exportDepartmentStudents(): ?StreamedResponse
    {
        if (!$this->activePeriod) {
            return null;
        }

        $students = StudentDetail::where('department_id', $this->departmentId)
            ->orderBy('year_level')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $details = $this->buildStudentDetails($students, false);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('All Students');

        $headers = [
            'Student',
            'ID',
            'Status',
            'Dept. Signed At',
            'Signed By',
            'Clearance Completed At',
        ];

        foreach ($headers as $index => $header) {
            $column = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue($column . '1', $header);
        }

        foreach ($details as $rowIndex => $detail) {
            $status = $this->formatStatusLabel($detail);

            $row = $rowIndex + 2;
            $sheet->setCellValue('A' . $row, $detail['name']);
            $sheet->setCellValue('B' . $row, $detail['student_id']);
            $sheet->setCellValue('C' . $row, $status);
            $sheet->setCellValue('D' . $row, $detail['dept_signed_at'] ?? '—');
            $sheet->setCellValue('E' . $row, $detail['signed_by'] ?? '—');
            $sheet->setCellValue('F' . $row, $detail['clearance_completed_at'] ?? '—');
        }

        foreach (range('A', 'F') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $filename = sprintf(
            '%s-all-students-%s.xlsx',
            Str::slug($this->department->name),
            now()->format('Ymd_His')
        );

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    protected function formatStatusLabel(array $detail): string
    {
        return match (true) {
            $detail['clearance_completed'] => '✓ Completed',
            $detail['dept_signed'] => '✓ Dept. Signed',
            in_array($detail['request_status'], ['in_progress', 'pending'], true) => 'In Progress',
            default => 'No Request',
        };
    }

    public function render()
    {
        return view('livewire.department.department-overview');
    }
}