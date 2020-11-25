<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class XcxCartModel extends Model
{
    protected $table = 'xcx_cart';
    protected $primaryKey="id";
    public $timestamps = false;
}
