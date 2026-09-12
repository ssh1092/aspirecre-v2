"""Fixed-scope, cached legacy archive discovery. No WordPress dependencies."""
import argparse, hashlib, json, pathlib, subprocess, time
from html.parser import HTMLParser
from urllib.parse import urljoin, urlsplit, urlunsplit, unquote
ROOT=pathlib.Path(__file__).resolve().parents[2]
CACHE=ROOT/'var/migration/aspire-properties'
BASE='https://www.aspirecre.com'
ARCHIVES=[BASE+'/properties'+q for q in ['', '?offset=1752069960170','?offset=1748785847222','?offset=1746117645948','?offset=1713848107409']]
UA='Mozilla/5.0 (compatible; AspireCREMigrationAudit/1.0; dry-run inventory review)'
def fetch(url, path, refresh=False):
    if path.exists() and not refresh:return path.read_text()
    path.parent.mkdir(parents=True,exist_ok=True)
    time.sleep(.75)
    temp=path.with_suffix('.tmp')
    try:
        subprocess.run(['curl','--fail','--silent','--show-error','--location','--proto','=https','--proto-redir','=https','--connect-timeout','15','--max-time','45','--retry','2','--retry-delay','2','--user-agent',UA,url,'-o',str(temp)],check=True)
        temp.replace(path)
    finally:temp.unlink(missing_ok=True)
    return path.read_text()
class Links(HTMLParser):
    def __init__(self):super().__init__();self.urls=set()
    def handle_starttag(self,tag,attrs):
        if tag!='a':return
        href=dict(attrs).get('href','');u=urlsplit(urljoin(BASE,href));p=u.path.rstrip('/')
        if u.hostname not in ('www.aspirecre.com','aspirecre.com') or u.query or not p.startswith('/properties/'):return
        tail=unquote(p[len('/properties/'):])
        if not tail or tail.lower().split('/')[0] in ('category','tag','page','author'):return
        self.urls.add(urlunsplit(('https','www.aspirecre.com',p,'','')))
def discover(refresh=False):
    urls=set();pages=[]
    for i,url in enumerate(ARCHIVES):
        body=fetch(url,CACHE/f'archive-{i}.html',refresh);parser=Links();parser.feed(body);urls.update(parser.urls);pages.append({'url':url,'property_links':len(parser.urls)});print(f'Archive {i+1}: {len(parser.urls)} property links',flush=True)
    result={'archives':pages,'expected_count':87,'unique_count':len(urls),'urls':sorted(urls)}
    (CACHE/'discovery.json').write_text(json.dumps(result,indent=2)+'\n')
    if len(urls)!=87:raise SystemExit(f'STOP: expected 87 unique property pages, discovered {len(urls)}. No property parsing performed.')
    return result
if __name__=='__main__':
    parser=argparse.ArgumentParser();parser.add_argument('--refresh',action='store_true');args=parser.parse_args();print(json.dumps(discover(args.refresh),indent=2))
