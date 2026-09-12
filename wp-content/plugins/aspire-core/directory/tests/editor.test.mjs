import test from 'node:test';
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import vm from 'node:vm';
test('native editor registers editable settings and a server-rendered preview with no frontend runtime',()=>{
 let name,block;const updates=[];
 const wp={blocks:{registerBlockType:(n,b)=>{name=n;block=b;}},element:{createElement:(type,props,...children)=>({type,props,children}),Fragment:'Fragment'},blockEditor:{InspectorControls:'Inspector',useBlockProps:()=>({className:'wp-block'})},components:{PanelBody:'Panel',SelectControl:'Select',ToggleControl:'Toggle',Disabled:'Disabled'},serverSideRender:'SSR'};
 vm.runInNewContext(readFileSync(new URL('../src/editor.js',import.meta.url),'utf8'),{wp});
 assert.equal(name,'aspire/property-directory');assert.equal(block.apiVersion,3);assert.equal(block.category,'aspirecre');assert.equal(block.save(),null);
 const tree=block.edit({attributes:{defaultView:'split',showMap:true,perLoad:0},setAttributes:value=>updates.push(value)});
 const all=[];function walk(node){if(!node||typeof node!=='object')return;all.push(node);node.children?.forEach(walk);}walk(tree);
 assert.deepEqual(all.filter(n=>['Select','Toggle'].includes(n.type)).map(n=>n.props.label),['Default View','Show Map','Properties per load']);
 all.find(n=>n.props?.label==='Properties per load').props.onChange('24');assert.equal(updates[0].perLoad,24);
 assert.equal(all.find(n=>n.type==='SSR').props.block,'aspire/property-directory');
});
