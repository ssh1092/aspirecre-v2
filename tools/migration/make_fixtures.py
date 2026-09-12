"""Extract reduced semantic fixtures, not cached HTML or entire page bodies."""
import json,re
from bs4 import BeautifulSoup
from discover import CACHE,ROOT
CASES={'clay':'16840-clay-road','atascocita':'7506-e-fm-1960','broadway':'5900-5940','office_condo':'kingsley-ridge','woodsons':'4095-4107','land':'21617-fm-1093','office':'14602-presidio','redirect':'centre-at-pearland'}
index=json.loads((CACHE/'crawl-index.json').read_text())
for name,needle in CASES.items():
 matches=[i for i in index if needle in i['url']]
 if len(matches)!=1:raise ValueError((name,len(matches)))
 i=matches[0];soup=BeautifulSoup((CACHE/i['cache']).read_text(),'html.parser');body=soup.select_one('.blog-item-content');data={'source_url':i['url'],'title':soup.select_one('h1.entry-title').get_text(),'category':soup.select_one('.blog-item-category').get_text(),'blocks':[],'images':[],'links':[]}
 for c in body.select('.sqs-html-content'):
  data['blocks'].append([{'tag':x.name,'text':x.get_text('\n',strip=True)} for x in c.find_all(['h1','h2','h3','h4','p','li']) if not(x.name=='p' and x.find_parent('li'))])
 seen=set()
 for img in body.select('img'):
  src=img.get('data-src') or img.get('src')
  if not src or src in seen:continue
  seen.add(src);data['images'].append({'source_url':src,'alt_text':img.get('alt',''),'gallery':bool(img.find_parent(class_='sqs-block-gallery'))})
 for a in body.select('a[href]'):
  if re.search('brochure',a.get_text(),re.I):data['links'].append({'url':a['href'],'text':a.get_text(' ',strip=True)})
 (ROOT/'tools/migration/tests/fixtures'/f'{name}.json').write_text(json.dumps(data,indent=2,ensure_ascii=False)+'\n')
