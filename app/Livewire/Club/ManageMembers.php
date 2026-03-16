<?php

namespace App\Livewire\Club;

use App\Models\Club;
use App\Models\Department;
use App\Models\StudentDetail;
use App\Models\User;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Livewire\Attributes\Layout; // 1. Add this import

#[Layout('components.layouts.app')] // 
class ManageMembers extends Component
{
    use WithFileUploads;

    public $clubId;
    public $club;
    public $members;
    public $departments;
    public $availableStudents;
    public $selectedStudent = '';
    public $studentSearch = '';
    public $memberSearch = '';
    public $memberDepartmentFilter = '';
    public $memberYearLevelFilter = '';
    public $showAddModal = false;

    // Import
    public $showImportModal = false;
    public $csvFile;
    public $importResults = null;

    public function mount($id)
    {
        $this->clubId = $id;
        $this->loadClubData();
    }

    public function loadClubData()
    {
        $this->club = Club::with('moderator')->findOrFail($this->clubId);

        $this->departments = Department::query()
            ->orderBy('name')
            ->get(['id', 'name', 'abbreviation']);

        $this->loadMembers();

        $this->loadAvailableStudents();
    }

    protected function loadMembers()
    {
        $query = User::whereHas('clubs', function($clubQuery) {
                $clubQuery->where('club_id', $this->clubId);
            })
            ->with('studentDetail.department')
            ->where('role', 'student');

        if (!empty($this->memberDepartmentFilter)) {
            $query->whereHas('studentDetail', function ($studentQuery) {
                $studentQuery->where('department_id', $this->memberDepartmentFilter);
            });
        }

        if (!empty($this->memberYearLevelFilter)) {
            $query->whereHas('studentDetail', function ($studentQuery) {
                $studentQuery->where('year_level', $this->memberYearLevelFilter);
            });
        }

        if (!empty($this->memberSearch)) {
            $search = trim($this->memberSearch);

            $query->where(function ($memberQuery) use ($search) {
                $memberQuery->where('email', 'like', "%{$search}%")
                    ->orWhereHas('studentDetail', function ($studentQuery) use ($search) {
                        $studentQuery->where('student_id', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                            ->orWhere('year_level', 'like', "%{$search}%")
                            ->orWhereHas('department', function ($departmentQuery) use ($search) {
                                $departmentQuery->where('name', 'like', "%{$search}%")
                                    ->orWhere('abbreviation', 'like', "%{$search}%");
                            });
                    });
            });
        }

        $this->members = $query->get();
    }

    public function updatedMemberSearch()
    {
        $this->loadMembers();
    }

    public function updatedMemberDepartmentFilter()
    {
        $this->loadMembers();
    }

    public function updatedMemberYearLevelFilter()
    {
        $this->loadMembers();
    }

    protected function loadAvailableStudents()
    {
        $query = User::whereDoesntHave('clubs', function($query) {
                $query->where('club_id', $this->clubId);
            })
            ->where('role', 'student')
            ->with('studentDetail.department');

        if (!empty($this->studentSearch)) {
            $search = trim($this->studentSearch);

            $query->whereHas('studentDetail', function ($studentQuery) use ($search) {
                $studentQuery->where('student_id', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                    ->orWhere('year_level', 'like', "%{$search}%")
                    ->orWhereHas('department', function ($departmentQuery) use ($search) {
                        $departmentQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('abbreviation', 'like', "%{$search}%");
                    });
            });
        }

        $this->availableStudents = $query->get();
    }

    public function updatedStudentSearch()
    {
        $this->loadAvailableStudents();
    }

    public function openAddModal()
    {
        $this->resetValidation('selectedStudent');
        $this->selectedStudent = '';
        $this->studentSearch = '';
        $this->showAddModal = true;
        $this->loadAvailableStudents();
    }

    public function closeAddModal()
    {
        $this->selectedStudent = '';
        $this->studentSearch = '';
        $this->showAddModal = false;
    }

    public function addMember()
    {
        $this->validate([
            'selectedStudent' => 'required|exists:users,id',
        ]);

        // Check if student is already a member
        $exists = DB::table('club_memberships')
            ->where('club_id', $this->clubId)
            ->where('student_id', $this->selectedStudent)
            ->exists();

        if (!$exists) {
            DB::table('club_memberships')->insert([
                'club_id'    => $this->clubId,
                'student_id' => $this->selectedStudent,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            session()->flash('success', 'Member added successfully.');
        } else {
            session()->flash('error', 'Student is already a member.');
        }

        $this->closeAddModal();
        $this->loadClubData();
    }

    public function removeMember($studentId)
    {
        DB::table('club_memberships')
            ->where('club_id', $this->clubId)
            ->where('student_id', $studentId)
            ->delete();

        session()->flash('success', 'Member removed successfully.');
        $this->loadClubData();
    }

    // ─── Import ────────────────────────────────────────────────────────────────

    public function openImportModal()
    {
        $this->csvFile     = null;
        $this->importResults = null;
        $this->showImportModal = true;
    }

    public function closeImportModal()
    {
        $this->csvFile       = null;
        $this->importResults = null;
        $this->showImportModal = false;
    }

    public function importCsv()
    {
        $this->validate([
            'csvFile' => 'required|file|max:5120|mimes:csv,txt,xlsx,xls',
        ], [
            'csvFile.required' => 'Please select a file.',
            'csvFile.mimes'    => 'The file must be a CSV (.csv) or Excel (.xlsx, .xls).',
            'csvFile.max'      => 'The file must not exceed 5 MB.',
        ]);

        $path = $this->csvFile->getRealPath();
        $ext  = strtolower($this->csvFile->getClientOriginalExtension());

        try {
            if (in_array($ext, ['xlsx', 'xls'])) {
                [$header, $dataRows] = $this->readExcelRows($path);
            } else {
                [$header, $dataRows] = $this->readCsvRows($path);
            }
        } catch (\Exception $e) {
            $this->addError('csvFile', 'Could not read file: ' . $e->getMessage());
            return;
        }

        if (empty($header)) {
            $this->addError('csvFile', 'The file is empty.');
            return;
        }

        $header = array_map(fn($h) => strtolower(trim((string) $h)), $header);

        if (!in_array('student_id', $header)) {
            $this->addError('csvFile', 'Missing required column: student_id');
            return;
        }

        $imported = 0;
        $skipped  = 0;
        $errors   = [];
        $row      = 1;

        foreach ($dataRows as $data) {
            $row++;

            if (empty(array_filter(array_map('strval', $data)))) {
                continue;
            }

            $data      = array_pad(array_map(fn($v) => trim((string) $v), $data), count($header), '');
            $record    = array_combine($header, $data);
            $studentId = $record['student_id'] ?? '';

            if (!$studentId) {
                $errors[] = "Row {$row}: student_id is empty";
                $skipped++;
                continue;
            }

            // Look up the student by their student_id string
            $studentDetail = StudentDetail::where('student_id', $studentId)->first();

            if (!$studentDetail) {
                $errors[] = "Row {$row}: student_id '{$studentId}' not found";
                $skipped++;
                continue;
            }

            $userId = $studentDetail->user_id;

            // Already a member?
            $exists = DB::table('club_memberships')
                ->where('club_id', $this->clubId)
                ->where('student_id', $userId)
                ->exists();

            if ($exists) {
                $errors[] = "Row {$row}: '{$studentId}' is already a member";
                $skipped++;
                continue;
            }

            try {
                DB::table('club_memberships')->insert([
                    'club_id'    => $this->clubId,
                    'student_id' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Row {$row}: " . $e->getMessage();
                $skipped++;
            }
        }

        $this->importResults = [
            'imported' => $imported,
            'skipped'  => $skipped,
            'errors'   => $errors,
        ];

        $this->csvFile = null;
        $this->loadClubData();
    }

    private function readCsvRows(string $path): array
    {
        $handle = fopen($path, 'r');
        if (!$handle) {
            throw new \RuntimeException('Could not open the file.');
        }
        $header   = fgetcsv($handle) ?: [];
        $dataRows = [];
        while (($row = fgetcsv($handle)) !== false) {
            $dataRows[] = $row;
        }
        fclose($handle);
        return [$header, $dataRows];
    }

    private function readExcelRows(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $worksheet   = $spreadsheet->getActiveSheet();
        $allRows     = $worksheet->toArray(null, true, true, false);

        if (empty($allRows)) {
            throw new \RuntimeException('The spreadsheet is empty.');
        }

        $header   = array_shift($allRows);
        $dataRows = array_values(array_filter($allRows, fn($r) => !empty(array_filter(array_map('strval', $r)))));
        return [$header, $dataRows];
    }

    public function downloadSampleCsv()
    {
        $csv  = "student_id\n";
        $csv .= "2021-00001\n";
        $csv .= "2021-00002\n";

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, 'club_members_import_template.csv', ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        return view('livewire.club.manage-members');
    }
}
