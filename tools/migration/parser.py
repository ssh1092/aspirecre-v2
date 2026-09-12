"""Conservative, evidence-preserving Squarespace property parser. Never imports data."""
import json,re,unicodedata
from urllib.parse import urljoin,urlsplit,urlunsplit,unquote
from bs4 import BeautifulSoup
CATEGORY={'Retail':'Retail','Office':'Office','Office Condos':'Office Condo','Industrial':'Industrial / Flex','Land':'Land'}
FIELDS='source_title source_category source_author source_publish_date property_type transaction_type_candidate transaction_confidence address_line_1 address_line_2 city state postal_code address_confidence building_sf available_sf minimum_available_sf maximum_contiguous_sf lot_acres year_built renovated_year building_class stories clear_height_ft parking_spaces parking_ratio traffic_count_vpd sale_price lease_rate_min lease_rate_max lease_rate_type lease_rate_display price_display featured_image_url brochure_url existing_wp_post_id'.split()
N=r'(\d[\d,]*(?:\.\d+)?)'
SF=r'(?:SF|sq\.?\s*ft\.?|square feet)'
def clean(s):return re.sub(r'\s+',' ',s or '').strip()
def number(s):return float(s.replace(',','')) if '.' in s else int(s.replace(',',''))
def normalized_url(url):
 u=urlsplit(url);return urlunsplit((u.scheme.lower(),u.netloc.lower(),u.path,'',''))
def slug_policy(url):
 path=urlsplit(url).path;tail=unquote(path[len('/properties/'):]).rstrip('/')
 safe=re.sub(r'[^a-z0-9]+','-',unicodedata.normalize('NFKD',tail).encode('ascii','ignore').decode().lower()).strip('-')
 return safe,tail!=safe

def address(title):
 out={k:None for k in ['address_line_1','address_line_2','city','state','postal_code','address_confidence']}
 m=re.search(r'\b(TX|Texas)\s*,?\s*(\d{4,6}(?:-\d{4})?)?\s*$',title,re.I)
 if not m:return out
 out['state']='TX';z=m[2];out['postal_code']=z if z and re.fullmatch(r'\d{5}(?:-\d{4})?',z) else None
 prefix=title[:m.start()].rstrip(' ,');city=None;street=None
 # A comma before the city is strongest evidence. Otherwise require a recognizable street suffix.
 if ',' in prefix:
  a,b=prefix.rsplit(',',1)
  if re.fullmatch(r'[A-Za-z .\'-]+',b.strip()):street=a.strip();city=b.strip()
 if not street:
  pattern=r'^(.*(?:\b(?:Road|Rd\.?|Street|St\.?|Drive|Dr\.?|Boulevard|Blvd\.?|Lane|Ln\.?|Parkway|Pkwy\.?|Freeway|Fwy\.?|Court|Ct\.?|Avenue|Ave\.?|Way|Loop|Trail|Trace|Row)|\b(?:FM(?: Road)?|(?:State )?Highway|Hwy)[- ]?\d+))\s+([A-Za-z][A-Za-z .\'-]+)$'
  match=re.match(pattern,prefix,re.I)
  if match:street,city=match.groups()
 if street:
  # Drop property names only when a delimiter leads directly into an actual numbered address.
  street=(re.split(r'\s+[–—-]\s+(?=\d)',street,maxsplit=1)[-1] if not re.match(r'^\d',street) else street).strip()
  if not re.match(r'^\d',street):street=None
 if street and city:out.update(address_line_1=street,city=city,address_confidence='high' if out['postal_code'] else 'medium')
 return out

def parse(html,url):
 soup=BeautifulSoup(html,'html.parser');article=soup.select_one('article.h-entry');body=soup.select_one('.blog-item-content')
 if not article or not body or not article.select_one('h1'):raise ValueError('Missing property article/body/H1; refusing generic-page parsing')
 r={k:None for k in FIELDS};r.update(source_url=url,source_path=urlsplit(url).path,source_slug=unquote(urlsplit(url).path[len('/properties/'):]),migration_review_status='needs_review',warnings=[],warning_details=[],field_evidence={},property_highlights=[],suite_candidates=[],gallery_images=[],brochure_candidates=[])
 def warn(code,detail=None):
  if code not in r['warnings']:r['warnings'].append(code)
  if detail and {'code':code,'detail':detail} not in r['warning_details']:r['warning_details'].append({'code':code,'detail':detail})
 r['proposed_wp_slug'],r['redirect_required']=slug_policy(url)
 if r['redirect_required']:warn('legacy_url_requires_redirect')
 r['source_title']=clean(article.select_one('h1').get_text(' ',strip=True));categories=[clean(x.get_text()) for x in article.select('.blog-item-category')];r['source_categories']=list(dict.fromkeys(categories));r['source_category']=categories[0] if len(set(categories))==1 else None
 r['property_type']=CATEGORY.get(r['source_category']);
 if not r['property_type']:warn('property_type_missing')
 author=article.select_one('.blog-author-name');r['source_author']=clean(author.get_text()) if author else None
 for script in soup.select('script[type="application/ld+json"]'):
  try:
   data=json.loads(script.string or script.get_text());items=data if isinstance(data,list) else [data]
   for item in items:
    if isinstance(item,dict) and item.get('datePublished') and (item.get('headline')==r['source_title'] or normalized_url(urljoin(url,item.get('url','')))==normalized_url(url)):
     r['source_publish_date']=item['datePublished']
  except (ValueError,TypeError):pass
 if not r['source_publish_date']:warn('missing_publish_date')
 r.update(address(r['source_title']));r['address_source_text']=r['source_title']
 if not r['address_line_1']:warn('ambiguous_address' if r['state'] else 'missing_address')
 if not r['postal_code']:warn('missing_or_malformed_postal_code')
 # Use only property text, never outer pagination, author, newsletter or footer.
 tags=[x for x in body.find_all(['h1','h2','h3','h4','h5','h6','p','li']) if not(x.name=='p' and x.find_parent('li')) and not(x.name=='li' and x.find_parent('li'))]
 lines=[clean(x.get_text(' ',strip=True)) for x in tags];lines=[x for x in lines if x]
 r['source_headings']=[clean(x.get_text(' ',strip=True)) for x in body.find_all(re.compile('^h[1-6]$'))]
 for heading in body.find_all(re.compile('^h[1-6]$')):
  if re.search(r'(?:property|building|site)\s+highlights',heading.get_text(),re.I):
   for tag in heading.find_all_next(['h1','h2','h3','h4','h5','h6','li']):
    if tag not in body.descendants or re.fullmatch('h[1-6]',tag.name):break
    text=clean(tag.get_text(' ',strip=True))
    if text and text not in r['property_highlights']:r['property_highlights'].append(text)
 # Explicitly group suites only within their own text block and stop at the next heading/section.
 suite_lines=set()
 for container in body.select('.sqs-html-content'):
  raw_nodes=[x for x in container.find_all(['h1','h2','h3','h4','h5','h6','p','li']) if not(x.name=='p' and x.find_parent('li'))]
  nodes=[(tag.name,clean(part).lstrip('• ').strip()) for tag in raw_nodes for part in re.split(r'[\n•]+',tag.get_text('\n',strip=True)) if clean(part).lstrip('• ').strip()]
  for i,(kind,text) in enumerate(nodes):
   m=re.fullmatch(r'(SUITE\s+(?:[A-Za-z]?\d+[A-Za-z0-9/-]*|[A-Za-z])(?:\s*[/&–-]\s*[A-Za-z0-9]+)*)(?:\s+(available|leased|coming soon|unavailable))?',text,re.I)
   if not m:continue
   group=[text]
   for next_kind,line in nodes[i+1:]:
    if re.match(r'^suite\b',line,re.I) or next_kind.startswith('h'):break
    if line:group.append(line)
   suite_lines.update(group);raw='\n'.join(group);sf=re.findall(N+r'\s*'+SF,raw,re.I);base=re.findall(r'\$'+N+r'\s*/\s*SF\s*(?:Base)?',raw,re.I)
   s={k:None for k in ['square_feet','rate','rate_type','former_use','notes','availability_status']};s.update(suite_name=m[1],confidence='medium',source_text=raw)
   if m[2]:s['availability_status']=m[2].lower().replace(' ','_')
   # NNN components do not become a second suite base rate.
   primary=[x for x in group[1:] if not re.search(r'NNN',x,re.I) or re.search(r'Base',x,re.I)]
   sf=[number(m[1]) for line in primary for m in re.finditer(N+r'\s*'+SF,line,re.I) if not re.search(r'\$',line)]
   if len(set(sf))==1:s['square_feet']=sf[0]
   rates=[number(m[1]) for line in primary for m in re.finditer(r'\$'+N+r'\s*/\s*SF',line,re.I)]
   if len(set(rates))==1:s['rate']=rates[0]
   if 'base' in raw.lower():s['rate_type']='Base + NNN' if 'nnn' in raw.lower() else 'Base'
   former=[x for x in group[1:] if re.search(r'second gen|2nd gen|former',x,re.I)]
   if len(former)==1:s['former_use']=former[0]
   s['notes']='\n'.join(group[1:]) or None
   statuses=[line.lower() for line in group[1:] if line.lower() in ['available','leased','coming soon','unavailable']]
   if len(set(statuses))==1:s['availability_status']=statuses[0].replace(' ','_')
   if not s['square_feet']:s['confidence']='unparsed';warn('suite_parse_review',text)
   r['suite_candidates'].append(s)
   for line in lines:
    if re.match(r'^'+re.escape(text)+r'\b',line,re.I):suite_lines.add(line)
 if any(re.search(r'\bsuites?\b|\bleasing information\b',x,re.I) for x in lines) and not r['suite_candidates']:warn('suite_parse_review','Availability/suite language lacks unambiguous suite headings')
 # Preserve complex blocks for review without pretending they are individual suites.
 r['unparsed_availability_blocks']=[clean(c.get_text(' ',strip=True)) for c in body.select('.sqs-html-content') if re.search(r'leasing information|available spaces|suite\b',c.get_text(),re.I) and not any(s['suite_name'] in c.get_text() for s in r['suite_candidates'])]
 meaningful=[x for x in lines if x not in suite_lines]
 patterns={
 'building_sf':[r'building\s*(?:size|area)?\s*[:–—-]?\s*'+N+r'\s*'+SF,N+r'\s*'+SF+r'\s*(?:office |retail |industrial |flex )?building',N+r'\s*total\s*(?:building\s*)?square feet'],
 'available_sf':[r'(?:total\s*)?(?:space available|available space|available SF)\s*[:–—-]?\s*'+N, N+r'\s*'+SF+r'\s+available'],
 'minimum_available_sf':[r'minimum (?:available|divisible)(?: space)?\s*[:–—-]?\s*'+N],
 'maximum_contiguous_sf':[r'max(?:imum)? contiguous(?: space)?\s*[:–—-]?\s*'+N],
 'lot_acres':[N+r'\s*-?\s*acres?\b',r'lot (?:size|area)\s*[:–—-]?\s*'+N+r'\s*AC\b'],
 'year_built':[r'(?:year built(?!/)|built in)\s*[:–—-]?\s*(\d{4})'],
 'renovated_year':[r'(?:renovated(?: year| in)?|year renovated)\s*[:–—-]?\s*(\d{4})'],
 'building_class':[r'(?:building\s+)?class\s*[:–—-]?\s*([ABC])\b'],
 'stories':[N+r'\s*(?:stories|story)\b'],
 'clear_height_ft':[N+r'[\s’\'′]*(?:FT\s*)?clear height'],
 'parking_spaces':[N+r'\s*parking spaces',r'parking\s*[:–—-]?\s*'+N+r'\s*spaces'],
 'traffic_count_vpd':[N+r'\s*VPD\b'],
 }
 for field,regexes in patterns.items():
  evidence=[]
  for line in meaningful:
   if field=='lot_acres' and re.search(r'within|mile|population|surrounding|trade area',line,re.I):continue
   if field in ['available_sf','building_sf','lot_acres'] and re.search(N+r'\s*[–—-]\s*'+N+r'\s*(?:'+SF+r'|acres?)',line,re.I):
    warn('structured_value_review','Range is not a single '+field+': '+line);continue
   for pattern in regexes:
    for m in re.finditer(pattern,line,re.I):
     value=m[1].upper() if field=='building_class' else number(m[1]);evidence.append({'value':value,'source_text':line,'confidence':'high'})
  if evidence:
   r['field_evidence'][field]=evidence;values={x['value'] for x in evidence}
   if len(values)==1:r[field]=next(iter(values))
   else:warn('structured_value_conflict',field)
 for line in meaningful:
  m=re.search(r'(\d+(?:\.\d+)?\s*spaces?\s+per\s+[\d,]+\s*SF(?:\s+Leased)?)',line,re.I)
  if m:r['parking_ratio']=m[1]
  if re.search(r'year built/renovated|building size.*\d\s*S\b',line,re.I):warn('structured_value_review',line)
 # Transaction intent must be explicit and conflicts are surfaced rather than resolved by category.
 signals={'For Sale or Lease':r'for sale\s*(?:or|/)\s*lease','Ground Lease':r'ground lease','For Lease':r'for lease|lease information|leasing information|lease rate','For Sale':r'for sale|asking price|sale price'}
 evidence={k:[line for line in [r['source_title']]+lines if re.search(pattern,line,re.I)] for k,pattern in signals.items()}
 r['transaction_evidence']={k:v for k,v in evidence.items() if v}
 sale=bool(evidence['For Sale']);lease=bool(evidence['For Lease']);dual=bool(evidence['For Sale or Lease']);ground=bool(evidence['Ground Lease'])
 if dual and not ground:r['transaction_type_candidate']='For Sale or Lease'
 elif ground and not sale:r['transaction_type_candidate']='Ground Lease'
 elif sale and lease or ground and sale:warn('ambiguous_transaction')
 elif sale:r['transaction_type_candidate']='For Sale'
 elif lease:r['transaction_type_candidate']='For Lease'
 else:warn('missing_transaction')
 r['transaction_confidence']='high' if r['transaction_type_candidate'] else ('ambiguous' if r['transaction_evidence'] else None)
 # A base + NNN lease component is explicit evidence of leasing; a bare $/SF is not.
 component_lines=[x for x in lines if re.search(r'\$[\d,.]+\s*/\s*SF\s*(?:Base|NNN)',x,re.I)]
 if component_lines and not r['transaction_type_candidate'] and not sale:
  r['transaction_type_candidate']='For Lease';r['transaction_confidence']='high';r['transaction_evidence']['Lease components']=component_lines
  if 'missing_transaction' in r['warnings']:r['warnings'].remove('missing_transaction')
 lease_lines=[x for x in meaningful if re.search(r'\$',x) and re.search(r'lease rate|\bbase\b|\bNNN\b',x,re.I)]
 # If there is exactly one suite, preserve its components but do not promote suite size to total availability.
 if not lease_lines and len(r['suite_candidates'])==1:lease_lines=[x for x in r['suite_candidates'][0]['source_text'].split('\n') if re.search(r'\$.*(?:Base|NNN)',x,re.I)]
 if lease_lines:
  r['lease_rate_display']='; '.join(dict.fromkeys(lease_lines));r['field_evidence']['lease_rate_display']=[{'source_text':x,'confidence':'high'} for x in lease_lines];rates=[]
  for line in lease_lines:
   if re.search(r'NNN',line,re.I) and not re.search(r'base|lease rate',line,re.I):continue
   m=re.search(r'\$'+N+r'(?:\s*[–—-]\s*\$?'+N+r')?',line)
   if m:rates.extend([number(m[1])]+([number(m[2])] if m[2] else []))
  if len(lease_lines)>1 and len(r['suite_candidates'])!=1:warn('structured_value_review','Multiple lease components/contexts retained as display text')
  if rates and len(set(rates))<=2 and (len(lease_lines)==1 or len(r['suite_candidates'])==1):r['lease_rate_min']=min(rates);r['lease_rate_max']=max(rates) if len(set(rates))>1 else None
  r['lease_rate_type']='Base + NNN' if re.search(r'Base',r['lease_rate_display'],re.I) and re.search(r'NNN',r['lease_rate_display'],re.I) else None
 price_lines=[x for x in meaningful if re.search(r'asking price|sale price|price\s*[:–—-]',x,re.I) and re.search(r'\$',x)]
 if price_lines:
  r['price_display']='; '.join(dict.fromkeys(price_lines));r['field_evidence']['price_display']=[{'source_text':x,'confidence':'high'} for x in price_lines];values=[]
  for line in price_lines:
   if re.search(r'/\s*(?:SF|acre)',line,re.I):continue
   if re.search(r'\$[\d,.]+\s*(?:million|billion|[MK])\b',line,re.I):warn('structured_value_review','Unnormalized price magnitude: '+line);continue
   values.extend(number(m[1]) for m in re.finditer(r'\$'+N,line))
  if len(set(values))==1:r['sale_price']=values[0]
  elif len(set(values))>1:warn('structured_value_conflict','sale_price')
 for a in body.select('a[href]'):
  href=urljoin(url,a['href']);label=clean(a.get_text(' ',strip=True))
  if urlsplit(href).scheme in ['http','https'] and (re.search('brochure',label,re.I) or re.search(r'\.pdf$',urlsplit(href).path,re.I) and re.search('download|flyer|property',label,re.I)):
   if href not in r['brochure_candidates']:r['brochure_candidates'].append(href)
 if len(r['brochure_candidates'])==1:r['brochure_url']=r['brochure_candidates'][0]
 elif not r['brochure_candidates']:warn('no_brochure')
 else:warn('multiple_brochures')
 images=[];seen=set()
 for img in body.select('img'):
  src=img.get('data-src') or img.get('data-image') or img.get('src')
  if not src:continue
  src=urljoin(url,src);norm=normalized_url(src);filename=unquote(urlsplit(norm).path.rsplit('/',1)[-1]);alt=clean(img.get('alt'))
  if urlsplit(src).scheme not in ['http','https'] or re.search(r'logo|newsletter|social|tracking|icon',filename+' '+alt,re.I):continue
  if norm in seen:continue
  seen.add(norm);images.append({'source_url':src,'normalized_url':norm,'filename':filename,'alt_text':alt or None,'in_gallery':bool(img.find_parent(class_='sqs-block-gallery'))})
 og=soup.select_one('meta[property="og:image"]');ogurl=normalized_url(urljoin(url,og.get('content',''))) if og else None
 primary=next((i for i in images if not i['in_gallery']),images[0] if images else None)
 if primary:
  r['featured_image_url']=primary['source_url'];r['featured_image_confidence']='high' if primary['normalized_url']==ogurl else 'low'
  if r['featured_image_confidence']=='low':warn('featured_image_low_confidence')
 else:warn('no_images');r['featured_image_confidence']=None
 for img in images:
  if img['in_gallery'] or img is not primary:
   r['gallery_images'].append({k:v for k,v in img.items() if k!='in_gallery'}|{'order':len(r['gallery_images'])+1,'is_featured':img['normalized_url']==(primary or {}).get('normalized_url')})
 r['image_count']=len(images)
 if not r['property_highlights']:warn('no_structured_highlights')
 return r

def match_existing(records,snapshot):
 posts=snapshot['properties'];meta={int(p['ID']):{} for p in posts}
 for m in snapshot['meta']:meta[int(m['post_id'])].setdefault(m['meta_key'],[]).append(m['meta_value'])
 norm=lambda s:re.sub(r'[^a-z0-9]','',unicodedata.normalize('NFKD',s).lower())
 for r in records:
  matches=[];method=None
  for p in posts:
   m=meta[int(p['ID'])]
   if any(r['source_url'].rstrip('/')==v.rstrip('/') for k,vs in m.items() if re.search(r'legacy.*url|source.*url',k,re.I) for v in vs):matches.append(p);method='saved_source_url'
  if not matches:matches=[p for p in posts if p['post_name']==r['source_slug']];method='exact_slug'
  if not matches:matches=[p for p in posts if norm(p['post_title'])==norm(r['source_title'])];method='exact_normalized_title'
  if not matches and all(r.get(k) for k in ['address_line_1','city','state','postal_code']):
   keys=['address_line_1','city','state','postal_code'];target='|'.join(norm(r[k]) for k in keys)
   matches=[p for p in posts if '|'.join(norm(meta[int(p['ID'])].get('_aspire_'+k,[''])[0]) for k in keys)==target];method='exact_normalized_full_address'
  if len(matches)==1:r['existing_wp_post_id']=int(matches[0]['ID']);r['existing_wp_match_method']=method
  elif len(matches)>1:r['warnings'].append('existing_wp_match_uncertain');r['existing_wp_match_candidates']=[int(p['ID']) for p in matches]
 # Never silently assign two legacy pages to one local record.
 for r in records:
  if r['existing_wp_post_id'] and sum(x['existing_wp_post_id']==r['existing_wp_post_id'] for x in records)>1:
   if 'existing_wp_match_uncertain' not in r['warnings']:r['warnings'].append('existing_wp_match_uncertain')
