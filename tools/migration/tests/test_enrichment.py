import json,sys,html,unittest,hashlib
from pathlib import Path
sys.path.insert(0,str(Path(__file__).resolve().parents[1]))
from enrich import process
from enrichment_parser import reconcile,ev,transactions,contacts,lines_from_pages,structured,page_metadata
ROOT=Path(__file__).resolve().parents[3]
def fixture(name):
 d=json.loads((Path(__file__).parent/'enrichment-fixtures'/(name+'.json')).read_text());meta=''.join('<meta '+' '.join(k+'="'+html.escape(str(v),quote=True)+'"' for k,v in t.items())+'>' for t in d['metadata']);blocks=''
 for block in d['page_blocks']:blocks+='<div class="sqs-html-content">'+''.join('<'+x['tag']+'>'+html.escape(x['text']).replace('\n','<br>')+'</'+x['tag']+'>' for x in block)+'</div>'
 return process(d['record'],meta+'<div class="blog-item-content">'+blocks+'</div>',d['brochure_pages'],d['info'])
class EnrichmentTests(unittest.TestCase):
 def test_clay_loading_and_contacts(self):
  r=fixture('clay');self.assertEqual(r['brochure_download_status'],'success');self.assertEqual(r['enriched_fields']['clear_height_ft']['candidate'],14);self.assertEqual(r['decision_field_status']['grade_level_doors'],'known');self.assertEqual(r['decision_field_status']['loading_configuration'],'known');self.assertIsNone(r['transaction_type_candidate_v2']);self.assertIn('alexb@aspirecre.com',[c['email'] for c in r['broker_candidates']]);self.assertTrue(any(s['suite_name']=='SUITE 101' and s['square_feet']==3774 for s in r['suite_candidates_v2']))
 def test_atascocita_conflicting_suite_inventory(self):
  r=fixture('atascocita');self.assertEqual(r['transaction_type_candidate_v2'],'For Lease');self.assertEqual(r['enriched_fields']['traffic_count_vpd']['candidate'],23967);self.assertEqual(r['decision_field_status']['average_household_income'],'known');self.assertEqual({s['suite_name'] for s in r['suite_candidates_v2']},{'SUITE E','SUITE F'});self.assertIn('suite_inventory_source_difference',r['enrichment_warnings']);self.assertIn('$5.84/SF NNN',r['lease_rate_display'])
 def test_broadway_agreement_and_contact_pairs(self):
  r=fixture('broadway');s=r['suite_candidates_v2'];self.assertEqual([x['square_feet'] for x in s],[8400,2994]);self.assertEqual([x['rate'] for x in s],[12,20]);self.assertTrue(all(x['confidence']=='high_agreement' for x in s));self.assertEqual(next(c['name'] for c in r['broker_candidates'] if c['email']=='das@aspirecre.com'),'David A. Smith')
 def test_woodsons_contexts(self):
  r=fixture('woodsons');self.assertFalse(r['suite_candidates_v2']);self.assertEqual(len(r['availability_contexts_v2']),3);self.assertTrue(all(c['suite_name'] is None for c in r['availability_contexts_v2']));self.assertEqual(len(r['gallery_images']),16)
 def test_greatwood_partial_text(self):
  r=fixture('greatwood');self.assertTrue(r['brochure_partial_text']);self.assertEqual(r['transaction_type_candidate_v2'],'For Sale or Lease');self.assertEqual(r['decision_field_status']['office_configuration'],'known');self.assertEqual(r['decision_field_status']['furnished'],'known');self.assertFalse(r['broker_candidates'])
 def test_prairie(self):
  r=fixture('prairie');self.assertEqual(r['enriched_fields']['available_sf']['candidate'],1250);self.assertEqual(r['enriched_fields']['year_built']['candidate'],1880);self.assertEqual(r['address_candidate_v2']['postal_code'],'77002')
 def test_monroe_no_ocr(self):
  r=fixture('monroe');self.assertEqual(r['brochure_text_status'],'unavailable');self.assertIn('brochure_text_unavailable',r['enrichment_warnings']);self.assertEqual(r['decision_field_status']['outdoor_storage'],'known');self.assertEqual(r['decision_field_status']['office_sf'],'known');self.assertEqual(r['decision_field_status']['clear_height'],'known');self.assertFalse(r['broker_candidates'])
 def test_land(self):
  r=fixture('land');self.assertEqual(r['decision_field_status']['utilities'],'known');self.assertEqual(r['decision_field_status']['floodplain'],'known');self.assertNotEqual(r['decision_field_status']['zoning'],'known')
 def test_fm1093_policy(self):
  r=fixture('fm1093');self.assertEqual(r['sale_price'],3410000);self.assertIn('existing_wp_pricing_review',r['warnings']);self.assertEqual(r['migration_review_status'],'needs_review')
 def test_reconciliation(self):
  agree=reconcile([ev('page',14,'14 clear'),ev('brochure',14,'14 clear')]);self.assertEqual(agree['confidence'],'high_agreement');conflict=reconcile([ev('page',14,'14 clear'),ev('brochure',16,'16 clear')]);self.assertIsNone(conflict['candidate']);self.assertTrue(conflict['field_conflict'])
 def test_no_legal_or_rent_roll_transaction(self):
  self.assertFalse(transactions(lines_from_pages(['Information About Brokerage Services\nasking price']), 'brochure'));self.assertFalse(transactions([(1,'Scheduled Base Rental Income $100,000')],'brochure'));self.assertFalse(contacts(['Information About Brokerage Services\nSales Team\nJane Smith\n(713) 123-4567\njane@aspirecre.com']))
 def test_range_not_building(self):
  r=structured([(1,'Availabilities from 700–11,000 SF')],'brochure');self.assertFalse(r['building_sf']);self.assertEqual(r['minimum_available_sf'][0]['value'],700);self.assertEqual(r['maximum_available_sf'][0]['value'],11000)
 def test_v1_preserved_and_v2_complete(self):
  v1=json.loads((ROOT/'docs/migration/aspire-properties-manifest.json').read_text());v2=json.loads((ROOT/'docs/migration/aspire-properties-manifest-v2.json').read_text());self.assertEqual(v2['schema_version'],2);self.assertEqual(len(v2['records']),87)
  for a,b in zip(v1['records'],v2['records']):
   for k in a:self.assertEqual(a[k],b[k],(a['source_title'],k))
  for name,digest in v2['v1_sha256'].items():self.assertEqual(hashlib.sha256((ROOT/'docs/migration'/name).read_bytes()).hexdigest(),digest)
  self.assertTrue(v2['wordpress_unchanged']);self.assertEqual(v2['summary']['coordinate_reuse_rejected'],82);self.assertEqual(v2['summary']['coverage']['coordinates']['after'],5)
if __name__=='__main__':unittest.main()
