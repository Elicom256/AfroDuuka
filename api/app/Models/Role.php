<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Role extends BaseModel
{
    use HasFactory, LogsActivity;

    protected $fillable = ['name', 'business_id'];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
