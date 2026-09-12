"""Exercise cache reuse and validation without making network requests."""
import hashlib
import json
from pathlib import Path
import sys
import tempfile
import unittest
from unittest.mock import patch

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))
import brochures
from pypdf import PdfWriter


class BrochureCacheTests(unittest.TestCase):
    def test_pdf_and_extracted_text_reused(self):
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            url = 'https://example.test/property.pdf'
            key = hashlib.sha256(url.encode()).hexdigest()[:24]
            pdf = root / (key + '.pdf')
            writer = PdfWriter()
            writer.add_blank_page(width=100, height=100)
            writer.write(pdf)
            with patch.object(brochures, 'PDFS', root), patch.object(brochures.subprocess, 'run') as network:
                first = brochures.download(url)
                self.assertEqual(first['brochure_download_status'], 'success')
                self.assertEqual(first['brochure_text_status'], 'unavailable')
                text = root / (key + '.text.json')
                modified = text.stat().st_mtime_ns
                with patch.object(brochures, 'PdfReader', side_effect=AssertionError('Text should be cached')):
                    second = brochures.download(url)
                self.assertEqual(first['brochure_sha256'], second['brochure_sha256'])
                self.assertEqual(modified, text.stat().st_mtime_ns)
                network.assert_not_called()

    def test_invalid_cached_pdf_does_not_inherit_success(self):
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            url = 'https://example.test/property.pdf'
            key = hashlib.sha256(url.encode()).hexdigest()[:24]
            (root / (key + '.pdf')).write_text('<html>Not a PDF</html>')
            (root / (key + '.json')).write_text(json.dumps({'brochure_download_status': 'success'}))
            with patch.object(brochures, 'PDFS', root), patch.object(brochures.subprocess, 'run') as network:
                result = brochures.download(url)
                self.assertEqual(result['brochure_download_status'], 'failed')
                self.assertEqual(result['brochure_url'], url)
                network.assert_not_called()
