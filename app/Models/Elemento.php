<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Elemento extends Model
{

    protected $table = 'elementos';
    protected $fillable = [
        'nombre',
        'tipo',
        'procesos'
    ];
}
