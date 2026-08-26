<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceImportError extends Model
{
    protected $fillable = ['attendance_import_id', 'row_number', 'raw_data', 'reason'];

    protected function casts(): array
    {
        return [
            'raw_data' => 'array',
        ];
    }

    public function attendanceImport(): BelongsTo
    {
        return $this->belongsTo(AttendanceImport::class);
    }
}
