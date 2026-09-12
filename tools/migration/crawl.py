"""Fetch property HTML only after fixed-archive discovery succeeds."""
import argparse,hashlib,json,subprocess
from discover import CACHE,discover,fetch
if __name__=='__main__':
 p=argparse.ArgumentParser();p.add_argument('--refresh',action='store_true');args=p.parse_args()
 discovery=discover(args.refresh);index=[]
 for i,url in enumerate(discovery['urls']):
  path=CACHE/('property-'+hashlib.sha256(url.encode()).hexdigest()[:20]+'.html')
  try:
   fetch(url,path,args.refresh);index.append({'url':url,'cache':path.name,'status':'ok'});print(f'{i+1}/87 cached {url}',flush=True)
  except Exception as e:
   if isinstance(e,subprocess.CalledProcessError) and e.returncode in (5,6,7,35,60):raise SystemExit('STOP: outbound HTTPS unavailable; no parsing/import performed')
   index.append({'url':url,'status':'failed','error':str(e)});print(f'{i+1}/87 FAILED {url}',flush=True)
 (CACHE/'crawl-index.json').write_text(json.dumps(index,indent=2)+'\n')
