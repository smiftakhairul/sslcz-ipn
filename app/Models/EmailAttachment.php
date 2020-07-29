<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailAttachment extends Model
{
    protected $guarded = [];

    public function email() {
        return $this->belongsTo('App\Models\Email');
    }
}
