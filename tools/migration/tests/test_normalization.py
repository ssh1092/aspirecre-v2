"""Real immutable V2 records are evidence fixtures; no network/WordPress writes."""
import copy
import csv
import hashlib
import json
from pathlib import Path
import sys
import unittest

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))
from normalize import normalize_record, transaction_candidate, traffic, GATES, FACTS, YEAR_PAIR
import re
ROOT = Path(__file__).resolve().parents[3]
V2 = json.loads((ROOT/'docs/migration/aspire-properties-manifest-v2.json').read_text())
V3 = json.loads((ROOT/'docs/migration/aspire-properties-manifest-v3.json').read_text())


def record(needle, version=V2):
    return next(r for r in version['records'] if needle in (r['source_url'] + r['source_title']).lower())


def normalized(needle):
    return normalize_record(copy.deepcopy(record(needle)))


class NormalizationTests(unittest.TestCase):
    def test_hammerly_signage_and_parking_are_not_space(self):
        r = normalized('10750')
        self.assertEqual(r['decision_field_status']['availability_range'], 'unknown')
        self.assertEqual(r['decision_field_status']['covered_parking'], 'known')
        for text in ['Building and Monument Signage Available', 'Covered parking available', 'Fiber-Optic service available', 'Floor plans available']:
            self.assertIsNone(re.search(GATES['availability_range'], text, re.I))

    def test_woodbranch_and_generic_amenity_heading(self):
        r = normalized('woodbranch')
        self.assertNotIn('availability_range', r['decision_facts_import'])
        self.assertNotIn('amenities', r['decision_facts_import'])
        self.assertIn('monument_signage', r['decision_facts_import'])

    def test_wirt_year_pair_and_physical_type(self):
        r = normalized('1700-wirt'); c = r['import_candidate']
        self.assertEqual(c['metrics']['year_built'], 1987)
        self.assertEqual(c['metrics']['renovated_year'], 2024)
        self.assertEqual(c['property_type'], 'Industrial / Flex')
        self.assertEqual(r['property_type_v2'], 'Office')
        self.assertTrue(r['property_type_evidence'])
        self.assertEqual(c['metrics']['warehouse_sf'], 7624)

    def test_sugarwell_restaurant_names(self):
        r = normalized('sugarwell')
        self.assertNotIn('restaurant_infrastructure', r['decision_facts_import'])
        for name in ['Popeyes Louisiana Kitchen', 'BBQ Restaurant', 'Nearby Amenities']:
            self.assertIsNone(re.search(GATES['restaurant_infrastructure'], name, re.I))
        self.assertIsNotNone(re.search(GATES['restaurant_infrastructure'], '2,000-gallon grease trap', re.I))

    def test_stuebner_two_road_counts(self):
        r = normalized('16803')
        self.assertEqual([(x['road'],x['vpd']) for x in r['import_candidate']['traffic_counts']], [('Louetta',35157),('Stuebner Airline',24407)])
        self.assertNotIn('traffic_count_vpd', r['remaining_conflicts'])
        bad = traffic([{'text':'35,157 VPD on Louetta'}, {'text':'40,000 VPD on Louetta'}])
        self.assertTrue(bad[0]['field_conflict']); self.assertIsNone(bad[0]['vpd'])

    def test_jfk_suite_rates_not_property_conflict(self):
        r = normalized('12425'); suites = r['import_candidate']['suites']
        self.assertEqual({s['rate'] for s in suites}, {22,24})
        self.assertEqual(len(suites),5)
        self.assertNotIn('lease_rate_min',r['remaining_conflicts'])
        self.assertTrue(all(s['availability_status'] is None for s in suites))
        self.assertEqual(r['transaction_derivation']['method'],'strongly_derived')

    def test_longenbaugh_parcels_and_ground_lease(self):
        # This compact source block follows the cached page's explicit headings.
        blocks=[{'source':'page','block_index':0,'lines':['Property Details:', 'PRICE - $4,000,000', 'LOT SIZE – 5.75 Acres (Divisible)', 'RETAIL PAD A', 'PRICE - $900,000', 'LOT SIZE - 1.12 Acres', 'GROUND LEASE RATE - Negotiable', 'Property Highlights:']}]
        r=normalize_record(record('19520'),blocks); c=r['import_candidate']
        self.assertEqual(c['transaction_types'],['For Sale','Ground Lease'])
        self.assertEqual([(p['lot_acres'],p['sale_price']) for p in c['offering_contexts']],[(5.75,4000000),(1.12,900000),(3.51,None)])
        self.assertNotIn('sale_price',c['metrics']);self.assertNotIn('lot_acres',r['remaining_conflicts'])
        self.assertEqual(record('19520',V3)['import_candidate']['offering_contexts'][1]['sale_price'],900000)

    def test_sienna_anomaly(self):
        r=normalized('4303');self.assertTrue(r['price_anomalies'])
        self.assertNotIn('sale_price',r['import_candidate']['metrics'])
        self.assertIn('$2,2000,000',[x['raw_number'] for x in r['price_anomalies']])
        self.assertNotEqual(r['import_readiness'],'READY')

    def test_clay_split_loading_and_suite(self):
        r=normalized('16840');c=r['import_candidate']
        self.assertEqual([c['metrics'][k] for k in ['office_sf','warehouse_sf','clear_height_ft']],[1687,2087,14])
        self.assertIn('grade_level_doors',c['decision_facts']);self.assertIn('loading_configuration',c['decision_facts'])
        self.assertEqual(c['suites'][0]['suite_name'],'SUITE 101')
        self.assertEqual(c['suites'][0]['square_feet'],3774)

    def test_atascocita_base_nnn_and_demographics(self):
        r=normalized('7506');c=r['import_candidate'];s=next(s for s in c['suites'] if s['suite_name']=='SUITE F')
        self.assertEqual(s['rate'],18);self.assertEqual(s['nnn_cam']['value'],5.84)
        self.assertEqual(c['traffic_counts'][0]['vpd'],23967)
        self.assertIn('population_radius_facts',c['decision_facts'])
        self.assertIn('average_household_income',c['decision_facts'])
        self.assertNotIn('restaurant_infrastructure',c['decision_facts'])
        self.assertIn('suite_inventory',r['remaining_conflicts'])

    def test_transaction_tiers_and_multiple_intents(self):
        intents, d=transaction_candidate([{'text':'For Sale or Ground Lease'}])
        self.assertEqual(intents,['For Sale','Ground Lease']);self.assertEqual(d['method'],'explicit')
        intents,d=transaction_candidate([{'text':'Asking price $900,000'},{'text':'SUITE 1 $22/SF Base'}])
        self.assertEqual(intents,['For Sale','For Lease']);self.assertEqual(d['method'],'strongly_derived')
        self.assertFalse(transaction_candidate([{'text':'Scheduled Base Rental Income $90,000'}])[0])

    def test_coordinates_contacts_and_wrong_subject(self):
        self.assertEqual(sum(r['import_candidate']['coordinates'] is None for r in V3['records']),82)
        for a,b in zip(V2['records'],V3['records']):
            if a.get('coordinate_reuse_count',0)>1:self.assertIsNone(b['import_candidate']['coordinates'])
            self.assertFalse(b['primary_contact_candidates'])
            self.assertTrue(all(not c['display_approved'] for c in b['secondary_contact_candidates']))
            if a['brochure_subject_mismatch']:
                self.assertEqual(b['import_readiness'],'BLOCKED');self.assertFalse(b['import_candidate']['contacts'])

    def test_woodsons_and_fm_policy(self):
        r=normalized('4095');self.assertEqual(len(r['import_candidate']['availability_contexts']),3)
        self.assertFalse(r['import_candidate']['suites'])
        self.assertEqual(normalized('21617')['pricing_policy'],'preserve_existing_suppression_and_review')

    def test_preservation_and_review_columns(self):
        self.assertEqual(len(V3['records']),87);self.assertEqual(V3['schema_version'],3)
        for name,digest in V3['preserved_input_sha256'].items():
            self.assertEqual(hashlib.sha256((ROOT/'docs/migration'/name).read_bytes()).hexdigest(),digest)
        for i,r in enumerate(V3['records']):
            self.assertEqual(r['v2_evidence_ref']['json_pointer'],f'/records/{i}')
            self.assertEqual(r['import_candidate']['brochure_url'],V2['records'][i]['brochure_url'])
        with (ROOT/'docs/migration/aspire-properties-review-v3.csv').open() as f:
            rows=list(csv.DictReader(f));self.assertEqual(len(rows),87)
            self.assertTrue(all(not r['review_decision'] and not r['review_notes'] for r in rows))
        self.assertTrue(V3['wordpress_unchanged'])
        self.assertTrue(set().union(*(set(v) for v in FACTS.values())) <= GATES.keys())

if __name__=='__main__':unittest.main()
