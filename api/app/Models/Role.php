<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Role extends BaseModel
{
    use LogsActivity;

    protected $fillable = ["name", "business_id"];

    public function users()
    {
        return $this->hasMany(User::class);
    }

}
