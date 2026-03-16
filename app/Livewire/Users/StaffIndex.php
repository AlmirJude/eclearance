<?php

namespace App\Livewire\Users;

use App\Models\StaffDetail;
use App\Models\User;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Livewire\Attributes\Layout; // 1. Add this import

#[Layout('components.layouts.app')] // 
class StaffIndex extends Component
{
    use WithFileUploads;

    public $showViewModal = false;
    public $showDeleteModal = false;
    public $selectedStaff;
    public $search = '';

    // CSV Import
    public $showImportModal = false;
    public $csvFile;
    public $importResults = null; // ['imported' => n, 'skipped' => n, 'errors' => [...]]

    public function render()
    {
        $staffs = StaffDetail::query()
            ->with('user')
            ->when($this->search, function ($query) {
                $search = trim($this->search);

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('employee_id', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                        ->orWhere('department', 'like', "%{$search}%")
                        ->orWhere('position', 'like', "%{$search}%")
                        ->orWhere('employee_type', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('email', 'like', "%{$search}%")
                                ->orWhere('role', 'like', "%{$search}%");
                        });
                });
            })
            ->get();

        return view('livewire.users.staff-index', compact('staffs'));
    }

    // ─── CSV Import ────────────────────────────────────────────────────────────

    public function openImportModal()
    {
        $this->csvFile = null;
        $this->importResults = null;
        $this->showImportModal = true;
        $this->dispatch('open-modal', name: 'import-staff-csv-modal');
    }

    public function closeImportModal()
    {
        $this->showImportModal = false;
        $this->csvFile = null;
        $this->importResults = null;
        $this->dispatch('close-modal', name: 'import-staff-csv-modal');
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

        $required = ['employee_id', 'email', 'password', 'first_name', 'last_name'];
        $missing  = array_diff($required, $header);

        if (!empty($missing)) {
            $this->addError('csvFile', 'Missing columns: ' . implode(', ', $missing));
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

            $data   = array_pad(array_map(fn($v) => trim((string) $v), $data), count($header), '');
            $record = array_combine($header, $data);

            $employeeId = $record['employee_id'] ?? '';
            $email      = $record['email']       ?? '';
            $password   = $record['password']    ?? '';
            $firstName  = $record['first_name']  ?? '';
            $lastName   = $record['last_name']   ?? '';
            $department  = $record['department']  ?? '';
            $position    = $record['position']    ?? '';

            // Auto-detect employee_type from employee_id
            $empIdUpper   = strtoupper($employeeId);
            if (str_contains($empIdUpper, 'HED') || str_contains($empIdUpper, 'BED')) {
                $employeeType = 'teaching';
            } elseif (str_contains($empIdUpper, 'NT')) {
                $employeeType = 'non-teaching';
            } else {
                $employeeType = null;
            }

            // ── Row-level validation ──────────────────────────────────────────
            $rowErrors = [];

            if (!$employeeId) $rowErrors[] = 'employee_id is empty';
            if (!$email)      $rowErrors[] = 'email is empty';
            elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $rowErrors[] = 'email is invalid';
            if (strlen($password) < 8)  $rowErrors[] = 'password must be at least 8 characters';
            if (!$firstName)  $rowErrors[] = 'first_name is empty';
            if (!$lastName)   $rowErrors[] = 'last_name is empty';

            // Duplicate checks
            if ($employeeId && User::where('user_id', $employeeId)->exists())
                $rowErrors[] = "employee_id '{$employeeId}' already exists";
            if ($email && User::where('email', $email)->exists())
                $rowErrors[] = "email '{$email}' already exists";
            if ($employeeId && StaffDetail::where('employee_id', $employeeId)->exists())
                $rowErrors[] = "employee_id '{$employeeId}' already in staff_details";

            if (!empty($rowErrors)) {
                $errors[] = "Row {$row}: " . implode('; ', $rowErrors);
                $skipped++;
                continue;
            }

            // ── Insert ────────────────────────────────────────────────────────
            try {
                DB::transaction(function () use ($employeeId, $email, $password, $firstName, $lastName, $department, $position, $employeeType) {
                    $user = User::create([
                        'user_id'  => $employeeId,
                        'email'    => $email,
                        'password' => Hash::make($password),
                        'role'     => 'staff',
                    ]);

                    StaffDetail::create([
                        'user_id'       => $user->id,
                        'employee_id'   => $employeeId,
                        'first_name'    => $firstName,
                        'last_name'     => $lastName,
                        'department'    => $department,
                        'position'      => $position,
                        'employee_type' => $employeeType,
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
        $csv  = "employee_id,email,password,first_name,last_name,department,position\n";
        $csv .= "HED-00001,juan.delacruz@school.edu,password123,Juan,Dela Cruz,IT Department,Teacher\n";
        $csv .= "NT-00001,maria.santos@school.edu,password123,Maria,Santos,Admin Office,Registrar\n";

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, 'staff_import_template.csv', ['Content-Type' => 'text/csv']);
    }

    public function view($id)
    {
        $this->selectedStaff = StaffDetail::with('user')->find($id);
        $this->showViewModal = true;
        $this->dispatch('open-modal', name: 'view-staff-modal');
    }

    public function closeModalViewStaff()
    {
        $this->showViewModal = false;
        $this->selectedStaff = null;
        $this->dispatch('close-modal', name: 'view-staff-modal');
    }

    public function confirmDelete($id)
    {
        $this->selectedStaff = StaffDetail::with('user')->find($id);
        $this->showDeleteModal = true;
        $this->dispatch('open-modal', name: 'showDeleteModal');
    }

    public function delete()
    {
        if ($this->selectedStaff) {
            $this->selectedStaff->user->delete(); // Deletes user; DB cascade removes staff_details
            session()->flash('success', 'Staff deleted successfully.');
            
            $this->showDeleteModal = false;
            $this->selectedStaff = null;
            $this->dispatch('close-modal', name: 'showDeleteModal');
        } else {
            session()->flash('error', 'Staff not found.');
        }
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->selectedStaff = null;
        $this->dispatch('close-modal', name: 'showDeleteModal');
    }
}