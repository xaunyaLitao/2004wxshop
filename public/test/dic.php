<?php

function readDirs($path) {

    $dir_handle = openDir($path);

    while(false !== $file=readDir($dir_handle)) {
        if ($file=='.' || $file=='..') continue;

        //输出该文件
        echo $file, '<br>';
        //判断当前是否为目录
        if(is_dir($path . '/' . $file)) {
            //是目录
            readDirs($path . '/' . $file);
        }

    }

    closeDir($dir_handle);
}
