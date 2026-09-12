<?php
/** Direct SQL snapshot: does not bootstrap WordPress and permits no database writes. */
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$host=explode(':',getenv('WORDPRESS_DB_HOST'));$db=new mysqli($host[0],getenv('WORDPRESS_DB_USER'),getenv('WORDPRESS_DB_PASSWORD'),getenv('WORDPRESS_DB_NAME'),(int)($host[1]??3306));
$db->set_charset('utf8mb4');$db->query('SET TRANSACTION READ ONLY');$db->query('START TRANSACTION WITH CONSISTENT SNAPSHOT');
$tables=$db->query('SHOW TABLES')->fetch_all(MYSQLI_NUM);$posts=array_values(array_filter(array_column($tables,0),static fn($t)=>str_ends_with($t,'_posts')));if(count($posts)!==1)throw new RuntimeException('Ambiguous WordPress table prefix');$prefix=substr($posts[0],0,-5);if(!preg_match('/^[a-zA-Z0-9_]+$/',$prefix))throw new RuntimeException('Invalid prefix');
$read=static fn($sql)=>$db->query($sql)->fetch_all(MYSQLI_ASSOC);
$properties=$read("SELECT * FROM {$prefix}posts WHERE post_type='property' ORDER BY ID");
$meta=$read("SELECT m.* FROM {$prefix}postmeta m JOIN {$prefix}posts p ON p.ID=m.post_id WHERE p.post_type='property' ORDER BY meta_id");
$taxonomy=$read("SELECT t.*,tt.* FROM {$prefix}terms t JOIN {$prefix}term_taxonomy tt ON tt.term_id=t.term_id WHERE tt.taxonomy IN ('property_type','transaction_type') ORDER BY tt.term_taxonomy_id");
$relations=$read("SELECT r.* FROM {$prefix}term_relationships r JOIN {$prefix}posts p ON p.ID=r.object_id WHERE p.post_type='property' ORDER BY r.object_id,r.term_taxonomy_id");
$attachments=$read("SELECT COUNT(*) count FROM {$prefix}posts WHERE post_type='attachment'")[0]['count'];
$result=array('property_count'=>count($properties),'attachment_count'=>(int)$attachments,'taxonomy'=>$taxonomy,'property_sha256'=>hash('sha256',json_encode(array($properties,$meta,$relations))),'properties'=>$properties,'meta'=>$meta,'relationships'=>$relations);
$db->rollback();echo json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n";
