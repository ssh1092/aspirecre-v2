#!/usr/bin/env python3
"""Development only: extract Houston with HTTP ranges; never download a planet file."""
import argparse, hashlib, json, platform, re, subprocess, tarfile, zipfile
from pathlib import Path
parser=argparse.ArgumentParser(description=__doc__)
parser.add_argument('--date',help='YYYYMMDD daily build; defaults to latest completed build')
parser.add_argument('--force',action='store_true',help='Regenerate an existing local Houston file')
args=parser.parse_args()
root=Path(__file__).resolve().parent.parent
assets=root/'wp-content/uploads/aspire-atlas';assets.mkdir(parents=True,exist_ok=True)
tools=root/'.local-tools/pmtiles';tools.mkdir(parents=True,exist_ok=True)
def fetch(url,destination):
 subprocess.run(['curl','--fail','--location','--silent','--show-error','--retry','3',url,'-o',str(destination)],check=True)
metadata=tools/'builds.json';fetch('https://build-metadata.protomaps.dev/builds.json',metadata)
builds=sorted(json.loads(metadata.read_text()),key=lambda b:b['key'],reverse=True)
if args.date and not re.fullmatch(r'\d{8}',args.date):parser.error('--date must be YYYYMMDD')
key=args.date+'.pmtiles' if args.date else builds[0]['key']
build=next((b for b in builds if b['key']==key),None)
if not build:parser.error('Date is not a published Protomaps daily build')
if not build['version'].startswith('4.'):parser.error('Atlas style targets Protomaps v4; review layer compatibility before extracting this build')
version='1.31.2';system=platform.system();machine=platform.machine()
arch={'arm64':'arm64','aarch64':'arm64','x86_64':'x86_64','AMD64':'x86_64'}.get(machine)
if system not in ('Darwin','Linux') or not arch:parser.error('Use the documented pmtiles CLI manually on this platform')
name=f'go-pmtiles-{version}_Darwin_{arch}.zip' if system=='Darwin' else f'go-pmtiles_{version}_Linux_{arch}.tar.gz'
cli=tools/f'pmtiles-{version}'
if not cli.exists():
 release_file=tools/'release.json';fetch(f'https://api.github.com/repos/protomaps/go-pmtiles/releases/tags/v{version}',release_file)
 asset=next(a for a in json.loads(release_file.read_text())['assets'] if a['name']==name)
 archive=tools/name;fetch(asset['browser_download_url'],archive)
 digest=asset.get('digest')
 if not digest or digest!='sha256:'+hashlib.sha256(archive.read_bytes()).hexdigest():raise RuntimeError('CLI release checksum missing or mismatched')
 if name.endswith('.zip'):
  with zipfile.ZipFile(archive) as z:cli.write_bytes(z.read('pmtiles'))
 else:
  with tarfile.open(archive) as t:cli.write_bytes(t.extractfile(next(m for m in t.getmembers() if Path(m.name).name=='pmtiles')).read())
 cli.chmod(0o755)
output=assets/'houston.pmtiles';manifest=assets/'source.json'
if not output.exists() or args.force:
 temporary=assets/'houston.extracting.pmtiles'
 if temporary.exists():temporary.unlink()
 subprocess.run([str(cli),'extract','https://build.protomaps.com/'+key,str(temporary),'--bbox=-95.95,29.45,-95.05,30.25','--maxzoom=15','--quiet'],check=True)
 subprocess.run([str(cli),'show',str(temporary)],check=True)
 temporary.replace(output)
 manifest.write_text(json.dumps({'source':'https://build.protomaps.com/'+key,'tilesetVersion':build['version'],'bbox':[-95.95,29.45,-95.05,30.25],'maxZoom':15,'bytes':output.stat().st_size,'sha256':hashlib.sha256(output.read_bytes()).hexdigest(),'cliVersion':version},indent=2)+'\n')
else:print('Keeping existing Houston archive; use --force to refresh. See source.json for its original build.')
# English/Latin Houston labels and punctuation; all glyphs are served locally.
font=assets/'fonts/Noto Sans Regular';font.mkdir(parents=True,exist_ok=True)
for glyph_range in ['0-255','256-511','512-767','768-1023','8192-8447','8448-8703']:
 target=font/(glyph_range+'.pbf')
 if not target.exists():fetch('https://protomaps.github.io/basemaps-assets/fonts/Noto%20Sans%20Regular/'+glyph_range+'.pbf',target)
print(f'Houston archive: {output.stat().st_size:,} bytes. Local URL: /wp-content/uploads/aspire-atlas/houston.pmtiles')
