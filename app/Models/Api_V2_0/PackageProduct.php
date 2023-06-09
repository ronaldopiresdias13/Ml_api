<?php

namespace App\Models\Api_V2_0;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackageProduct extends Model
{
    protected $table = 'ml_packages_products';
    protected $fillable = [
        'product_company_id',
        'packages_id',
        'quantity',
        'unitary_value',
        'subtotal',
        'cost',
        'subtotal_cost',
        'monthly_result_value',
        'monthly_cost_value',
        'lease'
    ];

    public function ml_products_company()
    {
        return $this->belongsTo(ProductCompany::class, 'product_company_id');
    }

    public function ml_package()
    {
        return $this->belongsTo(Package::class, 'packages_id');
    }

    // public function ml_package()
    // {
    //     return $this->belongsTo(Package::class, 'packages_id');
    // }
}
