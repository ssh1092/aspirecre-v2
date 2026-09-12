"""Sequential, verified PDF-only cache. No WordPress writes, OCR or image fetching."""
import argparse,hashlib,json,re,subprocess,time
from pypdf import PdfReader
from discover import CACHE,ROOT,UA
PDFS=CACHE/'brochures'
def download(url,refresh=False):
 key=hashlib.sha256(url.encode()).hexdigest()[:24];path=PDFS/(key+'.pdf');meta=PDFS/(key+'.json');text=PDFS/(key+'.text.json');PDFS.mkdir(parents=True,exist_ok=True)
 info={'brochure_url':url,'brochure_download_status':'failed','brochure_text_status':'unavailable','brochure_file_size':None,'brochure_sha256':None,'cache_key':key}
 try:
  if refresh or not path.exists():
   time.sleep(.75);temp=path.with_suffix('.tmp')
   result=subprocess.run(['curl','--fail','--silent','--show-error','--location','--proto','=https','--proto-redir','=https','--connect-timeout','15','--max-time','90','--retry','2','--retry-delay','2','--user-agent',UA,'--write-out','%{http_code}',url,'-o',str(temp)],capture_output=True,text=True)
   info['http_status']=int(result.stdout) if result.stdout.isdigit() else None
   if result.returncode:temp.unlink(missing_ok=True);raise ValueError('HTTP download failed: '+result.stderr.strip())
   if not temp.read_bytes().startswith(b'%PDF-'):temp.unlink(missing_ok=True);raise ValueError('Response is not a PDF')
   temp.replace(path)
  elif meta.exists():info.update(json.loads(meta.read_text()))
  data=path.read_bytes();info['brochure_file_size']=len(data);info['brochure_sha256']=hashlib.sha256(data).hexdigest()
  if not data.startswith(b'%PDF-'):raise ValueError('Cached content is not a PDF')
  cached=json.loads(text.read_text()) if text.exists() and not refresh else {}
  if cached.get('sha256')==info['brochure_sha256'] and cached.get('pages'):
   pages=cached['pages']
  else:
   reader=PdfReader(path);pages=[page.extract_text() or '' for page in reader.pages]
   if not pages:raise ValueError('PDF has no readable pages')
   text.write_text(json.dumps({'sha256':info['brochure_sha256'],'pages':pages},ensure_ascii=False))
  usable=re.sub(r'/C\d+', '', '\n'.join(pages))
  info.pop('error',None)
  info.update(brochure_download_status='success',brochure_page_count=len(pages),brochure_text_status='available' if sum(c.isalnum() for c in usable)>=40 else 'unavailable')
 except Exception as e:
  info.update(brochure_download_status='failed',brochure_text_status='unavailable',error=str(e))
 meta.write_text(json.dumps(info,indent=2)+'\n');return info
if __name__=='__main__':
 p=argparse.ArgumentParser();p.add_argument('--refresh',action='store_true');args=p.parse_args();records=json.loads((ROOT/'docs/migration/aspire-properties-manifest.json').read_text())['records'];index={}
 for n,r in enumerate(records,1):
  url=r['brochure_url']
  if url not in index:index[url]=download(url,args.refresh)
  print(n,len(records),index[url]['brochure_download_status'],r['source_title'],flush=True)
 (PDFS/'index.json').write_text(json.dumps(index,indent=2)+'\n')
