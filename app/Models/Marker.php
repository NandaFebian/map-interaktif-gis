<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Marker extends Model
{
    use HasFactory;

    // Atribut yang dapat diisi
    protected $fillable = [
        'name',
        'latitude',
        'longitude',
        'description',
    ];
}
