<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SistemaExterno extends Model
{
    protected $table = 'sistemas_externos';
    public $fillable = [
        'nombre',
        'subdominio',
        'apiToken',
        'extras'
    ];
}
