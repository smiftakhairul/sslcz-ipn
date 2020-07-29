<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Email extends Model
{
    protected $guarded = [];

    public function attachments() {
        return $this->belongsToMany(
            'App\Models\EmailAttachment',
            'email_attachments', 'email_id', 'path', 'id'
        )->withTimestamps();
    }
}
