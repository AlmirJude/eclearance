<?php

namespace App\Livewire\Users;

use App\Models\Department;
use App\Models\StudentDetail;
use App\Models\User;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Livewire\Attributes\Layout; // 1. Add this import

#[Layout('components.layouts.app')] // 
class StudentIndex extends Component
{
    use WithFileUploads;

    public $showViewModal = false;
    public $showDeleteModal = false;
    public $selectedStudent;
    public $search = '';
    public $departmentFilter = '';
    public $yearLevelFilter = '';

    // CSV Import
    public $showImportModal = false;
    public $csvFile;
    public $importResults = null; // ['imported' => n, 'skipped' => n, 'errors' => [...]]

    public function render()
    {
        $students = StudentDetail::query()
            ->with(['department', 'user'])
            ->when($this->departmentFilter, function ($query) {
                $query->where('department_id', $this->departmentFilter);
            })
            ->when($this->yearLevelFilter, function ($query) {
                $query->where('year_level', $this->yearLevelFilter);
            })
            ->when($this->search, function ($query) {
                $search = trim($this->search);

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('student_id', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                        ->orWhere('year_level', 'like', "%{$search}%")
                        ->orWhereHas('department', function ($departmentQuery) use ($search) {
                            $departmentQuery->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('email', 'like', "%{$search}%");
                        });
                });
            })
            ->get();

        $departments = Department::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('livewire.users.student-index', compact('students', 'departments'));
    }

    // ─── CSV Import ────────────────────────────────────────────────────────────

    public function openImportModal()
    {
        $this->csvFile = null;
        $this->importResults = null;
        $this->showImportModal = true;
        $this->dispatch('open-modal', name: 'import-csv-modal');
    }

    public function closeImportModal()
    {
        $this->showImportModal = false;
        $this->csvFile = null;
        $this->importResults = null;
        $this->dispatch('close-modal', name: 'import-csv-modal');
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

        // Parse rows depending on file type
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

        // Normalise header names (trim + lowercase)
        $header = array_map(fn($h) => strtolower(trim((string) $h)), $header);

        $required = ['student_id', 'email', 'password', 'first_name', 'last_name', 'department', 'year_level'];
        $missing  = array_diff($required, $header);

        if (!empty($missing)) {
            $this->addError('csvFile', 'Missing columns: ' . implode(', ', $missing));
            return;
        }

        // Cache departments keyed by abbreviation and name (case-insensitive)
        $departments = Department::all()->keyBy(fn($d) => strtolower(trim($d->Abbreviation ?? '')))
            ->union(Department::all()->keyBy(fn($d) => strtolower(trim($d->name))));

        $imported = 0;
        $skipped  = 0;
        $errors   = [];
        $row      = 1; // 1 = header

        foreach ($dataRows as $data) {
            $row++;

            if (empty(array_filter(array_map('strval', $data)))) {
                continue; // skip blank rows
            }

            // Pad short rows and cast to string
            $data   = array_pad(array_map(fn($v) => trim((string) $v), $data), count($header), '');
            $record = array_combine($header, $data);

            $studentId = $record['student_id']  ?? '';
            $email     = $record['email']        ?? '';
            $password  = $record['password']     ?? '';
            $firstName = $record['first_name']   ?? '';
            $lastName  = $record['last_name']    ?? '';
            $deptInput = $record['department']   ?? '';
            $yearLevel = (int) ($record['year_level'] ?? 0);

            // ── Row-level validation ──────────────────────────────────────────
            $rowErrors = [];

            if (!$studentId) $rowErrors[] = 'student_id is empty';
            elseif (strlen($studentId) > 8) $rowErrors[] = 'student_id must not exceed 8 characters';
            elseif (!preg_match('/^[0-9-]+$/', $studentId)) $rowErrors[] = 'student_id may only contain numbers and dashes';
            if (!$email)     $rowErrors[] = 'email is empty';
            elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $rowErrors[] = 'email is invalid';
            if (strlen($password) < 8)  $rowErrors[] = 'password must be at least 8 characters';
            if (!$firstName) $rowErrors[] = 'first_name is empty';
            if (!$lastName)  $rowErrors[] = 'last_name is empty';
            if ($yearLevel < 1 || $yearLevel > 6) $rowErrors[] = 'year_level must be 1–6';

            // Duplicate checks
            if ($studentId && User::where('user_id', $studentId)->exists())
                $rowErrors[] = "student_id '{$studentId}' already exists";
            if ($email && User::where('email', $email)->exists())
                $rowErrors[] = "email '{$email}' already exists";
            if ($studentId && StudentDetail::where('student_id', $studentId)->exists())
                $rowErrors[] = "student_id '{$studentId}' already in student_details";

            // Department lookup
            $department = $departments->get(strtolower($deptInput));
            if (!$department) {
                $rowErrors[] = "department '{$deptInput}' not found (use abbreviation or full name)";
            }

            if (!empty($rowErrors)) {
                $errors[] = "Row {$row}: " . implode('; ', $rowErrors);
                $skipped++;
                continue;
            }

            // ── Insert ────────────────────────────────────────────────────────
            try {
                DB::transaction(function () use ($studentId, $email, $password, $firstName, $lastName, $department, $yearLevel) {
                    $user = User::create([
                        'user_id'  => $studentId,
                        'email'    => $email,
                        'password' => Hash::make($password),
                        'role'     => 'student',
                    ]);

                    StudentDetail::create([
                        'user_id'       => $user->id,
                        'student_id'    => $studentId,
                        'first_name'    => $firstName,
                        'last_name'     => $lastName,
                        'department_id' => $department->id,
                        'year_level'    => $yearLevel,
                    ]);
                });

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
    }

    /**
     * Read rows from a CSV file. Returns [header, dataRows].
     */
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

    /**
     * Read rows from an Excel (.xlsx / .xls) file. Returns [header, dataRows].
     */
    private function readExcelRows(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $worksheet   = $spreadsheet->getActiveSheet();
        $allRows     = $worksheet->toArray(null, true, true, false);

        if (empty($allRows)) {
            throw new \RuntimeException('The spreadsheet is empty.');
        }

        $header   = array_shift($allRows);
        // Filter out completely blank rows
        $dataRows = array_values(array_filter($allRows, fn($r) => !empty(array_filter(array_map('strval', $r)))));

        return [$header, $dataRows];
    }

    public function downloadSampleCsv()
    {
        $csv = "student_id,email,password,first_name,last_name,department,year_level\n";
        $csv .= "21-001,juan.delacruz@school.edu,password123,Juan,Dela Cruz,BSCS,1\n";
        $csv .= "21-002,maria.santos@school.edu,password123,Maria,Santos,BSIT,2\n";

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, 'students_import_template.csv', ['Content-Type' => 'text/csv']);
    }

    // ─── View / Delete ─────────────────────────────────────────────────────────

    public function view($id)
    {
        $this->selectedStudent = StudentDetail::with('user')->find($id);
        $this->showViewModal = true;
        $this->dispatch('open-modal', name: 'view-student-modal');
    }

    public function closeModalViewStudent(){
        $this->showViewModal = false;
        $this->selectedStudent = null;
        $this->dispatch('close-modal', name: 'view-student-modal');
    }



    public function confirmDelete($id)
    {
        $this->selectedStudent = StudentDetail::with('user')->find($id);
        $this->showDeleteModal = true;
        $this->dispatch('open-modal', name: 'showDeleteModal');
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->selectedStudent = null;
        $this->dispatch('close-modal', name: 'showDeleteModal');
    }

    public function delete()
    {
        if ($this->selectedStudent) {
            $this->selectedStudent->user->delete(); // Deletes user; DB cascade removes student_details
            session()->flash('success', 'Student deleted successfully.');
            $this->showDeleteModal = false;
            $this->selectedStudent = null;
            $this->dispatch('close-modal', name: 'showDeleteModal');
        }
        else {
            session()->flash('error', 'Student not found.');
        }
    }
}
