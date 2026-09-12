"""Census-only pre-import coordinate audit. Offline by default; never imports data."""
import argparse
import collections
import copy
import csv
import hashlib
import io
import json
import math
import re
import subprocess
from pathlib import Path

from discover import ROOT, CACHE

OUT = ROOT / 'docs/migration'
WORK = CACHE / 'geocoding'
V3 = OUT / 'aspire-properties-manifest-v3.json'
ENDPOINT = 'https://geocoding.geo.census.gov/geocoder/locations/addressbatch'
BENCHMARK = 'Public_AR_Current'
DOCS = 'https://geocoding.geo.census.gov/geocoder/Geocoding_Services_API.html'
ADDRESS_KEYS = ['address_line_1', 'city', 'state', 'postal_code']


def sha(path):
    return hashlib.sha256(path.read_bytes()).hexdigest()


def submissions(v3):
    result = {}
    for i, record in enumerate(v3['records']):
        c = record['import_candidate']; address = c['address']
        complete = address and all(address.get(k) for k in ADDRESS_KEYS)
        reason = 'v3_geocoding_eligible' if record['geocoding_eligible'] else 'source_coordinate_comparison' if c['coordinates'] else None
        if complete and reason:
            result[f'aspire-{i+1:03d}'] = {'record_index': i, 'submitted_address': copy.deepcopy(address), 'reason': reason}
    return result


def input_bytes(items):
    stream = io.StringIO(newline='')
    writer = csv.writer(stream)
    for key, item in items.items():
        writer.writerow([key] + [item['submitted_address'][k] for k in ADDRESS_KEYS])
    return stream.getvalue().encode()


def parse_batch(text, expected):
    rows = {}
    for row in csv.reader(io.StringIO(text)):
        if not row: continue
        if len(row) < 3 or row[0] not in expected or row[0] in rows:
            raise ValueError('Invalid/duplicate/unexpected Census result row')
        if row[2] not in ['Match', 'No_Match', 'Tie']:
            raise ValueError('Unknown Census match status: ' + row[2])
        if row[2] == 'Match' and len(row) < 8:
            raise ValueError('Truncated Census match row')
        submitted = expected[row[0]]['submitted_address']
        echoed = ', '.join(submitted[k] for k in ADDRESS_KEYS)
        if row[1] != echoed: raise ValueError('Returned input address differs from submitted address')
        rows[row[0]] = row
    if set(rows) != set(expected): raise ValueError('Census response IDs do not match submitted IDs')
    return rows


def valid_coordinates(latitude, longitude):
    try:
        return math.isfinite(float(latitude)) and math.isfinite(float(longitude)) and -90 <= float(latitude) <= 90 and -180 <= float(longitude) <= 180
    except (TypeError, ValueError): return False


def street_parts(street):
    value = street.upper().replace('–','-').replace('—','-')
    number = re.match(r'^\s*(\d+(?:\s*-\s*\d+)?[A-Z]?)\b', value)
    if not number: return None, None
    road = value[number.end():]
    aliases = {'ROAD':'RD','STREET':'ST','BOULEVARD':'BLVD','DRIVE':'DR','LANE':'LN','AVENUE':'AVE','PARKWAY':'PKWY','PKY':'PKWY','FREEWAY':'FWY','HIGHWAY':'HWY','TRACE':'TRCE','COURT':'CT','CIRCLE':'CIR','NORTH':'N','SOUTH':'S','EAST':'E','WEST':'W'}
    tokens = re.findall(r'[A-Z0-9]+', road)
    tokens = [aliases.get(t,t) for t in tokens]
    # "Highway 87" and "State Highway 87" denote the same numbered route;
    # preserve explicit US/interstate designations and direction qualifiers.
    if tokens[:2] == ['STATE','HWY']: tokens = tokens[1:]
    return re.sub(r'\s','',number[1]), ' '.join(tokens)


def classify(row, submitted):
    evidence = {'submitted_address': submitted, 'match_status': row[2], 'match_type': row[3] if len(row)>3 else None,
                'matched_address': row[4] if len(row)>4 else None, 'longitude': None, 'latitude': None,
                'tiger_line_id': row[6] if len(row)>6 else None, 'tiger_side': row[7] if len(row)>7 else None,
                'raw_result_fields': row, 'coordinate_candidate': None, 'review_reasons': []}
    if row[2] == 'No_Match': return {**evidence,'status':'UNMATCHED'}
    if row[2] == 'Tie': return {**evidence,'status':'REVIEW_REQUIRED','review_reasons':['ambiguous_tied_match']}
    reasons = []
    try:
        lon, lat = map(float,row[5].split(','))
        if valid_coordinates(lat,lon):
            evidence.update(latitude=lat,longitude=lon,coordinate_candidate={'latitude':lat,'longitude':lon})
        else: reasons.append('invalid_coordinates')
    except (ValueError,TypeError): reasons.append('invalid_coordinates')
    if evidence['coordinate_candidate'] and not (25 <= lat <= 37 and -107 <= lon <= -93): reasons.append('outside_broad_texas_geography')
    if row[3] != 'Exact': reasons.append('non_exact_match_requires_precision_review')
    parts = [p.strip() for p in row[4].rsplit(',',3)]
    if len(parts) != 4: reasons.append('unparseable_matched_address')
    else:
        street, city, state, zipcode = parts
        if state.upper() != submitted['state'].upper(): reasons.append('state_mismatch')
        if zipcode and zipcode[:5] != submitted['postal_code'][:5]: reasons.append('zip_mismatch')
        if not zipcode: reasons.append('returned_zip_missing')
        a_num,a_road=street_parts(submitted['address_line_1']);b_num,b_road=street_parts(street)
        if not a_num or a_num != b_num: reasons.append('street_number_mismatch')
        if not a_road or a_road != b_road: reasons.append('material_road_difference')
        if a_num and '-' in a_num: reasons.append('address_range_precision_requires_review')
        if city.upper() != submitted['city'].upper(): reasons.append('city_difference_requires_review')
    return {**evidence,'status':'REVIEW_REQUIRED' if reasons else 'ACCEPTED','review_reasons':reasons}


def distance_m(a,b):
    if not a or not b or not all(valid_coordinates(x.get('latitude'),x.get('longitude')) for x in [a,b]): return None
    lat1,lat2=map(math.radians,[a['latitude'],b['latitude']]);dl=math.radians(b['longitude']-a['longitude']);dp=lat2-lat1
    h=math.sin(dp/2)**2+math.cos(lat1)*math.cos(lat2)*math.sin(dl/2)**2
    return round(6371008.8*2*math.asin(math.sqrt(min(1,max(0,h)))),1)


def comparison(a,b):
    distance=distance_m(a,b)
    return {'coordinate':a,'distance_m':distance,'status':'NOT_COMPARABLE' if distance is None else 'CONSISTENT' if distance<=250 else 'REVIEW' if distance<=2000 else 'CONFLICT'}


def inside(coordinate,bounds):
    if not coordinate or not valid_coordinates(coordinate.get('latitude'),coordinate.get('longitude')): return None
    return bounds['west']<=coordinate['longitude']<=bounds['east'] and bounds['south']<=coordinate['latitude']<=bounds['north']


def wp_coordinate(record,snapshot):
    matches=[p for p in snapshot['properties'] if p['post_name']==record['import_candidate']['slug']]
    if len(matches)!=1:return None,None
    post=matches[0];meta={m['meta_key']:m['meta_value'] for m in snapshot['meta'] if m['post_id']==post['ID']}
    try:coordinate={'latitude':float(meta['_aspire_latitude']),'longitude':float(meta['_aspire_longitude'])}
    except (KeyError,ValueError):coordinate=None
    return post['ID'],coordinate


def run(fetch=False):
    WORK.mkdir(parents=True,exist_ok=True)
    preserved={p.name:sha(p) for p in OUT.iterdir() if p.is_file() and p.name not in ['aspire-properties-manifest-v4.json','aspire-properties-review-v4.csv','aspire-properties-geocoding-summary.md']}
    v3=json.loads(V3.read_text());items=submissions(v3);data=input_bytes(items)
    assert sum(x['reason']=='v3_geocoding_eligible' for x in items.values())==71
    assert len(items)==74
    input_path=WORK/'input.csv';response_path=WORK/'response.csv'
    if input_path.exists() and input_path.read_bytes()!=data:raise ValueError('Cached input differs from V3; refusing response reuse')
    input_path.write_bytes(data)
    request={'benchmark':BENCHMARK,'endpoint':ENDPOINT,'input_sha256':sha(input_path),'v3_sha256':sha(V3),'count':len(items)}
    request_path=WORK/'request.json'
    if request_path.exists() and json.loads(request_path.read_text())!=request:raise ValueError('Cached request provenance differs')
    request_path.write_text(json.dumps(request,indent=2))
    if not response_path.exists():
        if not fetch:raise ValueError('No cached response. Explicit --fetch required for one Census batch submission.')
        temp=WORK/'response.tmp'
        result=subprocess.run(['curl','--fail','--silent','--show-error','--proto','=https','--connect-timeout','20','--max-time','180','--user-agent','AspireCRE-Migration-Audit/4.0 (one-time property address audit)','--form','addressFile=@'+str(input_path),'--form','benchmark='+BENCHMARK,ENDPOINT,'--output',str(temp)],capture_output=True,text=True)
        if result.returncode:raise RuntimeError('Census service unavailable. STOP; no fallback provider. '+result.stderr)
        parse_batch(temp.read_text(),items);temp.replace(response_path)
    rows=parse_batch(response_path.read_text(),items)
    map_path=WORK/'pmtiles.json'
    map_data=json.loads(map_path.read_text())
    if sha(ROOT/map_data['path'])!=map_data['sha256']:raise ValueError('PMTiles archive changed since inspection')
    before=json.loads((CACHE/'geocoding-before.json').read_text());after=json.loads((CACHE/'geocoding-after.json').read_text())
    if before!=after:raise ValueError('WordPress snapshot changed')
    records=[]
    for i,original in enumerate(v3['records']):
        r=copy.deepcopy(original);key=f'aspire-{i+1:03d}';submitted=items.get(key)
        geocode=classify(rows[key],submitted['submitted_address']) if submitted else {'status':'NOT_ATTEMPTED','submitted_address':None,'coordinate_candidate':None,'matched_address':None,'latitude':None,'longitude':None,'review_reasons':['v3_address_not_eligible_or_incomplete']}
        geocode['submission_reason']=submitted['reason'] if submitted else None
        coordinate={**geocode['coordinate_candidate'],'source':'US Census Geocoder','status':'accepted'} if geocode['status']=='ACCEPTED' else None
        source_comparison=comparison(original['import_candidate']['coordinates'],geocode['coordinate_candidate'])
        if original['import_candidate']['coordinates'] and not submitted:source_comparison['reason']='No reliable V3 address; not submitted'
        post_id,wp=wp_coordinate(original,before);wp_comparison=comparison(wp,coordinate)
        if post_id and coordinate is None:wp_comparison['reason']='No accepted Census candidate; distance unavailable'
        r['import_candidate']['coordinates']=coordinate
        r.update(migration_id=key,v3_evidence_ref={'path':V3.name,'sha256':sha(V3),'json_pointer':f'/records/{i}'},geocoding=geocode,
                 source_coordinate_comparison=source_comparison,existing_wp_comparison={**wp_comparison,'post_id':post_id},inside_current_pmtiles_bounds=inside(coordinate,map_data['bounds']),import_readiness_v3=original['import_readiness'])
        r['geocode_readiness']='BLOCKED' if original['import_readiness']=='BLOCKED' else 'COORDINATE_REVIEW_REQUIRED' if geocode['status']=='REVIEW_REQUIRED' else 'READY_WITH_COORDINATES' if coordinate else 'READY_WITHOUT_COORDINATES'
        records.append(r)
    counts=collections.Counter(r['geocoding']['status'] for r in records)
    cities={}
    for r in records:
        a=r['import_candidate']['address'];city=a['city'].upper() if a else '(UNRESOLVED ADDRESS)'
        c=cities.setdefault(city,{'inventory':0,'attempted':0,'accepted':0,'review_required':0,'unmatched':0,'inside':0,'outside':0})
        c['inventory']+=1;c['attempted']+=r['geocoding']['status']!='NOT_ATTEMPTED'
        for status,field in [('ACCEPTED','accepted'),('REVIEW_REQUIRED','review_required'),('UNMATCHED','unmatched')]:c[field]+=r['geocoding']['status']==status
        c['inside']+=r['inside_current_pmtiles_bounds'] is True;c['outside']+=r['inside_current_pmtiles_bounds'] is False
    outside=[{'title':r['source_title'],'source_url':r['source_url'],'coordinate':r['import_candidate']['coordinates']} for r in records if r['inside_current_pmtiles_bounds'] is False]
    summary={'attempted':len(items),'eligible_submissions':71,'source_comparison_submissions':len(items)-71,'accepted':counts['ACCEPTED'],'review_required':counts['REVIEW_REQUIRED'],'unmatched':counts['UNMATCHED'],'not_attempted':counts['NOT_ATTEMPTED'],
             'inside_map':sum(r['inside_current_pmtiles_bounds'] is True for r in records),'outside_map':len(outside),'coordinate_unavailable':sum(r['import_candidate']['coordinates'] is None for r in records),'outside_properties':outside,'cities':dict(sorted(cities.items())),
             'recommendation':'B. Current basemap needs expansion before full migration' if outside else 'C. Mixed strategy recommended; unresolved coordinates prevent a complete portfolio coverage claim'}
    manifest={'schema_version':4,'pre_import':True,'v3_sha256':sha(V3),'preserved_audit_sha256':preserved,'census_request':{**request,'response_sha256':sha(response_path),'documentation':DOCS,'precision':'Census address-range interpolation; not rooftop or parcel-boundary verification'},'pmtiles':map_data,
              'readiness_note':'geocode_readiness is coordinate-only. import_readiness and all non-coordinate V3 decisions remain unchanged; no publication/import is authorized.',
              'wordpress_unchanged':True,'wordpress_safety':{'property_count':before['property_count'],'attachment_count':before['attachment_count'],'property_sha256':before['property_sha256'],'taxonomy_sha256':hashlib.sha256(json.dumps(before['taxonomy'],sort_keys=True).encode()).hexdigest()},'summary':summary,'records':records}
    (OUT/'aspire-properties-manifest-v4.json').write_text(json.dumps(manifest,indent=2,ensure_ascii=False)+'\n')
    with (OUT/'aspire-properties-review-v3.csv').open() as f:v3rows=list(csv.DictReader(f))
    extra=['submitted_address','geocode_status','matched_address','latitude','longitude','geocode_review_reason','source_coordinate_status','source_coordinate_distance_m','inside_current_pmtiles_bounds','existing_wp_coordinate','existing_wp_coordinate_distance_m','import_readiness_v3','geocode_readiness']
    columns=list(v3rows[0])+extra
    with (OUT/'aspire-properties-review-v4.csv').open('w',newline='') as f:
        writer=csv.DictWriter(f,fieldnames=columns);writer.writeheader()
        for old,r in zip(v3rows,records):
            g=r['geocoding'];s=r['source_coordinate_comparison'];w=r['existing_wp_comparison']
            row={**old,'submitted_address':json.dumps(g['submitted_address'],ensure_ascii=False) if g['submitted_address'] else '', 'geocode_status':g['status'],'matched_address':g['matched_address'] or '', 'latitude':g['latitude'],'longitude':g['longitude'],'geocode_review_reason':'; '.join(g['review_reasons']), 'source_coordinate_status':s['status'] if s['coordinate'] else '', 'source_coordinate_distance_m':s['distance_m'],'inside_current_pmtiles_bounds':r['inside_current_pmtiles_bounds'],'existing_wp_coordinate':json.dumps(w['coordinate']) if w['coordinate'] else '', 'existing_wp_coordinate_distance_m':w['distance_m'],'import_readiness_v3':r['import_readiness_v3'],'geocode_readiness':r['geocode_readiness'],'review_decision':'','review_notes':''}
            for k,value in row.items():
                if k in extra and isinstance(value,str) and value.startswith(('=','+','-','@','\t','\r')):row[k]="'"+value
            writer.writerow(row)
    report=['# Aspire Census geocoding and map coverage audit','',f"Attempted **{len(items)}**: 71 eligible addresses plus 3 reliable-address source-coordinate comparisons. Accepted **{counts['ACCEPTED']}**, review required **{counts['REVIEW_REQUIRED']}**, unmatched **{counts['UNMATCHED']}**. Thirteen incomplete/ineligible addresses were not submitted.",'',f'Used [{BENCHMARK}, locations/addressbatch]({DOCS}). Census coordinates are interpolated along address ranges, not verified rooftop/parcel positions. Exact V3 address fields were submitted without rewriting. No fallback provider was used.','',f"## Existing map\n\nFile: `{map_data['path']}`. Header bounds: {json.dumps(map_data['bounds'])}. Zooms {map_data['min_zoom']}–{map_data['max_zoom']}. SHA-256: `{map_data['sha256']}`.",f"\nAccepted inside: **{summary['inside_map']}**; outside: **{summary['outside_map']}**; coordinate unavailable: **{summary['coordinate_unavailable']}**.",'','Header-center tiles were present at the sampled minimum and maximum zooms. This proves only those tile samples exist; bounding-box inclusion does not establish detailed coverage at every property.','', '**'+summary['recommendation']+'**. This is a recommendation only; no map was downloaded, rebuilt or modified.','', '### Outside-map properties','']
    report += ['- '+r['title'] for r in outside]
    report += ['','## Source-coordinate comparisons','','| Property | Status | Distance m |','|---|---|---:|']
    report += [f"| {r['source_title']} | {r['source_coordinate_comparison']['status']} | {r['source_coordinate_comparison']['distance_m']} |" for r in records if r['source_coordinate_comparison']['coordinate']]
    report += ['','Distances compare source metadata to returned Census coordinates; non-accepted returns never populate import coordinates. Thresholds: ≤250 m consistent; >250 m–2 km review; >2 km conflict. Two source-coordinate records lack reliable addresses and cannot be compared.','', '## Existing WordPress comparisons','','| Property | Census status | Distance m | Comparison |','|---|---|---:|---|']
    report += [f"| {r['source_title']} | {r['geocoding']['status']} | {r['existing_wp_comparison']['distance_m']} | {r['existing_wp_comparison']['status']} |" for r in records if r['existing_wp_comparison']['post_id']]
    report += ['', '## Representative QA','','| Property | Result | Inside bounds | Review reason |','|---|---|---|---|']
    needles=['16840','7506','14602','21617','sugarwell','grand parkway times','kingsley ridge','beaumont','galveston','cotulla','crystal beach']
    report += [f"| {r['source_title']} | {r['geocoding']['status']} | {r['inside_current_pmtiles_bounds']} | {'; '.join(r['geocoding']['review_reasons'])} |" for r in records if any(n in r['source_title'].lower() for n in needles)]
    report += ['','Unmatched addresses were not rewritten or replaced with city/ZIP centroids. In particular, the Cotulla and Galveston results do not establish any property location or map inclusion. Accepted Beaumont and Crystal Beach locations provide direct evidence of geographic limits.','', '## City distribution','','| City | Inventory | Attempted | Accepted | Review | Unmatched | Inside | Outside |','|---|---:|---:|---:|---:|---:|---:|---:|']
    report += ['| '+city+' | '+' | '.join(str(c[k]) for k in ['inventory','attempted','accepted','review_required','unmatched','inside','outside'])+' |' for city,c in summary['cities'].items()]
    report += ['','## Review matches','','| Property | Matched address | Reasons |','|---|---|---|']
    report += [f"| {r['source_title']} | {r['geocoding']['matched_address']} | {'; '.join(r['geocoding']['review_reasons'])} |" for r in records if r['geocoding']['status']=='REVIEW_REQUIRED']
    report += ['','## Safety and interpretation','',f"WordPress properties {before['property_count']} → {after['property_count']}; attachments {before['attachment_count']} → {after['attachment_count']}. Full before/after read-only snapshots match, including property/meta fingerprint and taxonomy snapshot.",'','V1/V2/V3 artifacts remain byte-for-byte unchanged. Only import_candidate.coordinates changes within the copied V3 candidate. Existing V3 coordinate provenance is preserved in the source comparison and immutable V3 reference. Coordinate readiness never approves unresolved V3 transactions, images or other decisions. No WordPress/media/taxonomy/frontend/Docker writes, runtime Census dependency or basemap download.','']
    (OUT/'aspire-properties-geocoding-summary.md').write_text('\n'.join(report))
    assert preserved=={name:sha(OUT/name) for name in preserved}
    print(json.dumps(summary,indent=2))

if __name__=='__main__':
    parser=argparse.ArgumentParser();parser.add_argument('--fetch',action='store_true');run(parser.parse_args().fetch)
