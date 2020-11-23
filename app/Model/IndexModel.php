<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class IndexModel extends Model
{
    //设置表名
    protected $table="goods";
    //设置主键
    protected $primaryKey="goods_id";
    //设置时间戳
    public $timestamps=false;
}
