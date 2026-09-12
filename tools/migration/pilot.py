"""Local six-property pilot. Defaults to a read-only plan; --apply requires backup."""
import argparse
import hashlib
import json
import subprocess
import time
from pathlib import Path
from urllib.parse import urlsplit,unquote

from discover import ROOT,CACHE
from parser import normalized_url
ALLOW={'aspire-040','aspire-015','aspire-012','aspire-021','aspire-011','aspire-081'}
WORK=CACHE/'pilot'


def command(args,**kw):return subprocess.run(args,check=True,**kw)
def digest(path):return hashlib.sha256(path.read_bytes()).hexdigest()

def prepare():
    path=ROOT/'docs/migration/aspire-properties-manifest-v4.json';v=json.loads(path.read_text())
    v2path=ROOT/'docs/migration/aspire-properties-manifest-v2.json'
    if digest(v2path)!=v['preserved_audit_sha256'][v2path.name]:raise ValueError('V2 source reference hash mismatch')
    sources={r['source_url']:r for r in json.loads(v2path.read_text())['records']}
    records=[]
    for r in v['records']:
        if r['migration_id'] not in ALLOW:continue
        c=r['import_candidate'];assets={}
        def asset(url,kind):
            normalized=normalized_url(url)
            host=urlsplit(url).hostname or ''
            if host not in ['images.squarespace-cdn.com','static1.squarespace.com','static.squarespace.com','www.aspirecre.com','aspirecre.com'] or urlsplit(url).scheme!='https':raise ValueError('Unexpected media host: '+host)
            suffix=Path(unquote(urlsplit(url).path)).suffix.lower()
            if kind=='pdf':suffix='.pdf'
            if suffix not in ['.pdf','.jpg','.jpeg','.png','.webp']:raise ValueError('Unexpected media extension')
            key=hashlib.sha256(normalized.encode()).hexdigest()[:24]
            assets[normalized]={'url':normalized,'source_url':url,'kind':kind,'file':key+suffix,'title':c['title']+(' — brochure' if kind=='pdf' else ' — property image')}
            return normalized
        r['gallery_assets']=[asset(g['source_url'],'image') for g in c['gallery']]
        primary_url=c['primary_image']
        basename=lambda u:unquote(urlsplit(u).path.split('/')[-1]).replace('+',' ')
        same=[g['source_url'] for g in c['gallery'] if basename(g['source_url'])==basename(primary_url)]
        if len(same)==1:primary_url=same[0]
        r['primary_asset']=asset(primary_url,'image')
        r['brochure_asset']=asset(c['brochure_url'],'pdf') if r['migration_id']!='aspire-021' else None
        r['protected_brochure_path']='var/migration/aspire-properties/pilot/media/61f74db495b5a6e7874f9905.pdf' if r['migration_id']=='aspire-021' else None
        if r['migration_id']=='aspire-021':r['non_blocking_warnings'].append('legacy_price_brochure_held_privately_public_field_unset')
        r['assets']=list(assets.values())
        source=sources[r['source_url']];r['source_publish_date']=source['source_publish_date']
        # Source highlights for new drafts only. Exclude suite/rate/numeric label blocks;
        # normalized metrics and suites already carry those structured values.
        import re
        r['clean_highlights']=[s for s in source['property_highlights'] if not re.search(r'\$|^SUITE\b|^PRICE\b|About Us|Brokerage Services|SF of Frontage',s,re.I)]
        records.append(r)
    return {'manifest_sha256':digest(path),'records':records,'backup_verified':False}


def invoke(payload,apply=False):
    WORK.mkdir(parents=True,exist_ok=True);WORK.chmod(0o700);path=WORK/'payload.json';path.write_text(json.dumps(payload))
    command(['docker','cp',str(path),'aspirecre-wordpress:/tmp/aspire-pilot-payload.json'],stdout=subprocess.DEVNULL)
    command(['docker','cp',str(ROOT/'tools/migration/pilot-import.php'),'aspirecre-wordpress:/tmp/aspire-pilot.php'],stdout=subprocess.DEVNULL)
    result=command(['docker','compose','exec','-T','wordpress','php','/tmp/aspire-pilot.php']+(['--apply'] if apply else []),cwd=ROOT,capture_output=True,text=True)
    return json.loads(result.stdout)


def main():
    parser=argparse.ArgumentParser();parser.add_argument('--pilot',action='store_true');parser.add_argument('--property',choices=sorted(ALLOW));parser.add_argument('--dry-run',action='store_true');parser.add_argument('--apply',action='store_true');parser.add_argument('--stage-only',action='store_true');args=parser.parse_args()
    if args.apply and args.dry_run:parser.error('Choose --apply or --dry-run')
    payload=prepare()
    if args.property:payload['records']=[r for r in payload['records'] if r['migration_id']==args.property]
    plan=invoke(payload);(WORK/'dry-run.json').write_text(json.dumps(plan,indent=2)+'\n')
    if not args.apply and not args.stage_only:print(json.dumps(plan,indent=2));return
    if plan['safety_checks']!='passed':raise ValueError('Dry-run safety failure')
    backup=WORK/'backup.sql'
    if not backup.exists():
        with backup.open('wb') as f:command(['docker','compose','exec','-T','db','sh','-c','exec mariadb-dump --single-transaction --routines --triggers -uroot -p"$MARIADB_ROOT_PASSWORD" "$MARIADB_DATABASE"'],cwd=ROOT,stdout=f)
        backup.chmod(0o600)
        if backup.stat().st_size<10000:raise ValueError('Database backup is unexpectedly small')
        (WORK/'backup.sha256').write_text(digest(backup)+'\n')
        uploads=ROOT/'wp-content/uploads';(WORK/'uploads-before.json').write_text(json.dumps(sorted(str(p.relative_to(uploads)) for p in uploads.rglob('*') if p.is_file())))
    if digest(backup)!=(WORK/'backup.sha256').read_text().strip():raise ValueError('Backup checksum mismatch')
    media=WORK/'media';media.mkdir(exist_ok=True)
    for i,a in enumerate(plan['needed_assets'],1):
        path=media/a['file']
        if not path.exists():
            print(f"Staging media {i}/{len(plan['needed_assets'])}: {a['kind']}",flush=True)
            time.sleep(.75)
            command(['curl','--fail','--silent','--show-error','--location','--proto','=https','--proto-redir','=https','--connect-timeout','20','--max-time','120','--retry','2','--user-agent','AspireCRE-Pilot-Migration/1.0','--output',str(path),a['source_url']])
        if path.stat().st_size==0:raise ValueError('Empty media')
        if a['kind']=='pdf':
            from pypdf import PdfReader
            if not PdfReader(path).pages:raise ValueError('PDF has no readable pages')
        # Real MIME/signature verification happens in WordPress before attachment APIs.
        a['sha256']=digest(path)
    staged={a['url']:a for a in plan['needed_assets']}
    for r in payload['records']:
        r['assets']=[staged.get(a['url'],a) for a in r['assets']]
    payload['backup_verified']=True
    command(['docker','exec','aspirecre-wordpress','mkdir','-p','/tmp/aspire-pilot-media'])
    command(['docker','cp',str(media)+'/.','aspirecre-wordpress:/tmp/aspire-pilot-media'],stdout=subprocess.DEVNULL)
    if args.stage_only:
        (WORK/'staged-payload.json').write_text(json.dumps(payload));print('Media staged and backup verified; no WordPress writes.');return
    try:
        result=invoke(payload,True)
    finally:
        uploads=ROOT/'wp-content/uploads'
        original=set(json.loads((WORK/'uploads-before.json').read_text()))
        current={str(p.relative_to(uploads)) for p in uploads.rglob('*') if p.is_file()}
        (WORK/'uploads-created.json').write_text(json.dumps(sorted(current-original),indent=2)+'\n')
    (WORK/'result.json').write_text(json.dumps(result,indent=2)+'\n');print(json.dumps(result,indent=2))

if __name__=='__main__':main()
