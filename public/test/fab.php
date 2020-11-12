<?php
//  1 1 2 3 5 8 13 21 ....

set_time_limit(0);

$n = $_GET['n'];
// 求斐波那契数列第 n 项
function fab($n)
{
    if($n==1 || $n==2)
    {
        return 1;
    }

    return fab($n-2) + fab($n-1);

}

echo fab(40);echo '</br>';die;
