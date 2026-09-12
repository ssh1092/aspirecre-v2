"""Evidence-first enrichment. No OCR, geocoding, chronology preference or import logic."""
import re,json,unicodedata
from urllib.parse import urlsplit,unquote,urljoin
from bs4 import BeautifulSoup
from parser import clean,number,address,normalized_url,N,SF
NUMERIC={
 'building_sf':[r'(?:building (?:size|area)|total building(?: size)?)\s*[:–—-]?\s*'+N+r'\s*'+SF,N+r'\s*(?:total\s*)?'+SF+r'\s*(?:office |industrial |retail )?building',N+r'\s*total square feet'],
 'available_sf':[r'(?:total\s*)?(?:space available|available space|available area)\s*[:–—-]?\s*'+N+r'\s*'+SF,N+r'\s*'+SF+r'\s+(?:available|availability)'],
 'minimum_available_sf':[r'min(?:imum)?(?:imum)? (?:available|divisible)(?: space)?\s*[:–—-]?\s*'+N+r'\s*'+SF],
 'maximum_available_sf':[r'max(?:imum)? available(?: space)?\s*[:–—-]?\s*'+N+r'\s*'+SF],
 'maximum_contiguous_sf':[r'max(?:imum)? contiguous(?: space)?\s*[:–—-]?\s*'+N+r'\s*'+SF],
 'office_sf':[N+r'\s*'+SF+r'\s*(?:of\s+)?office\b',r'office (?:size|area|space)\s*[:–—-]?\s*'+N+r'\s*'+SF],
 'warehouse_sf':[N+r'\s*'+SF+r'\s*(?:of\s+)?warehouse\b',r'warehouse (?:size|area|space)\s*[:–—-]?\s*'+N+r'\s*'+SF],
 'lot_acres':[r'(?:lot|land|site) (?:size|area)\s*[:–—-]?\s*'+N+r'\s*(?:AC|acres?)\b',N+r'\s*-?\s*acres?\b'],
 'year_built':[r'(?:year built(?!/)|built(?: in)?)\s*[:–—-]?\s*(\d{4})'],
 'renovated_year':[r'(?:renovated(?: year| in)?|year renovated)\s*[:–—-]?\s*(\d{4})'],
 'stories':[N+r'\s*(?:stories|story)\b'],
 'clear_height_ft':[N+r'\s*(?:[’\'′]|ft|feet)?\s*clear(?: height)?',r'clear height\s*[:–—-]?\s*'+N],
 'parking_spaces':[N+r'\s*parking spaces',r'parking\s*[:–—-]?\s*'+N+r'\s*spaces'],
 'traffic_count_vpd':[N+r'\s*VPD\b'],
}
FACTS={
 'industrial':{'construction_type':r'masonry|steel (?:frame|construction)|tilt.?wall|concrete construction','clear_height':r'clear height|[’\']\s*clear','dock_high_doors':r'dock.?high|dock doors','grade_level_doors':r'grade.?level|drive.?in doors','loading_configuration':r'rear.?load|front.?load|cross.?dock|loading','sprinklered':r'sprinkler|ESFR','office_sf':r'\bSF\b.*office|office.*\bSF\b','warehouse_sf':r'\bSF\b.*warehouse|warehouse.*\bSF\b','outdoor_storage':r'outdoor storage|\bIOS\b','yard':r'\byard\b','power_capacity':r'\bpower\b|\bamps?\b|\bvolt','HVAC':r'HVAC|air.condition|heating|cooling','parking':r'parking','access_notes':r'access (?:to|via)|highway access'},
 'retail':{'traffic_counts_by_road':r'VPD|vehicles per day','frontage':r'frontage','signalized_intersection':r'signalized|signalised','turn_lane':r'turn lane','ingress_egress':r'ingress|egress|curb cut','visibility':r'visibility','signage':r'signage|pylon sign|monument sign','tenant_mix':r'tenant mix|tenants including|anchored by','nearby_retailers':r'national retailers|surrounded by.*retail|nearby retailers','population_radius_facts':r'population|\d.{0,5}mile radius','average_household_income':r'household income|AHHI','drive_thru':r'drive.thr[ou]','grease_trap':r'grease trap','restaurant_infrastructure':r'restaurant (?:space|infrastructure|build.out)|(?:second gen|former).*restaurant|kitchen|vent hood'},
 'office':{'building_class':r'class [ABC]\b','building_size':r'building size|SF office building','availability_range':r'available|availabilities','parking_ratio':r'spaces? per|parking ratio','parking_type':r'surface parking|garage|parking lot','covered_parking':r'covered parking','building_signage':r'building signage','monument_signage':r'monument signage','on_site_management':r'on.?site management|on.?site managed','24_7_access':r'24.?7|24.hour access','elevator':r'elevator','security_access':r'security|controlled access|key.?card','amenities':r'conference|fitness|break ?room|kitchenette|amenities','office_configuration':r'private offices|office conversion|converted|medical|office configuration|floor plans','move_in_ready':r'move.in.ready','furnished':r'furnished'},
 'land':{'acreage':r'acres?\b','frontage':r'frontage','utilities':r'utilities|water|sewer|electricity','detention':r'detention','zoning':r'zoning|zoned','restrictions':r'restrict','floodplain':r'floodplain|flood plain|flood zone|floodway','development_ready':r'development.ready','shovel_ready':r'shovel.ready','access_points':r'access points|curb cut|ingress|egress','signalized_access':r'signalized','cross_access':r'cross.access','ETJ':r'\bETJ\b|extraterritorial','visibility':r'visibility','nearby_corridors':r'corridor|tollway|freeway|highway'},
}
def ev(source,value,text,page=None,confidence='high',**extra):return {'source':source,'value':value,'text':clean(text)[:500],'confidence':confidence,**({'page':page} if page else {}),**extra}
def unspace(line):
 tokens=line.split()
 if len(tokens)>5 and sum(len(x)==1 for x in tokens)/len(tokens)>.7:line=re.sub(r'(?<=\S) (?=\S)','',line)
 return line
def lines_from_pages(pages):
 result=[]
 for page,text in enumerate(pages,1):
  # TREC/IABS boilerplate is never property pricing, transaction or listing-contact evidence.
  text=re.split(r'Information About Brokerage Services|TYPES OF REAL ESTATE LICENSE HOLDERS',text,flags=re.I)[0]
  if re.search(r'About Us',text,re.I):continue
  lines=[clean(unicodedata.normalize('NFKC',unspace(x))).strip('• \t') for x in text.splitlines() if clean(x)]
  for i,line in enumerate(lines):
   if len(line)>1 and not re.match(r'^/C\d',line):result.append((page,line))
   if re.fullmatch(r'AVAILABLE FOR',line,re.I) and i+1<len(lines):result.append((page,line+' '+lines[i+1]))
   if re.fullmatch(r'(?:BUILDING SIZE|AVAILABLE SPACE|TOTAL SPACE AVAILABLE|LEASE RATE|ASKING PRICE|SALE PRICE|YEAR BUILT|CLEAR HEIGHT|LOT SIZE|PARKING RATIO)\s*[:–—-]?',line,re.I) and i+1<len(lines):result.append((page,line+' '+lines[i+1]))
 return list(dict.fromkeys(result))
def reconcile(evidence):
 evidence=list({json.dumps(x,sort_keys=True):x for x in evidence}.values());values=[]
 for e in evidence:
  if e['value'] not in values:values.append(e['value'])
 conflict=len(values)>1
 return {'candidate':values[0] if len(values)==1 else None,'confidence':'conflicting' if conflict else ('high_agreement' if len({e['source'] for e in evidence})>1 else ('high' if evidence else None)),'field_conflict':conflict,'evidence':evidence}
def transactions(lines,source):
 evidence=[]
 for page,line in lines:
  lower=line.lower()
  if re.search(r'rent roll|rental income|rent increase|lease start|lease end|gross rent.*income',line,re.I):continue
  if re.search(r'for (?:sale|purchase)\s*(?:or|/)\s*lease|available for purchase or lease',lower):v='For Sale or Lease'
  elif re.search(r'ground lease',lower):v='Ground Lease'
  elif re.search(r'for sale|asking price|sale price|available for purchase',lower):v='For Sale'
  elif re.search(r'for lease|lease rate|leasing information|lease information|base rent\b|leases available|lease options|one.year lease|\$[\d,.]+/SF\s*Base',line,re.I):v='For Lease'
  else:continue
  evidence.append(ev(source,v,line,page))
 # A dual or ground heading subsumes its own generic rate label, not unrelated explicit titles.
 values={e['value'] for e in evidence}
 if 'For Sale or Lease' in values and not 'Ground Lease' in values:
  for e in evidence:
   if e['value'] in ['For Sale','For Lease']:e['context_value']=e['value'];e['value']='For Sale or Lease'
 elif 'Ground Lease' in values and 'For Sale' not in values:
  for e in evidence:
   if e['value']=='For Lease' and not re.search('for lease',e['text'],re.I):e['context_value']=e['value'];e['value']='Ground Lease'
 return evidence

def structured(lines,source):
 result={k:[] for k in list(NUMERIC)+['building_class','parking_ratio','sale_price','lease_rate_min','lease_rate_max','lease_rate_display','price_display','price_per_sf','nnn_cam','ground_lease_rate']}
 for page,line in lines:
  if re.search(r'about us|our company|our team|landlord.*tenant',line,re.I):continue
  ranged=bool(re.search(N+r'\s*(?:[–—-]|to)\s*'+N+r'\s*'+SF,line,re.I))
  for field,patterns in NUMERIC.items():
   if ranged and field in ['available_sf','building_sf','office_sf','warehouse_sf']:continue
   if field=='lot_acres' and re.search(r'radius|population|within.*mile|trade area',line,re.I):continue
   for pattern in patterns:
    for m in re.finditer(pattern,line,re.I):
     val=number(m[1])
     if val>0:result[field].append(ev(source,val,line,page))
  m=re.search(N+r'\s*(?:[–—-]|to)\s*'+N+r'\s*'+SF,line,re.I)
  if m and re.search(r'availab|shell (?:units|suites)',line,re.I):
   result['minimum_available_sf'].append(ev(source,number(m[1]),line,page));result['maximum_available_sf'].append(ev(source,number(m[2]),line,page))
  m=re.search(r'(?:building )?class\s*[:–—-]?\s*([ABC])\b',line,re.I)
  if m:result['building_class'].append(ev(source,m[1].upper(),line,page))
  m=re.search(r'(\d+(?:\.\d+)?\s*spaces?\s+per\s+[\d,]+\s*SF)',line,re.I)
  if m:result['parking_ratio'].append(ev(source,m[1].lower(),line,page))
  if '$' in line and re.search(r'asking price|sale price|^price\s*[:–—-]',line,re.I):
   result['price_display'].append(ev(source,line,line,page));m=re.search(r'\$'+N,line)
   if m and not re.search(r'/\s*(?:SF|AC)|million|billion|\d[MK]\b',line,re.I):result['sale_price'].append(ev(source,number(m[1]),line,page))
  if '$' in line and re.search(r'lease rate|base rent|\bbase\b|\bNNN\b|ground lease',line,re.I):
   result['lease_rate_display'].append(ev(source,line,line,page))
   # Multiple separate rates remain conflicting; no phase-level min/max aggregation.
   m=re.search(r'\$'+N+r'\s*/\s*SF(?:\s*(?:[–—-]|to)\s*\$'+N+r'\s*/\s*SF)?',line,re.I)
   if m and not re.search('NNN',line,re.I):
    result['lease_rate_min'].append(ev(source,number(m[1]),line,page))
    if m[2]:result['lease_rate_max'].append(ev(source,number(m[2]),line,page))
  for field,pattern in [('nnn_cam',r'NNN|CAM'),('ground_lease_rate',r'ground lease rate'),('price_per_sf',r'price per (?:SF|square foot)')]:
   if '$' in line and re.search(pattern,line,re.I):result[field].append(ev(source,line,line,page))
 return result

def contacts(pages):
 results=[]
 for page,text in enumerate(pages,1):
  text=re.split(r'Information About Brokerage Services|TYPES OF REAL ESTATE LICENSE HOLDERS',text,flags=re.I)[0]
  if not re.search(r'leasing team|leasing contact|listing contact|sales contact|for (?:more|additional) information|contact (?:us|information)|sales team',text,re.I):continue
  lines=[clean(unicodedata.normalize('NFKC',x)) for x in text.splitlines() if clean(x)]
  for i,line in enumerate(lines):
   email=re.search(r'[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}',line,re.I)
   if not email or email[0].lower().startswith(('info@','admin@')):continue
   previous=max([j for j in range(i) if '@' in lines[j]],default=-1)
   start=max(previous+1,i-12);window=lines[start:i+2];name=None;title=None;phone=None
   name_pattern=r'[A-Z][a-z]+(?: (?:[A-Z][a-z]+|[A-Z]\.)){1,3}'
   for s in reversed(lines[start:i]):
    if re.fullmatch(r'(?:Senior |Managing )?(?:Director|Associate|Analyst|Broker|Advisor|Partner|Principal|Vice President|President)',s,re.I):title=title or s
    elif not name and re.fullmatch(name_pattern,s) and not re.search(r'Team|Estate|About|Leasing|Commercial|Information|Contact',s):name=s
   ph=re.findall(r'(?:\+1\s*)?\(?\d{3}\)?[ .-]*\d{3}[ .-]*\d{4}',' '.join(lines[start:i+1]))
   if len(ph)==1:phone=ph[0]
   if not name and phone and i+1<len(lines) and re.fullmatch(name_pattern,lines[i+1]) and not re.search(r'Team|Estate|About|Leasing|Commercial',lines[i+1]):name=lines[i+1]
   if name and phone:
    candidate={'name':name,'title':title,'phone':phone,'email':email[0],'evidence_text':clean(' | '.join(window))[:550],'page':page,'confidence':'high','source':'brochure'}
    if not any(c['email'].lower()==email[0].lower() for c in results):results.append(candidate)
 return results

def page_metadata(html,record):
 soup=BeautifulSoup(html,'html.parser');coordinate=[];images=[]
 def add_coord(lat,lon,source,text):
  try:
   lat=float(lat);lon=float(lon)
   if not(-90<=lat<=90 and -180<=lon<=180):return
   coordinate.append({'latitude':lat,'longitude':lon,'source':source,'text':text[:350],'confidence':'high' if 25<=lat<=37 and -107<=lon<=-93 else 'review_outside_texas'})
  except (ValueError,TypeError):pass
 lat=soup.select_one('meta[property="og:latitude"]');lon=soup.select_one('meta[property="og:longitude"]')
 if lat and lon:add_coord(lat.get('content'),lon.get('content'),'page:og:latitude/longitude',str(lat)+str(lon))
 for tag in soup.select('meta[property="og:image"],meta[name="twitter:image"],meta[property="twitter:image"]'):
  if tag.get('content'):images.append({'source':'page:'+str(tag.get('property') or tag.get('name')),'url':urljoin(record['source_url'],tag['content'])})
 def walk(obj,origin):
  if isinstance(obj,dict):
   if 'latitude' in obj and 'longitude' in obj:add_coord(obj['latitude'],obj['longitude'],origin,json.dumps(obj)[:350])
   if 'lat' in obj and ('lng' in obj or 'lon' in obj):add_coord(obj['lat'],obj.get('lng',obj.get('lon')),origin,json.dumps(obj)[:350])
   for key,v in obj.items():
    if key in ['image','thumbnailUrl','assetUrl','mainImage','originalSize'] and isinstance(v,str) and v.startswith(('http','//')):images.append({'source':origin+':'+key,'url':urljoin(record['source_url'],v)})
    walk(v,origin)
  elif isinstance(obj,list):
   for v in obj:walk(v,origin)
 for script in soup.select('script[type="application/ld+json"],script[type="application/json"]'):
  try:walk(json.loads(script.get_text()),'page:structured-json')
  except ValueError:pass
 for tag in soup.select('[data-block-json],[data-map-options],[data-latitude],[data-longitude]'):
  if tag.has_attr('data-latitude') and tag.has_attr('data-longitude'):add_coord(tag['data-latitude'],tag['data-longitude'],'page:data-attributes',str(tag)[:350])
  for attr in ['data-block-json','data-map-options']:
   if tag.has_attr(attr):
    try:walk(json.loads(tag[attr]),'page:'+attr)
    except ValueError:pass
 # Conservative adjacent scalar keys in script objects (no executing source scripts).
 for script in soup.find_all('script'):
  for m in re.finditer(r'["\']?latitude["\']?\s*:\s*(-?\d+(?:\.\d+)?)\s*,\s*["\']?longitude["\']?\s*:\s*(-?\d+(?:\.\d+)?)',script.get_text()):add_coord(m[1],m[2],'page:script-object',m[0])
 valid={(c['latitude'],c['longitude']) for c in coordinate if c['confidence']=='high'};chosen=next(iter(valid)) if len(valid)==1 else None
 candidates=[record['featured_image_url']]+[g['source_url'] for g in record['gallery_images']];candidates=list(dict.fromkeys(x for x in candidates if x));matches=[]
 for item in images:
  name=unquote(urlsplit(item['url']).path.split('/')[-1]).replace('+',' ')
  if re.search('logo|social|icon|newsletter',name,re.I):continue
  exact=[c for c in candidates if normalized_url(c)==normalized_url(item['url'])]
  # Squarespace social derivatives use a different path; require site identity and unique complete filename.
  site_ids=[m[1] for c in candidates for m in [re.search(r'/content/v1/([^/]+)/',c)] if m]
  same_site=any(site in item['url'] for site in site_ids)
  filename=[c for c in candidates if unquote(urlsplit(c).path.split('/')[-1]).replace('+',' ')==name] if same_site and not re.fullmatch(r'(?:image|photo|thumbnail|banner)\.(?:jpg|png|jpeg)',name,re.I) else []
  match=exact or filename
  if len(match)==1:matches.append({**item,'matched_property_image':match[0],'method':'exact_asset_url' if exact else 'same_site_unique_filename'})
 unique={x['matched_property_image'] for x in matches};featured=next(iter(unique)) if len(unique)==1 else record['featured_image_url']
 return {'latitude':chosen[0] if chosen else None,'longitude':chosen[1] if chosen else None,'coordinate_source':'; '.join(sorted({c['source'] for c in coordinate if c['confidence']=='high'})) if chosen else None,'coordinate_confidence':'high' if chosen else None,'coordinate_evidence':coordinate,'coordinate_conflict':len(valid)>1,'featured_image_url_v2':featured,'featured_image_confidence_v2':'high' if len(unique)==1 else record.get('featured_image_confidence','low'),'featured_image_evidence':matches,'featured_image_conflict':len(unique)>1,'featured_image_in_gallery':any(normalized_url(g['source_url'])==normalized_url(featured or '') for g in record['gallery_images'])}


def brochure_addresses(pages):
 evidence=[]
 for page,text in enumerate(pages[:2],1):
  if evidence:break
  text=re.split(r'Information About Brokerage Services|TYPES OF REAL ESTATE LICENSE HOLDERS',text,flags=re.I)[0]
  lines=[clean(unspace(x)).strip('• ,') for x in text.splitlines() if clean(x)]
  for i,line in enumerate(lines):
   if not re.match(r'^\d',line) or re.search(r'\$|SF|phone|copyright|\d{3}[-)]',line,re.I):continue
   candidate=line
   if not re.search(r'\b(?:TX|Texas)\b',line,re.I) and i+1<len(lines):
    locality=re.fullmatch(r'([A-Za-z .-]+?),?\s+(TX|Texas)\s*,?\s*(\d{5})',lines[i+1],re.I)
    if locality:
     street=re.sub(r'\s*\|\s*SUITE.*$','',line,flags=re.I).strip(' ,')
     evidence.append(ev('brochure',{'address_line_1':street,'city':locality[1].strip(' ,'),'state':'TX','postal_code':locality[3]},line+' '+lines[i+1],page));continue
   candidate=re.sub(r'\s*[|]\s*SUITE[^,]+?(?=\s+[A-Z][A-Z ]*,?\s*(?:TX|TEXAS))','',candidate,flags=re.I)
   found=address(candidate)
   if found['address_line_1'] and found['city'] and found['postal_code']:evidence.append(ev('brochure',{k:found[k] for k in ['address_line_1','city','state','postal_code']},candidate,page))
 return evidence

def availability(lines,source):
 result=[]
 for i,(page,line) in enumerate(lines):
  # Named suites or distinct explicitly named phases only; ordinary availability is not a fake suite.
  m=re.fullmatch(r'(SUITE\s+[A-Z0-9-]+)(?:\s*[—–-].*)?',line,re.I)
  phase=bool(re.match(r'^(?:RETAIL [AB]\s*[–—-]|MEDICAL AND PROFESSIONAL OFFICE\s*[–—-])',line,re.I))
  if not m and not phase:continue
  following=[]
  for nextpage,nextline in lines[i+1:i+12]:
   if nextpage!=page or re.match(r'^(?:SUITE\s+[A-Z0-9]|RETAIL [AB]\s*[–—-]|MEDICAL AND PROFESSIONAL OFFICE\s*[–—-])',nextline,re.I):break
   following.append(nextline)
  raw=' | '.join([line]+following)
  sizes=[number(x[1]) for s in following if '$' not in s and not re.search(r'office|warehouse|building',s,re.I) for x in re.finditer(N+r'\s*'+SF,s,re.I)]
  ranges=[(number(x[1]),number(x[2])) for s in following for x in re.finditer(N+r'\s*[–—-]\s*'+N+r'\s*'+SF,s,re.I)]
  rates=[number(x[1]) for s in following for x in re.finditer(r'\$'+N+r'\s*/\s*SF\s*Base',s,re.I)]
  if not sizes and not ranges:continue
  result.append({'context':line if phase else None,'suite_name':m[1].upper() if m else None,'square_feet':sizes[0] if len(set(sizes))==1 and not ranges else None,'minimum_available_sf':ranges[0][0] if len(set(ranges))==1 else None,'maximum_available_sf':ranges[0][1] if len(set(ranges))==1 else None,'rate':rates[0] if len(set(rates))==1 else None,'rate_type':'Base + NNN' if 'Base' in raw and 'NNN' in raw else ('Base' if 'Base' in raw else None),'former_use':next((s for s in following if re.search(r'former|second gen|2nd gen',s,re.I)),None),'availability_status':next((s.lower().replace(' ','_') for s in following if s.lower() in ['available','leased','coming soon']),None),'notes':raw[:650],'evidence':[ev(source,raw[:650],raw,page,'medium')],'confidence':'medium','field_conflict':False})
 # Repeated brochure headers must not create duplicates.
 return list({json.dumps({k:v for k,v in x.items() if k not in ['evidence','notes']},sort_keys=True):x for x in result}.values())
