import unittest,json,sys,html,tempfile
from unittest.mock import patch
from pathlib import Path
sys.path.insert(0,str(Path(__file__).resolve().parents[1]))
from parser import parse,slug_policy,address,match_existing
from discover import Links,ARCHIVES
FIXTURES=Path(__file__).parent/'fixtures'
def fixture(name):
 d=json.loads((FIXTURES/(name+'.json')).read_text());blocks=''
 for block in d['blocks']:
  blocks+='<div class="sqs-html-content">'+''.join('<'+x['tag']+'>'+html.escape(x['text']).replace('\n','<br>')+'</'+x['tag']+'>' for x in block)+'</div>'
 images=''
 for i in d['images']:
  img='<img data-src="'+html.escape(i['source_url'],quote=True)+'" alt="'+html.escape(i['alt_text'],quote=True)+'">'
  images+=('<div class="sqs-block-gallery">'+img+img+'</div>') if i['gallery'] else img
 links=''.join('<a href="'+html.escape(a['url'],quote=True)+'">'+html.escape(a['text'])+'</a>' for a in d['links'])
 raw='<article class="h-entry"><h1>'+html.escape(d['title'])+'</h1><a class="blog-item-category">'+d['category']+'</a><div class="blog-item-content">'+blocks+images+links+'</div></article>'
 return parse(raw,d['source_url'])
class AuditTests(unittest.TestCase):
 def test_clay(self):
  r=fixture('clay');self.assertEqual(r['property_type'],'Industrial / Flex');self.assertEqual(r['clear_height_ft'],14);self.assertIn('Masonry Construction',r['property_highlights']);self.assertTrue(r['brochure_url']);self.assertEqual(len(r['gallery_images']),4);self.assertIsNone(r['transaction_type_candidate'])
 def test_atascocita(self):
  r=fixture('atascocita');self.assertEqual(r['property_type'],'Retail');self.assertEqual(r['lot_acres'],5.49);self.assertEqual(r['traffic_count_vpd'],23967);self.assertEqual(len(r['suite_candidates']),1);s=r['suite_candidates'][0];self.assertEqual(s['square_feet'],4361);self.assertEqual(s['rate'],18);self.assertIn('$5.84/SF NNN',r['lease_rate_display']);self.assertIsNone(s['availability_status']);self.assertIsNone(r['available_sf'])
 def test_broadway(self):
  r=fixture('broadway');self.assertEqual([s['square_feet'] for s in r['suite_candidates']],[8400,2994]);self.assertEqual(r['available_sf'],11394);self.assertEqual(r['maximum_contiguous_sf'],8400);self.assertEqual(r['address_line_1'],'5900 - 5940 Broadway Street');self.assertTrue(r['brochure_url']);self.assertEqual(len(r['gallery_images']),3)
 def test_office_condo(self):
  r=fixture('office_condo');self.assertEqual(r['property_type'],'Office Condo');self.assertTrue(r['brochure_url']);self.assertEqual(len(r['gallery_images']),4);self.assertEqual(r['city'],'Pearland')
 def test_woodsons(self):
  r=fixture('woodsons');self.assertEqual(r['property_type'],'Retail');self.assertEqual(r['transaction_type_candidate'],'For Lease');self.assertEqual(len(r['gallery_images']),16);self.assertTrue(r['brochure_url']);self.assertFalse(r['suite_candidates']);self.assertTrue(r['unparsed_availability_blocks']);self.assertIsNone(r['available_sf']);self.assertIsNone(r['lease_rate_min']);self.assertIn('suite_parse_review',r['warnings'])
 def test_land(self):
  r=fixture('land');self.assertEqual(r['property_type'],'Land');self.assertEqual(r['lot_acres'],2.21);self.assertEqual(r['sale_price'],3410000);self.assertEqual(r['city'],'Richmond');self.assertIsNone(r['building_sf']);self.assertNotIn('listing_status',r)
 def test_office(self):
  r=fixture('office');self.assertEqual(r['property_type'],'Office');self.assertEqual(r['parking_spaces'],160);self.assertEqual(r['building_class'],'B');self.assertIsNone(r['building_sf']);self.assertIsNone(r['year_built']);self.assertIn('structured_value_review',r['warnings'])
 def test_redirect(self):
  r=fixture('redirect');self.assertTrue(r['redirect_required']);self.assertNotIn('/',r['proposed_wp_slug']);self.assertIn('1849/1851/1853',r['source_path']);self.assertFalse(slug_policy('https://www.aspirecre.com/properties/normal-safe-slug')[1])
 def test_discovery(self):
  p=Links();p.feed('<a href="/properties/a">x</a><a href="https://www.aspirecre.com/properties/a#next">x</a><a href="/properties/category/Retail">Retail</a><a href="/properties?offset=123">next</a><a href="/properties/">All</a><a href="https://other.test/properties/b">bad</a><a href="/contact">no</a>');self.assertEqual(p.urls,{'https://www.aspirecre.com/properties/a'});self.assertEqual(len(ARCHIVES),5)
 def test_count_gate_stops_on_discrepancy(self):
  import discover
  with tempfile.TemporaryDirectory() as folder, patch.object(discover,'CACHE',Path(folder)), patch.object(discover,'fetch',return_value='<a href="/properties/one">One</a>') as fetch:
   with self.assertRaisesRegex(SystemExit,'expected 87'):
    discover.discover()
   self.assertEqual(fetch.call_count,5)
   self.assertEqual(json.loads((Path(folder)/'discovery.json').read_text())['unique_count'],1)
 def test_conservative_address(self):
  self.assertIsNone(address('Unknown property')['city']);self.assertIsNone(address('1 Main Road Houston TX 7749')['postal_code']);self.assertEqual(address('1 Main Road Houston Texas 77084')['city'],'Houston')
 def test_conflicts_and_scope(self):
  raw='<article class="h-entry"><h1>Unknown</h1><div class="blog-item-content"><div class="sqs-html-content"><h3>Property Highlights</h3><ul><li>5,000 SF</li><li>$20/SF</li><li>For Sale</li><li>For Lease</li><li>Building Size: 2,000 SF</li><li>Building Size: 3,000 SF</li></ul></div></div></article><footer><h3>Property Highlights</h3><ul><li>Footer copy</li></ul><img src="https://x.test/logo.png"></footer>'
  r=parse(raw,'https://www.aspirecre.com/properties/test');self.assertIsNone(r['building_sf']);self.assertIsNone(r['lease_rate_min']);self.assertIsNone(r['transaction_type_candidate']);self.assertIn('ambiguous_transaction',r['warnings']);self.assertNotIn('Footer copy',r['property_highlights']);self.assertEqual(r['image_count'],0)
 def test_unambiguous_existing_match(self):
  r=fixture('clay');snapshot={'properties':[{'ID':'120','post_name':r['source_slug'],'post_title':r['source_title']}],'meta':[]};match_existing([r],snapshot);self.assertEqual(r['existing_wp_post_id'],120)
 def test_ambiguous_existing_match(self):
  r=fixture('clay');snapshot={'properties':[{'ID':str(i),'post_name':'other','post_title':r['source_title']} for i in [1,2]],'meta':[]};match_existing([r],snapshot);self.assertIsNone(r['existing_wp_post_id']);self.assertIn('existing_wp_match_uncertain',r['warnings'])
 def test_manifest_invariants(self):
  m=json.loads((Path(__file__).resolve().parents[3]/'docs/migration/aspire-properties-manifest.json').read_text());rs=m['records'];self.assertEqual(len(rs),87);self.assertEqual(len({r['source_url'] for r in rs}),87);self.assertEqual({r['existing_wp_post_id'] for r in rs if r['existing_wp_post_id']},{119,120,121,122});self.assertTrue(m['wordpress_unchanged']);self.assertTrue(all(r['migration_review_status']=='needs_review' and 'listing_status' not in r for r in rs))
if __name__=='__main__':unittest.main()
