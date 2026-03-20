<?php

use App\Models\ClearanceRequest;
use App\Services\ClearanceResolver;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('resolver:benchmark
    {requestId : Clearance request ID}
    {--runs=30 : Number of benchmark runs}
    {--method=status : status|signatories|can-sign}
    {--type= : Signable type for can-sign}
    {--id= : Signable ID for can-sign}
    {--query-stats : Include DB query count and total query time}', function () {
    $requestId = (int) $this->argument('requestId');
    $runs = max(1, (int) $this->option('runs'));
    $method = strtolower((string) $this->option('method'));
    $includeQueryStats = (bool) $this->option('query-stats');

    $clearanceRequest = ClearanceRequest::find($requestId);
    if (!$clearanceRequest) {
        $this->error("Clearance request {$requestId} not found.");
        return 1;
    }

    $resolver = app(ClearanceResolver::class);
    $signableType = null;
    $signableId = null;

    if ($method === 'can-sign') {
        $typeOption = trim((string) $this->option('type'));
        $idOption = $this->option('id');

        if ($typeOption === '' || $idOption === null) {
            $this->error('The --type and --id options are required when method is can-sign.');
            return 1;
        }

        $signableType = match (strtolower($typeOption)) {
            'club' => ClearanceResolver::TYPE_CLUB,
            'office' => ClearanceResolver::TYPE_OFFICE,
            'department' => ClearanceResolver::TYPE_DEPARTMENT,
            'homeroom', 'homeroom_adviser' => ClearanceResolver::TYPE_HOMEROOM,
            'student-government', 'student_government', 'studentgovernment' => ClearanceResolver::TYPE_STUDENT_GOVERNMENT,
            default => $typeOption,
        };
        $signableId = (int) $idOption;

        if ($signableId <= 0) {
            $this->error('The --id value must be a positive integer.');
            return 1;
        }
    }

    $executor = match ($method) {
        'status' => fn () => $resolver->getClearanceStatus($clearanceRequest),
        'signatories' => fn () => $resolver->getAvailableSignatories($clearanceRequest),
        'can-sign' => fn () => $resolver->canSign($signableType, $signableId, $clearanceRequest),
        default => null,
    };

    if (!$executor) {
        $this->error('Invalid --method. Use one of: status, signatories, can-sign.');
        return 1;
    }

    $executor();

    $durations = [];
    $queryCounts = [];
    $queryTimes = [];

    for ($index = 0; $index < $runs; $index++) {
        if ($includeQueryStats) {
            DB::flushQueryLog();
            DB::enableQueryLog();
        }

        $start = hrtime(true);
        $executor();
        $durations[] = (hrtime(true) - $start) / 1_000_000;

        if ($includeQueryStats) {
            $queries = DB::getQueryLog();
            $queryCounts[] = count($queries);
            $queryTimes[] = array_sum(array_column($queries, 'time'));
            DB::disableQueryLog();
        }
    }

    sort($durations);
    $average = array_sum($durations) / count($durations);
    $minimum = $durations[0];
    $maximum = $durations[count($durations) - 1];
    $p95Index = (int) ceil(0.95 * count($durations)) - 1;
    $p95 = $durations[max(0, $p95Index)];

    $this->newLine();
    $this->info('ClearanceResolver benchmark results');
    $this->line("Request ID: {$requestId}");
    $this->line("Method: {$method}");
    $this->line("Runs: {$runs}");

    if ($method === 'can-sign') {
        $this->line("Type: {$signableType}");
        $this->line("Signable ID: {$signableId}");
    }

    $this->newLine();
    $this->table(['Metric', 'Milliseconds'], [
        ['Average', number_format($average, 3)],
        ['Minimum', number_format($minimum, 3)],
        ['P95', number_format($p95, 3)],
        ['Maximum', number_format($maximum, 3)],
    ]);

    if ($includeQueryStats) {
        $averageQueryCount = array_sum($queryCounts) / count($queryCounts);
        $averageQueryTime = array_sum($queryTimes) / count($queryTimes);

        $this->newLine();
        $this->table(['DB Query Metric', 'Value'], [
            ['Average query count', number_format($averageQueryCount, 2)],
            ['Average total query time (ms)', number_format($averageQueryTime, 3)],
        ]);
    }

    return 0;
})->purpose('Benchmark ClearanceResolver execution time in milliseconds');
