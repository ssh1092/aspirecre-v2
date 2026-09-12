"""Offline pilot planning safety; never calls WordPress or downloads media."""
import hashlib
import json
from pathlib import Path
import sys
import unittest
sys.path.insert(0,str(Path(__file__).resolve().parents[1]))
from pilot import prepare, ALLOW, ROOT

class PilotPlanTests(unittest.TestCase):
    def test_allowlist_and_determinism(self):
        before=(ROOT/'docs/migration/aspire-properties-manifest-v4.json').read_bytes()
        first=prepare()
        self.assertEqual(first,prepare())
        self.assertEqual({r['migration_id'] for r in first['records']},ALLOW)
        self.assertEqual(len(first['records']),6)
        self.assertFalse(first['backup_verified'])
        self.assertEqual(first['manifest_sha256'],hashlib.sha256(before).hexdigest())
        self.assertEqual(before,(ROOT/'docs/migration/aspire-properties-manifest-v4.json').read_bytes())

    def test_fm_brochure_is_never_a_public_asset(self):
        fm=next(r for r in prepare()['records'] if r['migration_id']=='aspire-021')
        self.assertIsNone(fm['brochure_asset'])
        self.assertTrue(fm['protected_brochure_path'].startswith('var/migration/'))
        self.assertTrue(all(a['kind']=='image' for a in fm['assets']))
        self.assertTrue(fm['import_candidate']['brochure_url'])

    def test_asset_identity_and_safe_new_coordinates(self):
        for r in prepare()['records']:
            assets={a['url']:a for a in r['assets']}
            self.assertEqual(len(assets),len(r['assets']))
            self.assertTrue(all(u in assets for u in r['gallery_assets']))
            self.assertIn(r['primary_asset'],assets)
            if r['migration_id'] in {'aspire-011','aspire-081'}:
                self.assertEqual(r['import_candidate']['coordinates']['status'],'accepted')
