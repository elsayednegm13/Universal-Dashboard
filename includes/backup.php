<?php

declare(strict_types=1);
require_once __DIR__.'/database.php';
require_once __DIR__.'/module-registry.php';

function cmsBackupTables(): array
{
    $core = ['admin_users','admin_login_attempts','site_settings','cms_modules','cms_schema_migrations','audit_logs'];
    return array_values(array_unique(array_merge($core, cmsModuleTables())));
}
function cmsTableExists(PDO $pdo,string $table): bool { $s=$pdo->prepare('SHOW TABLES LIKE :t');$s->execute([':t'=>$table]);return (bool)$s->fetchColumn(); }
function cmsTableColumns(PDO $pdo,string $table): array { if(!in_array($table,cmsBackupTables(),true))return []; $cols=[];foreach($pdo->query('DESCRIBE `'.$table.'`')->fetchAll() as $r)$cols[]=(string)$r['Field'];return $cols; }
function cmsBackupDir(): string { $d=__DIR__.'/../storage/backups';if(!is_dir($d))mkdir($d,0770,true);return $d; }
function cmsCollectUploadFiles(int $maxTotal=104857600): array
{
    $root=realpath(__DIR__.'/../uploads');if(!$root)return [];$out=[];$total=0;$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS));
    foreach($it as $file){if(!$file->isFile()||$file->isLink())continue;$size=$file->getSize();if($size>20*1024*1024)continue;$total+=$size;if($total>$maxTotal)throw new RuntimeException('UPLOAD_BACKUP_TOO_LARGE');$real=$file->getRealPath();if(!$real||!str_starts_with($real,$root.DIRECTORY_SEPARATOR))continue;$rel=str_replace(DIRECTORY_SEPARATOR,'/',substr($real,strlen($root)+1));$bin=file_get_contents($real);if($bin===false)continue;$out[]=['path'=>$rel,'sha256'=>hash('sha256',$bin),'data'=>base64_encode($bin)];}
    return $out;
}
function cmsCreateBackup(PDO $pdo,string $reason='manual'): string
{
    $tables=[];foreach(cmsBackupTables() as $t){if(cmsTableExists($pdo,$t))$tables[$t]=$pdo->query('SELECT * FROM `'.$t.'`')->fetchAll(PDO::FETCH_ASSOC);}
    $payload=['format'=>'universal-cms-backup','version'=>2,'created_at'=>date(DATE_ATOM),'reason'=>$reason,'tables'=>$tables,'uploads'=>cmsCollectUploadFiles()];
    $json=json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE);if($json===false)throw new RuntimeException('BACKUP_JSON_FAILED');$envelope=['format'=>'universal-cms-envelope','sha256'=>hash('sha256',$json),'payload'=>base64_encode($json)];$raw=json_encode($envelope,JSON_UNESCAPED_SLASHES);if($raw===false)throw new RuntimeException('BACKUP_ENVELOPE_FAILED');$compressed=gzencode($raw,6);if($compressed===false)throw new RuntimeException('BACKUP_COMPRESS_FAILED');$name='cms-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.cmsbak';if(file_put_contents(cmsBackupDir().'/'.$name,$compressed,LOCK_EX)===false)throw new RuntimeException('BACKUP_WRITE_FAILED');return $name;
}
function cmsReadBackup(string $path): array
{
    $raw=file_get_contents($path);if($raw===false)throw new RuntimeException('BACKUP_READ_FAILED');$decoded=gzdecode($raw);if($decoded===false)throw new RuntimeException('BACKUP_DECOMPRESS_FAILED');$env=json_decode($decoded,true);if(!is_array($env)||($env['format']??'')!=='universal-cms-envelope'||empty($env['payload'])||empty($env['sha256']))throw new RuntimeException('BACKUP_INVALID');$json=base64_decode((string)$env['payload'],true);if($json===false||!hash_equals((string)$env['sha256'],hash('sha256',$json)))throw new RuntimeException('BACKUP_HASH_MISMATCH');$p=json_decode($json,true);if(!is_array($p)||($p['format']??'')!=='universal-cms-backup'||(int)($p['version']??0)!==2)throw new RuntimeException('BACKUP_INCOMPATIBLE');return $p;
}
function cmsRestoreBackup(PDO $pdo,string $path): void
{
    $p=cmsReadBackup($path);cmsCreateBackup($pdo,'pre-restore');$pdo->beginTransaction();try{$pdo->exec('SET FOREIGN_KEY_CHECKS=0');foreach((array)$p['tables'] as $table=>$rows){if(!in_array($table,cmsBackupTables(),true)||!cmsTableExists($pdo,$table))continue;$pdo->exec('DELETE FROM `'.$table.'`');foreach((array)$rows as $row){if(!is_array($row)||!$row)continue;$allowed=cmsTableColumns($pdo,$table);$row=array_intersect_key($row,array_flip($allowed));if(!$row)continue;$cols=array_keys($row);$names=implode(',',array_map(fn($c)=>'`'.$c.'`',$cols));$params=implode(',',array_fill(0,count($cols),'?'));$s=$pdo->prepare('INSERT INTO `'.$table.'` ('.$names.') VALUES ('.$params.')');$s->execute(array_values($row));}}$pdo->exec('SET FOREIGN_KEY_CHECKS=1');$pdo->commit();}catch(Throwable $e){try{$pdo->exec('SET FOREIGN_KEY_CHECKS=1');}catch(Throwable $x){}if($pdo->inTransaction())$pdo->rollBack();throw $e;}
    $root=realpath(__DIR__.'/../uploads');if(!$root)throw new RuntimeException('UPLOAD_ROOT_MISSING');foreach((array)$p['uploads'] as $f){if(!is_array($f)||empty($f['path'])||empty($f['data']))continue;$rel=str_replace('\\','/',(string)$f['path']);if(str_contains($rel,'..')||str_starts_with($rel,'/'))continue;$bin=base64_decode((string)$f['data'],true);if($bin===false||!hash_equals((string)($f['sha256']??''),hash('sha256',$bin)))continue;$dest=$root.'/'.$rel;$dir=dirname($dest);if(!is_dir($dir))mkdir($dir,0775,true);file_put_contents($dest,$bin,LOCK_EX);}
}
