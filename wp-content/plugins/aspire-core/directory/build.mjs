import {build} from 'esbuild';
import {readFileSync,writeFileSync} from 'node:fs';
import {createHash} from 'node:crypto';
const root=new URL('.',import.meta.url),out=new URL('build/',root);
await build({entryPoints:[new URL('src/view.js',root).pathname],bundle:true,minify:true,format:'iife',target:['es2022'],outfile:new URL('view.js',out).pathname,legalComments:'linked'});
await build({entryPoints:[new URL('src/map.js',root).pathname],bundle:true,minify:true,format:'esm',target:['es2022'],outfile:new URL('map.mjs',out).pathname,legalComments:'linked'});
for(const file of ['editor.js','editor.css'])writeFileSync(new URL(file,out),readFileSync(new URL('src/'+file,root)));
const hash=createHash('sha256');for(const file of ['view.js','map.mjs','map.css','editor.js','editor.css'])hash.update(readFileSync(new URL(file,out)));
writeFileSync(new URL('manifest.json',out),JSON.stringify({version:hash.digest('hex').slice(0,12)}));console.log('Property Directory assets built.');
