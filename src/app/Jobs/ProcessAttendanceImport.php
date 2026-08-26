<?php

namespace App\Jobs;

use App\Imports\AttendanceImportFile;
use App\Models\AttendanceImport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Runs off the queue (not synchronously in the request) so a large or repeated
 * upload can't tie up a web worker or be used as a simple DoS lever — see PRD
 * 5.3.1 and the security notes in the project plan.
 */
class ProcessAttendanceImport implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(
        private readonly int $attendanceImportId,
        private readonly string $storedPath,
        private readonly int $branchId,
    ) {}

    public function handle(): void
    {
        $attendanceImport = AttendanceImport::findOrFail($this->attendanceImportId);

        try {
            Excel::import(
                new AttendanceImportFile($attendanceImport, $this->branchId),
                $this->storedPath,
                'local'
            );
        } catch (\Throwable $e) {
            $attendanceImport->update(['status' => 'failed']);

            report($e);
        } finally {
            Storage::disk('local')->delete($this->storedPath);
        }
    }
}
