"""Offline V2 enrichment: preserves every V1 record field and emits separate candidates."""
import copy,csv,hashlib,json,re,collections,datetime
from pathlib import Path
from bs4 import BeautifulSoup
from discover import ROOT,CACHE
from parser import clean,normalized_url
from enrichment_parser import *
OUT=ROOT/'docs/migration'
def norm_address(v):
 s=v.lower();parts=re.split(r'\s+[–—-]\s+',s)
 if len(parts)==2 and re.match(r'^\d+',parts[0]) and re.match(r'^\d+',parts[1]) and parts[0].split()[0]==parts[1].split()[0]:s=parts[1]
 s=re.sub(r'\btexas\b','tx',s)
 for a,b in [('road','rd'),('street','st'),('drive','dr'),('boulevard','blvd'),('parkway','pky'),('east','e'),('west','w'),('south','s'),('north','n')]:s=re.sub(r'\b'+a+r'\b',b,s)
 # Direction/road token order may differ for numbered FM roads while identifying the same street.
 m=re.fullmatch(r'(\d+)\s+(?:([nesw])\s+)?fm[- ]?(\d+)(?:\s+rd)?(?:\s+([nesw]))?',s)
 if m:s=' '.join(filter(None,[m[1],m[2] or m[4],'fm',m[3]]))
 return re.sub(r'[^a-z0-9]','',s)
def process(original,html,pages,info):
 r=copy.deepcopy(original);r.update(info);r['enrichment_warnings']=[];warning=r['enrichment_warnings'].append
 r.update(page_metadata(html,r));body=BeautifulSoup(html,'html.parser').select_one('.blog-item-content')
 page_lines=[(None,clean(t.get_text(' ',strip=True))) for t in body.select('h1,h2,h3,h4,p,li') if not(t.name=='p' and t.find_parent('li'))]
 page_lines=[x for x in page_lines if x[1]];brochure_lines=lines_from_pages(pages)
 r['brochure_partial_text']=any(re.search(r'/C\d+',p) for p in pages)
 if r['brochure_partial_text']:warning('brochure_partial_text')
 if info['brochure_download_status']!='success':warning('brochure_download_failed')
 if info['brochure_text_status']!='available':warning('brochure_text_unavailable')
 tx=[]
 for value,strings in original.get('transaction_evidence',{}).items():
  if value in ['For Lease','For Sale','For Sale or Lease','Ground Lease']:
   tx.extend(ev('page',value,s) for s in strings)
 if original.get('transaction_type_candidate'):tx=[ev('page',original['transaction_type_candidate'],'; '.join(s for v in original.get('transaction_evidence',{}).values() for s in v))]
 tx+=transactions(brochure_lines,'brochure');transaction=reconcile(tx)
 r.update(transaction_evidence_v2=transaction['evidence'],transaction_type_candidate_v2=transaction['candidate'],transaction_confidence_v2=transaction['confidence'])
 fields=structured(brochure_lines,'brochure');page_fields=structured(page_lines,'page');r['enriched_fields']={}
 for field in fields:
  evidence=fields[field]+page_fields[field]
  if original.get(field) is not None:
   detail=original.get('field_evidence',{}).get(field,[]);text='; '.join(x['source_text'] for x in detail) if detail else str(original[field]);evidence.append(ev('page',original[field],text))
  # Display strings and rate components retain contexts, not false conflicts over typography.
  if field in ['lease_rate_display','price_display','nnn_cam','ground_lease_rate']:
   model={'candidate':list(dict.fromkeys(e['value'] for e in evidence)) or None,'confidence':'source_contexts' if evidence else None,'field_conflict':False,'evidence':evidence}
  else:
   if field=='parking_ratio':
    for e in evidence:e['value']=re.sub(r'\s+leased$','',e['value'],flags=re.I).lower()
   model=reconcile(evidence)
  r['enriched_fields'][field]=model
 # Address reconciliation compares equivalent suffix spelling without rewriting preserved source data.
 ad=[ev('page',{k:r[k] for k in ['address_line_1','city','state','postal_code']},r['source_title'])] if r['address_line_1'] and r['city'] else []
 ad+=brochure_addresses(pages);groups={}
 for e in ad:
  key='|'.join(norm_address(e['value'].get(k) or '') for k in ['address_line_1','city','state','postal_code']);groups.setdefault(key,[]).append(e)
 chosen=next(iter(groups.values())) if len(groups)==1 else []
 r['address_evidence_v2']=ad;r['address_candidate_v2']=copy.deepcopy(chosen[-1]['value']) if chosen else None;r['address_confidence_v2']='high_agreement' if chosen and len({e['source'] for e in chosen})>1 else ('high' if chosen else None);r['address_field_conflict']=len(groups)>1
 if r['address_candidate_v2']:
  street=r['address_candidate_v2']['address_line_1'];parts=re.split(r'\s+[–—-]\s+',street)
  if len(parts)==2 and parts[0].split()[0]==parts[1].split()[0]:r['address_candidate_v2']['address_line_1']=parts[1]
 r['broker_candidates']=contacts(pages)
 page_number=re.match(r'\d+',r['address_line_1'] or '')
 brochure_numbers={m[0] for e in ad if e['source']=='brochure' for m in [re.match(r'\d+',e['value']['address_line_1'])] if m}
 r['brochure_subject_mismatch']=bool(page_number and brochure_numbers and page_number[0] not in brochure_numbers)
 if r['brochure_subject_mismatch']:
  warning('brochure_subject_mismatch')
  for field,model in r['enriched_fields'].items():
   if any(e['source']=='brochure' for e in model['evidence']):model.update(candidate=None,field_conflict=True,confidence='subject_mismatch')
  if any(e['source']=='brochure' for e in transaction['evidence']):transaction.update(candidate=None,field_conflict=True,confidence='subject_mismatch');r['transaction_type_candidate_v2']=None;r['transaction_confidence_v2']='subject_mismatch'
  for broker in r['broker_candidates']:broker['confidence']='review_subject_mismatch'

 # Retain original suites with complete source evidence, then merge only exact named-suite matches.
 suites=[]
 for s in original['suite_candidates']:
  suites.append({**copy.deepcopy(s),'evidence':[ev('page',s,s['source_text'],confidence=s['confidence'])],'field_conflict':False,'field_evidence':{}})
 new=availability(brochure_lines,'brochure');contexts=[]
 for candidate in new:
  if candidate['context']:contexts.append(candidate);continue
  matching=[s for s in suites if s['suite_name'].upper()==candidate['suite_name'].upper()]
  if len(matching)==1:
   target=matching[0];target['evidence']+=candidate['evidence'];conflict=False;agreed=False
   for field in ['square_feet','rate','rate_type','former_use','availability_status']:
    evidence=[]
    if target.get(field) is not None:evidence.append(ev('page',target[field],original['source_url']))
    if candidate.get(field) is not None:evidence.append(ev('brochure',candidate[field],candidate['notes']))
    model=reconcile(evidence);target['field_evidence'][field]=model;target[field]=model['candidate'];conflict|=model['field_conflict'];agreed|=model['confidence']=='high_agreement'
   target['field_conflict']=conflict;target['confidence']='conflicting' if conflict else ('high_agreement' if agreed else 'medium')
  else:suites.append(candidate)
 # Page phase contexts get their own objects even if no named suites exist.
 page_phase=[]
 for block in body.select('.sqs-html-content'):
  parts=[(None,clean(x.get_text(' ',strip=True))) for x in block.find_all(['h1','h2','h3','h4','p','li']) if not(x.name=='p' and x.find_parent('li'))]
  page_phase.extend(c for c in availability(parts,'page') if c['context'])
 merged={}
 for ctx in page_phase+contexts:
  key=re.sub(r'\W','',ctx['context'].upper());dest=merged.get(key)
  if not dest:merged[key]=copy.deepcopy(ctx);continue
  dest['evidence']+=ctx['evidence']
  for field in ['minimum_available_sf','maximum_available_sf','rate']:
   if dest.get(field) is not None and ctx.get(field) is not None and dest[field]!=ctx[field]:dest['field_conflict']=True;dest[field]=None
  dest['confidence']='conflicting' if dest['field_conflict'] else 'high_agreement'
 if r['brochure_subject_mismatch']:
  for suite in suites:
   if any(e['source']=='brochure' for e in suite['evidence']):suite['field_conflict']=True;suite['confidence']='review_subject_mismatch'
 r['suite_candidates_v2']=suites;r['availability_contexts_v2']=list(merged.values())
 page_names={s['suite_name'].upper() for s in original['suite_candidates']};brochure_names={s['suite_name'].upper() for s in new if s['suite_name']}
 r['suite_inventory_difference']=bool(page_names and brochure_names and page_names!=brochure_names)
 if r['suite_inventory_difference']:warning('suite_inventory_source_difference')
 typ={'Industrial / Flex':'industrial','Retail':'retail','Office':'office','Office Condo':'office','Land':'land'}.get(r['property_type'],'office')
 facts={};statuses={}
 for field,pattern in FACTS[typ].items():
  evidence=[]
  for source,lines in [('page',page_lines),('brochure',brochure_lines)]:
   for page,line in lines:
    if re.search(pattern,line,re.I):evidence.append(ev(source,line,line,page,'medium'))
  evidence=list({json.dumps(e,sort_keys=True):e for e in evidence}.values())
  # Quoted qualitative facts may be complementary. Only explicit negation vs affirmation is a conflict.
  neg=[e for e in evidence if re.search(r'\bno\b|\bnot\b|non[- ]|without',e['text'],re.I)];pos=[e for e in evidence if e not in neg]
  conflict=bool(neg and pos and field in ['sprinklered','covered_parking','elevator','furnished','floodplain','utilities','detention'])
  numeric_link={'clear_height':'clear_height_ft','office_sf':'office_sf','warehouse_sf':'warehouse_sf','building_class':'building_class','building_size':'building_sf','parking_ratio':'parking_ratio','acreage':'lot_acres'}.get(field)
  if numeric_link and r['enriched_fields'][numeric_link]['field_conflict']:conflict=True
  if r['brochure_subject_mismatch'] and any(e['source']=='brochure' for e in evidence):conflict=True
  facts[field]={'candidate':list(dict.fromkeys(e['value'] for e in evidence)) if evidence and not conflict else None,'evidence':evidence,'field_conflict':conflict,'confidence':'source_quoted' if evidence else None};statuses[field]='conflicting' if conflict else ('known' if evidence else 'unknown')
 r['decision_facts']={typ:facts};r['decision_field_status']=statuses
 known=[k for k,v in statuses.items() if v=='known'];unknown=[k for k,v in statuses.items() if v=='unknown'];conflicting=[k for k,v in statuses.items() if v=='conflicting']
 r['decision_intelligence']={'property_type':typ,'known_fields':known,'unknown_fields':unknown,'conflicting_fields':conflicting,'completeness_score':round(100*len(known)/len(statuses),1),'score_purpose':'Internal migration QA only','score_formula':'100 × non-conflicting evidence-backed fields / enumerated fields for this property type; all fields equally weighted'}
 conflicts=[k for k,v in r['enriched_fields'].items() if v['field_conflict']]
 if transaction['field_conflict']:conflicts.append('transaction_type')
 if r['brochure_subject_mismatch']:conflicts.append('brochure_subject')
 if r['address_field_conflict']:conflicts.append('address')
 if any(s['field_conflict'] for s in suites):conflicts.append('suite_fields')
 if r['suite_inventory_difference']:conflicts.append('suite_inventory')
 r['brochure_page_conflicts']=conflicts;r['field_conflict']=bool(conflicts)
 if conflicts:warning('source_field_conflict')
 if not r['transaction_type_candidate_v2']:warning('transaction_still_unresolved')
 if not r['address_candidate_v2']:warning('address_still_unresolved')
 if r['coordinate_conflict']:warning('coordinate_source_conflict')
 if r['featured_image_conflict']:warning('primary_image_source_conflict')
 r['enrichment_status']='complete' if info['brochure_text_status']=='available' else 'complete_with_text_gap'
 return r

def run():
 original_paths=[OUT/'aspire-properties-manifest.json',OUT/'aspire-properties-review.csv',OUT/'aspire-properties-summary.md'];hashes={p.name:hashlib.sha256(p.read_bytes()).hexdigest() for p in original_paths}
 v1=json.loads(original_paths[0].read_text());index=json.loads((CACHE/'brochures/index.json').read_text());htmlindex={i['url']:i for i in json.loads((CACHE/'crawl-index.json').read_text())}
 before=json.loads((CACHE/'enrichment-before.json').read_text());after=json.loads((CACHE/'enrichment-after.json').read_text())
 for key in ['property_count','attachment_count','property_sha256','taxonomy','relationships']:
  if before[key]!=after[key]:raise ValueError('WordPress changed: '+key)
 records=[]
 for record in v1['records']:
  info=index[record['brochure_url']];text=CACHE/'brochures'/(info['cache_key']+'.text.json');pages=json.loads(text.read_text())['pages'] if text.exists() and info['brochure_download_status']=='success' else []
  records.append(process(record,(CACHE/htmlindex[record['source_url']]['cache']).read_text(),pages,info))
 coordinate_groups=collections.defaultdict(list)
 for r in records:
  r['source_coordinates_found']=bool(r['coordinate_evidence'])
  if r['latitude'] is not None:coordinate_groups[(r['latitude'],r['longitude'])].append(r)
 for point,group in coordinate_groups.items():
  addresses={norm_address((r['address_line_1'] or r['source_title'])+' '+(r['city'] or '')) for r in group}
  if len(addresses)>1:
   for r in group:
    r['coordinate_reuse_count']=len(group);r['coordinate_confidence']='review_reused_metadata';r['latitude']=None;r['longitude']=None;r['enrichment_warnings'].append('coordinate_reused_across_properties')
 coverage={}
 def add(label,a,b):coverage[label]={'before':a,'after':b}
 add('transaction_candidates',sum(bool(r['transaction_type_candidate']) for r in records),sum(bool(r['transaction_type_candidate_v2']) for r in records))
 add('addresses',sum(bool(r['address_line_1'] and r['city']) for r in records),sum(bool(r['address_candidate_v2']) for r in records))
 for field in ['building_sf','available_sf','lot_acres','year_built','clear_height_ft','parking_spaces','sale_price','lease_rate_min']:
  add(field,sum(r.get(field) is not None for r in records),sum(r['enriched_fields'][field]['candidate'] is not None for r in records))
 add('availability_ranges',0,sum(r['enriched_fields']['minimum_available_sf']['candidate'] is not None or bool(r['availability_contexts_v2']) for r in records))
 add('suite_properties',sum(bool(r['suite_candidates']) for r in records),sum(bool(r['suite_candidates_v2']) for r in records))
 add('broker_properties',0,sum(bool(r['broker_candidates']) for r in records));add('coordinates',0,sum(r['latitude'] is not None for r in records));add('high_confidence_primary_images',0,sum(r['featured_image_confidence_v2']=='high' for r in records))
 transition={'transactions_newly_resolved':sum(not r['transaction_type_candidate'] and bool(r['transaction_type_candidate_v2']) for r in records),'transactions_newly_conflicting':sum(bool(r['transaction_type_candidate']) and not r['transaction_type_candidate_v2'] for r in records),'addresses_newly_resolved':sum(not r['address_line_1'] and bool(r['address_candidate_v2']) for r in records),'addresses_newly_conflicting':sum(bool(r['address_line_1']) and not r['address_candidate_v2'] for r in records)}
 summary={'transitions':transition,'enriched':len(records),'source_coordinate_properties':sum(r['source_coordinates_found'] for r in records),'coordinate_reuse_rejected':sum(r.get('coordinate_reuse_count',0)>1 for r in records),'unique_pdf_urls':len(index),'brochure_download_success':sum(r['brochure_download_status']=='success' for r in records),'brochure_text_success':sum(r['brochure_text_status']=='available' for r in records),'partial_text_properties':sum(r['brochure_partial_text'] for r in records),'coverage':coverage,'broker_candidates':sum(len(r['broker_candidates']) for r in records),'suite_candidates_v2':sum(len(r['suite_candidates_v2']) for r in records),'availability_contexts':sum(len(r['availability_contexts_v2']) for r in records),'properties_with_conflicts':sum(r['field_conflict'] for r in records),'conflicting_fields':dict(collections.Counter(k for r in records for k in r['brochure_page_conflicts'])),'warnings':dict(collections.Counter(k for r in records for k in r['enrichment_warnings'])),'manual_review_required':len(records)}
 manifest={'schema_version':2,'audit_only':True,'generated_at':datetime.datetime.now(datetime.timezone.utc).isoformat(),'v1_sha256':hashes,'v1_audit_evidence':{k:v for k,v in v1.items() if k!='records'},'wordpress_unchanged':True,'wordpress_before_after':{k:{'before':before[k],'after':after[k]} for k in ['property_count','attachment_count','property_sha256']},'decision_field_catalog':{k:list(v) for k,v in FACTS.items()},'summary':summary,'records':records}
 (OUT/'aspire-properties-manifest-v2.json').write_text(json.dumps(manifest,indent=2,ensure_ascii=False)+'\n')
 with original_paths[1].open() as f:reader=csv.DictReader(f);columns=reader.fieldnames;rows=list(reader)
 extra=['transaction_v1','transaction_v2','transaction_confidence_v2','address_confidence_v2','brochure_text_status','broker_candidate_count','source_coordinates_found','featured_image_confidence_v2','decision_known_count','decision_unknown_count','decision_conflict_count','suite_count_v2','brochure_page_conflicts']
 with (OUT/'aspire-properties-review-v2.csv').open('w',newline='') as f:
  writer=csv.DictWriter(f,fieldnames=[x for x in columns if x not in ['review_decision','review_notes']]+extra+['review_decision','review_notes']);writer.writeheader()
  for row,r in zip(rows,records):
   di=r['decision_intelligence'];row.update(transaction_v1=r['transaction_type_candidate'],transaction_v2=r['transaction_type_candidate_v2'],transaction_confidence_v2=r['transaction_confidence_v2'],address_confidence_v2=r['address_confidence_v2'],brochure_text_status=r['brochure_text_status'],broker_candidate_count=len(r['broker_candidates']),source_coordinates_found=r['source_coordinates_found'],featured_image_confidence_v2=r['featured_image_confidence_v2'],decision_known_count=len(di['known_fields']),decision_unknown_count=len(di['unknown_fields']),decision_conflict_count=len(di['conflicting_fields']),suite_count_v2=len(r['suite_candidates_v2']),brochure_page_conflicts='; '.join(r['brochure_page_conflicts']),review_decision='',review_notes='');writer.writerow(row)
 lines=['# Aspire property enrichment audit','', '**Audit only. No import, OCR, geocoding or image downloads. All 87 records still need human review.**','',f"Enriched: {len(records)}/87. PDF downloads valid/readable: {summary['brochure_download_success']}/87. Usable embedded text: {summary['brochure_text_success']}/87. Partial text encoding: {summary['partial_text_properties']}.",'','| Coverage | V1 | V2 non-conflicting candidates |','|---|---:|---:|']
 lines += [f'| {k} | {v["before"]} | {v["after"]} |' for k,v in coverage.items()]
 lines += ['', 'Explicit transaction evidence remains limited. The requested substantial reduction in unknown transactions was not achieved; no candidate was forced from a generic team heading, existing tenancy or legal text.', '', 'Newly resolved/conflicting coverage: '+json.dumps(transition)+'.', '',f"Broker candidates: {summary['broker_candidates']}. Suite candidates: {summary['suite_candidates_v2']}. Separate availability contexts: {summary['availability_contexts']}. Properties with unresolved source/field differences: {summary['properties_with_conflicts']}.",'','## Conflicts','']+[f'- {k}: {v} properties' for k,v in summary['conflicting_fields'].items()]
 lines += ['','## Warnings','']+[f'- {k}: {v}' for k,v in summary['warnings'].items()]
 lines += ['','## Interpretation','', '- V1 fields and evidence remain verbatim within each record. New candidates and source evidence live in `enriched_fields`, `transaction_evidence_v2`, `address_evidence_v2`, `suite_candidates_v2`, `availability_contexts_v2`, and `decision_facts`.', '- Exact numeric disagreements remain null candidates with both values retained. Qualitative decision facts are quoted claims with evidence, not rewritten marketing prose. Display prices retain separate contexts. Brochures are not assumed newer.', '- Internal completeness is the percentage of enumerated type-specific fields with non-conflicting evidence. The field catalog is in the manifest. This is not a consumer property score.', '- Legal IABS/TREC boilerplate and generic About Us pages are excluded from property fact extraction. Brochure contact candidates require a property-associated contact/team heading, a name, an email and a nearby telephone number; authors are never used.', '- Addresses require a complete source street/locality/ZIP pairing. Generic brochure footer localities alone are not treated as addresses. Spelling equivalence is limited to common directional/street suffix normalization. Conflicting subject-address strings remain for human review.', '- Coordinates come only from cached source metadata and must be in valid ranges and broadly within Texas; no address-to-coordinate inference occurs. Repeated identical coordinates across different addresses are withheld as usable candidates and retained in coordinate_evidence.', f"- Coordinate metadata found on {summary['source_coordinate_properties']} pages; {summary['coordinate_reuse_rejected']} repeated-location candidates rejected for review.", '- Squarespace social derivatives may use a different path. A primary-image match requires an exact underlying URL or a unique full filename within the same source site and the legitimate V1 property-image set. Gallery order remains unchanged.', '- Source availability may differ: page and brochure suite inventories are compared and retained separately when different. No suite is marked available merely because it exists.', '- FM 1093 source prices and its existing V1 pricing-review warning are retained. Local frontend suppression was not touched.', '- Image-only or unusable PDFs are flagged without OCR. Partially encoded text is retained with a warning; no contact or fact is invented to compensate.','', '## Safety','',f"- WordPress properties {before['property_count']} → {after['property_count']}; attachments {before['attachment_count']} → {after['attachment_count']}.",'- Property records/meta fingerprints, taxonomy counts and relationships match using direct SQL read-only snapshots.','- V1 outputs retain their exact SHA-256 hashes, stored in V2. Raw PDFs and text remain in the ignored cache.','- No Atlas, directory, property frontend, Docker or taxonomy-definition changes.','','## Outputs','','- `docs/migration/aspire-properties-manifest-v2.json`','- `docs/migration/aspire-properties-review-v2.csv`','- `docs/migration/aspire-properties-enrichment-summary.md`','']
 (OUT/'aspire-properties-enrichment-summary.md').write_text('\n'.join(lines))
 assert all(hashlib.sha256(p.read_bytes()).hexdigest()==hashes[p.name] for p in original_paths)
 print(json.dumps(summary,indent=2));return manifest
if __name__=='__main__':run()
