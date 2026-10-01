<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginLog extends Model
{
    const UPDATED_AT = null;

    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'role',
        'name',
        'identifier',
        'subject_id',
        'branch_id',
        'branch_name',
        'login_method',
        'status',
        'failure_reason',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
