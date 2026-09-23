<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
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
     * A stored mail_password can be undecryptable if APP_KEY was ever
     * regenerated after it was saved — accessing the raw (encrypted)
     * attribute in that case throws and would otherwise crash any page
     * that merely checks "is a password already set?" (e.g. the Settings
     * form's placeholder). This is the safe way to ask that question.
     */
    public function hasMailPassword(): bool
    {
        try {
            return filled($this->mail_password);
        } catch (DecryptException) {
            return false;
        }
    }

    /**
     * There is only ever one settings row.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate([]);
    }
}
