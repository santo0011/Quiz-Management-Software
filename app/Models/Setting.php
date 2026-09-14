<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'site_name',
        'logo_path',
        'mail_mailer',
        'mail_host',
        'mail_port',
        'mail_username',
        'mail_password',
        'mail_encryption',
        'mail_from_address',
        'mail_from_name',
        'common_student_password',
    ];

    protected $hidden = [
        'mail_password',
        'common_student_password',
    ];

    protected function casts(): array
    {
        return [
            'mail_password' => 'encrypted',
            'common_student_password' => 'hashed',
        ];
    }

    public function hasCommonStudentPassword(): bool
    {
        return filled($this->common_student_password);
    }

    /**
     * There is only ever one settings row.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate([]);
    }
}
