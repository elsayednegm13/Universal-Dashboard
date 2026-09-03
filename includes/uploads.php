<?php

declare(strict_types=1);
require_once __DIR__ . '/helpers.php';

function cmsAllowedMimeMap(): array
{
    return [
        'jpg'=>['image/jpeg'],'jpeg'=>['image/jpeg'],'png'=>['image/png'],'webp'=>['image/webp'],'gif'=>['image/gif'],
        'pdf'=>['application/pdf'],'mp4'=>['video/mp4','application/octet-stream'],'webm'=>['video/webm'],'mov'=>['video/quicktime','application/octet-stream'],
    ];
}

function cmsUploadFile(string $field,string $subdir,array $allowedExt,int $maxBytes=8388608): ?string
{
    if(empty($_FILES[$field])||!is_array($_FILES[$field])||($_FILES[$field]['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE) return null;
    $f=$_FILES[$field];
    if(($f['error']??UPLOAD_ERR_OK)!==UPLOAD_ERR_OK) throw new RuntimeException('UPLOAD_FAILED');
    if((int)($f['size']??0)<=0 || (int)$f['size']>$maxBytes) throw new RuntimeException('UPLOAD_SIZE_INVALID');
    $ext=strtolower(pathinfo((string)$f['name'],PATHINFO_EXTENSION));
    if(!in_array($ext,$allowedExt,true)) throw new RuntimeException('UPLOAD_EXTENSION_INVALID');
    $mime='';
    if(class_exists('finfo')){
        $fi=new finfo(FILEINFO_MIME_TYPE);$mime=(string)$fi->file((string)$f['tmp_name']);
        $map=cmsAllowedMimeMap();
        if(isset($map[$ext])&&!in_array($mime,$map[$ext],true)) throw new RuntimeException('UPLOAD_MIME_INVALID');
    }
    $root=realpath(__DIR__.'/../uploads'); if($root===false) throw new RuntimeException('UPLOAD_ROOT_MISSING');
    $subdir=trim(str_replace('\\','/',$subdir),'/');
    if($subdir===''||str_contains($subdir,'..')) throw new RuntimeException('UPLOAD_PATH_INVALID');
    $dir=$root.'/'.$subdir; if(!is_dir($dir)&&!mkdir($dir,0775,true)&&!is_dir($dir)) throw new RuntimeException('UPLOAD_DIR_CREATE_FAILED');
    $name=date('YmdHis').'-'.bin2hex(random_bytes(8)).'.'.$ext; $dest=$dir.'/'.$name;
    if(!move_uploaded_file((string)$f['tmp_name'],$dest)) throw new RuntimeException('UPLOAD_SAVE_FAILED');
    @chmod($dest,0644);
    return '/uploads/'.$subdir.'/'.$name;
}

function cmsDeleteLocalUpload(?string $publicPath): bool
{
    if(!$publicPath||!str_starts_with($publicPath,'/uploads/')) return false;
    $root=realpath(__DIR__.'/../uploads'); if($root===false) return false;
    $candidate=__DIR__.'/..'.$publicPath; if(!is_file($candidate)||is_link($candidate)) return false;
    $real=realpath($candidate); if($real===false||!str_starts_with($real,$root.DIRECTORY_SEPARATOR)) return false;
    return @unlink($real);
}
