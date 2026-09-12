"""Offline V3 review candidates. No network requests or WordPress write path."""
import collections
import copy
import csv
import hashlib
import json
import re
from pathlib import Path
from urllib.parse import urlsplit

from bs4 import BeautifulSoup
from discover import ROOT, CACHE
from enrichment_parser import FACTS, NUMERIC
from parser import clean, normalized_url

OUT = ROOT / 'docs/migration'
V2 = OUT / 'aspire-properties-manifest-v2.json'
PRESERVED = ['aspire-properties-manifest.json', 'aspire-properties-review.csv',
             'aspire-properties-summary.md', 'aspire-properties-manifest-v2.json',
             'aspire-properties-review-v2.csv', 'aspire-properties-enrichment-summary.md']
NUMBER = r'(\d[\d,]*(?:\.\d+)?)'
YEAR_PAIR = r'year\s+built\s*/\s*renovated\s*[:–—-]?\s*(\d{4})\s*/\s*(\d{4})'
TYPE_KEYS = {'Retail': 'retail', 'Office': 'office', 'Office Condo': 'office',
             'Industrial / Flex': 'industrial', 'Land': 'land'}

# Every field in the V2 catalog has an explicit positive gate here. Broad keyword
# matches alone (power, amenities, available, kitchen, water) are insufficient.
GATES = {
 'construction_type': r'masonry construction|tilt[ -]?wall|steel (?:frame|construction)|concrete construction',
 'clear_height': r'\d+(?:\.\d+)?\s*[’\'′]?\s*(?:ft\s*)?clear(?: height)?',
 'dock_high_doors': r'dock[ -]?high|dock doors',
 'grade_level_doors': r'grade[ -]?(?:level|loading)|drive[ -]in doors',
 'loading_configuration': r'rear[ /-]?load|front[ /-].*load|cross[ -]?dock|grade.level loading',
 'sprinklered': r'(?:fully |not |non[ -])?sprinklered|sprinkler system|ESFR',
 'office_sf': r'\d[\d,]*\s*SF\s*(?:of )?office',
 'warehouse_sf': r'\d[\d,]*\s*SF\s*(?:of )?warehouse',
 'outdoor_storage': r'(?:industrial )?outdoor storage|\bIOS\b',
 'yard': r'(?:fenced|paved|secured|storage|truck) yard',
 'power_capacity': r'\d[\d,]*\s*(?:amps?|volts?|kVA|phase)\b',
 'HVAC': r'HVAC|air.conditioned|central (?:heating|cooling)',
 'parking': r'\d+ parking spaces|(?:surface|covered|secured|garage|concrete) parking',
 'access_notes': r'access (?:to|via) .+|highway access .+',
 'traffic_counts_by_road': r'\d[\d,]*\s*VPD',
 'frontage': r'\d[\d,]*\s*(?:feet|ft|[’\'])\s*(?:of )?frontage|frontage (?:on|along) .+',
 'signalized_intersection': r'signalized intersection (?:of|at|with) .+',
 'turn_lane': r'(?:dedicated|left|right|center) turn lane',
 'ingress_egress': r'ingress|egress|\d+ curb cuts?',
 'visibility': r'(?:visibility (?:from|on|along)|visible from) .+',
 'signage': r'(?:monument|pylon|building) sign(?:age)?|signage (?:on|along) .+',
 'tenant_mix': r'tenant mix.*(?:including|medical|retail)|anchored by .+|tenants includ',
 'nearby_retailers': r'(?:surrounded by|nearby|national) retailers includ.+',
 'population_radius_facts': r'(?:mile|radius).*population.*\d',
 'average_household_income': r'(?:household income|AHHI).*\$?\d[\d,]+',
 'drive_thru': r'(?:existing |with |includes? |equipped.*)drive.thr[ou]|drive.thr[ou] (?:infrastructure|lane|window)',
 'grease_trap': r'grease trap',
 'restaurant_infrastructure': r'grease trap|vent hood|commercial kitchen (?:infrastructure|equipment|build.out)|restaurant (?:build.out|equipment|infrastructure)|(?:second[ -]gen(?:eration)?|former) restaurant|drive.thr[ou] infrastructure|(?:gas|plumbing) (?:connection|infrastructure)',
 'building_class': r'(?:building )?class\s*[:–—-]?\s*[ABC]\b',
 'building_size': r'\d[\d,]*\s*SF\s*(?:office )?building|building (?:size|area).*\d',
 'availability_range': r'(?:availab|suite|unit).*\d[\d,]*\s*(?:SF|square feet)|\d[\d,]*(?:\s*[–—-]\s*\d[\d,]*)?\s*(?:SF|square feet).*availab',
 'parking_ratio': r'\d+(?:\.\d+)?\s*/\s*1,?000\s*SF|\d+(?:\.\d+)?\s*spaces? per\s*\d',
 'parking_type': r'surface parking|parking garage|(?:paved|concrete) parking lot',
 'covered_parking': r'covered parking',
 'building_signage': r'building (?:and monument )?signage',
 'monument_signage': r'monument signage',
 'on_site_management': r'on.site manage(?:ment|d)',
 '24_7_access': r'24[/ -]7.*access|24.hour access',
 'elevator': r'elevators?',
 'security_access': r'(?:card[ -]?key|key[ -]?card|controlled|gated|secured) access|security (?:system|cameras)|fully gated',
 'amenities': r'(?:conference room|fitness center|break ?room|kitchenette|tenant lounge)',
 'office_configuration': r'\d+ private offices|office conversion|converted .+|open (?:plan|office)|medical (?:build.out|office space)',
 'move_in_ready': r'move.in.ready',
 'furnished': r'(?:fully )?furnished',
 'acreage': r'\d+(?:\.\d+)?\s*-?\s*acres?\b',
 'utilities': r'utilities (?:on site|available|to site)|\d+\s*["”]?\s*(?:inch\s*)?(?:water|sewer)|(?:water|sewer|electricity) (?:available|on site)',
 'detention': r'(?:no |off.site |on.site )detention|detention (?:required|provided|available)',
 'zoning': r'(?:zoning|zoned)\s*[:–—-]?\s*\S+|no zoning',
 'restrictions': r'(?:no|deed|use) restrictions|unrestricted',
 'floodplain': r'(?:outside|within|in|not in|out of).*flood(?:plain| plain| zone|way)|floodplain\s*:',
 'development_ready': r'development.ready',
 'shovel_ready': r'shovel.ready',
 'access_points': r'\d+ (?:access points|curb cuts)|ingress|egress',
 'signalized_access': r'signalized (?:access|intersection)',
 'cross_access': r'cross.access',
 'ETJ': r'(?:Houston|city|county).*\bETJ\b|extraterritorial jurisdiction',
 'nearby_corridors': r'(?:access to|near|minutes from|from|along).*(?:tollway|freeway|highway|corridor)',
}
assert set().union(*(set(v) for v in FACTS.values())) <= GATES.keys()


def unique(items):
    return list({json.dumps(x, sort_keys=True): x for x in items}.values())


def evidence_pool(r):
    out = []
    def add(text, source, pointer, page=None):
        if isinstance(text, str) and clean(text):
            out.append({'source': source, 'text': clean(text), 'v2_pointer': pointer,
                        **({'page': page} if page else {})})
    for i, text in enumerate(r['property_highlights']):
        add(text, 'page', f'/property_highlights/{i}')
    for field, model in r['enriched_fields'].items():
        for i, e in enumerate(model['evidence']):
            add(e['text'], e['source'], f'/enriched_fields/{field}/evidence/{i}', e.get('page'))
    for typ, fields in r['decision_facts'].items():
        for field, model in fields.items():
            for i, e in enumerate(model['evidence']):
                add(e['text'], e['source'], f'/decision_facts/{typ}/{field}/evidence/{i}', e.get('page'))
    for i, e in enumerate(r['transaction_evidence_v2']):
        add(e['text'], e['source'], f'/transaction_evidence_v2/{i}', e.get('page'))
    for i, s in enumerate(r['suite_candidates_v2']):
        for j, e in enumerate(s['evidence']):
            add(e['text'], e['source'], f'/suite_candidates_v2/{i}/evidence/{j}', e.get('page'))
    if r.get('brochure_subject_mismatch'):
        out = [e for e in out if e['source'] != 'brochure']
    return unique(out)


def price_anomalies(pool):
    result = []
    for e in pool:
        for m in re.finditer(r'\$\s*(\d[\d,]*(?:\.\d+)?)', e['text']):
            raw = m[1]
            if ',' in raw and not re.fullmatch(r'\d{1,3}(?:,\d{3})+(?:\.\d+)?', raw):
                result.append({**e, 'raw_number': m[0], 'warning': 'price_format_anomaly'})
    return unique(result)


def transaction_candidate(pool):
    evidence = []
    for e in pool:
        text = e['text']
        if re.search(r'rent roll|rental income|leaseback|lease start|lease end|parking.*\$', text, re.I):
            continue
        intents = []
        if re.search(r'for sale or ground lease', text, re.I):
            intents = ['For Sale', 'Ground Lease']
        elif re.search(r'for (?:sale|purchase)\s*(?:or|/)\s*lease|available for purchase or lease', text, re.I):
            intents = ['For Sale', 'For Lease']
        else:
            if re.search(r'for sale|available for purchase', text, re.I): intents.append('For Sale')
            if re.search(r'ground lease(?! rate)', text, re.I): intents.append('Ground Lease')
            if re.search(r'for lease', text, re.I): intents.append('For Lease')
        tier = 'A'
        if not intents:
            tier = 'B'
            if re.search(r'ground lease rate', text, re.I): intents = ['Ground Lease']
            elif not price_anomalies([e]):
                if re.search(r'(?:asking price|sale price|^price\s*[:–—-]).*\$', text, re.I): intents.append('For Sale')
                if re.search(r'lease rate.*\$|base rent.*\$|\$[\d,.]+\s*/\s*SF\s*(?:Base|NNN)|\bSUITE\b.*\$[\d,.]+\s*/\s*SF', text, re.I): intents.append('For Lease')
        if intents: evidence.append({**e, 'tier': tier, 'intents': intents})
    intents = [x for x in ['For Sale', 'For Lease', 'Ground Lease'] if any(x in e['intents'] for e in evidence)]
    explicit = {x for e in evidence if e['tier'] == 'A' for x in e['intents']}
    derived = bool(set(intents) - explicit)
    method = 'explicit' if intents and not derived else ('strongly_derived' if intents else 'unresolved')
    return intents, {'method': method, 'confidence': 'high' if method == 'explicit' else ('review_required' if intents else None), 'evidence': evidence}


def traffic(pool):
    groups = {}
    for e in pool:
        for m in re.finditer(NUMBER + r'\s*VPD(?:\s+on\s+(.+?))?(?=\s+and\s+\d|[;|]|$)', e['text'], re.I):
            road = clean(m[2]).strip(' .') if m[2] else None
            vpd = int(float(m[1].replace(',', '')))
            key = re.sub(r'[^a-z0-9]', '', (road or 'unspecified').lower())
            g = groups.setdefault(key, {'road': road, 'values': [], 'evidence': []})
            g['values'].append(vpd); g['evidence'].append(e)
    result = []
    for g in groups.values():
        values = sorted(set(g.pop('values')))
        result.append({**g, 'vpd': values[0] if len(values) == 1 else None,
                       'field_conflict': len(values) > 1, 'source_values': values})
    return result


def clean_facts(r, typ, pool, metrics, traffic_records):
    result, statuses, removed = {}, {}, []
    old = next(iter(r['decision_facts'].values()))
    links = {'clear_height': 'clear_height_ft', 'office_sf': 'office_sf', 'warehouse_sf': 'warehouse_sf',
             'building_size': 'building_sf', 'building_class': 'building_class', 'acreage': 'lot_acres'}
    for field in FACTS[typ]:
        evidence = []
        for e in pool:
            text = e['text']
            if re.search(r'/C\d|\b(?:Nearby Amenities|Property Highlights|Property Details)\s*$', text, re.I): continue
            if re.search(r'[,;:]$|\b(?:and|of|including|with|the)\s*$', text, re.I): continue
            if field == 'frontage' and re.search(r'SF of frontage', text, re.I): continue
            if re.search(GATES[field], text, re.I): evidence.append(e)
        # Reject inherited keyword matches explicitly for review; evidence remains in V2.
        for e in old.get(field, {}).get('evidence', []):
            if not any(e['text'] == c['text'] for c in evidence):
                removed.append({'field': field, 'text': e['text'], 'reason': 'fails_context_or_fact_gate'})
        conflict = False
        if field in links:
            value = metrics.get(links[field])
            conflict = bool(r['enriched_fields'].get(links[field], {}).get('field_conflict'))
            if value is None: evidence = []
        elif field == 'traffic_counts_by_road':
            value = [x for x in traffic_records if not x['field_conflict']]
            conflict = any(x['field_conflict'] for x in traffic_records)
        else:
            value = list(dict.fromkeys(clean(part) for e in evidence for part in re.split(r'\s*[|•]\s*', e['text']) if len(part) <= 300 and re.search(GATES[field], part, re.I)))
            if field in ['sprinklered', 'covered_parking', 'furnished', 'utilities', 'detention', 'floodplain']:
                neg = any(re.search(r'\bno\b|\bnot\b|without|outside|non[ -]', e['text'], re.I) for e in evidence)
                pos = any(not re.search(r'\bno\b|\bnot\b|without|outside|non[ -]', e['text'], re.I) for e in evidence)
                conflict = neg and pos
        statuses[field] = 'conflicting' if conflict else ('known' if evidence and value is not None and value != [] else 'unknown')
        if statuses[field] == 'known': result[field] = {'value': value, 'evidence': unique(evidence), 'attribution': 'per source; not independently verified'}
    return result, statuses, unique(removed)


def page_blocks(record, index):
    entry = index[record['source_url']]
    path = CACHE / entry['cache']
    soup = BeautifulSoup(path.read_text(), 'html.parser')
    blocks = []
    for i, block in enumerate(soup.select('.blog-item-content .sqs-html-content')):
        lines = [clean(t.get_text(' ', strip=True)) for t in block.select('h1,h2,h3,h4,p,li') if not (t.name == 'p' and t.find_parent('li'))]
        blocks.append({'lines': lines, 'source': 'page', 'cache_sha256': hashlib.sha256(path.read_bytes()).hexdigest(), 'block_index': i})
    return blocks


def parcels(r, blocks):
    if r['property_type'] != 'Land': return []
    contexts = []
    for block in blocks:
        current = {'context': 'Property offering', 'lot_acres': None, 'sale_price': None, 'price_per_sf_display': None, 'ground_lease_rate_display': None, 'evidence': []}
        def finish():
            if current['lot_acres'] is not None: contexts.append(copy.deepcopy(current))
        for line in block['lines']:
            if re.match(r'^(?:RETAIL PAD|PARCEL|TRACT|LOT [A-Z0-9])\b', line, re.I):
                finish(); current = {**{k: None for k in current}, 'context': line, 'evidence': []}
            if re.match(r'Property Highlights', line, re.I): break
            field, value = None, None
            m = re.search(r'LOT SIZE\s*[:–—-]?\s*' + NUMBER + r'\s*Acres?', line, re.I)
            if m: field, value = 'lot_acres', float(m[1].replace(',', ''))
            m = re.match(r'PRICE\s*[:–—-]\s*\$' + NUMBER, line, re.I)
            if m and not price_anomalies([{'text': line}]): field, value = 'sale_price', float(m[1].replace(',', ''))
            if re.match(r'PPF\b', line, re.I): field, value = 'price_per_sf_display', line
            if re.match(r'GROUND LEASE RATE', line, re.I): field, value = 'ground_lease_rate_display', line
            if field:
                current[field] = value
                current['evidence'].append({**{k: v for k, v in block.items() if k != 'lines'}, 'text': line})
        finish()
    contexts = list({json.dumps({k:v for k,v in c.items() if k != 'evidence'},sort_keys=True):c for c in contexts}.values())
    # Map labels establish additional parcel extents, but cannot attach an adjacent
    # price or an availability claim without a reliably associated source block.
    if len(contexts) > 1:
        sizes = {c['lot_acres'] for c in contexts}
        for e in r['enriched_fields']['lot_acres']['evidence']:
            if e['value'] not in sizes and e['source'] == 'brochure':
                contexts.append({'context': 'Source map parcel label', 'lot_acres': e['value'], 'sale_price': None, 'review_required': True, 'evidence': [e]}); sizes.add(e['value'])
    return contexts if len(contexts) > 1 else []


def normalize_suites(r):
    result = []
    for i, original in enumerate(r['suite_candidates_v2']):
        if r.get('brochure_subject_mismatch') and any(e['source'] == 'brochure' for e in original['evidence']): continue
        s = {k: copy.deepcopy(original.get(k)) for k in ['suite_name', 'square_feet', 'rate', 'rate_type', 'former_use', 'availability_status', 'notes', 'evidence', 'field_conflict']}
        text = original.get('source_text') or original.get('notes') or ''
        nnn = re.search(r'\$' + NUMBER + r'\s*/\s*SF\s*(NNN|CAM)', text, re.I)
        s['nnn_cam'] = {'value': float(nnn[1].replace(',', '')), 'unit': '$/SF', 'type': nnn[2].upper()} if nnn else None
        s['source_total_space_available'] = None
        total = re.search(r'total space available\s*[:–—-]?\s*' + NUMBER + r'\s*SF', text, re.I)
        if total:
            s['source_total_space_available'] = float(total[1].replace(',', ''))
            if s['square_feet'] == s['source_total_space_available']: s['square_feet'] = None
        if s['availability_status'] and not re.search(r'\b' + re.escape(s['availability_status'].replace('_', ' ')) + r'\b', text, re.I): s['availability_status'] = None
        s['source_ref'] = f'/suite_candidates_v2/{i}'
        result.append(s)
    return result


def normalized_contacts(r):
    groups = {}
    for i, c in enumerate(r['broker_candidates']):
        if c.get('confidence') == 'review_subject_mismatch': continue
        email = c['email'].strip().lower(); phone = re.sub(r'\D', '', c['phone'])
        if len(phone) == 11 and phone.startswith('1'): phone = phone[1:]
        key = (re.sub(r'[^a-z]', '', c['name'].lower()), email)
        item = groups.setdefault(key, {'name': clean(c['name']), 'title': c['title'], 'phone': '+1' + phone if len(phone) == 10 else None, 'email': email, 'association': 'property_associated_team_candidate', 'display_approved': False, 'evidence': []})
        item['evidence'].append({'source_ref': f'/broker_candidates/{i}', 'text': c['evidence_text'], 'page': c.get('page'), 'source': 'brochure'})
        if re.search(r'info@|general (?:inquiries|contact)', c['evidence_text'], re.I): item['association'] = 'generic_company_contact'
    # Team membership is not an explicit primary designation. No primary chosen.
    return [], list(groups.values())


def normalize_record(r, blocks=()):
    pool = evidence_pool(r); anomalies = price_anomalies(pool)
    metrics = {k: copy.deepcopy(v['candidate']) for k,v in r['enriched_fields'].items() if k in set(NUMERIC) | {'building_class', 'parking_ratio', 'sale_price'} and v['candidate'] is not None and not v['field_conflict']}
    metrics.pop('traffic_count_vpd', None)
    corrections = []
    paired = [e for e in pool if re.search(YEAR_PAIR, e['text'], re.I)]
    if paired:
        for field, group in [('year_built', 1), ('renovated_year', 2)]:
            values = {int(re.search(YEAR_PAIR,e['text'],re.I)[group]) for e in paired}
            metrics.pop(field, None)
            if len(values) == 1: metrics[field] = values.pop()
        corrections.append({'rule': 'combined_year_label', 'evidence': paired})
    if anomalies: metrics.pop('sale_price', None)
    typ = r['property_type']; type_evidence = []
    signatures = [r'\d[\d,]* SF warehouse', r'\d[\d,]* SF office', r'tilt[ -]?wall', r'\d+[’\']?\s*clear height', r'dock.high doors', r'loading configuration']
    if typ in ['Office', 'Office Condo'] and all(any(re.search(p, e['text'], re.I) for e in pool) for p in signatures):
        typ = 'Industrial / Flex'
        type_evidence = [e for e in pool if any(re.search(p,e['text'],re.I) for p in signatures)]
    intents, derivation = transaction_candidate(pool)
    traffic_records = traffic(pool)
    land = parcels(r, blocks)
    suites = normalize_suites(r)
    contexts = copy.deepcopy(r['availability_contexts_v2']) if not r.get('brochure_subject_mismatch') else []
    resolved = []
    if land:
        metrics.pop('lot_acres', None); metrics.pop('sale_price', None)
        resolved += [x for x in ['lot_acres','sale_price'] if x in r['brochure_page_conflicts']]
    if len(traffic_records) > 1 and all(not t['field_conflict'] and t['road'] for t in traffic_records): resolved.append('traffic_count_vpd')
    # Only clear aggregate rate conflicts when all source values can be assigned
    # to separate named suites; same-suite disagreements remain unresolved.
    rate_values = {s['rate'] for s in suites if s['rate'] is not None}
    for field in ['lease_rate_min','lease_rate_max']:
        model = r['enriched_fields'][field]
        if model['field_conflict'] and len(rate_values) > 1 and all(e['value'] in rate_values for e in model['evidence']) and not any(s['field_conflict'] for s in suites): resolved.append(field)
    if contexts:
        resolved += [f for f in ['minimum_available_sf','maximum_available_sf','lease_rate_min','lease_rate_max'] if f in r['brochure_page_conflicts'] and not any(c['field_conflict'] for c in contexts)]
    if intents: resolved.append('transaction_type')
    facts, statuses, removed = clean_facts(r, TYPE_KEYS[typ], pool, metrics, traffic_records)
    if land:
        facts['acreage'] = {'value': [{'context': c['context'], 'lot_acres': c['lot_acres']} for c in land], 'evidence': [e for c in land for e in c['evidence']], 'attribution': 'separate source offerings; map-only label requires review'}
        statuses['acreage'] = 'known'
    address = copy.deepcopy(r['address_candidate_v2'])
    if address:
        street = address['address_line_1']; parts = re.split(r'\s+[–—-]\s+',street)
        if len(parts) == 2 and re.sub(r'\W','',parts[0].lower()) == re.sub(r'\W','',parts[1].lower()): address['address_line_1'] = parts[0]
    complete_address = bool(address and all(address.get(k) for k in ['address_line_1','city','state','postal_code']))
    coordinate = None
    if r.get('coordinate_confidence') == 'high' and not r.get('coordinate_conflict') and r.get('coordinate_reuse_count',0) <= 1 and r.get('latitude') is not None and r.get('longitude') is not None:
        coordinate = {'latitude': r['latitude'], 'longitude': r['longitude'], 'source': r['coordinate_source'], 'confidence': 'source_metadata_unverified'}
    primary, secondary = normalized_contacts(r)
    gallery, seen = [], set()
    for image in r['gallery_images']:
        url = normalized_url(image['source_url'])
        if url in seen or re.search(r'logo|social|newsletter|favicon', urlsplit(url).path.split('/')[-1], re.I): continue
        seen.add(url); gallery.append(copy.deepcopy(image))
    image = r['featured_image_url_v2']; image_review = r['featured_image_confidence_v2'] != 'high'
    if image and re.search(r'logo|social|newsletter|favicon', urlsplit(image).path.split('/')[-1], re.I):
        image = gallery[0]['source_url'] if gallery else None
        image_review = True
    warnings = []
    real_conflicts = [f for f in r['brochure_page_conflicts'] if f not in resolved]
    real_conflicts = sorted(set(real_conflicts) | {'decision_facts.' + k for k,v in statuses.items() if v == 'conflicting' and k not in {'acreage','traffic_counts_by_road'}})
    if anomalies: warnings.append('price_format_anomaly')
    if 'existing_wp_pricing_review' in r['warnings']: warnings.append('existing_wp_pricing_review')
    if derivation['method'] == 'strongly_derived': warnings.append('derived_transaction_requires_approval')
    if not intents: warnings.append('transaction_unresolved')
    if typ != r['property_type']: warnings.append('property_type_change_requires_approval')
    if image_review: warnings.append('primary_image_review_required')
    if not coordinate: warnings.append('needs_geocoding')
    if r['brochure_text_status'] != 'available': warnings.append('brochure_text_unavailable')
    if any(c.get('review_required') for c in land): warnings.append('parcel_map_label_requires_review')
    if len(intents) > 1: warnings.append('multi_transaction_frontend_schema_gap')
    blockers = []
    if not complete_address: blockers.append('unresolved_critical_address')
    if r.get('brochure_subject_mismatch'): blockers.append('wrong_brochure_subject')
    if not r['source_title'] or not r['proposed_wp_slug'] or not r['source_url']: blockers.append('unusable_identity')
    readiness = 'BLOCKED' if blockers else 'REVIEW_REQUIRED' if real_conflicts or any(w not in ['needs_geocoding','brochure_text_unavailable'] for w in warnings) else 'READY_WITH_NULLS' if not coordinate or len(metrics) < len(NUMERIC) or any(v=='unknown' for v in statuses.values()) else 'READY'
    property_pricing = {'sale_price': metrics.get('sale_price'), 'price_display': [], 'lease_rate_display': [], 'evidence': []}
    if not land and not anomalies:
        for field in ['price_display', 'lease_rate_display']:
            if field == 'lease_rate_display' and (suites or contexts): continue
            conflicts = ['sale_price', 'price_per_sf'] if field == 'price_display' else ['lease_rate_min', 'lease_rate_max']
            if any(f in real_conflicts for f in conflicts): continue
            for e in r['enriched_fields'][field]['evidence']:
                if r.get('brochure_subject_mismatch') and e['source'] == 'brochure': continue
                if re.search(r'SUITE|RETAIL PAD|rental income|household|parking', e['text'], re.I): continue
                if not re.search(r'asking price|sale price|^price\s*[:–—-]|lease rate|base rent|\$[\d,.]+/SF\s*(?:Base|NNN)', e['text'], re.I): continue
                property_pricing[field].append(e['text']); property_pricing['evidence'].append(e)
            property_pricing[field] = list(dict.fromkeys(property_pricing[field]))
    candidate = {'title': r['source_title'], 'slug': r['proposed_wp_slug'], 'source_url': r['source_url'], 'property_type': typ,
                 'transaction_types': intents, 'address': address if complete_address else None, 'coordinates': coordinate,
                 'primary_image': image, 'gallery': gallery, 'brochure_url': r['brochure_url'], 'brochure_sha256': r['brochure_sha256'],
                 'brochure_text_status': r['brochure_text_status'], 'metrics': metrics, 'pricing': property_pricing, 'suites': suites,
                 'availability_contexts': contexts, 'offering_contexts': land, 'traffic_counts': [t for t in traffic_records if not t['field_conflict']],
                 'contacts': secondary, 'decision_facts': facts}
    return {'source_title': r['source_title'], 'source_url': r['source_url'], 'source_category': r['source_category'],
            'property_type_v2': r['property_type'], 'property_type_changed': typ != r['property_type'], 'property_type_evidence': type_evidence,
            'transaction_v2': r['transaction_type_candidate_v2'], 'transaction_types_candidate': intents, 'transaction_derivation': derivation,
            'import_candidate': candidate, 'decision_facts_import': facts, 'decision_field_status': statuses,
            'primary_contact_candidates': primary, 'secondary_contact_candidates': secondary,
            'address_status': 'reliable' if complete_address else 'review_required', 'address_review_required': not complete_address,
            'coordinate_status': 'source_candidate' if coordinate else 'needs_geocoding', 'geocoding_eligible': not coordinate and complete_address,
            'primary_image_review_required': image_review, 'featured_image_in_gallery': normalized_url(image or '') in seen,
            'price_anomalies': anomalies, 'corrections': corrections, 'parser_matches_removed': removed,
            'context_conflicts_resolved': sorted(set(resolved) & set(r['brochure_page_conflicts'])), 'remaining_conflicts': real_conflicts,
            'traffic_evidence': traffic_records, 'blocking_conflicts': blockers, 'non_blocking_warnings': warnings,
            'import_readiness': readiness, 'review_required': readiness in ['REVIEW_REQUIRED','BLOCKED'],
            'publication_approved': False, 'pricing_policy': 'preserve_existing_suppression_and_review' if 'existing_wp_pricing_review' in r['warnings'] else None}


def run():
    hashes = {name: hashlib.sha256((OUT/name).read_bytes()).hexdigest() for name in PRESERVED}
    v2 = json.loads(V2.read_text())
    if len(v2['records']) != 87: raise ValueError('Expected 87 V2 records')
    before = json.loads((CACHE/'normalization-before.json').read_text())
    after = json.loads((CACHE/'normalization-after.json').read_text())
    if before != after: raise ValueError('WordPress read-only snapshots differ')
    index = {x['url']:x for x in json.loads((CACHE/'crawl-index.json').read_text())}
    records = []
    for i, r in enumerate(v2['records']):
        n = normalize_record(r, page_blocks(r,index))
        n['v2_evidence_ref'] = {'path': 'aspire-properties-manifest-v2.json', 'sha256': hashes[V2.name], 'json_pointer': f'/records/{i}', 'source_url': r['source_url']}
        records.append(n)
    if len({r['import_candidate']['slug'] for r in records}) != 87: raise ValueError('Candidate slug collision')
    summary = {
        'normalized': len(records), 'readiness': {k:sum(r['import_readiness']==k for r in records) for k in ['READY','READY_WITH_NULLS','REVIEW_REQUIRED','BLOCKED']},
        'transaction_coverage': {'v2':sum(bool(r['transaction_v2']) for r in records), 'v3':sum(bool(r['transaction_types_candidate']) for r in records)},
        'transaction_methods': dict(collections.Counter(r['transaction_derivation']['method'] for r in records)),
        'property_type_changes': [{'source_title':r['source_title'],'before':r['property_type_v2'],'after':r['import_candidate']['property_type']} for r in records if r['property_type_changed']],
        'addresses_reliable': sum(r['address_status']=='reliable' for r in records),
        'geocoding_needed': sum(r['coordinate_status']=='needs_geocoding' for r in records),
        'geocoding_eligible_addresses': sum(r['geocoding_eligible'] for r in records),
        'parser_matches_removed': sum(len(r['parser_matches_removed']) for r in records),
        'context_conflicts_resolved': dict(collections.Counter(f for r in records for f in r['context_conflicts_resolved'])),
        'remaining_conflicts': dict(collections.Counter(f for r in records for f in r['remaining_conflicts'])),
        'properties_with_remaining_conflicts': sum(bool(r['remaining_conflicts']) for r in records),
        'suite_count': sum(len(r['import_candidate']['suites']) for r in records),
        'availability_context_count': sum(len(r['import_candidate']['availability_contexts']) for r in records),
        'offering_context_count': sum(len(r['import_candidate']['offering_contexts']) for r in records),
        'contact_count': sum(len(r['secondary_contact_candidates']) for r in records),
        'contact_properties': sum(bool(r['secondary_contact_candidates']) for r in records),
        'primary_images_high_confidence': sum(not r['primary_image_review_required'] for r in records),
        'primary_images_need_review': sum(r['primary_image_review_required'] for r in records),
        'price_anomaly_properties': [r['source_title'] for r in records if r['price_anomalies']],
    }
    gaps = ['WordPress transaction taxonomy can store multiple terms, but Aspire_Atlas::term() selects only the first term and Atlas filters/Brief use one scalar intent. Sale + Ground Lease must remain separate in V3; no taxonomy/frontend changes made.', 'Office Condo remains source provenance; existing taxonomy models it as an Office child. No new property taxonomy terms are proposed.']
    manifest = {'schema_version':3, 'audit_only':True, 'import_executed':False, 'preserved_input_sha256':hashes,
                'evidence_reference_rules':'Record-relative v2_pointer/source_ref values resolve beneath v2_evidence_ref.json_pointer. Cached page block evidence carries its HTML SHA-256 and block index.',
                'core_import_fields':['title','slug','property_type','transaction_types','address','source_url'],
                'readiness_policy':'No publication authorized. Missing optional fields do not block draft readiness. Derived intents, type/image decisions and conflicts require review; missing critical addresses and wrong brochure subjects block.',
                'schema_gaps':gaps, 'wordpress_unchanged':True, 'wordpress_safety':{'property_count':before['property_count'],'attachment_count':before['attachment_count'],'property_sha256':before['property_sha256'],'taxonomy_sha256':hashlib.sha256(json.dumps(before['taxonomy'],sort_keys=True).encode()).hexdigest()},
                'summary':summary,'records':records}
    (OUT/'aspire-properties-manifest-v3.json').write_text(json.dumps(manifest,indent=2,ensure_ascii=False)+'\n')
    cols=['source_title','source_url','property_type_v2','property_type_v3','property_type_changed','transaction_v2','transaction_v3','transaction_derivation','address_v3','address_status','geocoding_needed','primary_image_status','suite_count','availability_context_count','contact_count','blocking_conflicts','non_blocking_warnings','remaining_conflicts','import_readiness','review_decision','review_notes']
    with (OUT/'aspire-properties-review-v3.csv').open('w',newline='') as f:
        writer=csv.DictWriter(f,fieldnames=cols);writer.writeheader()
        for r in records:
            c=r['import_candidate'];row={k:r.get(k,'') for k in cols}
            row.update(property_type_v3=c['property_type'],transaction_v3=' + '.join(c['transaction_types']),transaction_derivation=r['transaction_derivation']['method'],address_v3=json.dumps(c['address'],ensure_ascii=False) if c['address'] else '',geocoding_needed=r['coordinate_status']=='needs_geocoding',primary_image_status='review_required' if r['primary_image_review_required'] else 'high_confidence',suite_count=len(c['suites']),availability_context_count=len(c['availability_contexts']),contact_count=len(c['contacts']))
            for key,value in row.items():
                if isinstance(value,list):row[key]='; '.join(value)
                if isinstance(row[key],str) and row[key].startswith(('=','+','-','@','\t','\r')):row[key]="'"+row[key]
            writer.writerow(row)
    lines=['# Aspire property normalization audit','', '**87/87 records normalized. Audit only: no property or media import. V1 and V2 remain unchanged.**','', '## Coverage','', '| Measure | Result |','|---|---|']
    for k,v in summary.items(): lines.append('| '+k+' | '+json.dumps(v,ensure_ascii=False).replace('|','\\|')+' |')
    lines += ['', '## Rules and limitations','', '- Import candidates are separate from immutable V2 evidence. READY and READY_WITH_NULLS are draft-review classifications, never publication approval.', '- Explicit transaction wording is Tier A. Labelled property prices and suite lease rates are Tier B and require approval. Ordinary sale + lease are two semantic intents; ground lease remains distinct. Complementary offers do not establish which source is current.', '- Every decision-field matcher has a positive context gate. Generic headings, restaurant names, availability of signage/parking, malformed frontage units and truncated claims are excluded. Statements remain attributed to the source.', '- Combined built/renovated labels are parsed in order. Malformed comma groupings quarantine numeric prices and preserve raw anomaly evidence. FM 1093 pricing suppression/review is unchanged.', '- Suite rates remain suite-specific; no property-level aggregate rate is manufactured. Source total-space labels are separate from suite areas. Source suite inventory differences still require review.', '- Parcel prices are attached only within source blocks. Map-only acreage labels have no assigned price or availability and require review. No parcel sizes are summed.', '- Same-road traffic disagreements remain conflicts. Unnamed counts retain a null road; no road is inferred from the address.', '- The 82 reused source coordinates are excluded. Five V2 usable metadata candidates remain unverified source locations. No external geocoding or image downloads.', '- Contact candidates are deduplicated per person/email and are not approved for display. No primary is selected from a generic team listing.', '', '## Schema gaps',''] + ['- '+x for x in gaps]
    lines += ['', '## Remaining property reviews','', '| Property | Readiness | Conflicts / blockers |','|---|---|']
    for r in records:
        if r['remaining_conflicts'] or r['blocking_conflicts']:lines.append('| '+r['source_title'].replace('|','/')+' | '+r['import_readiness']+' | '+', '.join(r['blocking_conflicts']+r['remaining_conflicts'])+' |')
    lines += ['', '## Safety','', f"Properties {before['property_count']} → {after['property_count']}; attachments {before['attachment_count']} → {after['attachment_count']}. Full read-only SQL snapshots match, including property/meta fingerprints, taxonomy rows and relationships.", '', 'No WordPress bootstrap/write API, attachments, taxonomy mutations, frontend changes or Docker edits. All six V1/V2 output hashes are preserved in the V3 manifest.','']
    (OUT/'aspire-properties-normalization-summary.md').write_text('\n'.join(lines))
    assert hashes == {name:hashlib.sha256((OUT/name).read_bytes()).hexdigest() for name in PRESERVED}
    print(json.dumps(summary,indent=2))

if __name__ == '__main__':
    run()
