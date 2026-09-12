"""Offline Census evidence, classifier, map and preservation regressions."""
import copy
import csv
import hashlib
import io
import json
from pathlib import Path
import struct
import subprocess
import sys
import unittest

sys.path.insert(0,str(Path(__file__).resolve().parents[1]))
from geocode import submissions,input_bytes,parse_batch,classify,valid_coordinates,street_parts,distance_m,comparison,inside,wp_coordinate
ROOT=Path(__file__).resolve().parents[3]
V3=json.loads((ROOT/'docs/migration/aspire-properties-manifest-v3.json').read_text())
V4=json.loads((ROOT/'docs/migration/aspire-properties-manifest-v4.json').read_text())
ADDRESS={'address_line_1':'16840 CLAY ROAD','city':'HOUSTON','state':'TX','postal_code':'77084'}
ROW=['test','16840 CLAY ROAD, HOUSTON, TX, 77084','Match','Exact','16840 CLAY RD, HOUSTON, TX, 77084','-95.66,29.83','1234','R']

class GeocodingTests(unittest.TestCase):
    def test_only_authorized_unmodified_addresses(self):
        items=submissions(V3)
        self.assertEqual(len(items),74)
        self.assertEqual(sum(x['reason']=='v3_geocoding_eligible' for x in items.values()),71)
        self.assertEqual(sum(x['reason']=='source_coordinate_comparison' for x in items.values()),3)
        for item in items.values():
            r=V3['records'][item['record_index']]
            self.assertEqual(item['submitted_address'],r['import_candidate']['address'])
            self.assertTrue(r['geocoding_eligible'] or r['import_candidate']['coordinates'])
        rows=list(csv.reader(io.StringIO(input_bytes(items).decode())))
        self.assertEqual(len(rows),74)
        self.assertTrue(all(len(r)==5 for r in rows))

    def test_match_parser_and_identity_validation(self):
        s=io.StringIO();csv.writer(s).writerow(ROW)
        expected={'test':{'submitted_address':ADDRESS}}
        self.assertEqual(parse_batch(s.getvalue(),expected)['test'],ROW)
        with self.assertRaises(ValueError):parse_batch(s.getvalue()*2,expected)
        with self.assertRaises(ValueError):parse_batch('',expected)
        with self.assertRaises(ValueError):parse_batch(s.getvalue().replace('CLAY ROAD','OTHER ROAD'),expected)

    def test_address_standardization(self):
        self.assertEqual(street_parts('100 North Main Street'),street_parts('100 N MAIN ST.'))
        self.assertEqual(street_parts('6544 Greatwood Pky'),street_parts('6544 GREATWOOD PKWY'))
        self.assertEqual(street_parts('1689 Highway 87'),street_parts('1689 STATE HWY 87'))
        self.assertNotEqual(street_parts('100 Main Road'),street_parts('100 Other Road'))

    def test_accepted_review_unmatched_and_tie(self):
        self.assertEqual(classify(ROW,ADDRESS)['status'],'ACCEPTED')
        for matched,reason in [('16841 CLAY RD, HOUSTON, TX, 77084','street_number_mismatch'),('16840 OTHER RD, HOUSTON, TX, 77084','material_road_difference'),('16840 CLAY RD, HOUSTON, TX, 77085','zip_mismatch'),('16840 CLAY RD, HOUSTON, LA, 77084','state_mismatch')]:
            row=ROW.copy();row[4]=matched;r=classify(row,ADDRESS)
            self.assertEqual(r['status'],'REVIEW_REQUIRED');self.assertIn(reason,r['review_reasons'])
        row=ROW.copy();row[3]='Non_Exact';self.assertEqual(classify(row,ADDRESS)['status'],'REVIEW_REQUIRED')
        result=classify(['test','input','No_Match'],ADDRESS)
        self.assertEqual(result['status'],'UNMATCHED');self.assertIsNone(result['coordinate_candidate'])
        self.assertEqual(classify(['test','input','Tie'],ADDRESS)['status'],'REVIEW_REQUIRED')

    def test_coordinate_validation(self):
        for lat,lon in [(float('nan'),-95),(91,-95),(29,181),(None,-95)]:self.assertFalse(valid_coordinates(lat,lon))
        row=ROW.copy();row[5]='-95,nan';self.assertEqual(classify(row,ADDRESS)['status'],'REVIEW_REQUIRED')
        row[5]='-75,40';self.assertIn('outside_broad_texas_geography',classify(row,ADDRESS)['review_reasons'])

    def test_distance_and_thresholds(self):
        a={'latitude':0,'longitude':0}
        self.assertEqual(distance_m(a,a),0)
        self.assertAlmostEqual(distance_m(a,{'latitude':0,'longitude':1}),111195.1,places=1)
        for longitude,status in [(0.001,'CONSISTENT'),(0.01,'REVIEW'),(0.1,'CONFLICT')]:self.assertEqual(comparison(a,{'latitude':0,'longitude':longitude})['status'],status)
        self.assertIsNone(distance_m(a,None))

    def test_actual_pmtiles_extraction(self):
        path=ROOT/V4['pmtiles']['path']
        with path.open('rb') as f:header=f.read(127)
        self.assertEqual(header[:7],b'PMTiles')
        # Independent binary-header assertion validates installed-reader output.
        west,south,east,north=struct.unpack_from('<iiii',header,102)
        self.assertEqual(V4['pmtiles']['bounds'],dict(zip(['west','south','east','north'],[x/1e7 for x in [west,south,east,north]])))
        self.assertEqual([V4['pmtiles']['min_zoom'],V4['pmtiles']['max_zoom']],list(header[100:102]))
        actual=json.loads(subprocess.check_output(['node','tools/migration/inspect-map.mjs'],cwd=ROOT))
        self.assertEqual(actual,V4['pmtiles'])

    def test_inside_outside(self):
        bounds=V4['pmtiles']['bounds']
        self.assertTrue(inside({'latitude':29.8,'longitude':-95.5},bounds))
        self.assertTrue(inside({'latitude':bounds['south'],'longitude':bounds['west']},bounds))
        self.assertFalse(inside({'latitude':30.1,'longitude':-94.1},bounds))
        self.assertIsNone(inside(None,bounds))
        self.assertEqual(V4['summary']['inside_map'],47);self.assertEqual(V4['summary']['outside_map'],3)

    def test_wp_comparisons(self):
        entries=[r for r in V4['records'] if r['existing_wp_comparison']['post_id']]
        self.assertEqual(len(entries),4)
        clay=next(r for r in entries if '16840' in r['source_title'])
        self.assertEqual(clay['existing_wp_comparison']['status'],'REVIEW')
        self.assertAlmostEqual(clay['existing_wp_comparison']['distance_m'],390.9,places=1)
        snapshot={'properties':[{'post_name':'clay','ID':'120'}],'meta':[{'post_id':'120','meta_key':'_aspire_latitude','meta_value':'29.8'},{'post_id':'120','meta_key':'_aspire_longitude','meta_value':'-95.6'}]}
        self.assertEqual(wp_coordinate({'import_candidate':{'slug':'clay'}},snapshot),('120',{'latitude':29.8,'longitude':-95.6}))

    def test_v3_and_all_prior_artifacts_unchanged(self):
        for name,digest in V4['preserved_audit_sha256'].items():self.assertEqual(hashlib.sha256((ROOT/'docs/migration'/name).read_bytes()).hexdigest(),digest)
        for a,b in zip(V3['records'],V4['records']):
            expected=copy.deepcopy(a['import_candidate']);expected['coordinates']=b['import_candidate']['coordinates']
            self.assertEqual(expected,b['import_candidate'])
            for key in a:
                if key!='import_candidate':self.assertEqual(a[key],b[key])
            if b['geocoding']['status']!='ACCEPTED':self.assertIsNone(b['import_candidate']['coordinates'])
        self.assertEqual(sum(r['source_coordinate_comparison']['status']=='CONSISTENT' for r in V4['records']),3)

    def test_cached_output_determinism(self):
        paths=[ROOT/'docs/migration'/n for n in ['aspire-properties-manifest-v4.json','aspire-properties-review-v4.csv','aspire-properties-geocoding-summary.md']]
        before=[p.read_bytes() for p in paths]
        subprocess.check_output([sys.executable,'tools/migration/geocode.py'],cwd=ROOT)
        self.assertEqual(before,[p.read_bytes() for p in paths])
        with paths[1].open() as f:rows=list(csv.DictReader(f))
        self.assertEqual(len(rows),87);self.assertTrue(all(not r['review_decision'] and not r['review_notes'] for r in rows))

if __name__=='__main__':unittest.main()
