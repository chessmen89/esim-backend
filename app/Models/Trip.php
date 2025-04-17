<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    use HasFactory;

    protected $table = 'trips';

    // جميع الحقول مطلوبة ولن تُقبل قيم فارغة
    protected $fillable = [
        'name',
        'pdf_path',
        'image_path',
        'country_code',
        'price',
        'description',
    ];
}
