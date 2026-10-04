<?php

namespace App\Models;

use Database\Factories\BusinessCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessCategory extends Model
{
    /** @use HasFactory<BusinessCategoryFactory> */
    use HasFactory;

    protected $fillable = ['name', 'description', 'status'];
}
