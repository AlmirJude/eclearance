<?php

namespace App\Livewire\Office;

use App\Models\Office;
use App\Models\ClearanceItem;
use App\Models\ClearancePeriod;
use App\Models\ClearanceRequest;
use App\Models\Department;
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
class OfficeOverview extends Component
{
    public $office;
    public $officeId;
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
    
    // Filter
    public $departmentFilter = '';
    public $departments = [];

    public function mount($officeId)
    {
        $this->officeId = $officeId;
        $this->office = Office::with(['manager.staffDetail', 'signatories'])
            ->findOrFail($officeId);

        // Check if user has access
        $this->authorizeAccess();
        
        $this->activePeriod = ClearancePeriod::where('is_active', true)->first();
        $this->loadDepartments();
        $this->loadAnalytics();
    }
    
    protected function loadDepartments()
    {
        // Get all departments
        $this->departments = Department::orderBy('name')->get();
    }
    
    public function updatedDepartmentFilter()
    {
        $this->selectedYearLevel = null;
        $this->yearLevelDetails = [];
        $this->loadAnalytics();
    }

    protected function authorizeAccess()
    {
        $user = Auth::user();
        
        // Superadmin can access everything
        if ($user->role === 'superadmin') {
            return;
        }

        // Admin can access everything
        if ($user->role === 'admin') {
            return;
        }

        // Check if user is manager or signatory
        $isManager = $this->office->manager_id === $user->id;
        $isSignatory = DB::table('office_signatories')
            ->where('office_id', $this->office->id)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->exists();

        if (!$isManager && !$isSignatory) {
            abort(403, 'You do not have access to this office.');
        }
    }
    
    protected function loadAnalytics()
    {
        if (!$this->activePeriod) {
            return;
        }

        $students = $this->getScopedStudentsQuery()->get();
        $this->totalStudents = $students->count();
        
        if ($this->totalStudents === 0) {
            $this->completedCount = 0;
            $this->inProgressCount = 0;
            $this->pendingCount = 0;
            $this->noRequestCount = 0;
            $this->completionPercentage = 0;
            $this->yearLevelStats = [];
            return;
        }

        $details = collect($this->buildStudentDetails($students, false));

        $this->completedCount = $details
            ->where('unit_signed', true)
            ->count();

        $this->inProgressCount = $details
            ->filter(fn($detail) => !$detail['unit_signed'] && $detail['request_status'] === 'in_progress')
            ->count();

        $this->pendingCount = $details
            ->filter(fn($detail) => !$detail['unit_signed'] && $detail['request_status'] === 'pending')
            ->count();

        $this->noRequestCount = $details
            ->where('request_status', 'none')
            ->count();
        
        $this->completionPercentage = $this->totalStudents > 0 
            ? round(($this->completedCount / $this->totalStudents) * 100, 1) 
            : 0;
        
        // Year level breakdown
        $this->yearLevelStats = [];
        $yearLevels = $details->groupBy('year_level');
        
        foreach ($yearLevels as $yearLevel => $students) {
            $yearTotal = $students->count();
            $yearCompleted = $students->where('unit_signed', true)->count();
            
            $this->yearLevelStats[] = [
                'year_level' => $yearLevel,
                'total' => $yearTotal,
                'completed' => $yearCompleted,
                'in_progress' => $students->filter(fn($detail) => !$detail['unit_signed'] && $detail['request_status'] === 'in_progress')->count(),
                'pending' => $students->filter(fn($detail) => !$detail['unit_signed'] && $detail['request_status'] === 'pending')->count(),
                'no_request' => $students->where('request_status', 'none')->count(),
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

    protected function getScopedStudentsQuery()
    {
        return StudentDetail::query()
            ->when($this->departmentFilter, fn($query) => $query->where('department_id', $this->departmentFilter));
    }

    protected function loadYearLevelDetails($yearLevel): void
    {
        if (!$this->activePeriod) {
            $this->yearLevelDetails = [];
            return;
        }

        $students = $this->getScopedStudentsQuery()
            ->where('year_level', $yearLevel)
            ->get();

        $this->yearLevelDetails = $this->buildStudentDetails($students, true);
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

        $officeItems = ClearanceItem::whereIn('request_id', $requestIds)
            ->where('signable_type', 'App\\Models\\Office')
            ->where('signable_id', $this->officeId)
            ->where('status', 'approved')
            ->with('signer.staffDetail')
            ->get()
            ->keyBy('request_id');

        $details = $students->map(function ($student) use ($requests, $officeItems) {
            $request = $requests->get($student->user_id);
            $officeItem = $request ? $officeItems->get($request->id) : null;

            $clearanceCompleted = $request && $request->status === 'completed';
            $officeSigned = $officeItem !== null;

            $signerName = null;
            if ($officeItem && $officeItem->signer) {
                $sd = $officeItem->signer->staffDetail;
                $signerName = $sd ? $sd->first_name . ' ' . $sd->last_name : $officeItem->signer->email;
            }

            $sortWeight = match (true) {
                $clearanceCompleted => 0,
                $officeSigned => 1,
                $request !== null => 2,
                default => 3,
            };

            return [
                'name' => $student->first_name . ' ' . $student->last_name,
                'last_name' => $student->last_name,
                'first_name' => $student->first_name,
                'year_level' => $student->year_level,
                'student_id' => $student->student_id,
                'clearance_completed' => $clearanceCompleted,
                'clearance_completed_at' => $clearanceCompleted && $request->completed_at
                    ? $request->completed_at->format('M d, Y H:i')
                    : null,
                'unit_signed' => $officeSigned,
                'unit_signed_at' => $officeItem?->signed_at?->format('M d, Y H:i'),
                'signed_by' => $signerName,
                'request_status' => $request?->status ?? 'none',
                'sort_weight' => $sortWeight,
            ];
        });

        if ($sortByStatus) {
            return $details->sortBy('sort_weight')->values()->toArray();
        }

        return $details->values()->toArray();
    }

    protected function formatStatusLabel(array $detail): string
    {
        return match (true) {
            $detail['clearance_completed'] => '✓ Completed',
            $detail['unit_signed'] => '✓ Office Signed',
            in_array($detail['request_status'], ['in_progress', 'pending'], true) => 'In Progress',
            default => 'No Request',
        };
    }

    public function exportYearLevelStudents($yearLevel): ?StreamedResponse
    {
        if (!$this->activePeriod) {
            return null;
        }

        $students = $this->getScopedStudentsQuery()
            ->where('year_level', $yearLevel)
            ->get();

        $details = $this->buildStudentDetails($students, true);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Year ' . $yearLevel);

        $headers = ['Student', 'ID', 'Status', 'Office Signed At', 'Signed By', 'Clearance Completed At'];

        foreach ($headers as $index => $header) {
            $column = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue($column . '1', $header);
        }

        foreach ($details as $rowIndex => $detail) {
            $row = $rowIndex + 2;
            $sheet->setCellValue('A' . $row, $detail['name']);
            $sheet->setCellValue('B' . $row, $detail['student_id']);
            $sheet->setCellValue('C' . $row, $this->formatStatusLabel($detail));
            $sheet->setCellValue('D' . $row, $detail['unit_signed_at'] ?? '—');
            $sheet->setCellValue('E' . $row, $detail['signed_by'] ?? '—');
            $sheet->setCellValue('F' . $row, $detail['clearance_completed_at'] ?? '—');
        }

        foreach (range('A', 'F') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $filename = sprintf(
            '%s-year-%s-students-%s.xlsx',
            Str::slug($this->office->name),
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

    public function exportAllStudents(): ?StreamedResponse
    {
        if (!$this->activePeriod) {
            return null;
        }

        $students = $this->getScopedStudentsQuery()
            ->orderBy('year_level')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $details = $this->buildStudentDetails($students, false);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('All Students');

        $headers = ['Student', 'ID', 'Status', 'Office Signed At', 'Signed By', 'Clearance Completed At'];

        foreach ($headers as $index => $header) {
            $column = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue($column . '1', $header);
        }

        foreach ($details as $rowIndex => $detail) {
            $row = $rowIndex + 2;
            $sheet->setCellValue('A' . $row, $detail['name']);
            $sheet->setCellValue('B' . $row, $detail['student_id']);
            $sheet->setCellValue('C' . $row, $this->formatStatusLabel($detail));
            $sheet->setCellValue('D' . $row, $detail['unit_signed_at'] ?? '—');
            $sheet->setCellValue('E' . $row, $detail['signed_by'] ?? '—');
            $sheet->setCellValue('F' . $row, $detail['clearance_completed_at'] ?? '—');
        }

        foreach (range('A', 'F') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $filename = sprintf(
            '%s-all-students-%s.xlsx',
            Str::slug($this->office->name),
            now()->format('Ymd_His')
        );

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function render()
    {
        return view('livewire.office.office-overview');
    }
}
