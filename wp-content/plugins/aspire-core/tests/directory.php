<?php
if(PHP_SAPI!=='cli')exit;
require dirname(__DIR__,4).'/wp-load.php';
$checks=0;
function directory_check($condition,$message){global $checks;if(!$condition)throw new RuntimeException($message);$checks++;echo "PASS: $message\n";}
$page=get_page_by_path('properties',OBJECT,'page');
directory_check($page&&$page->post_status==='publish'&&$page->post_title==='Properties','Real published Properties Page');
directory_check(has_block('aspire/property-directory',$page)&&!str_contains($page->post_content,'[aspire'),'Native block, no shortcode');
directory_check(get_post_meta($page->ID,'_wp_page_template',true)==='templates/property-directory.php','Normal theme content template');
directory_check(str_contains($page->post_content,'Commercial opportunities across Greater Houston.')&&str_contains($page->post_content,'Commercial property across Greater Houston'),'Editable intro and editorial sections');
$saved_content=$page->post_content;
exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg(dirname(__DIR__).'/bin/setup-property-directory.php'),$setup_output,$setup_code);
clean_post_cache($page->ID);
directory_check($setup_code===0&&get_post($page->ID)->post_content===$saved_content,'Repeat setup preserves Gutenberg content');
directory_check(count(get_posts(array('post_type'=>'page','post_status'=>'publish','name'=>'properties','numberposts'=>-1)))===1,'Repeat setup creates no duplicate Page');
$registered=WP_Block_Type_Registry::get_instance()->get_registered('aspire/property-directory');directory_check($registered&&$registered->api_version===3&&$registered->category==='aspirecre','API v3 AspireCRE block registered');
$before=Aspire_Atlas::collection();$request=new WP_REST_Request('GET','/aspire/v1/atlas/properties');$normal=rest_do_request($request);directory_check($normal->get_data()===$before,'Default Atlas response unchanged');
$request->set_param('directory','1');$response=rest_do_request($request);$response=apply_filters('rest_post_dispatch',$response,rest_get_server(),$request);$enriched=$response->get_data();
directory_check(count($enriched['features'])===count($before['features']),'Directory uses existing available inventory');
foreach($enriched['features'] as $i=>$f){$p=$f['properties'];directory_check(isset($p['directory']['featured'],$p['directory']['publishedAt']),'Sorting data present');directory_check($p['pricing']===$before['features'][$i]['properties']['pricing'],'Shared safe pricing unchanged');unset($enriched['features'][$i]['properties']['directory']);directory_check($enriched['features'][$i]===$before['features'][$i],'Existing GeoJSON fields preserved');directory_check(url_to_postid($p['permalink'])===$f['id'],'Property single permalink resolves');}
$html=Aspire_Property_Directory::render(array('showMap'=>false));directory_check(!str_contains($html,'data-map-module')&&!str_contains($html,'class="directory-map"'),'Show Map off excludes map configuration');directory_check(str_contains($html,'data-view="list"'),'Show Map off renders List');
$html=Aspire_Property_Directory::render(array('showMap'=>true,'perLoad'=>12));directory_check(str_contains($html,'data-per-load="12"')&&str_contains($html,'atlas/build/worker.js'),'Per-load setting and shared worker');
$http=static fn($url)=>wp_remote_get(str_replace('localhost:8080','localhost',$url),array('headers'=>array('Host'=>'localhost:8080'),'redirection'=>0));$base=$http(get_permalink($page));$filtered=$http(add_query_arg('type','retail',get_permalink($page)));$body=wp_remote_retrieve_body($filtered);
directory_check(wp_remote_retrieve_response_code($base)===200&&wp_remote_retrieve_response_code($filtered)===200,'Base and filtered pages return 200');
directory_check((bool)preg_match('/<meta name=[\'"]robots[\'"][^>]+noindex[^>]+follow/i',$body),'Filtered page robots noindex,follow');
directory_check(str_contains($body,'rel="canonical" href="'.get_permalink($page).'"'),'Canonical points to unfiltered page');
directory_check(!str_contains($body,'atlas/build/view.js'),'Directory does not load Atlas application runtime');
directory_check(str_contains($body,'directory/build/view.js'),'Directory runtime is scoped to its page');
$admin=get_users(array('role'=>'administrator','number'=>1));wp_set_current_user($admin[0]->ID);define('REST_REQUEST',true);
$req=new WP_REST_Request('GET','/wp/v2/block-renderer/aspire/property-directory');$req->set_param('context','edit');$req->set_param('attributes',array('defaultView'=>'list','showMap'=>false,'perLoad'=>24));$preview=rest_do_request($req);$html=$preview->get_data()['rendered']??'';
directory_check($preview->get_status()===200&&str_contains($html,'ASPIRE PROPERTY DIRECTORY'),'Native editor preview');directory_check(str_contains($html,'List')&&str_contains($html,'Off')&&str_contains($html,'24'),'Editor settings reflected');directory_check(!str_contains($html,'data-directory')&&!str_contains($html,'pmtiles')&&!str_contains($html,'data-worker'),'Static editor has no map initialization or internal paths');
echo "SUCCESS: $checks directory checks. No property records changed.\n";
