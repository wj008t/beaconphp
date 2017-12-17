<?php

//上传配置项
return array(
    'imageCompressEnable' => true, /* 是否压缩图片,默认是true */
    'imageCompressBorder' => 1600, /* 图片压缩最长边限制 */
    /* 上传保存路径,可以自定义保存路径和文件名格式 */
    'imagePathFormat' => '/upfiles/images/{time}{rand:4}',
    /* {filename} 会替换成原文件名,配置这项需要注意中文乱码问题 */
    /* {rand:6} 会替换成随机数,后面的数字是随机数的位数 */
    /* {time} 会替换成时间戳 */
    /* {yyyy} 会替换成四位年份 */
    /* {yy} 会替换成两位年份 */
    /* {mm} 会替换成两位月份 */
    /* {dd} 会替换成两位日期 */
    /* {hh} 会替换成两位小时 */
    /* {ii} 会替换成两位分钟 */
    /* {ss} 会替换成两位秒 */
    /* {md5} 会替换成两位秒 */
    'filePathFormat' => "/upfiles/files/{time}{rand:4}",
    'uploadMaxSize' => 52428800,
    'uploadAllowFiles' => array(".png", ".jpg", ".jpeg", ".gif",
        ".flv", ".swf", ".mkv", ".avi", ".rm", ".rmvb", ".mpeg", ".mpg",
        ".ogg", ".ogv", ".mov", ".wmv", ".mp4", ".webm", ".mp3", ".wav", ".mid",
        ".rar", ".zip", ".tar", ".gz", ".7z", ".bz2", ".cab", ".iso",
        ".doc", ".docx", ".xls", ".xlsx", ".ppt", ".pptx", ".pdf", ".txt", ".md", ".xml", ".dat"),
    //列出文件配置
    /* 列出指定目录下的图片 */
    "imageManagerListPath" => "/upfiles/images/", /* 指定要列出图片的目录 */
    "imageManagerListSize" => 20, /* 每次列出文件数量 */

    /* 列出指定目录下的文件 */
    "fileManagerListPath" => "/upfiles/files/", /* 指定要列出文件的目录 */
    "fileManagerListSize" => 20, /* 每次列出文件数量 */
    'ImgThumb' => array(
        '160x160' => true,
        '100x100' => true,
        '300x300' => true,
    ),
);
