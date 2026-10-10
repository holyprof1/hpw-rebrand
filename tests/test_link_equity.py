import csv
import json
import subprocess
import sys
import tempfile
import unittest
from pathlib import Path

TOOL = Path(__file__).resolve().parents[1] / 'tools' / 'link-equity.py'

class LinkEquityEvidenceTest(unittest.TestCase):
    def report(self, path='/unknown/', rules=None, exports=None):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            (root / 'removed.json').write_text(json.dumps({'trashed': [{'path': path}], 'gone': [], 'rules': rules or []}))
            for name, rows in (exports or {}).items():
                with (root / name).open('w', newline='') as f:
                    csv.writer(f).writerows(rows)
            subprocess.run([sys.executable, str(TOOL), '--removed', str(root / 'removed.json'), '--gsc', str(root), '--out', str(root / 'report.csv')], check=True, capture_output=True)
            with (root / 'report.csv').open() as f:
                return next(csv.DictReader(f))

    def test_missing_data_does_not_authorize_retirement(self):
        row = self.report()
        self.assertEqual(row['class'], 'MANUAL REVIEW')
        self.assertEqual(row['backlink_coverage'], 'unknown')
        self.assertEqual(row['performance_coverage'], 'unknown')

    def test_explicit_zero_rows_still_need_retirement_review(self):
        row = self.report(exports={'links.csv': [['Target page', 'Incoming links', 'Linking sites'], ['/unknown/', 0, 0]], 'pages.csv': [['Top pages', 'Clicks', 'Impressions', 'Position'], ['/unknown/', 0, 0, 0]]})
        self.assertEqual(row['class'], 'REVIEW RETIREMENT')
        self.assertEqual(row['backlink_coverage'], 'observed row')

    def test_same_entity_is_only_a_redirect_candidate(self):
        row = self.report(path='/expedia-complaints-how-to-resolve/')
        self.assertEqual(row['class'], 'REVIEW EQUIVALENT REDIRECT')
        self.assertIn('old search intent', row['reason'])

    def test_existing_redirect_is_preserved(self):
        self.assertEqual(self.report(rules=['/unknown/ | /replacement/'])['class'], 'ALREADY 301')

    def test_domain_suffix_is_a_review_flag_not_link_discard(self):
        row = self.report(exports={'links.csv': [['Target page', 'Linking page'], ['/unknown/', 'https://useful.example.xyz/article']]})
        self.assertEqual(row['linking_domains'], '1')
        self.assertEqual(row['domains_flagged_for_review'], 'example.xyz')

if __name__ == '__main__':
    unittest.main()
