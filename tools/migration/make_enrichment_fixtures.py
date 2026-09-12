"""Reduced, reviewable JSON evidence fixtures; no PDFs, HTML or full brochure text committed."""
import json,re,hashlib
from bs4 import BeautifulSoup
from discover import CACHE,ROOT
CASES={'clay':'16840-clay','atascocita':'7506-e-fm','broadway':'5900-5940','woodsons':'4095-4107','greatwood':'6544-greatwood','prairie':'912-prairie','monroe':'8401-w-monroe','land':'0-hiram-clarke','fm1093':'21617-fm'}
records=json.loads((ROOT/'docs/migration/aspire-properties-manifest.json').read_text())['records'];index={i['url']:i for i in json.loads((CACHE/'crawl-index.json').read_text())}
for name,needle in CASES.items():
 r=next(r for r in records if needle in r['source_url']);soup=BeautifulSoup((CACHE/index[r['source_url']]['cache']).read_text(),'html.parser');body=soup.select_one('.blog-item-content');key=hashlib.sha256(r['brochure_url'].encode()).hexdigest()[:24]
 data={'record':r,'info':json.loads((CACHE/'brochures'/(key+'.json')).read_text()),'metadata':[dict(t.attrs) for t in soup.select('meta[property^="og:"],meta[name="twitter:image"]')],'page_blocks':[],'brochure_pages':[]}
 for block in body.select('.sqs-html-content'):data['page_blocks'].append([{'tag':t.name,'text':t.get_text('\n',strip=True)} for t in block.select('h1,h2,h3,h4,p,li') if not(t.name=='p' and t.find_parent('li'))])
 pages=json.loads((CACHE/'brochures'/(key+'.text.json')).read_text())['pages']
 for page in pages:
  page=re.split(r'Information About Brokerage Services|TYPES OF REAL ESTATE LICENSE HOLDERS',page,flags=re.I)[0]
  if 'About Us' in page:
   # Keep contact layout, omit the generic company narrative.
   lines=page.splitlines();hits=[i for i,l in enumerate(lines) if '@aspirecre' in l or re.search('Leasing Team|Sales Team|Sales and Leasing Team',l,re.I)];keep={i for h in hits for i in range(max(0,h-8),min(len(lines),h+4))};page='\n'.join(l for i,l in enumerate(lines) if i in keep)
  elif not re.search(r'/C\d+|clear|loading|SUITE|Property Highlights|For Sale|For Lease|P u r c h a s e|Property Identity|Property Details|Leasing Information|floodplain|detention|utilities',page,re.I):page=''
  data['brochure_pages'].append(page[:5500])
 (ROOT/'tools/migration/tests/enrichment-fixtures'/(name+'.json')).write_text(json.dumps(data,indent=2,ensure_ascii=False)+'\n')
